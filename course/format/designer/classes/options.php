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
 * Designer additional options for activities.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/designer/lib.php');

use format_designer\output\cm_completion;

/**
 * Module additional custom fields processing.
 */
class options {

    /**
     * Find the given string is JSON format or not.
     *
     * @param string $string
     * @return bool
     */
    public static function is_json($string) {
        return (is_null(json_decode($string))) ? false : true;
    }

    /**
     * Get custom additional field value for the module.
     *
     * @param int $cmid Course module id.
     * @param string $name Module additional field name.
     * @return null|string Returns value of given module field.
     */
    public static function get_option(int $cmid, $name) {
        global $DB;
        if ($data = $DB->get_field('format_designer_options', 'value',
            ['cmid' => $cmid, 'name' => $name])) {
            return $data;
        }
        return null;
    }

    /**
     * Get designer additional fields values for the given module.
     *
     * @param int $cmid course module id.
     * @return stdclass $options List of additional field values
     */
    public static function get_options($cmid) {
        global $DB;
        $options = new \stdclass;
        if ($records = $DB->get_records('format_designer_options', ['cmid' => $cmid])) {
            foreach ($records as $key => $field) {
                $options->{$field->name} = self::is_json($field->value)
                    ? json_decode($field->value, true) : $field->value;
            }
        }
        return $options;
    }

    /**
     * Insert the additional module fields data to the table.
     *
     * @param int $cmid Course module id.
     * @param int $courseid Course id.
     * @param string $name Field name.
     * @param mixed $value value of the field.
     * @return void
     */
    public static function insert_option(int $cmid, int $courseid, $name, $value) {
        global $DB;

        $record = new \stdClass;
        $record->cmid = $cmid;
        $record->courseid = $courseid;
        $record->name = $name;
        $record->value = $value ?: '';
        $record->timemodified = time();
        if ($exitrecord = $DB->get_record('format_designer_options', [
            'cmid' => $cmid, 'courseid' => $courseid, 'name' => $name, ])) {
            $record->id = $exitrecord->id;
            $record->timecreated = $exitrecord->timecreated;
            $DB->update_record('format_designer_options', $record);
        } else {
            $record->timecreated = time();
            $DB->insert_record('format_designer_options', $record);
        }
    }

    /**
     * Find the current logged in user completed the module completion conditions.
     *
     * @param \cm_info $mod Course modulel info.
     * @return bool Result of module completion.
     */
    public static function is_mod_completed($mod) {
        $cmcompletion = new \format_designer\output\cm_completion($mod);
        $cmcompletionstate = $cmcompletion->get_completion_state();
        if ($cmcompletionstate == COMPLETION_COMPLETE || $cmcompletionstate == COMPLETION_COMPLETE_PASS) {
            return true;
        }
        return false;
    }

    /**
     * Check the section able to complete.
     *
     * @param [object] $section
     * @param [object] $course
     * @param [object] $modinfo
     * @param boolean $onlyrelative
     * @return boolean
     */
    public static function is_vaild_section_completed($section, $course, $modinfo, $onlyrelative = false) {

        $completioninfo = new \completion_info($course);
        $completionactivities = array_column($completioninfo->get_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY), 'moduleinstance');
        if (!empty($modinfo->sections[$section->section]) && $section->uservisible) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];
                if (!empty($mod)) {
                    if ($onlyrelative && !in_array($mod->id, $completionactivities)) {
                        continue;
                    }
                    $cmcompletion = new cm_completion($mod);
                    if ($mod->is_visible_on_course_page() && $cmcompletion->get_completion_mode() != COMPLETION_TRACKING_NONE) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Find all the modules inside the given sections are completed by the logged in user.
     * If result is not true it will return the progress and current completion details of section.
     *
     * @param \section_info $section Section info data.
     * @param stdclass $course Course instance record.
     * @param \Course_modinfo $modinfo Course mod info.
     * @param bool $result True to only for REsult, False for current progress.
     * @param bool $onlyrelative
     * @return bool|array Result of section completion or Current progress data.
     */
    public static function is_section_completed($section, $course, $modinfo, $result = false, $onlyrelative = false) {

        $completioninfo = new \completion_info($course);
        $completionactivities = array_column($completioninfo->get_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY), 'moduleinstance');
        $cmcompleted = 0;
        $totalmods = 0;
        $issectioncompletion = 0;
        if (!empty($modinfo->sections[$section->section]) && $section->uservisible) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];
                if (!empty($mod)) {
                    if ($onlyrelative && !in_array($mod->id, $completionactivities)) {
                        continue;
                    }
                    $cmcompletion = new cm_completion($mod);
                    if ($mod->is_visible_on_course_page() && $cmcompletion->get_completion_mode() != COMPLETION_TRACKING_NONE) {
                        $totalmods++;
                        $cmcompletionstate = $cmcompletion->get_completion_state();
                        if ($cmcompletionstate == COMPLETION_COMPLETE || $cmcompletionstate == COMPLETION_COMPLETE_PASS ) {
                            $cmcompleted++;
                        }
                    }
                }
            }
        }

        if ($totalmods) {
            $sectionprogress = $cmcompleted / $totalmods * 100;
            $issectioncompletion = true;
        } else {
            $sectionprogress = false;
        }

        if (isguestuser()) {
            $issectioncompletion = false;
        }

        $sectionprogresscomp = ($sectionprogress == 100) ? true : false;

        return ($result) ? $sectionprogresscomp : [
            $issectioncompletion,
            $sectionprogress,
            $sectionprogresscomp,
        ];
    }

    /**
     * Get area files available for backup.
     * @param string $structure Type of format module or section
     * @return null|array List of available fileareas
     */
    public static function get_file_areas($structure='module') {
        if (format_designer_has_pro()) {
            return \local_designer\options::get_file_areas($structure);
        }
    }

    /**
     * Get timemanagement tools due date for the module.
     *
     * @param cm_info $cm
     * @return int|bool Mod due date if available otherwiser returns false.
     */
    public static function timetool_duedate($cm) {
        global $USER;
        if (format_designer_timemanagement_installed() && function_exists('ltool_timemanagement_get_mod_user_info')) {
            $data = ltool_timemanagement_get_mod_user_info($cm, $USER->id);
            return $data['duedate'] ?? false;
        }
        return false;
    }

    /**
     * Get default value for the course, section and module config.
     *
     * @param bool $issection
     * @return void
     */
    public static function get_default_options($issection=false) {
        global $DB, $PAGE;
        static $design;
        if ($design == null) {

            $formatdesign = (array) get_config('format_designer');
            $localdesign = (array) get_config('local_designer');
            $design = (object) array_merge($formatdesign, $localdesign);

            $design->bgimagestyle = [
                'size' => isset($design->bgimagestyle_size) ? $design->bgimagestyle_size : '',
                'size_adv' => isset($design->bgimagestyle_size_adv) ? $design->bgimagestyle_size_adv : '',
                'position' => isset($design->bgimagestyle_position) ? $design->bgimagestyle_position : '',
                'position_adv' => isset($design->bgimagestyle_position_adv) ? $design->bgimagestyle_position_adv : '',
                'repeat' => isset($design->bgimagestyle_repeat) ? $design->bgimagestyle_repeat : '',
                'repeat_adv' => isset($design->bgimagestyle_repeat_adv) ? $design->bgimagestyle_repeat_adv : '',
            ];

            $design->maskstyle = [
                'size' => isset($design->maskstyle_size) ? $design->maskstyle_size : '',
                'size_adv' => isset($design->maskstyle_size_adv) ? $design->maskstyle_size_adv : 0,
                'position' => isset($design->maskstyle_position) ? $design->maskstyle_position : '',
                'position_adv' => isset($design->maskstyle_position_adv) ? $design->maskstyle_position_adv : 0,
                'image' => isset($design->maskstyle_image) ? $design->maskstyle_image : '',
            ];

            $elements = ['icon', 'visits', 'calltoaction', 'title', 'description', 'modname', 'completionbadge'];
            foreach ($elements as $element) {
                $design->activityelements[$element] = isset($design->{'activityelements_'.$element})
                    ? $design->{'activityelements_'.$element} : '';
            }

            unset($design->sectiondesignerbackgroundimage);
        }
        return $design;
    }
}
