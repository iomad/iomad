<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Utility class.
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../testing/classes/util.php');

/**
 * Collection of utility methods.
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class phpunit_util extends testing_util {
    /**
     * @var int last value of db writes counter, used for db resetting
     */
    public static $lastdbwrites = null;

    /** @var array An array of original globals, restored after each test */
    protected static $globals = array();

    /** @var array list of debugging messages triggered during the last test execution */
    protected static $debuggings = array();

    /** @var phpunit_message_sink alternative target for moodle messaging */
    protected static $messagesink = null;

    /** @var phpunit_phpmailer_sink alternative target for phpmailer messaging */
    protected static $phpmailersink = null;

    /** @var phpunit_message_sink alternative target for moodle messaging */
    protected static $eventsink = null;

    /**
     * @var array Files to skip when resetting dataroot folder
     */
    protected static $datarootskiponreset = array('.', '..', 'phpunittestdir.txt', 'phpunit', '.htaccess');

    /**
     * @var array Files to skip when dropping dataroot folder
     */
    protected static $datarootskipondrop = array('.', '..', 'lock', 'webrunner.xml');

    /**
     * Load global $CFG;
     * @internal
     * @static
     * @return void
     */
    public static function initialise_cfg() {
        global $DB;
        $dbhash = false;
        try {
            $dbhash = $DB->get_field('config', 'value', array('name'=>'phpunittest'));
        } catch (Exception $e) {
            // not installed yet
            initialise_cfg();
            return;
        }
        if ($dbhash !== core_component::get_all_versions_hash()) {
            // do not set CFG - the only way forward is to drop and reinstall
            return;
        }
        // standard CFG init
        initialise_cfg();
    }

    /**
     * Reset contents of all database tables to initial values, reset caches, etc.
     *
     * Note: this is relatively slow (cca 2 seconds for pg and 7 for mysql) - please use with care!
     *
     * @static
     * @param bool $detectchanges
     *      true  - changes in global state and database are reported as errors
     *      false - no errors reported
     *      null  - only critical problems are reported as errors
     * @return void
     */
    public static function reset_all_data($detectchanges = false) {
        global $DB, $CFG, $USER, $SITE, $COURSE, $PAGE, $OUTPUT, $SESSION, $FULLME;

        // Stop any message redirection.
        self::stop_message_redirection();

        // Stop any message redirection.
        self::stop_event_redirection();

        // Start a new email redirection.
        // This will clear any existing phpmailer redirection.
        // We redirect all phpmailer output to this message sink which is
        // called instead of phpmailer actually sending the message.
        self::start_phpmailer_redirection();

        // We used to call gc_collect_cycles here to ensure desctructors were called between tests.
        // This accounted for 25% of the total time running phpunit - so we removed it.

        // Show any unhandled debugging messages, the runbare() could already reset it.
        self::display_debugging_messages();
        self::reset_debugging();

        // reset global $DB in case somebody mocked it
        $DB = self::get_global_backup('DB');

        if ($DB->is_transaction_started()) {
            // we can not reset inside transaction
            $DB->force_transaction_rollback();
        }

        $resetdb = self::reset_database();
        $localename = self::get_locale_name();
        $warnings = array();

        if ($detectchanges === true) {
            if ($resetdb) {
                $warnings[] = 'Warning: unexpected database modification, resetting DB state';
            }

            $oldcfg = self::get_global_backup('CFG');
            $oldsite = self::get_global_backup('SITE');
            foreach($CFG as $k=>$v) {
                if (!property_exists($oldcfg, $k)) {
                    $warnings[] = 'Warning: unexpected new $CFG->'.$k.' value';
                } else if ($oldcfg->$k !== $CFG->$k) {
                    $warnings[] = 'Warning: unexpected change of $CFG->'.$k.' value';
                }
                unset($oldcfg->$k);

            }
            if ($oldcfg) {
                foreach($oldcfg as $k=>$v) {
                    $warnings[] = 'Warning: unexpected removal of $CFG->'.$k;
                }
            }

            if ($USER->id != 0) {
                $warnings[] = 'Warning: unexpected change of $USER';
            }

            if ($COURSE->id != $oldsite->id) {
                $warnings[] = 'Warning: unexpected change of $COURSE';
            }

            if ($FULLME !== self::get_global_backup('FULLME')) {
                $warnings[] = 'Warning: unexpected change of $FULLME';
            }

            if (setlocale(LC_TIME, 0) !== $localename) {
                $warnings[] = 'Warning: unexpected change of locale';
            }
        }

        if (ini_get('max_execution_time') != 0) {
            // This is special warning for all resets because we do not want any
            // libraries to mess with timeouts unintentionally.
            // Our PHPUnit integration is not supposed to change it either.

            if ($detectchanges !== false) {
                $warnings[] = 'Warning: max_execution_time was changed to '.ini_get('max_execution_time');
            }
            set_time_limit(0);
        }

        // restore original globals
        $_SERVER = self::get_global_backup('_SERVER');
        $CFG = self::get_global_backup('CFG');
        $SITE = self::get_global_backup('SITE');
        $FULLME = self::get_global_backup('FULLME');
        $_GET = array();
        $_POST = array();
        $_FILES = array();
        $_REQUEST = array();
        $COURSE = $SITE;

        // reinitialise following globals
        $OUTPUT = new bootstrap_renderer();
        $PAGE = new moodle_page();
        $FULLME = null;
        $ME = null;
        $SCRIPT = null;

        // Empty sessison and set fresh new not-logged-in user.
        \core\session\manager::init_empty_session();

        // reset all static caches
        \core\event\manager::phpunit_reset();
        accesslib_clear_all_caches(true);
        get_string_manager()->reset_caches(true);
        reset_text_filters_cache(true);
        events_get_handlers('reset');
        core_text::reset_caches();
        get_message_processors(false, true, true);
        filter_manager::reset_caches();
        core_filetypes::reset_caches();
        \core_search\manager::clear_static();
        core_user::reset_caches();
        \core\output\icon_system::reset_caches();
        if (class_exists('core_media_manager', false)) {
            core_media_manager::reset_caches();
        }

        // Reset static unit test options.
        if (class_exists('\availability_date\condition', false)) {
            \availability_date\condition::set_current_time_for_test(0);
        }

        // Reset internal users.
        core_user::reset_internal_users();

        // Clear static caches in calendar container.
        if (class_exists('\core_calendar\local\event\container', false)) {
            core_calendar\local\event\container::reset_caches();
        }

        //TODO MDL-25290: add more resets here and probably refactor them to new core function

        // Reset course and module caches.
        if (class_exists('format_base')) {
            // If file containing class is not loaded, there is no cache there anyway.
            format_base::reset_course_cache(0);
        }
        get_fast_modinfo(0, 0, true);

        // Reset other singletons.
        if (class_exists('core_plugin_manager')) {
            core_plugin_manager::reset_caches(true);
        }
        if (class_exists('\core\update\checker')) {
            \core\update\checker::reset_caches(true);
        }

        // Clear static cache within restore.
        if (class_exists('restore_section_structure_step')) {
            restore_section_structure_step::reset_caches();
        }

        // purge dataroot directory
        self::reset_dataroot();

        // restore original config once more in case resetting of caches changed CFG
        $CFG = self::get_global_backup('CFG');

        // inform data generator
        self::get_data_generator()->reset();

        // fix PHP settings
        error_reporting($CFG->debug);

        // Reset the date/time class.
        core_date::phpunit_reset();

        // Make sure the time locale is consistent - that is Australian English.
        setlocale(LC_TIME, $localename);

        // Reset the log manager cache.
        get_log_manager(true);

        // Reset user agent.
        core_useragent::instance(true, null);

        // verify db writes just in case something goes wrong in reset
        if (self::$lastdbwrites != $DB->perf_get_writes()) {
            error_log('Unexpected DB writes in phpunit_util::reset_all_data()');
            self::$lastdbwrites = $DB->perf_get_writes();
        }

        if ($warnings) {
            $warnings = implode("\n", $warnings);
            trigger_error($warnings, E_USER_WARNING);
        }
    }

    /**
     * Reset all database tables to default values.
     * @static
     * @return bool true if reset done, false if skipped
     */
    public static function reset_database() {
        global $DB;

        if (!is_null(self::$lastdbwrites) and self::$lastdbwrites == $DB->perf_get_writes()) {
            return false;
        }

        if (!parent::reset_database()) {
            return false;
        }

        self::$lastdbwrites = $DB->perf_get_writes();

        return true;
    }

    /**
     * Called during bootstrap only!
     * @internal
     * @static
     * @return void
     */
    public static function bootstrap_init() {
        global $CFG, $SITE, $DB, $FULLME;

        // backup the globals
        self::$globals['_SERVER'] = $_SERVER;
        self::$globals['CFG'] = clone($CFG);
        self::$globals['SITE'] = clone($SITE);
        self::$globals['DB'] = $DB;
        self::$globals['FULLME'] = $FULLME;

        // refresh data in all tables, clear caches, etc.
        self::reset_all_data();
    }

    /**
     * Print some Moodle related info to console.
     * @internal
     * @static
     * @return void
     */
    public static function bootstrap_moodle_info() {
        echo self::get_site_info();
    }

    /**
     * Returns original state of global variable.
     * @static
     * @param string $name
     * @return mixed
     */
    public static function get_global_backup($name) {
        if ($name === 'DB') {
            // no cloning of database object,
            // we just need the original reference, not original state
            return self::$globals['DB'];
        }
        if (isset(self::$globals[$name])) {
            if (is_object(self::$globals[$name])) {
                $return = clone(self::$globals[$name]);
                return $return;
            } else {
                return self::$globals[$name];
            }
        }
        return null;
    }

    /**
     * Is this site initialised to run unit tests?
     *
     * @static
     * @return int array errorcode=>message, 0 means ok
     */
    public static function testing_ready_problem() {
        global $DB;

        $localename = self::get_locale_name();
        if (setlocale(LC_TIME, $localename) === false) {
            return array(PHPUNIT_EXITCODE_CONFIGERROR, "Required locale '$localename' is not installed.");
        }

        if (!self::is_test_site()) {
            // dataroot was verified in bootstrap, so it must be DB
            return array(PHPUNIT_EXITCODE_CONFIGERROR, 'Can not use database for testing, try different prefix');
        }

        $tables = $DB->get_tables(false);
        if (empty($tables)) {
            return array(PHPUNIT_EXITCODE_INSTALL, '');
        }

        if (!self::is_test_data_updated()) {
            return array(PHPUNIT_EXITCODE_REINSTALL, '');
        }

        return array(0, '');
    }

    /**
     * Drop all test site data.
     *
     * Note: To be used from CLI scripts only.
     *
     * @static
     * @param bool $displayprogress if true, this method will echo progress information.
     * @return void may terminate execution with exit code
     */
    public static function drop_site($displayprogress = false) {
        global $DB, $CFG;

        if (!self::is_test_site()) {
            phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, 'Can not drop non-test site!!');
        }

        // Purge dataroot
        if ($displayprogress) {
            echo "Purging dataroot:\n";
        }

        self::reset_dataroot();
        testing_initdataroot($CFG->dataroot, 'phpunit');

        // Drop all tables.
        self::drop_database($displayprogress);

        // Drop dataroot.
        self::drop_dataroot();
    }

    /**
     * Perform a fresh test site installation
     *
     * Note: To be used from CLI scripts only.
     *
     * @static
     * @return void may terminate execution with exit code
     */
    public static function install_site() {
        global $DB, $CFG;

        if (!self::is_test_site()) {
            phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGERROR, 'Can not install on non-test site!!');
        }

        if ($DB->get_tables()) {
            list($errorcode, $message) = self::testing_ready_problem();
            if ($errorcode) {
                phpunit_bootstrap_error(PHPUNIT_EXITCODE_REINSTALL, 'Database tables already present, Moodle PHPUnit test environment can not be initialised');
            } else {
                phpunit_bootstrap_error(0, 'Moodle PHPUnit test environment is already initialised');
            }
        }

        $options = array();
        $options['adminpass'] = 'admin';
        $options['shortname'] = 'phpunit';
        $options['fullname'] = 'PHPUnit test site';

        install_cli_database($options, false);

        // Set the admin email address.
        $DB->set_field('user', 'email', 'admin@example.com', array('username' => 'admin'));

        // Disable all logging for performance and sanity reasons.
        set_config('enabled_stores', '', 'tool_log');

        // We need to keep the installed dataroot filedir files.
        // So each time we reset the dataroot before running a test, the default files are still installed.
        self::save_original_data_files();

        // Store version hash in the database and in a file.
        self::store_versions_hash();

        // Store database data and structure.
        self::store_database_state();
    }

    /**
     * Builds dirroot/phpunit.xml and dataroot/phpunit/webrunner.xml files using defaults from /phpunit.xml.dist
     * @static
     * @return bool true means main config file created, false means only dataroot file created
     */
    public static function build_config_file() {
        global $CFG;

        $template = '
        <testsuite name="@component@_testsuite">
            <directory suffix="_test.php">@dir@</directory>
        </testsuite>';
        $data = file_get_contents("$CFG->dirroot/phpunit.xml.dist");

        $suites = '';

        $plugintypes = core_component::get_plugin_types();
        ksort($plugintypes);
        foreach ($plugintypes as $type=>$unused) {
            $plugs = core_component::get_plugin_list($type);
            ksort($plugs);
            foreach ($plugs as $plug=>$fullplug) {
                if (!file_exists("$fullplug/tests/")) {
                    continue;
                }
                $dir = substr($fullplug, strlen($CFG->dirroot)+1);
                $dir .= '/tests';
                $component = $type.'_'.$plug;

                $suite = str_replace('@component@', $component, $template);
                $suite = str_replace('@dir@', $dir, $suite);

                $suites .= $suite;
            }
        }
        // Start a sequence between 100000 and 199000 to ensure each call to init produces
        // different ids in the database.  This reduces the risk that hard coded values will
        // end up being placed in phpunit or behat test code.
        $sequencestart = 100000 + mt_rand(0, 99) * 1000;

        $data = preg_replace('|<!--@plugin_suites_start@-->.*<!--@plugin_suites_end@-->|s', $suites, $data, 1);
        $data = str_replace(
            '<const name="PHPUNIT_SEQUENCE_START" value=""/>',
            '<const name="PHPUNIT_SEQUENCE_START" value="' . $sequencestart . '"/>',
            $data);

        $result = false;
        if (is_writable($CFG->dirroot)) {
            if ($result = file_put_contents("$CFG->dirroot/phpunit.xml", $data)) {
                testing_fix_file_permissions("$CFG->dirroot/phpunit.xml");
            }
        }

        // relink - it seems that xml:base does not work in phpunit xml files, remove this nasty hack if you find a way to set xml base for relative refs
        $data = str_replace('lib/phpunit/', $CFG->dirroot.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'phpunit'.DIRECTORY_SEPARATOR, $data);
        $data = preg_replace('|<directory suffix="_test.php">([^<]+)</directory>|',
            '<directory suffix="_test.php">'.$CFG->dirroot.(DIRECTORY_SEPARATOR === '\\' ? '\\\\' : DIRECTORY_SEPARATOR).'$1</directory>',
            $data);
        file_put_contents("$CFG->dataroot/phpunit/webrunner.xml", $data);
        testing_fix_file_permissions("$CFG->dataroot/phpunit/webrunner.xml");

        return (bool)$result;
    }

    /**
     * Builds phpunit.xml files for all components using defaults from /phpunit.xml.dist
     *
     * @static
     * @return void, stops if can not write files
     */
    public static function build_component_config_files() {
        global $CFG;

        $template = '
        <testsuites>
            <testsuite name="@component@_testsuite">
                <directory suffix="_test.php">.</directory>
            </testsuite>
        </testsuites>
        <filter>
            <whitelist processUncoveredFilesFromWhitelist="false">
                <directory suffix=".php">.</directory>
                <exclude>
                    <directory suffix="_test.php">.</directory>
                </exclude>
            </whitelist>
        </filter>';

        // Start a sequence between 100000 and 199000 to ensure each call to init produces
        // different ids in the database.  This reduces the risk that hard coded values will
        // end up being placed in phpunit or behat test code.
        $sequencestart = 100000 + mt_rand(0, 99) * 1000;

        // Use the upstream file as source for the distributed configurations
        $ftemplate = file_get_contents("$CFG->dirroot/phpunit.xml.dist");
        $ftemplate = preg_replace('|<!--All core suites.*</testsuites>|s', '<!--@component_suite@-->', $ftemplate);

        // Gets all the components with tests
        $components = tests_finder::get_components_with_tests('phpunit');

        // Create the corresponding phpunit.xml file for each component
        foreach ($components as $cname => $cpath) {
            // Calculate the component suite
            $ctemplate = $template;
            $ctemplate = str_replace('@component@', $cname, $ctemplate);

            // Apply it to the file template
            $fcontents = str_replace('<!--@component_suite@-->', $ctemplate, $ftemplate);
            $fcontents = str_replace(
                '<const name="PHPUNIT_SEQUENCE_START" value=""/>',
                '<const name="PHPUNIT_SEQUENCE_START" value="' . $sequencestart . '"/>',
                $fcontents);

            // fix link to schema
            $level = substr_count(str_replace('\\', '/', $cpath), '/') - substr_count(str_replace('\\', '/', $CFG->dirroot), '/');
            $fcontents = str_replace('lib/phpunit/', str_repeat('../', $level).'lib/phpunit/', $fcontents);

            // Write the file
            $result = false;
            if (is_writable($cpath)) {
                if ($result = (bool)file_put_contents("$cpath/phpunit.xml", $fcontents)) {
                    testing_fix_file_permissions("$cpath/phpunit.xml");
                }
            }
            // Problems writing file, throw error
            if (!$result) {
                phpunit_bootstrap_error(PHPUNIT_EXITCODE_CONFIGWARNING, "Can not create $cpath/phpunit.xml configuration file, verify dir permissions");
            }
        }
    }

    /**
     * To be called from debugging() only.
     * @param string $message
     * @param int $level
     * @param string $from
     */
    public static function debugging_triggered($message, $level, $from) {
        // Store only if debugging triggered from actual test,
        // we need normal debugging outside of tests to find problems in our phpunit integration.
        $backtrace = debug_backtrace();

        foreach ($backtrace as $bt) {
            if (isset($bt['object']) and is_object($bt['object'])
                    && $bt['object'] instanceof PHPUnit_Framework_TestCase) {
                $debug = new stdClass();
                $debug->message = $message;
                $debug->level   = $level;
                $debug->from    = $from;

                self::$debuggings[] = $debug;

                return true;
            }
        }
        return false;
    }

    /**
     * Resets the list of debugging messages.
     */
    public static function reset_debugging() {
        self::$debuggings = array();
        set_debugging(DEBUG_DEVELOPER);
    }

    /**
     * Returns all debugging messages triggered during test.
     * @return array with instances having message, level and stacktrace property.
     */
    public static function get_debugging_messages() {
        return self::$debuggings;
    }

    /**
     * Prints out any debug messages accumulated during test execution.
     *
     * @param bool $return true to return the messages or false to print them directly. Default false.
     * @return bool|string false if no debug messages, true if debug triggered or string of messages
     */
    public static function display_debugging_messages($return = false) {
        if (empty(self::$debuggings)) {
            return false;
        }

        $debugstring = '';
        foreach(self::$debuggings as $debug) {
            $debugstring .= 'Debugging: ' . $debug->message . "\n" . trim($debug->from) . "\n";
        }

        if ($return) {
            return $debugstring;
        }
        echo $debugstring;
        return true;
    }

    /**
     * Start message redirection.
     *
     * Note: Do not call directly from tests,
     *       use $sink = $this->redirectMessages() instead.
     *
     * @return phpunit_message_sink
     */
    public static function start_message_redirection() {
        if (self::$messagesink) {
            self::stop_message_redirection();
        }
        self::$messagesink = new phpunit_message_sink();
        return self::$messagesink;
    }

    /**
     * End message redirection.
     *
     * Note: Do not call directly from tests,
     *       use $sink->close() instead.
     */
    public static function stop_message_redirection() {
        self::$messagesink = null;
    }

    /**
     * Are messages redirected to some sink?
     *
     * Note: to be called from messagelib.php only!
     *
     * @return bool
     */
    public static function is_redirecting_messages() {
        return !empty(self::$messagesink);
    }

    /**
     * To be called from messagelib.php only!
     *
     * @param stdClass $message record from message_read table
     * @return bool true means send message, false means message "sent" to sink.
     */
    public static function message_sent($message) {
        if (self::$messagesink) {
            self::$messagesink->add_message($message);
        }
    }

    /**
     * Start phpmailer redirection.
     *
     * Note: Do not call directly from tests,
     *       use $sink = $this->redirectEmails() instead.
     *
     * @return phpunit_phpmailer_sink
     */
    public static function start_phpmailer_redirection() {
        if (self::$phpmailersink) {
            // If an existing mailer sink is active, just clear it.
            self::$phpmailersink->clear();
        } else {
            self::$phpmailersink = new phpunit_phpmailer_sink();
        }
        return self::$phpmailersink;
    }

    /**
     * End phpmailer redirection.
     *
     * Note: Do not call directly from tests,
     *       use $sink->close() instead.
     */
    public static function stop_phpmailer_redirection() {
        self::$phpmailersink = null;
    }

    /**
     * Are messages for phpmailer redirected to some sink?
     *
     * Note: to be called from moodle_phpmailer.php only!
     *
     * @return bool
     */
    public static function is_redirecting_phpmailer() {
        return !empty(self::$phpmailersink);
    }

    /**
     * To be called from messagelib.php only!
     *
     * @param stdClass $message record from message_read table
     * @return bool true means send message, false means message "sent" to sink.
     */
    public static function phpmailer_sent($message) {
        if (self::$phpmailersink) {
            self::$phpmailersink->add_message($message);
        }
    }

    /**
     * Start event redirection.
     *
     * @private
     * Note: Do not call directly from tests,
     *       use $sink = $this->redirectEvents() instead.
     *
     * @return phpunit_event_sink
     */
    public static function start_event_redirection() {
        if (self::$eventsink) {
            self::stop_event_redirection();
        }
        self::$eventsink = new phpunit_event_sink();
        return self::$eventsink;
    }

    /**
     * End event redirection.
     *
     * @private
     * Note: Do not call directly from tests,
     *       use $sink->close() instead.
     */
    public static function stop_event_redirection() {
        self::$eventsink = null;
    }

    /**
     * Are events redirected to some sink?
     *
     * Note: to be called from \core\event\base only!
     *
     * @private
     * @return bool
     */
    public static function is_redirecting_events() {
        return !empty(self::$eventsink);
    }

    /**
     * To be called from \core\event\base only!
     *
     * @private
     * @param \core\event\base $event record from event_read table
     * @return bool true means send event, false means event "sent" to sink.
     */
    public static function event_triggered(\core\event\base $event) {
        if (self::$eventsink) {
            self::$eventsink->add_event($event);
        }
    }

    /**
     * Gets the name of the locale for testing environment (Australian English)
     * depending on platform environment.
     *
     * @return string the locale name.
     */
    protected static function get_locale_name() {
        global $CFG;
        if ($CFG->ostype === 'WINDOWS') {
            return 'English_Australia.1252';
        } else {
            return 'en_AU.UTF-8';
        }
    }

    /**
     * Executes all adhoc tasks in the queue. Useful for testing asynchronous behaviour.
     *
     * @return void
     */
    public static function run_all_adhoc_tasks() {
        $now = time();
        while (($task = \core\task\manager::get_next_adhoc_task($now)) !== null) {
            try {
                $task->execute();
                \core\task\manager::adhoc_task_complete($task);
            } catch (Exception $e) {
                \core\task\manager::adhoc_task_failed($task);
            }
        }
    }

    /**
     * Helper function to call a protected/private method of an object using reflection.
     *
     * Example 1. Calling a protected object method:
     *   $result = call_internal_method($myobject, 'method_name', [$param1, $param2], '\my\namespace\myobjectclassname');
     *
     * Example 2. Calling a protected static method:
     *   $result = call_internal_method(null, 'method_name', [$param1, $param2], '\my\namespace\myclassname');
     *
     * @param object|null $object the object on which to call the method, or null if calling a static method.
     * @param string $methodname the name of the protected/private method.
     * @param array $params the array of function params to pass to the method.
     * @param string $classname the fully namespaced name of the class the object was created from (base in the case of mocks),
     *        or the name of the static class when calling a static method.
     * @return mixed the respective return value of the method.
     */
    public static function call_internal_method($object, $methodname, array $params = array(), $classname) {
        $reflection = new \ReflectionClass($classname);
        $method = $reflection->getMethod($methodname);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $params);
    }
}
