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
 * Data generator.
 *
 * @package    core
 * @category   test
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Data generator class for unit tests and other tools that need to create fake test sites.
 *
 * @package    core
 * @category   test
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testing_data_generator {
    /** @var int The number of grade categories created */
    protected $gradecategorycounter = 0;
    /** @var int The number of grade items created */
    protected $gradeitemcounter = 0;
    /** @var int The number of grade outcomes created */
    protected $gradeoutcomecounter = 0;
    protected $usercounter = 0;
    protected $categorycount = 0;
    protected $cohortcount = 0;
    protected $coursecount = 0;
    protected $scalecount = 0;
    protected $groupcount = 0;
    protected $groupingcount = 0;
    protected $rolecount = 0;
    protected $tagcount = 0;

    /** @var array list of plugin generators */
    protected $generators = array();

    /** @var array lis of common last names */
    public $lastnames = array(
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'García', 'Rodríguez', 'Wilson',
        'Müller', 'Schmidt', 'Schneider', 'Fischer', 'Meyer', 'Weber', 'Schulz', 'Wagner', 'Becker', 'Hoffmann',
        'Novák', 'Svoboda', 'Novotný', 'Dvořák', 'Černý', 'Procházková', 'Kučerová', 'Veselá', 'Horáková', 'Němcová',
        'Смирнов', 'Иванов', 'Кузнецов', 'Соколов', 'Попов', 'Лебедева', 'Козлова', 'Новикова', 'Морозова', 'Петрова',
        '王', '李', '张', '刘', '陈', '楊', '黃', '趙', '吳', '周',
        '佐藤', '鈴木', '高橋', '田中', '渡辺', '伊藤', '山本', '中村', '小林', '斎藤',
    );

    /** @var array lis of common first names */
    public $firstnames = array(
        'Jacob', 'Ethan', 'Michael', 'Jayden', 'William', 'Isabella', 'Sophia', 'Emma', 'Olivia', 'Ava',
        'Lukas', 'Leon', 'Luca', 'Timm', 'Paul', 'Leonie', 'Leah', 'Lena', 'Hanna', 'Laura',
        'Jakub', 'Jan', 'Tomáš', 'Lukáš', 'Matěj', 'Tereza', 'Eliška', 'Anna', 'Adéla', 'Karolína',
        'Даниил', 'Максим', 'Артем', 'Иван', 'Александр', 'София', 'Анастасия', 'Дарья', 'Мария', 'Полина',
        '伟', '伟', '芳', '伟', '秀英', '秀英', '娜', '秀英', '伟', '敏',
        '翔', '大翔', '拓海', '翔太', '颯太', '陽菜', 'さくら', '美咲', '葵', '美羽',
    );

    public $loremipsum = <<<EOD
Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nulla non arcu lacinia neque faucibus fringilla. Vivamus porttitor turpis ac leo. Integer in sapien. Nullam eget nisl. Aliquam erat volutpat. Cras elementum. Mauris suscipit, ligula sit amet pharetra semper, nibh ante cursus purus, vel sagittis velit mauris vel metus. Integer malesuada. Nullam lectus justo, vulputate eget mollis sed, tempor sed magna. Mauris elementum mauris vitae tortor. Aliquam erat volutpat.
Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Pellentesque ipsum. Cras pede libero, dapibus nec, pretium sit amet, tempor quis. Aliquam ante. Proin in tellus sit amet nibh dignissim sagittis. Vivamus porttitor turpis ac leo. Duis bibendum, lectus ut viverra rhoncus, dolor nunc faucibus libero, eget facilisis enim ipsum id lacus. In sem justo, commodo ut, suscipit at, pharetra vitae, orci. Aliquam erat volutpat. Nulla est.
Vivamus luctus egestas leo. Aenean fermentum risus id tortor. Mauris dictum facilisis augue. Aliquam erat volutpat. Aliquam ornare wisi eu metus. Aliquam id dolor. Duis condimentum augue id magna semper rutrum. Donec iaculis gravida nulla. Pellentesque ipsum. Etiam dictum tincidunt diam. Quisque tincidunt scelerisque libero. Etiam egestas wisi a erat.
Integer lacinia. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Mauris tincidunt sem sed arcu. Nullam feugiat, turpis at pulvinar vulputate, erat libero tristique tellus, nec bibendum odio risus sit amet ante. Aliquam id dolor. Maecenas sollicitudin. Et harum quidem rerum facilis est et expedita distinctio. Mauris suscipit, ligula sit amet pharetra semper, nibh ante cursus purus, vel sagittis velit mauris vel metus. Nullam dapibus fermentum ipsum. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Pellentesque sapien. Duis risus. Mauris elementum mauris vitae tortor. Suspendisse nisl. Integer rutrum, orci vestibulum ullamcorper ultricies, lacus quam ultricies odio, vitae placerat pede sem sit amet enim.
In laoreet, magna id viverra tincidunt, sem odio bibendum justo, vel imperdiet sapien wisi sed libero. Proin pede metus, vulputate nec, fermentum fringilla, vehicula vitae, justo. Nullam justo enim, consectetuer nec, ullamcorper ac, vestibulum in, elit. Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur? Maecenas lorem. Etiam posuere lacus quis dolor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Curabitur ligula sapien, pulvinar a vestibulum quis, facilisis vel sapien. Nam sed tellus id magna elementum tincidunt. Suspendisse nisl. Vivamus luctus egestas leo. Nulla non arcu lacinia neque faucibus fringilla. Etiam dui sem, fermentum vitae, sagittis id, malesuada in, quam. Etiam dictum tincidunt diam. Etiam commodo dui eget wisi. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Proin pede metus, vulputate nec, fermentum fringilla, vehicula vitae, justo. Duis ante orci, molestie vitae vehicula venenatis, tincidunt ac pede. Pellentesque sapien.
EOD;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->usercounter = 0;
        $this->categorycount = 0;
        $this->coursecount = 0;
        $this->scalecount = 0;

        foreach ($this->generators as $generator) {
            $generator->reset();
        }
    }

    /**
     * Return generator for given plugin or component.
     * @param string $component the component name, e.g. 'mod_forum' or 'core_question'.
     * @return component_generator_base or rather an instance of the appropriate subclass.
     */
    public function get_plugin_generator($component) {
        list($type, $plugin) = core_component::normalize_component($component);
        $cleancomponent = $type . '_' . $plugin;
        if ($cleancomponent != $component) {
            debugging("Please specify the component you want a generator for as " .
                    "{$cleancomponent}, not {$component}.", DEBUG_DEVELOPER);
            $component = $cleancomponent;
        }

        if (isset($this->generators[$component])) {
            return $this->generators[$component];
        }

        $dir = core_component::get_component_directory($component);
        $lib = $dir . '/tests/generator/lib.php';
        if (!$dir || !is_readable($lib)) {
            throw new coding_exception("Component {$component} does not support " .
                    "generators yet. Missing tests/generator/lib.php.");
        }

        include_once($lib);
        $classname = $component . '_generator';

        if (!class_exists($classname)) {
            throw new coding_exception("Component {$component} does not support " .
                    "data generators yet. Class {$classname} not found.");
        }

        $this->generators[$component] = new $classname($this);
        return $this->generators[$component];
    }

    /**
     * Create a test user
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass user record
     */
    public function create_user($record=null, array $options=null) {
        global $DB, $CFG;

        $this->usercounter++;
        $i = $this->usercounter;

        $record = (array)$record;

        if (!isset($record['auth'])) {
            $record['auth'] = 'manual';
        }

        if (!isset($record['firstname']) and !isset($record['lastname'])) {
            $country = rand(0, 5);
            $firstname = rand(0, 4);
            $lastname = rand(0, 4);
            $female = rand(0, 1);
            $record['firstname'] = $this->firstnames[($country*10) + $firstname + ($female*5)];
            $record['lastname'] = $this->lastnames[($country*10) + $lastname + ($female*5)];

        } else if (!isset($record['firstname'])) {
            $record['firstname'] = 'Firstname'.$i;

        } else if (!isset($record['lastname'])) {
            $record['lastname'] = 'Lastname'.$i;
        }

        if (!isset($record['firstnamephonetic'])) {
            $firstnamephonetic = rand(0, 59);
            $record['firstnamephonetic'] = $this->firstnames[$firstnamephonetic];
        }

        if (!isset($record['lastnamephonetic'])) {
            $lastnamephonetic = rand(0, 59);
            $record['lastnamephonetic'] = $this->lastnames[$lastnamephonetic];
        }

        if (!isset($record['middlename'])) {
            $middlename = rand(0, 59);
            $record['middlename'] = $this->firstnames[$middlename];
        }

        if (!isset($record['alternatename'])) {
            $alternatename = rand(0, 59);
            $record['alternatename'] = $this->firstnames[$alternatename];
        }

        if (!isset($record['idnumber'])) {
            $record['idnumber'] = '';
        }

        if (!isset($record['mnethostid'])) {
            $record['mnethostid'] = $CFG->mnet_localhost_id;
        }

        if (!isset($record['username'])) {
            $record['username'] = 'username'.$i;
            $j = 2;
            while ($DB->record_exists('user', array('username'=>$record['username'], 'mnethostid'=>$record['mnethostid']))) {
                $record['username'] = 'username'.$i.'_'.$j;
                $j++;
            }
        }

        if (isset($record['password'])) {
            $record['password'] = hash_internal_user_password($record['password']);
        } else {
            // The auth plugin may not fully support this,
            // but it is still better/faster than hashing random stuff.
            $record['password'] = AUTH_PASSWORD_NOT_CACHED;
        }

        if (!isset($record['email'])) {
            $record['email'] = $record['username'].'@example.com';
        }

        if (!isset($record['confirmed'])) {
            $record['confirmed'] = 1;
        }

        if (!isset($record['lang'])) {
            $record['lang'] = 'en';
        }

        if (!isset($record['maildisplay'])) {
            $record['maildisplay'] = $CFG->defaultpreference_maildisplay;
        }

        if (!isset($record['mailformat'])) {
            $record['mailformat'] = $CFG->defaultpreference_mailformat;
        }

        if (!isset($record['maildigest'])) {
            $record['maildigest'] = $CFG->defaultpreference_maildigest;
        }

        if (!isset($record['autosubscribe'])) {
            $record['autosubscribe'] = $CFG->defaultpreference_autosubscribe;
        }

        if (!isset($record['trackforums'])) {
            $record['trackforums'] = $CFG->defaultpreference_trackforums;
        }

        if (!isset($record['deleted'])) {
            $record['deleted'] = 0;
        }

        if (!isset($record['timecreated'])) {
            $record['timecreated'] = time();
        }

        $record['timemodified'] = $record['timecreated'];
        $record['lastip'] = '0.0.0.0';

        if ($record['deleted']) {
            $delname = $record['email'].'.'.time();
            while ($DB->record_exists('user', array('username'=>$delname))) {
                $delname++;
            }
            $record['idnumber'] = '';
            $record['email']    = md5($record['username']);
            $record['username'] = $delname;
            $record['picture']  = 0;
        }

        $userid = $DB->insert_record('user', $record);

        if (!$record['deleted']) {
            context_user::instance($userid);
        }

        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

        if (!$record['deleted'] && isset($record['interests'])) {
            require_once($CFG->dirroot . '/user/editlib.php');
            if (!is_array($record['interests'])) {
                $record['interests'] = preg_split('/\s*,\s*/', trim($record['interests']), -1, PREG_SPLIT_NO_EMPTY);
            }
            useredit_update_interests($user, $record['interests']);
        }

        return $user;
    }

    /**
     * Create a test course category
     * @param array|stdClass $record
     * @param array $options
     * @return coursecat course category record
     */
    public function create_category($record=null, array $options=null) {
        global $DB, $CFG;
        require_once("$CFG->libdir/coursecatlib.php");

        $this->categorycount++;
        $i = $this->categorycount;

        $record = (array)$record;

        if (!isset($record['name'])) {
            $record['name'] = 'Course category '.$i;
        }

        if (!isset($record['description'])) {
            $record['description'] = "Test course category $i\n$this->loremipsum";
        }

        if (!isset($record['idnumber'])) {
            $record['idnumber'] = '';
        }

        return coursecat::create($record);
    }

    /**
     * Create test cohort.
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass cohort record
     */
    public function create_cohort($record=null, array $options=null) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/cohort/lib.php");

        $this->cohortcount++;
        $i = $this->cohortcount;

        $record = (array)$record;

        if (!isset($record['contextid'])) {
            $record['contextid'] = context_system::instance()->id;
        }

        if (!isset($record['name'])) {
            $record['name'] = 'Cohort '.$i;
        }

        if (!isset($record['idnumber'])) {
            $record['idnumber'] = '';
        }

        if (!isset($record['description'])) {
            $record['description'] = "Test cohort $i\n$this->loremipsum";
        }

        if (!isset($record['descriptionformat'])) {
            $record['descriptionformat'] = FORMAT_MOODLE;
        }

        if (!isset($record['visible'])) {
            $record['visible'] = 1;
        }

        if (!isset($record['component'])) {
            $record['component'] = '';
        }

        $id = cohort_add_cohort((object)$record);

        return $DB->get_record('cohort', array('id'=>$id), '*', MUST_EXIST);
    }

    /**
     * Create a test course
     * @param array|stdClass $record
     * @param array $options with keys:
     *      'createsections'=>bool precreate all sections
     * @return stdClass course record
     */
    public function create_course($record=null, array $options=null) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/course/lib.php");

        $this->coursecount++;
        $i = $this->coursecount;

        $record = (array)$record;

        if (!isset($record['fullname'])) {
            $record['fullname'] = 'Test course '.$i;
        }

        if (!isset($record['shortname'])) {
            $record['shortname'] = 'tc_'.$i;
        }

        if (!isset($record['idnumber'])) {
            $record['idnumber'] = '';
        }

        if (!isset($record['format'])) {
            $record['format'] = 'topics';
        }

        if (!isset($record['newsitems'])) {
            $record['newsitems'] = 0;
        }

        if (!isset($record['numsections'])) {
            $record['numsections'] = 5;
        }

        if (!isset($record['summary'])) {
            $record['summary'] = "Test course $i\n$this->loremipsum";
        }

        if (!isset($record['summaryformat'])) {
            $record['summaryformat'] = FORMAT_MOODLE;
        }

        if (!isset($record['category'])) {
            $record['category'] = $DB->get_field_select('course_categories', "MIN(id)", "parent=0");
        }

        if (!isset($record['startdate'])) {
            $record['startdate'] = usergetmidnight(time());
        }

        if (isset($record['tags']) && !is_array($record['tags'])) {
            $record['tags'] = preg_split('/\s*,\s*/', trim($record['tags']), -1, PREG_SPLIT_NO_EMPTY);
        }

        if (!empty($options['createsections']) && empty($record['numsections'])) {
            // Since Moodle 3.3 function create_course() automatically creates sections if numsections is specified.
            // For BC if 'createsections' is given but 'numsections' is not, assume the default value from config.
            $record['numsections'] = get_config('moodlecourse', 'numsections');
        }

        $course = create_course((object)$record);
        context_course::instance($course->id);

        return $course;
    }

    /**
     * Create course section if does not exist yet
     * @param array|stdClass $record must contain 'course' and 'section' attributes
     * @param array|null $options
     * @return stdClass
     * @throws coding_exception
     */
    public function create_course_section($record = null, array $options = null) {
        global $DB;

        $record = (array)$record;

        if (empty($record['course'])) {
            throw new coding_exception('course must be present in testing_data_generator::create_course_section() $record');
        }

        if (!isset($record['section'])) {
            throw new coding_exception('section must be present in testing_data_generator::create_course_section() $record');
        }

        course_create_sections_if_missing($record['course'], $record['section']);
        return get_fast_modinfo($record['course'])->get_section_info($record['section']);
    }

    /**
     * Create a test block.
     *
     * The $record passed in becomes the basis for the new row added to the
     * block_instances table. You only need to supply the values of interest.
     * Any missing values have sensible defaults filled in, and ->blockname will be set based on $blockname.
     *
     * The $options array provides additional data, not directly related to what
     * will be inserted in the block_instance table, which may affect the block
     * that is created. The meanings of any data passed here depends on the particular
     * type of block being created.
     *
     * @param string $blockname the type of block to create. E.g. 'html'.
     * @param array|stdClass $record forms the basis for the entry to be inserted in the block_instances table.
     * @param array $options further, block-specific options to control how the block is created.
     * @return stdClass new block_instance record.
     */
    public function create_block($blockname, $record=null, array $options=array()) {
        $generator = $this->get_plugin_generator('block_'.$blockname);
        return $generator->create_instance($record, $options);
    }

    /**
     * Create a test activity module.
     *
     * The $record should contain the same data that you would call from
     * ->get_data() when the mod_[type]_mod_form is submitted, except that you
     * only need to supply values of interest. The only required value is
     * 'course'. Any missing values will have a sensible default supplied.
     *
     * The $options array provides additional data, not directly related to what
     * would come back from the module edit settings form, which may affect the activity
     * that is created. The meanings of any data passed here depends on the particular
     * type of activity being created.
     *
     * @param string $modulename the type of activity to create. E.g. 'forum' or 'quiz'.
     * @param array|stdClass $record data, as if from the module edit settings form.
     * @param array $options additional data that may affect how the module is created.
     * @return stdClass activity record new new record that was just inserted in the table
     *      like 'forum' or 'quiz', with a ->cmid field added.
     */
    public function create_module($modulename, $record=null, array $options=null) {
        $generator = $this->get_plugin_generator('mod_'.$modulename);
        return $generator->create_instance($record, $options);
    }

    /**
     * Create a test group for the specified course
     *
     * $record should be either an array or a stdClass containing infomation about the group to create.
     * At the very least it needs to contain courseid.
     * Default values are added for name, description, and descriptionformat if they are not present.
     *
     * This function calls groups_create_group() to create the group within the database.
     * @see groups_create_group
     * @param array|stdClass $record
     * @return stdClass group record
     */
    public function create_group($record) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        $this->groupcount++;
        $i = $this->groupcount;

        $record = (array)$record;

        if (empty($record['courseid'])) {
            throw new coding_exception('courseid must be present in testing_data_generator::create_group() $record');
        }

        if (!isset($record['name'])) {
            $record['name'] = 'group-' . $i;
        }

        if (!isset($record['description'])) {
            $record['description'] = "Test Group $i\n{$this->loremipsum}";
        }

        if (!isset($record['descriptionformat'])) {
            $record['descriptionformat'] = FORMAT_MOODLE;
        }

        $id = groups_create_group((object)$record);

        return $DB->get_record('groups', array('id'=>$id));
    }

    /**
     * Create a test group member
     * @param array|stdClass $record
     * @throws coding_exception
     * @return boolean
     */
    public function create_group_member($record) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        $record = (array)$record;

        if (empty($record['userid'])) {
            throw new coding_exception('user must be present in testing_util::create_group_member() $record');
        }

        if (!isset($record['groupid'])) {
            throw new coding_exception('group must be present in testing_util::create_group_member() $record');
        }

        if (!isset($record['component'])) {
            $record['component'] = null;
        }
        if (!isset($record['itemid'])) {
            $record['itemid'] = 0;
        }

        return groups_add_member($record['groupid'], $record['userid'], $record['component'], $record['itemid']);
    }

    /**
     * Create a test grouping for the specified course
     *
     * $record should be either an array or a stdClass containing infomation about the grouping to create.
     * At the very least it needs to contain courseid.
     * Default values are added for name, description, and descriptionformat if they are not present.
     *
     * This function calls groups_create_grouping() to create the grouping within the database.
     * @see groups_create_grouping
     * @param array|stdClass $record
     * @return stdClass grouping record
     */
    public function create_grouping($record) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        $this->groupingcount++;
        $i = $this->groupingcount;

        $record = (array)$record;

        if (empty($record['courseid'])) {
            throw new coding_exception('courseid must be present in testing_data_generator::create_grouping() $record');
        }

        if (!isset($record['name'])) {
            $record['name'] = 'grouping-' . $i;
        }

        if (!isset($record['description'])) {
            $record['description'] = "Test Grouping $i\n{$this->loremipsum}";
        }

        if (!isset($record['descriptionformat'])) {
            $record['descriptionformat'] = FORMAT_MOODLE;
        }

        $id = groups_create_grouping((object)$record);

        return $DB->get_record('groupings', array('id'=>$id));
    }

    /**
     * Create a test grouping group
     * @param array|stdClass $record
     * @throws coding_exception
     * @return boolean
     */
    public function create_grouping_group($record) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        $record = (array)$record;

        if (empty($record['groupingid'])) {
            throw new coding_exception('grouping must be present in testing::create_grouping_group() $record');
        }

        if (!isset($record['groupid'])) {
            throw new coding_exception('group must be present in testing_util::create_grouping_group() $record');
        }

        return groups_assign_grouping($record['groupingid'], $record['groupid']);
    }

    /**
     * Create an instance of a repository.
     *
     * @param string type of repository to create an instance for.
     * @param array|stdClass $record data to use to up set the instance.
     * @param array $options options
     * @return stdClass repository instance record
     * @since Moodle 2.5.1
     */
    public function create_repository($type, $record=null, array $options = null) {
        $generator = $this->get_plugin_generator('repository_'.$type);
        return $generator->create_instance($record, $options);
    }

    /**
     * Create an instance of a repository.
     *
     * @param string type of repository to create an instance for.
     * @param array|stdClass $record data to use to up set the instance.
     * @param array $options options
     * @return repository_type object
     * @since Moodle 2.5.1
     */
    public function create_repository_type($type, $record=null, array $options = null) {
        $generator = $this->get_plugin_generator('repository_'.$type);
        return $generator->create_type($record, $options);
    }


    /**
     * Create a test scale
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass block instance record
     */
    public function create_scale($record=null, array $options=null) {
        global $DB;

        $this->scalecount++;
        $i = $this->scalecount;

        $record = (array)$record;

        if (!isset($record['name'])) {
            $record['name'] = 'Test scale '.$i;
        }

        if (!isset($record['scale'])) {
            $record['scale'] = 'A,B,C,D,F';
        }

        if (!isset($record['courseid'])) {
            $record['courseid'] = 0;
        }

        if (!isset($record['userid'])) {
            $record['userid'] = 0;
        }

        if (!isset($record['description'])) {
            $record['description'] = 'Test scale description '.$i;
        }

        if (!isset($record['descriptionformat'])) {
            $record['descriptionformat'] = FORMAT_MOODLE;
        }

        $record['timemodified'] = time();

        if (isset($record['id'])) {
            $DB->import_record('scale', $record);
            $DB->get_manager()->reset_sequence('scale');
            $id = $record['id'];
        } else {
            $id = $DB->insert_record('scale', $record);
        }

        return $DB->get_record('scale', array('id'=>$id), '*', MUST_EXIST);
    }

    /**
     * Creates a new role in the system.
     *
     * You can fill $record with the role 'name',
     * 'shortname', 'description' and 'archetype'.
     *
     * If an archetype is specified it's capabilities,
     * context where the role can be assigned and
     * all other properties are copied from the archetype;
     * if no archetype is specified it will create an
     * empty role.
     *
     * @param array|stdClass $record
     * @return int The new role id
     */
    public function create_role($record=null) {
        global $DB;

        $this->rolecount++;
        $i = $this->rolecount;

        $record = (array)$record;

        if (empty($record['shortname'])) {
            $record['shortname'] = 'role-' . $i;
        }

        if (empty($record['name'])) {
            $record['name'] = 'Test role ' . $i;
        }

        if (empty($record['description'])) {
            $record['description'] = 'Test role ' . $i . ' description';
        }

        if (empty($record['archetype'])) {
            $record['archetype'] = '';
        } else {
            $archetypes = get_role_archetypes();
            if (empty($archetypes[$record['archetype']])) {
                throw new coding_exception('\'role\' requires the field \'archetype\' to specify a ' .
                    'valid archetype shortname (editingteacher, student...)');
            }
        }

        // Creates the role.
        if (!$newroleid = create_role($record['name'], $record['shortname'], $record['description'], $record['archetype'])) {
            throw new coding_exception('There was an error creating \'' . $record['shortname'] . '\' role');
        }

        // If no archetype was specified we allow it to be added to all contexts,
        // otherwise we allow it in the archetype contexts.
        if (!$record['archetype']) {
            $contextlevels = array_keys(context_helper::get_all_levels());
        } else {
            // Copying from the archetype default rol.
            $archetyperoleid = $DB->get_field(
                'role',
                'id',
                array('shortname' => $record['archetype'], 'archetype' => $record['archetype'])
            );
            $contextlevels = get_role_contextlevels($archetyperoleid);
        }
        set_role_contextlevels($newroleid, $contextlevels);

        if ($record['archetype']) {

            // We copy all the roles the archetype can assign, override and switch to.
            if ($record['archetype']) {
                $types = array('assign', 'override', 'switch');
                foreach ($types as $type) {
                    $rolestocopy = get_default_role_archetype_allows($type, $record['archetype']);
                    foreach ($rolestocopy as $tocopy) {
                        $functionname = 'allow_' . $type;
                        $functionname($newroleid, $tocopy);
                    }
                }
            }

            // Copying the archetype capabilities.
            $sourcerole = $DB->get_record('role', array('id' => $archetyperoleid));
            role_cap_duplicate($sourcerole, $newroleid);
        }

        return $newroleid;
    }

    /**
     * Create a tag.
     *
     * @param array|stdClass $record
     * @return stdClass the tag record
     */
    public function create_tag($record = null) {
        global $DB, $USER;

        $this->tagcount++;
        $i = $this->tagcount;

        $record = (array) $record;

        if (!isset($record['userid'])) {
            $record['userid'] = $USER->id;
        }

        if (!isset($record['rawname'])) {
            if (isset($record['name'])) {
                $record['rawname'] = $record['name'];
            } else {
                $record['rawname'] = 'Tag name ' . $i;
            }
        }

        // Attribute 'name' should be a lowercase version of 'rawname', if not set.
        if (!isset($record['name'])) {
            $record['name'] = core_text::strtolower($record['rawname']);
        } else {
            $record['name'] = core_text::strtolower($record['name']);
        }

        if (!isset($record['tagcollid'])) {
            $record['tagcollid'] = core_tag_collection::get_default();
        }

        if (!isset($record['description'])) {
            $record['description'] = 'Tag description';
        }

        if (!isset($record['descriptionformat'])) {
            $record['descriptionformat'] = FORMAT_MOODLE;
        }

        if (!isset($record['flag'])) {
            $record['flag'] = 0;
        }

        if (!isset($record['timemodified'])) {
            $record['timemodified'] = time();
        }

        $id = $DB->insert_record('tag', $record);

        return $DB->get_record('tag', array('id' => $id), '*', MUST_EXIST);
    }

    /**
     * Helper method which combines $defaults with the values specified in $record.
     * If $record is an object, it is converted to an array.
     * Then, for each key that is in $defaults, but not in $record, the value
     * from $defaults is copied.
     * @param array $defaults the default value for each field with
     * @param array|stdClass $record
     * @return array updated $record.
     */
    public function combine_defaults_and_record(array $defaults, $record) {
        $record = (array) $record;

        foreach ($defaults as $key => $defaults) {
            if (!array_key_exists($key, $record)) {
                $record[$key] = $defaults;
            }
        }
        return $record;
    }

    /**
     * Simplified enrolment of user to course using default options.
     *
     * It is strongly recommended to use only this method for 'manual' and 'self' plugins only!!!
     *
     * @param int $userid
     * @param int $courseid
     * @param int|string $roleidorshortname optional role id or role shortname, use only with manual plugin
     * @param string $enrol name of enrol plugin,
     *     there must be exactly one instance in course,
     *     it must support enrol_user() method.
     * @param int $timestart (optional) 0 means unknown
     * @param int $timeend (optional) 0 means forever
     * @param int $status (optional) default to ENROL_USER_ACTIVE for new enrolments
     * @return bool success
     */
    public function enrol_user($userid, $courseid, $roleidorshortname = null, $enrol = 'manual',
            $timestart = 0, $timeend = 0, $status = null) {
        global $DB;

        // If role is specified by shortname, convert it into an id.
        if (!is_numeric($roleidorshortname) && is_string($roleidorshortname)) {
            $roleid = $DB->get_field('role', 'id', array('shortname' => $roleidorshortname), MUST_EXIST);
        } else {
            $roleid = $roleidorshortname;
        }

        if (!$plugin = enrol_get_plugin($enrol)) {
            return false;
        }

        $instances = $DB->get_records('enrol', array('courseid'=>$courseid, 'enrol'=>$enrol));
        if (count($instances) != 1) {
            return false;
        }
        $instance = reset($instances);

        if (is_null($roleid) and $instance->roleid) {
            $roleid = $instance->roleid;
        }

        $plugin->enrol_user($instance, $userid, $roleid, $timestart, $timeend, $status);
        return true;
    }

    /**
     * Assigns the specified role to a user in the context.
     *
     * @param int $roleid
     * @param int $userid
     * @param int $contextid Defaults to the system context
     * @return int new/existing id of the assignment
     */
    public function role_assign($roleid, $userid, $contextid = false) {

        // Default to the system context.
        if (!$contextid) {
            $context = context_system::instance();
            $contextid = $context->id;
        }

        if (empty($roleid)) {
            throw new coding_exception('roleid must be present in testing_data_generator::role_assign() arguments');
        }

        if (empty($userid)) {
            throw new coding_exception('userid must be present in testing_data_generator::role_assign() arguments');
        }

        return role_assign($roleid, $userid, $contextid);
    }

    /**
     * Create a grade_category.
     *
     * @param array|stdClass $record
     * @return stdClass the grade category record
     */
    public function create_grade_category($record = null) {
        global $CFG;

        $this->gradecategorycounter++;

        $record = (array)$record;

        if (empty($record['courseid'])) {
            throw new coding_exception('courseid must be present in testing::create_grade_category() $record');
        }

        if (!isset($record['fullname'])) {
            $record['fullname'] = 'Grade category ' . $this->gradecategorycounter;
        }

        // For gradelib classes.
        require_once($CFG->libdir . '/gradelib.php');
        // Create new grading category in this course.
        $gradecategory = new grade_category(array('courseid' => $record['courseid']), false);
        $gradecategory->apply_default_settings();
        grade_category::set_properties($gradecategory, $record);
        $gradecategory->apply_forced_settings();
        $gradecategory->insert();

        // This creates a default grade item for the category
        $gradeitem = $gradecategory->load_grade_item();

        $gradecategory->update_from_db();
        return $gradecategory->get_record_data();
    }

    /**
     * Create a grade_item.
     *
     * @param array|stdClass $record
     * @return stdClass the grade item record
     */
    public function create_grade_item($record = null) {
        global $CFG;
        require_once("$CFG->libdir/gradelib.php");

        $this->gradeitemcounter++;

        if (!isset($record['itemtype'])) {
            $record['itemtype'] = 'manual';
        }

        if (!isset($record['itemname'])) {
            $record['itemname'] = 'Grade item ' . $this->gradeitemcounter;
        }

        if (isset($record['outcomeid'])) {
            $outcome = new grade_outcome(array('id' => $record['outcomeid']));
            $record['scaleid'] = $outcome->scaleid;
        }
        if (isset($record['scaleid'])) {
            $record['gradetype'] = GRADE_TYPE_SCALE;
        } else if (!isset($record['gradetype'])) {
            $record['gradetype'] = GRADE_TYPE_VALUE;
        }

        // Create new grade item in this course.
        $gradeitem = new grade_item($record, false);
        $gradeitem->insert();

        $gradeitem->update_from_db();
        return $gradeitem->get_record_data();
    }

    /**
     * Create a grade_outcome.
     *
     * @param array|stdClass $record
     * @return stdClass the grade outcome record
     */
    public function create_grade_outcome($record = null) {
        global $CFG;

        $this->gradeoutcomecounter++;
        $i = $this->gradeoutcomecounter;

        if (!isset($record['fullname'])) {
            $record['fullname'] = 'Grade outcome ' . $i;
        }

        // For gradelib classes.
        require_once($CFG->libdir . '/gradelib.php');
        // Create new grading outcome in this course.
        $gradeoutcome = new grade_outcome($record, false);
        $gradeoutcome->insert();

        $gradeoutcome->update_from_db();
        return $gradeoutcome->get_record_data();
    }

    /**
     * Helper function used to create an LTI tool.
     *
     * @param array $data
     * @return stdClass the tool
     */
    public function create_lti_tool($data = array()) {
        global $DB;

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

        // Create a course if no course id was specified.
        if (empty($data->courseid)) {
            $course = $this->create_course();
            $data->courseid = $course->id;
        } else {
            $course = get_course($data->courseid);
        }

        if (!empty($data->cmid)) {
            $data->contextid = context_module::instance($data->cmid)->id;
        } else {
            $data->contextid = context_course::instance($data->courseid)->id;
        }

        // Set it to enabled if no status was specified.
        if (!isset($data->status)) {
            $data->status = ENROL_INSTANCE_ENABLED;
        }

        // Add some extra necessary fields to the data.
        $data->name = 'Test LTI';
        $data->roleinstructor = $studentrole->id;
        $data->rolelearner = $teacherrole->id;

        // Get the enrol LTI plugin.
        $enrolplugin = enrol_get_plugin('lti');
        $instanceid = $enrolplugin->add_instance($course, (array) $data);

        // Get the tool associated with this instance.
        return $DB->get_record('enrol_lti_tools', array('enrolid' => $instanceid));
    }

    /**
     * Helper function used to create an event.
     *
     * @param   array   $data
     * @return  stdClass
     */
    public function create_event($data = []) {
        global $CFG;

        require_once($CFG->dirroot . '/calendar/lib.php');
        $record = new \stdClass();
        $record->name = 'event name';
        $record->eventtype = 'global';
        $record->repeat = 0;
        $record->repeats = 0;
        $record->timestart = time();
        $record->timeduration = 0;
        $record->timesort = 0;
        $record->eventtype = 'user';
        $record->courseid = 0;
        $record->categoryid = 0;

        foreach ($data as $key => $value) {
            $record->$key = $value;
        }

        switch ($record->eventtype) {
            case 'user':
                unset($record->categoryid);
                unset($record->courseid);
                unset($record->groupid);
                break;
            case 'group':
                unset($record->categoryid);
                break;
            case 'course':
                unset($record->categoryid);
                unset($record->groupid);
                break;
            case 'category':
                unset($record->courseid);
                unset($record->groupid);
                break;
            case 'global':
                unset($record->categoryid);
                unset($record->courseid);
                unset($record->groupid);
                break;
        }

        $event = new calendar_event($record);
        $event->create($record);

        return $event->properties();
    }
}
