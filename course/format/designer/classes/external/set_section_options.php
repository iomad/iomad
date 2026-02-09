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
 * Set section options web service function.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designer\external;

defined('MOODLE_INTERNAL') || die();

use context_course;
use external_function_parameters;
use external_single_structure;
use external_value;
use format_designer;
use context_module;
use coding_exception;
use moodle_exception;
use completion_info;
require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/course/format/lib.php');

/**
 * Set section options web service function.
 */
trait set_section_options {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function set_section_options_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'sectionid' => new external_value(PARAM_INT, 'Section ID'),
            'options' => new \external_multiple_structure(new external_single_structure([
                'name' => new external_value(PARAM_TEXT, 'Option name to set on section'),
                'value' => new external_value(PARAM_RAW, 'Value for option'),
            ])),
        ]);
    }

    /**
     * Set section options web service function.
     *
     * @param int $courseid course id
     * @param int $sectionid section id
     * @param array $options
     */
    public static function set_section_options(int $courseid, int $sectionid, array $options) {
        global $DB, $PAGE;
        $context = context_course::instance($courseid);
        $PAGE->set_context($context);
        $params = self::validate_parameters(self::set_section_options_parameters(), [
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'options' => $options,
        ]);
        $course = $DB->get_record('course', ['id' => $params['courseid']]);
        /** @var format_designer $format */
        $format = course_get_format($course);
        require_capability('format/designer:changesectionoptions', $context);
        foreach ($params['options'] as $option) {
            $format->set_section_option($params['sectionid'], $option['name'], $option['value']);
        }

        return null;
    }

    /**
     * Return structure for edit_section()
     *
     * @since Moodle 3.3
     * @return external_description
     */
    public static function set_section_options_returns() {
        return new external_value(PARAM_RAW, 'Additional data for javascript (JSON-encoded string)');
    }

    /**
     * Parameters for function get_module()
     *
     * @since Moodle 3.3
     * @return external_function_parameters
     */
    public static function get_module_parameters() {
        return new external_function_parameters(
            [
                'id' => new external_value(PARAM_INT, 'course module id', VALUE_REQUIRED),
                'sectionid' => new external_value(PARAM_INT, 'course module section id', VALUE_REQUIRED),
                'sectionreturn' => new external_value(PARAM_INT, 'section to return to', VALUE_DEFAULT, null),
            ]
        );
    }

    /**
     * Returns html for displaying one activity module on course page
     *
     * @since Moodle 3.3
     * @param int $id
     * @param int $sectionid
     * @param null|int $sectionreturn
     * @return string
     */
    public static function get_module($id, $sectionid, $sectionreturn = null) {
        global $PAGE, $OUTPUT;
        // Validate and normalize parameters.
        $params = self::validate_parameters(self::get_module_parameters(),
            ['id' => $id, 'sectionid' => $sectionid, 'sectionreturn' => $sectionreturn]);
        $id = $params['id'];
        $sectionreturn = $params['sectionreturn'];

        // Set of permissions an editing user may have.
        $contextarray = [
            'moodle/course:update',
            'moodle/course:manageactivities',
            'moodle/course:activityvisibility',
            'moodle/course:sectionvisibility',
            'moodle/course:movesections',
            'moodle/course:setcurrentsection',
        ];
        $PAGE->set_other_editing_capability($contextarray);

        // Validate access to the course (note, this is html for the course view page, we don't validate access to the module).
        list($course, $cm) = get_course_and_cm_from_cmid($id);
        self::validate_context(context_course::instance($course->id));
        $renderer = $PAGE->get_renderer('format_designer');

        $format = course_get_format($course);
        $sectiontype = $format->get_section_option($sectionid, 'sectiontype') ?: get_config('format_designer', 'sectiontype');

        $section = (object) ['sectiontype' => $sectiontype];
        $cmlistdata = $renderer->render_course_module($cm, $sectionreturn, [], $section);

        $templatename = 'format_designer/cm/module_layout_' . $sectiontype;
        $prolayouts = format_designer_get_pro_layouts();
        if (in_array($sectiontype, $prolayouts)) {
            if (format_designer_has_pro()) {
                $templatename = 'layouts_' . $sectiontype . '/cm/module_layout_' . $sectiontype;
            }
        }
        $liclass = $sectiontype;
        $liclass .= ' '.$sectiontype.'-layout';
        $liclass .= ' '.$cmlistdata['modclasses'];
        $liclass .= (isset($cmlistdata['isrestricted']) && $cmlistdata['isrestricted']) ? ' restricted' : '';
        $html = \html_writer::start_tag('li', ['class' => $liclass, 'id' => $cmlistdata['id']]);
        $html .= $OUTPUT->render_from_template($templatename, $cmlistdata);
        $html .= \html_writer::end_tag('li');
        return $html;
    }

    /**
     * Return structure for get_module()
     *
     * @since Moodle 3.3
     * @return external_description
     */
    public static function get_module_returns() {
        return new external_value(PARAM_RAW, 'html to replace the current module with');
    }

    /**
     * Parameters for function section_refresh()
     *
     * @since Moodle 3.3
     * @return external_function_parameters
     */
    public static function section_refresh_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'course id', VALUE_REQUIRED),
                'sectionid' => new external_value(PARAM_INT, 'section id', VALUE_REQUIRED),
                'sectionreturn' => new external_value(PARAM_INT, 'section to return to', VALUE_DEFAULT, null),
            ]
        );
    }

    /**
     * Performs one of the refresh the sections and return new html for AJAX
     *
     * Returns html to replace the current module html with, for example:
     * - empty string for "delete" action,
     * - two modules html for "duplicate" action
     * - updated module html for everything else
     *
     * Throws exception if operation is not permitted/possible
     *
     * @since Moodle 3.3
     * @param int $courseid
     * @param int $sectionid
     * @param null|int $sectionreturn
     * @return string
     */
    public static function section_refresh($courseid, $sectionid, $sectionreturn = null) {
        global $PAGE, $DB, $OUTPUT;
        $context = context_course::instance($courseid);
        $PAGE->set_context($context);
        // Validate and normalize parameters.
        $params = self::validate_parameters(self::section_refresh_parameters(),
            ['courseid' => $courseid, 'sectionid' => $sectionid, 'sectionreturn' => $sectionreturn]);
        $courseid = $params['courseid'];
        $sectionid = $params['sectionid'];
        $sectionreturn = $params['sectionreturn'];
        $course = $DB->get_record('course', ['id' => $params['courseid']]);
        $modinfo = get_fast_modinfo($course);
        $sectioninfo = $DB->get_record('course_sections', ['id' => $params['sectionid']]);
        $section = $modinfo->get_section_info($sectioninfo->section);
        $rv = course_get_format($section->course)->section_action($section, 'setsectionoption', $sectionreturn);
        if ($rv) {
            return json_encode($rv);
        } else {
            return null;
        }
    }

    /**
     * Return structure for edit_module()
     *
     * @since Moodle 3.3
     * @return external_description
     */
    public static function section_refresh_returns() {
        return new external_value(PARAM_RAW, 'Additional data for javascript (JSON-encoded string)');
    }


    /**
     * Parameters for function get_videotime_instace_parameters()
     */
    public static function get_videotime_instace_parameters() {
        return new external_function_parameters(
            [
                'cmid' => new external_value(PARAM_INT, 'course module', VALUE_REQUIRED),
            ]
        );
    }

    /**
     * Get the videotime instance.
     *
     * @param int $cmid cm id.
     * @return array $data videotime instance data.
     */
    public static function get_videotime_instace($cmid) {
        global $CFG, $PAGE;
        $data = [];
        if (file_exists($CFG->dirroot. "/mod/videotime/lib.php")) {
            require_once($CFG->dirroot. "/mod/videotime/classes/videotime_instance.php");
            require_once($CFG->dirroot. "/mod/videotime/lib.php");
            $params = self::validate_parameters(self::get_videotime_instace_parameters(),
                ['cmid' => $cmid]);
            $cmid = $params['cmid'];
            $cm = get_coursemodule_from_id('videotime', $cmid, 0, false, MUST_EXIST);
            $context = context_course::instance($cm->course);
            $PAGE->set_context($context);
            $moduleinstance = \mod_videotime\videotime_instance::instance_by_id($cm->instance);
            $record = $moduleinstance->to_record();
            $data = [
                'instance' => json_encode($record),
                'cmid' => $cm->id,
                'haspro' => videotime_has_pro(),
                'interval' => $record->saveinterval ?? 5,
                'plugins' => file_exists($CFG->dirroot . '/mod/videotime/plugin/pro/templates/plugins.mustache'),
                'toast' => file_exists($CFG->dirroot . '/lib/amd/src/toast.js'),
                'video_description' => $record->video_description,
                'templatename' => 'videotimeplugin_vimeo/video_embed',
                'playertype' => 'vimeo',
            ];

            if (
                empty(get_config('videotimeplugin_vimeo', 'enabled'))
                || !mod_videotime_get_vimeo_id_from_link($moduleinstance->vimeo_url)
            ) {
                $videojs = true;
                $mimetype = resourcelib_guess_url_mimetype($moduleinstance->vimeo_url);
                $data = array_merge($data, [
                    'mimetype' => $mimetype,
                    'video' => !file_mimetype_in_typegroup($mimetype, ['web_audio']),
                    'templatename' => 'videotimeplugin_videojs/video_embed',
                    'playertype' => 'videojs',
                ]);
            }
        }
        return json_encode($data);

    }

    /**
     * Return structure for get_videotime_instace_returns()
     */
    public static function get_videotime_instace_returns() {
        return new external_value(PARAM_RAW, 'Additional data for javascript (JSON-encoded string)');
    }



}
