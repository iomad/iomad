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
 * Bulk activity completion manager class
 *
 * @package     core_completion
 * @category    completion
 * @copyright   2017 Adrian Greeve
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_completion;

use stdClass;
use context_course;
use cm_info;
use tabobject;
use lang_string;
use moodle_url;
defined('MOODLE_INTERNAL') || die;

/**
 * Bulk activity completion manager class
 *
 * @package     core_completion
 * @category    completion
 * @copyright   2017 Adrian Greeve
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /**
     * @var int $courseid the course id.
     */
    protected $courseid;

    /**
     * manager constructor.
     * @param int $courseid the course id.
     */
    public function __construct($courseid) {
        $this->courseid = $courseid;
    }

    /**
     * Gets the data (context) to be used with the bulkactivitycompletion template.
     *
     * @return stdClass data for use with the bulkactivitycompletion template.
     */
    public function get_activities_and_headings() {
        global $OUTPUT;
        $moduleinfo = get_fast_modinfo($this->courseid);
        $sections = $moduleinfo->get_sections();
        $data = new stdClass;
        $data->courseid = $this->courseid;
        $data->sesskey = sesskey();
        $data->helpicon = $OUTPUT->help_icon('bulkcompletiontracking', 'core_completion');
        $data->sections = [];
        foreach ($sections as $sectionnumber => $section) {
            $sectioninfo = $moduleinfo->get_section_info($sectionnumber);

            $sectionobject = new stdClass();
            $sectionobject->sectionnumber = $sectionnumber;
            $sectionobject->name = get_section_name($this->courseid, $sectioninfo);
            $sectionobject->activities = $this->get_activities($section, true);
            $data->sections[] = $sectionobject;
        }
        return $data;
    }

    /**
     * Gets the data (context) to be used with the activityinstance template
     *
     * @param array $cmids list of course module ids
     * @param bool $withcompletiondetails include completion details
     * @return array
     */
    public function get_activities($cmids, $withcompletiondetails = false) {
        $moduleinfo = get_fast_modinfo($this->courseid);
        $activities = [];
        foreach ($cmids as $cmid) {
            $mod = $moduleinfo->get_cm($cmid);
            if (!$mod->uservisible) {
                continue;
            }
            $moduleobject = new stdClass();
            $moduleobject->cmid = $cmid;
            $moduleobject->modname = $mod->get_formatted_name();
            $moduleobject->icon = $mod->get_icon_url()->out();
            $moduleobject->url = $mod->url;
            $moduleobject->canmanage = $withcompletiondetails && self::can_edit_bulk_completion($this->courseid, $mod);

            // Get activity completion information.
            if ($moduleobject->canmanage) {
                $moduleobject->completionstatus = $this->get_completion_detail($mod);
            } else {
                $moduleobject->completionstatus = ['icon' => null, 'string' => null];
            }
            if (self::can_edit_bulk_completion($this->courseid, $mod)) {
                $activities[] = $moduleobject;
            }
        }
        return $activities;
    }


    /**
     * Get completion information on the selected module or module type
     *
     * @param cm_info|stdClass $mod either instance of cm_info (with 'customcompletionrules' in customdata) or
     *      object with fields ->completion, ->completionview, ->completionexpected, ->completionusegrade
     *      and ->customdata['customcompletionrules']
     * @return array
     */
    private function get_completion_detail($mod) {
        global $OUTPUT;
        $strings = [];
        switch ($mod->completion) {
            case COMPLETION_TRACKING_NONE:
                $strings['string'] = get_string('none');
                break;

            case COMPLETION_TRACKING_MANUAL:
                $strings['string'] = get_string('manual', 'completion');
                $strings['icon'] = $OUTPUT->pix_icon('i/completion-manual-y', get_string('completion_manual', 'completion'));
                break;

            case COMPLETION_TRACKING_AUTOMATIC:
                $strings['string'] = get_string('withconditions', 'completion');
                $strings['icon'] = $OUTPUT->pix_icon('i/completion-auto-y', get_string('completion_automatic', 'completion'));
                break;

            default:
                $strings['string'] = get_string('none');
                break;
        }

        // Get the descriptions for all the active completion rules for the module.
        if ($ruledescriptions = $this->get_completion_active_rule_descriptions($mod)) {
            foreach ($ruledescriptions as $ruledescription) {
                $strings['string'] .= \html_writer::empty_tag('br') . $ruledescription;
            }
        }
        return $strings;
    }

    /**
     * Get the descriptions for all active conditional completion rules for the current module.
     *
     * @param cm_info|stdClass $moduledata either instance of cm_info (with 'customcompletionrules' in customdata) or
     *      object with fields ->completion, ->completionview, ->completionexpected, ->completionusegrade
     *      and ->customdata['customcompletionrules']
     * @return array $activeruledescriptions an array of strings describing the active completion rules.
     */
    protected function get_completion_active_rule_descriptions($moduledata) {
        $activeruledescriptions = [];

        if ($moduledata->completion == COMPLETION_TRACKING_AUTOMATIC) {
            // Generate the description strings for the core conditional completion rules (if set).
            if (!empty($moduledata->completionview)) {
                $activeruledescriptions[] = get_string('completionview_desc', 'completion');
            }
            if ($moduledata instanceof cm_info && !is_null($moduledata->completiongradeitemnumber) ||
                ($moduledata instanceof stdClass && !empty($moduledata->completionusegrade))) {
                $activeruledescriptions[] = get_string('completionusegrade_desc', 'completion');
            }

            // Now, ask the module to provide descriptions for its custom conditional completion rules.
            if ($customruledescriptions = component_callback($moduledata->modname,
                'get_completion_active_rule_descriptions', [$moduledata])) {
                $activeruledescriptions = array_merge($activeruledescriptions, $customruledescriptions);
            }
        }

        if ($moduledata->completion != COMPLETION_TRACKING_NONE) {
            if (!empty($moduledata->completionexpected)) {
                $activeruledescriptions[] = get_string('completionexpecteddesc', 'completion',
                    userdate($moduledata->completionexpected));
            }
        }

        return $activeruledescriptions;
    }

    /**
     * Gets the course modules for the current course.
     *
     * @return stdClass $data containing the modules
     */
    public function get_activities_and_resources() {
        global $DB, $OUTPUT, $CFG;
        require_once($CFG->dirroot.'/course/lib.php');

        // Get enabled activities and resources.
        $modules = $DB->get_records('modules', ['visible' => 1], 'name ASC');
        $data = new stdClass();
        $data->courseid = $this->courseid;
        $data->sesskey = sesskey();
        $data->helpicon = $OUTPUT->help_icon('bulkcompletiontracking', 'core_completion');
        // Add icon information.
        $data->modules = array_values($modules);
        $coursecontext = context_course::instance($this->courseid);
        $canmanage = has_capability('moodle/course:manageactivities', $coursecontext);
        $course = get_course($this->courseid);
        foreach ($data->modules as $module) {
            $module->icon = $OUTPUT->image_url('icon', $module->name)->out();
            $module->formattedname = format_string(get_string('modulenameplural', 'mod_' . $module->name),
                true, ['context' => $coursecontext]);
            $module->canmanage = $canmanage && course_allowed_module($course, $module->name);
            $defaults = self::get_default_completion($course, $module, false);
            $defaults->modname = $module->name;
            $module->completionstatus = $this->get_completion_detail($defaults);
        }

        return $data;
    }

    /**
     * Checks if current user can edit activity completion
     *
     * @param int|stdClass $courseorid
     * @param \cm_info|null $cm if specified capability for a given coursemodule will be check,
     *     if not specified capability to edit at least one activity is checked.
     */
    public static function can_edit_bulk_completion($courseorid, $cm = null) {
        if ($cm) {
            return $cm->uservisible && has_capability('moodle/course:manageactivities', $cm->context);
        }
        $coursecontext = context_course::instance(is_object($courseorid) ? $courseorid->id : $courseorid);
        if (has_capability('moodle/course:manageactivities', $coursecontext)) {
            return true;
        }
        $modinfo = get_fast_modinfo($courseorid);
        foreach ($modinfo->cms as $mod) {
            if ($mod->uservisible && has_capability('moodle/course:manageactivities', $mod->context)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets the available completion tabs for the current course and user.
     *
     * @param stdClass|int $courseorid the course object or id.
     * @return tabobject[]
     */
    public static function get_available_completion_tabs($courseorid) {
        $tabs = [];

        $courseid = is_object($courseorid) ? $courseorid->id : $courseorid;
        $coursecontext = context_course::instance($courseid);

        if (has_capability('moodle/course:update', $coursecontext)) {
            $tabs[] = new tabobject(
                'completion',
                new moodle_url('/course/completion.php', ['id' => $courseid]),
                new lang_string('coursecompletion', 'completion')
            );
        }

        if (has_capability('moodle/course:manageactivities', $coursecontext)) {
            $tabs[] = new tabobject(
                'defaultcompletion',
                new moodle_url('/course/defaultcompletion.php', ['id' => $courseid]),
                new lang_string('defaultcompletion', 'completion')
            );
        }

        if (self::can_edit_bulk_completion($courseorid)) {
            $tabs[] = new tabobject(
                'bulkcompletion',
                new moodle_url('/course/bulkcompletion.php', ['id' => $courseid]),
                new lang_string('bulkactivitycompletion', 'completion')
            );
        }

        return $tabs;
    }

    /**
     * Applies completion from the bulk edit form to all selected modules
     *
     * @param stdClass $data data received from the core_completion_bulkedit_form
     * @param bool $updateinstances if we need to update the instance tables of the module (i.e. 'assign', 'forum', etc.) -
     *      if no module-specific completion rules were added to the form, update of the module table is not needed.
     */
    public function apply_completion($data, $updateinstances) {
        $updated = false;
        $needreset = [];
        $modinfo = get_fast_modinfo($this->courseid);

        $cmids = $data->cmid;

        $data = (array)$data;
        unset($data['id']); // This is a course id, we don't want to confuse it with cmid or instance id.
        unset($data['cmid']);
        unset($data['submitbutton']);

        foreach ($cmids as $cmid) {
            $cm = $modinfo->get_cm($cmid);
            if (self::can_edit_bulk_completion($this->courseid, $cm) && $this->apply_completion_cm($cm, $data, $updateinstances)) {
                $updated = true;
                if ($cm->completion != COMPLETION_TRACKING_MANUAL || $data['completion'] != COMPLETION_TRACKING_MANUAL) {
                    // If completion was changed we will need to reset it's state. Exception is when completion was and remains as manual.
                    $needreset[] = $cm->id;
                }
            }
            // Update completion calendar events.
            $completionexpected = ($data['completionexpected']) ? $data['completionexpected'] : null;
            \core_completion\api::update_completion_date_event($cm->id, $cm->modname, $cm->instance, $completionexpected);
        }
        if ($updated) {
            // Now that modules are fully updated, also update completion data if required.
            // This will wipe all user completion data and recalculate it.
            rebuild_course_cache($this->courseid, true);
            $modinfo = get_fast_modinfo($this->courseid);
            $completion = new \completion_info($modinfo->get_course());
            foreach ($needreset as $cmid) {
                $completion->reset_all_state($modinfo->get_cm($cmid));
            }

            // And notify the user of the result.
            \core\notification::add(get_string('activitycompletionupdated', 'core_completion'), \core\notification::SUCCESS);
        }
    }

    /**
     * Applies new completion rules to one course module
     *
     * @param \cm_info $cm
     * @param array $data
     * @param bool $updateinstance if we need to update the instance table of the module (i.e. 'assign', 'forum', etc.) -
     *      if no module-specific completion rules were added to the form, update of the module table is not needed.
     * @return bool if module was updated
     */
    protected function apply_completion_cm(\cm_info $cm, $data, $updateinstance) {
        global $DB;

        $defaults = ['completion' => COMPLETION_DISABLED, 'completionview' => COMPLETION_VIEW_NOT_REQUIRED,
            'completionexpected' => 0, 'completiongradeitemnumber' => null];

        $data += ['completion' => $cm->completion,
            'completionexpected' => $cm->completionexpected,
            'completionview' => $cm->completionview];

        if ($cm->completion == $data['completion'] && $cm->completion == COMPLETION_TRACKING_NONE) {
            // If old and new completion are both "none" - no changes are needed.
            return false;
        }

        if ($cm->completion == $data['completion'] && $cm->completion == COMPLETION_TRACKING_NONE &&
                $cm->completionexpected == $data['completionexpected']) {
            // If old and new completion are both "manual" and completion expected date is not changed - no changes are needed.
            return false;
        }

        if (array_key_exists('completionusegrade', $data)) {
            // Convert the 'use grade' checkbox into a grade-item number: 0 if checked, null if not.
            $data['completiongradeitemnumber'] = !empty($data['completionusegrade']) ? 0 : null;
            unset($data['completionusegrade']);
        } else {
            $data['completiongradeitemnumber'] = $cm->completiongradeitemnumber;
        }

        // Update module instance table.
        if ($updateinstance) {
            $moddata = ['id' => $cm->instance, 'timemodified' => time()] + array_diff_key($data, $defaults);
            $DB->update_record($cm->modname, $moddata);
        }

        // Update course modules table.
        $cmdata = ['id' => $cm->id, 'timemodified' => time()] + array_intersect_key($data, $defaults);
        $DB->update_record('course_modules', $cmdata);

        \core\event\course_module_updated::create_from_cm($cm, $cm->context)->trigger();

        // We need to reset completion data for this activity.
        return true;
    }


    /**
     * Saves default completion from edit form to all selected module types
     *
     * @param stdClass $data data received from the core_completion_bulkedit_form
     * @param bool $updatecustomrules if we need to update the custom rules of the module -
     *      if no module-specific completion rules were added to the form, update of the module table is not needed.
     */
    public function apply_default_completion($data, $updatecustomrules) {
        global $DB;

        $courseid = $data->id;
        $coursecontext = context_course::instance($courseid);
        if (!$modids = $data->modids) {
            return;
        }
        $defaults = [
            'completion' => COMPLETION_DISABLED,
            'completionview' => COMPLETION_VIEW_NOT_REQUIRED,
            'completionexpected' => 0,
            'completionusegrade' => 0
        ];

        $data = (array)$data;

        if ($updatecustomrules) {
            $customdata = array_diff_key($data, $defaults);
            $data['customrules'] = $customdata ? json_encode($customdata) : null;
            $defaults['customrules'] = null;
        }
        $data = array_intersect_key($data, $defaults);

        // Get names of the affected modules.
        list($modidssql, $params) = $DB->get_in_or_equal($modids);
        $params[] = 1;
        $modules = $DB->get_records_select_menu('modules', 'id ' . $modidssql . ' and visible = ?', $params, '', 'id, name');

        // Get an associative array of [module_id => course_completion_defaults_id].
        list($in, $params) = $DB->get_in_or_equal($modids);
        $params[] = $courseid;
        $defaultsids = $DB->get_records_select_menu('course_completion_defaults', 'module ' . $in . ' and course = ?', $params, '',
                                                      'module, id');

        foreach ($modids as $modid) {
            if (!array_key_exists($modid, $modules)) {
                continue;
            }
            if (isset($defaultsids[$modid])) {
                $DB->update_record('course_completion_defaults', $data + ['id' => $defaultsids[$modid]]);
            } else {
                $defaultsids[$modid] = $DB->insert_record('course_completion_defaults', $data + ['course' => $courseid,
                                                                                                 'module' => $modid]);
            }
            // Trigger event.
            \core\event\completion_defaults_updated::create([
                'objectid' => $defaultsids[$modid],
                'context' => $coursecontext,
                'other' => ['modulename' => $modules[$modid]],
            ])->trigger();
        }

        // Add notification.
        \core\notification::add(get_string('defaultcompletionupdated', 'completion'), \core\notification::SUCCESS);
    }

    /**
     * Returns default completion rules for given module type in the given course
     *
     * @param stdClass $course
     * @param stdClass $module
     * @param bool $flatten if true all module custom completion rules become properties of the same object,
     *   otherwise they can be found as array in ->customdata['customcompletionrules']
     * @return stdClass
     */
    public static function get_default_completion($course, $module, $flatten = true) {
        global $DB, $CFG;
        if ($data = $DB->get_record('course_completion_defaults', ['course' => $course->id, 'module' => $module->id],
            'completion, completionview, completionexpected, completionusegrade, customrules')) {
            if ($data->customrules && ($customrules = @json_decode($data->customrules, true))) {
                if ($flatten) {
                    foreach ($customrules as $key => $value) {
                        $data->$key = $value;
                    }
                } else {
                    $data->customdata['customcompletionrules'] = $customrules;
                }
            }
            unset($data->customrules);
        } else {
            $data = new stdClass();
            $data->completion = COMPLETION_TRACKING_NONE;
            if ($CFG->completiondefault) {
                $completion = new \completion_info(get_fast_modinfo($course->id)->get_course());
                if ($completion->is_enabled() && plugin_supports('mod', $module->name, FEATURE_MODEDIT_DEFAULT_COMPLETION, true)) {
                    $data->completion = COMPLETION_TRACKING_MANUAL;
                    $data->completionview = 1;
                }
            }
        }
        return $data;
    }
}