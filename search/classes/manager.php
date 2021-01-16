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
 * Search subsystem manager.
 *
 * @package   core_search
 * @copyright Prateek Sachan {@link http://prateeksachan.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_search;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/lib/accesslib.php');

/**
 * Search subsystem manager.
 *
 * @package   core_search
 * @copyright Prateek Sachan {@link http://prateeksachan.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /**
     * @var int Text contents.
     */
    const TYPE_TEXT = 1;

    /**
     * @var int File contents.
     */
    const TYPE_FILE = 2;

    /**
     * @var int User can not access the document.
     */
    const ACCESS_DENIED = 0;

    /**
     * @var int User can access the document.
     */
    const ACCESS_GRANTED = 1;

    /**
     * @var int The document was deleted.
     */
    const ACCESS_DELETED = 2;

    /**
     * @var int Maximum number of results that will be retrieved from the search engine.
     */
    const MAX_RESULTS = 100;

    /**
     * @var int Number of results per page.
     */
    const DISPLAY_RESULTS_PER_PAGE = 10;

    /**
     * @var int The id to be placed in owneruserid when there is no owner.
     */
    const NO_OWNER_ID = 0;

    /**
     * @var \core_search\base[] Enabled search areas.
     */
    protected static $enabledsearchareas = null;

    /**
     * @var \core_search\base[] All system search areas.
     */
    protected static $allsearchareas = null;

    /**
     * @var \core_search\manager
     */
    protected static $instance = null;

    /**
     * @var \core_search\engine
     */
    protected $engine = null;

    /**
     * Note: This should be removed once possible (see MDL-60644).
     *
     * @var float Fake current time for use in PHPunit tests
     */
    protected static $phpunitfaketime = 0;

    /**
     * Constructor, use \core_search\manager::instance instead to get a class instance.
     *
     * @param \core_search\base The search engine to use
     */
    public function __construct($engine) {
        $this->engine = $engine;
    }

    /**
     * Returns an initialised \core_search instance.
     *
     * @see \core_search\engine::is_installed
     * @see \core_search\engine::is_server_ready
     * @throws \core_search\engine_exception
     * @return \core_search\manager
     */
    public static function instance() {
        global $CFG;

        // One per request, this should be purged during testing.
        if (static::$instance !== null) {
            return static::$instance;
        }

        if (empty($CFG->searchengine)) {
            throw new \core_search\engine_exception('enginenotselected', 'search');
        }

        if (!$engine = static::search_engine_instance()) {
            throw new \core_search\engine_exception('enginenotfound', 'search', '', $CFG->searchengine);
        }

        if (!$engine->is_installed()) {
            throw new \core_search\engine_exception('enginenotinstalled', 'search', '', $CFG->searchengine);
        }

        $serverstatus = $engine->is_server_ready();
        if ($serverstatus !== true) {
            // Skip this error in Behat when faking seach results.
            if (!defined('BEHAT_SITE_RUNNING') || !get_config('core_search', 'behat_fakeresult')) {
                // Error message with no details as this is an exception that any user may find if the server crashes.
                throw new \core_search\engine_exception('engineserverstatus', 'search');
            }
        }

        static::$instance = new \core_search\manager($engine);
        return static::$instance;
    }

    /**
     * Returns whether global search is enabled or not.
     *
     * @return bool
     */
    public static function is_global_search_enabled() {
        global $CFG;
        return !empty($CFG->enableglobalsearch);
    }

    /**
     * Returns whether indexing is enabled or not (you can enable indexing even when search is not
     * enabled at the moment, so as to have it ready for students).
     *
     * @return bool True if indexing is enabled.
     */
    public static function is_indexing_enabled() {
        global $CFG;
        return !empty($CFG->enableglobalsearch) || !empty($CFG->searchindexwhendisabled);
    }

    /**
     * Returns an instance of the search engine.
     *
     * @return \core_search\engine
     */
    public static function search_engine_instance() {
        global $CFG;

        $classname = '\\search_' . $CFG->searchengine . '\\engine';
        if (!class_exists($classname)) {
            return false;
        }

        return new $classname();
    }

    /**
     * Returns the search engine.
     *
     * @return \core_search\engine
     */
    public function get_engine() {
        return $this->engine;
    }

    /**
     * Returns a search area class name.
     *
     * @param string $areaid
     * @return string
     */
    protected static function get_area_classname($areaid) {
        list($componentname, $areaname) = static::extract_areaid_parts($areaid);
        return '\\' . $componentname . '\\search\\' . $areaname;
    }

    /**
     * Returns a new area search indexer instance.
     *
     * @param string $areaid
     * @return \core_search\base|bool False if the area is not available.
     */
    public static function get_search_area($areaid) {

        // We have them all here.
        if (!empty(static::$allsearchareas[$areaid])) {
            return static::$allsearchareas[$areaid];
        }

        $classname = static::get_area_classname($areaid);

        if (class_exists($classname) && static::is_search_area($classname)) {
            return new $classname();
        }

        return false;
    }

    /**
     * Return the list of available search areas.
     *
     * @param bool $enabled Return only the enabled ones.
     * @return \core_search\base[]
     */
    public static function get_search_areas_list($enabled = false) {

        // Two different arrays, we don't expect these arrays to be big.
        if (static::$allsearchareas !== null) {
            if (!$enabled) {
                return static::$allsearchareas;
            } else {
                return static::$enabledsearchareas;
            }
        }

        static::$allsearchareas = array();
        static::$enabledsearchareas = array();

        $plugintypes = \core_component::get_plugin_types();
        foreach ($plugintypes as $plugintype => $unused) {
            $plugins = \core_component::get_plugin_list($plugintype);
            foreach ($plugins as $pluginname => $pluginfullpath) {

                $componentname = $plugintype . '_' . $pluginname;
                $searchclasses = \core_component::get_component_classes_in_namespace($componentname, 'search');
                foreach ($searchclasses as $classname => $classpath) {
                    $areaname = substr(strrchr($classname, '\\'), 1);

                    if (!static::is_search_area($classname)) {
                        continue;
                    }

                    $areaid = static::generate_areaid($componentname, $areaname);
                    $searchclass = new $classname();

                    static::$allsearchareas[$areaid] = $searchclass;
                    if ($searchclass->is_enabled()) {
                        static::$enabledsearchareas[$areaid] = $searchclass;
                    }
                }
            }
        }

        $subsystems = \core_component::get_core_subsystems();
        foreach ($subsystems as $subsystemname => $subsystempath) {
            $componentname = 'core_' . $subsystemname;
            $searchclasses = \core_component::get_component_classes_in_namespace($componentname, 'search');

            foreach ($searchclasses as $classname => $classpath) {
                $areaname = substr(strrchr($classname, '\\'), 1);

                if (!static::is_search_area($classname)) {
                    continue;
                }

                $areaid = static::generate_areaid($componentname, $areaname);
                $searchclass = new $classname();
                static::$allsearchareas[$areaid] = $searchclass;
                if ($searchclass->is_enabled()) {
                    static::$enabledsearchareas[$areaid] = $searchclass;
                }
            }
        }

        if ($enabled) {
            return static::$enabledsearchareas;
        }
        return static::$allsearchareas;
    }

    /**
     * Clears all static caches.
     *
     * @return void
     */
    public static function clear_static() {

        static::$enabledsearchareas = null;
        static::$allsearchareas = null;
        static::$instance = null;

        base_block::clear_static();
    }

    /**
     * Generates an area id from the componentname and the area name.
     *
     * There should not be any naming conflict as the area name is the
     * class name in component/classes/search/.
     *
     * @param string $componentname
     * @param string $areaname
     * @return void
     */
    public static function generate_areaid($componentname, $areaname) {
        return $componentname . '-' . $areaname;
    }

    /**
     * Returns all areaid string components (component name and area name).
     *
     * @param string $areaid
     * @return array Component name (Frankenstyle) and area name (search area class name)
     */
    public static function extract_areaid_parts($areaid) {
        return explode('-', $areaid);
    }

    /**
     * Returns the contexts the user can access.
     *
     * The returned value is a multidimensional array because some search engines can group
     * information and there will be a performance benefit on passing only some contexts
     * instead of the whole context array set.
     *
     * @param array|false $limitcourseids An array of course ids to limit the search to. False for no limiting.
     * @return bool|array Indexed by area identifier (component + area name). Returns true if the user can see everything.
     */
    protected function get_areas_user_accesses($limitcourseids = false) {
        global $DB, $USER;

        // All results for admins. Eventually we could add a new capability for managers.
        if (is_siteadmin()) {
            return true;
        }

        $areasbylevel = array();

        // Split areas by context level so we only iterate only once through courses and cms.
        $searchareas = static::get_search_areas_list(true);
        foreach ($searchareas as $areaid => $unused) {
            $classname = static::get_area_classname($areaid);
            $searcharea = new $classname();
            foreach ($classname::get_levels() as $level) {
                $areasbylevel[$level][$areaid] = $searcharea;
            }
        }

        // This will store area - allowed contexts relations.
        $areascontexts = array();

        if (empty($limitcourseids) && !empty($areasbylevel[CONTEXT_SYSTEM])) {
            // We add system context to all search areas working at this level. Here each area is fully responsible of
            // the access control as we can not automate much, we can not even check guest access as some areas might
            // want to allow guests to retrieve data from them.

            $systemcontextid = \context_system::instance()->id;
            foreach ($areasbylevel[CONTEXT_SYSTEM] as $areaid => $searchclass) {
                $areascontexts[$areaid][$systemcontextid] = $systemcontextid;
            }
        }

        if (!empty($areasbylevel[CONTEXT_USER])) {
            if ($usercontext = \context_user::instance($USER->id, IGNORE_MISSING)) {
                // Extra checking although only logged users should reach this point, guest users have a valid context id.
                foreach ($areasbylevel[CONTEXT_USER] as $areaid => $searchclass) {
                    $areascontexts[$areaid][$usercontext->id] = $usercontext->id;
                }
            }
        }

        // Get the courses where the current user has access.
        $courses = enrol_get_my_courses(array('id', 'cacherev'), 'id', 0, [],
                (bool)get_config('core', 'searchallavailablecourses'));

        if (empty($limitcourseids) || in_array(SITEID, $limitcourseids)) {
            $courses[SITEID] = get_course(SITEID);
        }

        // Keep a list of included course context ids (needed for the block calculation below).
        $coursecontextids = [];

        foreach ($courses as $course) {
            if (!empty($limitcourseids) && !in_array($course->id, $limitcourseids)) {
                // Skip non-included courses.
                continue;
            }

            $coursecontext = \context_course::instance($course->id);
            $coursecontextids[] = $coursecontext->id;

            // Info about the course modules.
            $modinfo = get_fast_modinfo($course);

            if (!empty($areasbylevel[CONTEXT_COURSE])) {
                // Add the course contexts the user can view.
                foreach ($areasbylevel[CONTEXT_COURSE] as $areaid => $searchclass) {
                    if ($course->visible || has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                        $areascontexts[$areaid][$coursecontext->id] = $coursecontext->id;
                    }
                }
            }

            if (!empty($areasbylevel[CONTEXT_MODULE])) {
                // Add the module contexts the user can view (cm_info->uservisible).

                foreach ($areasbylevel[CONTEXT_MODULE] as $areaid => $searchclass) {

                    // Removing the plugintype 'mod_' prefix.
                    $modulename = substr($searchclass->get_component_name(), 4);

                    $modinstances = $modinfo->get_instances_of($modulename);
                    foreach ($modinstances as $modinstance) {
                        if ($modinstance->uservisible) {
                            $areascontexts[$areaid][$modinstance->context->id] = $modinstance->context->id;
                        }
                    }
                }
            }
        }

        // Add all supported block contexts, in a single query for performance.
        if (!empty($areasbylevel[CONTEXT_BLOCK])) {
            // Get list of all block types we care about.
            $blocklist = [];
            foreach ($areasbylevel[CONTEXT_BLOCK] as $areaid => $searchclass) {
                $blocklist[$searchclass->get_block_name()] = true;
            }
            list ($blocknamesql, $blocknameparams) = $DB->get_in_or_equal(array_keys($blocklist));

            // Get list of course contexts.
            list ($contextsql, $contextparams) = $DB->get_in_or_equal($coursecontextids);

            // Query all blocks that are within an included course, and are set to be visible, and
            // in a supported page type (basically just course view). This query could be
            // extended (or a second query added) to support blocks that are within a module
            // context as well, and we could add more page types if required.
            $blockrecs = $DB->get_records_sql("
                        SELECT x.*, bi.blockname AS blockname, bi.id AS blockinstanceid
                          FROM {block_instances} bi
                          JOIN {context} x ON x.instanceid = bi.id AND x.contextlevel = ?
                     LEFT JOIN {block_positions} bp ON bp.blockinstanceid = bi.id
                               AND bp.contextid = bi.parentcontextid
                               AND bp.pagetype LIKE 'course-view-%'
                               AND bp.subpage = ''
                               AND bp.visible = 0
                         WHERE bi.parentcontextid $contextsql
                               AND bi.blockname $blocknamesql
                               AND bi.subpagepattern IS NULL
                               AND (bi.pagetypepattern = 'site-index'
                                   OR bi.pagetypepattern LIKE 'course-view-%'
                                   OR bi.pagetypepattern = 'course-*'
                                   OR bi.pagetypepattern = '*')
                               AND bp.id IS NULL",
                    array_merge([CONTEXT_BLOCK], $contextparams, $blocknameparams));
            $blockcontextsbyname = [];
            foreach ($blockrecs as $blockrec) {
                if (empty($blockcontextsbyname[$blockrec->blockname])) {
                    $blockcontextsbyname[$blockrec->blockname] = [];
                }
                \context_helper::preload_from_record($blockrec);
                $blockcontextsbyname[$blockrec->blockname][] = \context_block::instance(
                        $blockrec->blockinstanceid);
            }

            // Add the block contexts the user can view.
            foreach ($areasbylevel[CONTEXT_BLOCK] as $areaid => $searchclass) {
                if (empty($blockcontextsbyname[$searchclass->get_block_name()])) {
                    continue;
                }
                foreach ($blockcontextsbyname[$searchclass->get_block_name()] as $context) {
                    if (has_capability('moodle/block:view', $context)) {
                        $areascontexts[$areaid][$context->id] = $context->id;
                    }
                }
            }
        }

        return $areascontexts;
    }

    /**
     * Returns requested page of documents plus additional information for paging.
     *
     * This function does not perform any kind of security checking for access, the caller code
     * should check that the current user have moodle/search:query capability.
     *
     * If a page is requested that is beyond the last result, the last valid page is returned in
     * results, and actualpage indicates which page was returned.
     *
     * @param stdClass $formdata
     * @param int $pagenum The 0 based page number.
     * @return object An object with 3 properties:
     *                    results    => An array of \core_search\documents for the actual page.
     *                    totalcount => Number of records that are possibly available, to base paging on.
     *                    actualpage => The actual page returned.
     */
    public function paged_search(\stdClass $formdata, $pagenum) {
        $out = new \stdClass();

        $perpage = static::DISPLAY_RESULTS_PER_PAGE;

        // Make sure we only allow request up to max page.
        $pagenum = min($pagenum, (static::MAX_RESULTS / $perpage) - 1);

        // Calculate the first and last document number for the current page, 1 based.
        $mindoc = ($pagenum * $perpage) + 1;
        $maxdoc = ($pagenum + 1) * $perpage;

        // Get engine documents, up to max.
        $docs = $this->search($formdata, $maxdoc);

        $resultcount = count($docs);
        if ($resultcount < $maxdoc) {
            // This means it couldn't give us results to max, so the count must be the max.
            $out->totalcount = $resultcount;
        } else {
            // Get the possible count reported by engine, and limit to our max.
            $out->totalcount = $this->engine->get_query_total_count();
            $out->totalcount = min($out->totalcount, static::MAX_RESULTS);
        }

        // Determine the actual page.
        if ($resultcount < $mindoc) {
            // We couldn't get the min docs for this page, so determine what page we can get.
            $out->actualpage = floor(($resultcount - 1) / $perpage);
        } else {
            $out->actualpage = $pagenum;
        }

        // Split the results to only return the page.
        $out->results = array_slice($docs, $out->actualpage * $perpage, $perpage, true);

        return $out;
    }

    /**
     * Returns documents from the engine based on the data provided.
     *
     * This function does not perform any kind of security checking, the caller code
     * should check that the current user have moodle/search:query capability.
     *
     * It might return the results from the cache instead.
     *
     * @param stdClass $formdata
     * @param int      $limit The maximum number of documents to return
     * @return \core_search\document[]
     */
    public function search(\stdClass $formdata, $limit = 0) {
        // For Behat testing, the search results can be faked using a special step.
        if (defined('BEHAT_SITE_RUNNING')) {
            $fakeresult = get_config('core_search', 'behat_fakeresult');
            if ($fakeresult) {
                // Clear config setting.
                unset_config('core_search', 'behat_fakeresult');

                // Check query matches expected value.
                $details = json_decode($fakeresult);
                if ($formdata->q !== $details->query) {
                    throw new \coding_exception('Unexpected search query: ' . $formdata->q);
                }

                // Create search documents from the JSON data.
                $docs = [];
                foreach ($details->results as $result) {
                    $doc = new \core_search\document($result->itemid, $result->componentname,
                            $result->areaname);
                    foreach ((array)$result->fields as $field => $value) {
                        $doc->set($field, $value);
                    }
                    foreach ((array)$result->extrafields as $field => $value) {
                        $doc->set_extra($field, $value);
                    }
                    $area = $this->get_search_area($doc->get('areaid'));
                    $doc->set_doc_url($area->get_doc_url($doc));
                    $doc->set_context_url($area->get_context_url($doc));
                    $docs[] = $doc;
                }

                return $docs;
            }
        }

        $limitcourseids = false;
        if (!empty($formdata->courseids)) {
            $limitcourseids = $formdata->courseids;
        }

        // Clears previous query errors.
        $this->engine->clear_query_error();

        $areascontexts = $this->get_areas_user_accesses($limitcourseids);
        if (!$areascontexts) {
            // User can not access any context.
            $docs = array();
        } else {
            $docs = $this->engine->execute_query($formdata, $areascontexts, $limit);
        }

        return $docs;
    }

    /**
     * Merge separate index segments into one.
     */
    public function optimize_index() {
        $this->engine->optimize();
    }

    /**
     * Index all documents.
     *
     * @param bool $fullindex Whether we should reindex everything or not.
     * @param float $timelimit Time limit in seconds (0 = no time limit)
     * @param \progress_trace|null $progress Optional class for tracking progress
     * @throws \moodle_exception
     * @return bool Whether there was any updated document or not.
     */
    public function index($fullindex = false, $timelimit = 0, \progress_trace $progress = null) {
        global $DB;

        // Cannot combine time limit with reindex.
        if ($timelimit && $fullindex) {
            throw new \coding_exception('Cannot apply time limit when reindexing');
        }
        if (!$progress) {
            $progress = new \null_progress_trace();
        }

        // Unlimited time.
        \core_php_time_limit::raise();

        // Notify the engine that an index starting.
        $this->engine->index_starting($fullindex);

        $sumdocs = 0;

        $searchareas = $this->get_search_areas_list(true);

        if ($timelimit) {
            // If time is limited (and therefore we're not just indexing everything anyway), select
            // an order for search areas. The intention here is to avoid a situation where a new
            // large search area is enabled, and this means all our other search areas go out of
            // date while that one is being indexed. To do this, we order by the time we spent
            // indexing them last time we ran, meaning anything that took a very long time will be
            // done last.
            uasort($searchareas, function(\core_search\base $area1, \core_search\base $area2) {
                return (int)$area1->get_last_indexing_duration() - (int)$area2->get_last_indexing_duration();
            });

            // Decide time to stop.
            $stopat = self::get_current_time() + $timelimit;
        }

        foreach ($searchareas as $areaid => $searcharea) {

            $progress->output('Processing area: ' . $searcharea->get_visible_name());

            // Notify the engine that an area is starting.
            $this->engine->area_index_starting($searcharea, $fullindex);

            $indexingstart = (int)self::get_current_time();
            $elapsed = self::get_current_time();

            // This is used to store this component config.
            list($componentconfigname, $varname) = $searcharea->get_config_var_name();

            $prevtimestart = intval(get_config($componentconfigname, $varname . '_indexingstart'));

            if ($fullindex === true) {
                $referencestarttime = 0;

                // For full index, we delete any queued context index requests, as those will
                // obviously be met by the full index.
                $DB->delete_records('search_index_requests');
            } else {
                $partial = get_config($componentconfigname, $varname . '_partial');
                if ($partial) {
                    // When the previous index did not complete all data, we start from the time of the
                    // last document that was successfully indexed. (Note this will result in
                    // re-indexing that one document, but we can't avoid that because there may be
                    // other documents in the same second.)
                    $referencestarttime = intval(get_config($componentconfigname, $varname . '_lastindexrun'));
                } else {
                    $referencestarttime = $prevtimestart;
                }
            }

            // Getting the recordset from the area.
            $recordset = $searcharea->get_recordset_by_timestamp($referencestarttime);

            // Pass get_document as callback.
            $fileindexing = $this->engine->file_indexing_enabled() && $searcharea->uses_file_indexing();
            $options = array('indexfiles' => $fileindexing, 'lastindexedtime' => $prevtimestart);
            if ($timelimit) {
                $options['stopat'] = $stopat;
            }
            $iterator = new skip_future_documents_iterator(new \core\dml\recordset_walk(
                    $recordset, array($searcharea, 'get_document'), $options));
            $result = $this->engine->add_documents($iterator, $searcharea, $options);
            $recordset->close();
            if (count($result) === 5) {
                list($numrecords, $numdocs, $numdocsignored, $lastindexeddoc, $partial) = $result;
            } else {
                // Backward compatibility for engines that don't support partial adding.
                list($numrecords, $numdocs, $numdocsignored, $lastindexeddoc) = $result;
                debugging('engine::add_documents() should return $partial (4-value return is deprecated)',
                        DEBUG_DEVELOPER);
                $partial = false;
            }

            if ($numdocs > 0) {
                $elapsed = round((self::get_current_time() - $elapsed), 3);
                $progress->output('Processed ' . $numrecords . ' records containing ' . $numdocs .
                        ' documents, in ' . $elapsed . ' seconds' .
                        ($partial ? ' (not complete)' : '') . '.', 1);
            } else {
                $progress->output('No new documents to index.', 1);
            }

            // Notify the engine this area is complete, and only mark times if true.
            if ($this->engine->area_index_complete($searcharea, $numdocs, $fullindex)) {
                $sumdocs += $numdocs;

                // Store last index run once documents have been committed to the search engine.
                set_config($varname . '_indexingstart', $indexingstart, $componentconfigname);
                set_config($varname . '_indexingend', (int)self::get_current_time(), $componentconfigname);
                set_config($varname . '_docsignored', $numdocsignored, $componentconfigname);
                set_config($varname . '_docsprocessed', $numdocs, $componentconfigname);
                set_config($varname . '_recordsprocessed', $numrecords, $componentconfigname);
                if ($lastindexeddoc > 0) {
                    set_config($varname . '_lastindexrun', $lastindexeddoc, $componentconfigname);
                }
                if ($partial) {
                    set_config($varname . '_partial', 1, $componentconfigname);
                } else {
                    unset_config($varname . '_partial', $componentconfigname);
                }
            } else {
                $progress->output('Engine reported error.');
            }

            if ($timelimit && (self::get_current_time() >= $stopat)) {
                $progress->output('Stopping indexing due to time limit.');
                break;
            }
        }

        if ($sumdocs > 0) {
            $event = \core\event\search_indexed::create(
                    array('context' => \context_system::instance()));
            $event->trigger();
        }

        $this->engine->index_complete($sumdocs, $fullindex);

        return (bool)$sumdocs;
    }

    /**
     * Indexes or reindexes a specific context of the system, e.g. one course.
     *
     * The function returns an object with field 'complete' (true or false).
     *
     * This function supports partial indexing via the time limit parameter. If the time limit
     * expires, it will return values for $startfromarea and $startfromtime which can be passed
     * next time to continue indexing.
     *
     * @param \context $context Context to restrict index.
     * @param string $singleareaid If specified, indexes only the given area.
     * @param float $timelimit Time limit in seconds (0 = no time limit)
     * @param \progress_trace|null $progress Optional class for tracking progress
     * @param string $startfromarea Area to start from
     * @param int $startfromtime Timestamp to start from
     * @return \stdClass Object indicating success
     */
    public function index_context($context, $singleareaid = '', $timelimit = 0,
            \progress_trace $progress = null, $startfromarea = '', $startfromtime = 0) {
        if (!$progress) {
            $progress = new \null_progress_trace();
        }

        // Work out time to stop, if limited.
        if ($timelimit) {
            // Decide time to stop.
            $stopat = self::get_current_time() + $timelimit;
        }

        // No PHP time limit.
        \core_php_time_limit::raise();

        // Notify the engine that an index starting.
        $this->engine->index_starting(false);

        $sumdocs = 0;

        // Get all search areas, in consistent order.
        $searchareas = $this->get_search_areas_list(true);
        ksort($searchareas);

        // Are we skipping past some that were handled previously?
        $skipping = $startfromarea ? true : false;

        foreach ($searchareas as $areaid => $searcharea) {
            // If we're only processing one area id, skip all the others.
            if ($singleareaid && $singleareaid !== $areaid) {
                continue;
            }

            // If we're skipping to a later area, continue through the loop.
            $referencestarttime = 0;
            if ($skipping) {
                if ($areaid !== $startfromarea) {
                    continue;
                }
                // Stop skipping and note the reference start time.
                $skipping = false;
                $referencestarttime = $startfromtime;
            }

            $progress->output('Processing area: ' . $searcharea->get_visible_name());

            $elapsed = self::get_current_time();

            // Get the recordset of all documents from the area for this context.
            $recordset = $searcharea->get_document_recordset($referencestarttime, $context);
            if (!$recordset) {
                if ($recordset === null) {
                    $progress->output('Skipping (not relevant to context).', 1);
                } else {
                    $progress->output('Skipping (does not support context indexing).', 1);
                }
                continue;
            }

            // Notify the engine that an area is starting.
            $this->engine->area_index_starting($searcharea, false);

            // Work out search options.
            $options = [];
            $options['indexfiles'] = $this->engine->file_indexing_enabled() &&
                    $searcharea->uses_file_indexing();
            if ($timelimit) {
                $options['stopat'] = $stopat;
            }

            // Construct iterator which will use get_document on the recordset results.
            $iterator = new \core\dml\recordset_walk($recordset,
                    array($searcharea, 'get_document'), $options);

            // Use this iterator to add documents.
            $result = $this->engine->add_documents($iterator, $searcharea, $options);
            if (count($result) === 5) {
                list($numrecords, $numdocs, $numdocsignored, $lastindexeddoc, $partial) = $result;
            } else {
                // Backward compatibility for engines that don't support partial adding.
                list($numrecords, $numdocs, $numdocsignored, $lastindexeddoc) = $result;
                debugging('engine::add_documents() should return $partial (4-value return is deprecated)',
                        DEBUG_DEVELOPER);
                $partial = false;
            }

            if ($numdocs > 0) {
                $elapsed = round((self::get_current_time() - $elapsed), 3);
                $progress->output('Processed ' . $numrecords . ' records containing ' . $numdocs .
                        ' documents, in ' . $elapsed . ' seconds' .
                        ($partial ? ' (not complete)' : '') . '.', 1);
            } else {
                $progress->output('No documents to index.', 1);
            }

            // Notify the engine this area is complete, but don't store any times as this is not
            // part of the 'normal' search index.
            if (!$this->engine->area_index_complete($searcharea, $numdocs, false)) {
                $progress->output('Engine reported error.', 1);
            }

            if ($partial && $timelimit && (self::get_current_time() >= $stopat)) {
                $progress->output('Stopping indexing due to time limit.');
                break;
            }
        }

        if ($sumdocs > 0) {
            $event = \core\event\search_indexed::create(
                    array('context' => $context));
            $event->trigger();
        }

        $this->engine->index_complete($sumdocs, false);

        // Indicate in result whether we completed indexing, or only part of it.
        $result = new \stdClass();
        if ($partial) {
            $result->complete = false;
            $result->startfromarea = $areaid;
            $result->startfromtime = $lastindexeddoc;
        } else {
            $result->complete = true;
        }
        return $result;
    }

    /**
     * Resets areas config.
     *
     * @throws \moodle_exception
     * @param string $areaid
     * @return void
     */
    public function reset_config($areaid = false) {

        if (!empty($areaid)) {
            $searchareas = array();
            if (!$searchareas[$areaid] = static::get_search_area($areaid)) {
                throw new \moodle_exception('errorareanotavailable', 'search', '', $areaid);
            }
        } else {
            // Only the enabled ones.
            $searchareas = static::get_search_areas_list(true);
        }

        foreach ($searchareas as $searcharea) {
            list($componentname, $varname) = $searcharea->get_config_var_name();
            $config = $searcharea->get_config();

            foreach ($config as $key => $value) {
                // We reset them all but the enable/disabled one.
                if ($key !== $varname . '_enabled') {
                    set_config($key, 0, $componentname);
                }
            }
        }
    }

    /**
     * Deletes an area's documents or all areas documents.
     *
     * @param string $areaid The area id or false for all
     * @return void
     */
    public function delete_index($areaid = false) {
        if (!empty($areaid)) {
            $this->engine->delete($areaid);
            $this->reset_config($areaid);
        } else {
            $this->engine->delete();
            $this->reset_config();
        }
    }

    /**
     * Deletes index by id.
     *
     * @param int Solr Document string $id
     */
    public function delete_index_by_id($id) {
        $this->engine->delete_by_id($id);
    }

    /**
     * Returns search areas configuration.
     *
     * @param \core_search\base[] $searchareas
     * @return \stdClass[] $configsettings
     */
    public function get_areas_config($searchareas) {

        $vars = array('indexingstart', 'indexingend', 'lastindexrun', 'docsignored',
                'docsprocessed', 'recordsprocessed', 'partial');

        $configsettings = [];
        foreach ($searchareas as $searcharea) {

            $areaid = $searcharea->get_area_id();

            $configsettings[$areaid] = new \stdClass();
            list($componentname, $varname) = $searcharea->get_config_var_name();

            if (!$searcharea->is_enabled()) {
                // We delete all indexed data on disable so no info.
                foreach ($vars as $var) {
                    $configsettings[$areaid]->{$var} = 0;
                }
            } else {
                foreach ($vars as $var) {
                    $configsettings[$areaid]->{$var} = get_config($componentname, $varname .'_' . $var);
                }
            }

            // Formatting the time.
            if (!empty($configsettings[$areaid]->lastindexrun)) {
                $configsettings[$areaid]->lastindexrun = userdate($configsettings[$areaid]->lastindexrun);
            } else {
                $configsettings[$areaid]->lastindexrun = get_string('never');
            }
        }
        return $configsettings;
    }

    /**
     * Triggers search_results_viewed event
     *
     * Other data required:
     * - q: The query string
     * - page: The page number
     * - title: Title filter
     * - areaids: Search areas filter
     * - courseids: Courses filter
     * - timestart: Time start filter
     * - timeend: Time end filter
     *
     * @since Moodle 3.2
     * @param array $other Other info for the event.
     * @return \core\event\search_results_viewed
     */
    public static function trigger_search_results_viewed($other) {
        $event = \core\event\search_results_viewed::create([
            'context' => \context_system::instance(),
            'other' => $other
        ]);
        $event->trigger();

        return $event;
    }

    /**
     * Checks whether a classname is of an actual search area.
     *
     * @param string $classname
     * @return bool
     */
    protected static function is_search_area($classname) {
        if (is_subclass_of($classname, 'core_search\base')) {
            return (new \ReflectionClass($classname))->isInstantiable();
        }

        return false;
    }

    /**
     * Requests that a specific context is indexed by the scheduled task. The context will be
     * added to a queue which is processed by the task.
     *
     * This is used after a restore to ensure that restored items are indexed, even though their
     * modified time will be older than the latest indexed.
     *
     * @param \context $context Context to index within
     * @param string $areaid Area to index, '' = all areas
     */
    public static function request_index(\context $context, $areaid = '') {
        global $DB;

        // Check through existing requests for this context or any parent context.
        list ($contextsql, $contextparams) = $DB->get_in_or_equal(
                $context->get_parent_context_ids(true));
        $existing = $DB->get_records_select('search_index_requests',
                'contextid ' . $contextsql, $contextparams, '', 'id, searcharea, partialarea');
        foreach ($existing as $rec) {
            // If we haven't started processing the existing request yet, and it covers the same
            // area (or all areas) then that will be sufficient so don't add anything else.
            if ($rec->partialarea === '' && ($rec->searcharea === $areaid || $rec->searcharea === '')) {
                return;
            }
        }

        // No suitable existing request, so add a new one.
        $newrecord = [ 'contextid' => $context->id, 'searcharea' => $areaid,
                'timerequested' => time(), 'partialarea' => '', 'partialtime' => 0 ];
        $DB->insert_record('search_index_requests', $newrecord);
    }

    /**
     * Processes outstanding index requests. This will take the first item from the queue and
     * process it, continuing until an optional time limit is reached.
     *
     * If there are no index requests, the function will do nothing.
     *
     * @param float $timelimit Time limit (0 = none)
     * @param \progress_trace|null $progress Optional progress indicator
     */
    public function process_index_requests($timelimit = 0.0, \progress_trace $progress = null) {
        global $DB;

        if (!$progress) {
            $progress = new \null_progress_trace();
        }

        $complete = false;
        $before = self::get_current_time();
        if ($timelimit) {
            $stopat = $before + $timelimit;
        }
        while (true) {
            // Retrieve first request, using fully defined ordering.
            $requests = $DB->get_records('search_index_requests', null,
                    'timerequested, contextid, searcharea',
                    'id, contextid, searcharea, partialarea, partialtime', 0, 1);
            if (!$requests) {
                // If there are no more requests, stop.
                $complete = true;
                break;
            }
            $request = reset($requests);

            // Calculate remaining time.
            $remainingtime = 0;
            $beforeindex = self::get_current_time();
            if ($timelimit) {
                $remainingtime = $stopat - $beforeindex;
            }

            // Show a message before each request, indicating what will be indexed.
            $context = \context::instance_by_id($request->contextid, IGNORE_MISSING);
            if (!$context) {
                $DB->delete_records('search_index_requests', ['id' => $request->id]);
                $progress->output('Skipped deleted context: ' . $request->contextid);
                continue;
            }
            $contextname = $context->get_context_name();
            if ($request->searcharea) {
                $contextname .= ' (search area: ' . $request->searcharea . ')';
            }
            $progress->output('Indexing requested context: ' . $contextname);

            // Actually index the context.
            $result = $this->index_context($context, $request->searcharea, $remainingtime,
                    $progress, $request->partialarea, $request->partialtime);

            // Work out shared part of message.
            $endmessage = $contextname . ' (' . round(self::get_current_time() - $beforeindex, 1) . 's)';

            // Update database table and continue/stop as appropriate.
            if ($result->complete) {
                // If we completed the request, remove it from the table.
                $DB->delete_records('search_index_requests', ['id' => $request->id]);
                $progress->output('Completed requested context: ' . $endmessage);
            } else {
                // If we didn't complete the request, store the partial details (how far it got).
                $DB->update_record('search_index_requests', ['id' => $request->id,
                        'partialarea' => $result->startfromarea,
                        'partialtime' => $result->startfromtime]);
                $progress->output('Ending requested context: ' . $endmessage);

                // The time limit must have expired, so stop looping.
                break;
            }
        }
    }

    /**
     * Gets current time for use in search system.
     *
     * Note: This should be replaced with generic core functionality once possible (see MDL-60644).
     *
     * @return float Current time in seconds (with decimals)
     */
    public static function get_current_time() {
        if (PHPUNIT_TEST && self::$phpunitfaketime) {
            return self::$phpunitfaketime;
        }
        return microtime(true);
    }
}
