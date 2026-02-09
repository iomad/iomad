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
 * File contains definition of designer pro options for activities and sections
 *
 * @package    local_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_designer;

defined('MOODLE_INTERNAL') || die();

use admin_setting_configtext_with_advanced;
use admin_setting_heading;
use core_customfield\category;

require_once($CFG->dirroot.'/local/designer/settingslib.php');

/**
 * Other component availability and layout features are loadded.
 */
class info {

    /**
     * Create a instance of designer info class.
     *
     * @return \local_designer\info
     */
    public static function create() {
        static $instance;
        if ($instance == null) {
            return new self();
        }
        return $instance;
    }

    /**
     * Gerenate the classes that needs to add in the body.
     *
     * @param stdclass $course course data.
     * @param format_designer $format course format.
     *
     * @return string CSS Classes to attach in body.
     */
    public function generate_body_classes($course, \format_designer $format) {
        $classes[] = courseheader::create($format)->get_header_type_class(true);
        $classes[] = ($course->courseheadertype == 0) ? 'course-header-type-default' : '';
        $classes[] = ($course->sectionprogresstype == DESIGNER_PROGERSS_TYPE_DONUT) ? 'section-progress-donut' : '';
        return implode(' ', $classes);
    }

    /**
     * Get the designer course form to fetch the list of elements.
     *
     * @return \designer_course_form
     */
    public function get_course_form() {
        global $CFG;
        require_once($CFG->dirroot. '/local/designer/locallib.php');
        // Use the site as course.
        $course = get_course(SITEID);

        if (!isset($course->category)) {
            return false;
        }
        // Set the designer as a format for the site, otherwiser courseform will raise the format missing issue.
        $course->format = 'designer';
        $courseform = new \designer_courseform($course);
        return $courseform;
    }

    /**
     * Return the course field lables.
     *
     * @return array course field names.
     */
    public function get_course_field_labels() {
        return [
            'fullname' => get_string('fullnamecourse'),
            'shortname' => get_string('shortnamecourse'),
            'category' => get_string('coursecategory'),
            'visible' => get_string('coursevisibility'),
            'tags' => get_string('tags'),
            'startdate' => get_string('startdate'),
            'enddate' => get_string('enddate'),
            'idnumber' => get_string('idnumbercourse'),
            'summary_editor' => get_string('coursesummary'),
            'overviewfiles_filemanager' => get_string('courseoverviewfiles'),
            'format' => get_string('format'),
            'maxbytes' => get_string('maximumupload'),
            'lang' => get_string('forcelanguage'),
            'newsitems' => get_string('newsitemsnumber'),
            'showgrades' => get_string('showgrades'),
            'showreports' => get_string('showreports'),
            'showactivitydates' => get_string('showactivitydates'),
            'enablecompletion' => get_string('enablecompletion', 'completion'),
            'showcompletionconditions' => get_string('showcompletionconditions', 'completion'),
            'groupmode' => get_string('groupmode', 'group'),
            'groupmodeforce' => get_string('groupmodeforce', 'group'),
            'defaultgroupingid' => get_string('defaultgrouping', 'group'),
            'rolerenaming' => get_string('rolerenaming'),
        ];
    }

    /**
     * Get the designer course form to fetch the list of elements.
     *
     * @return \designer_user_form
     */
    public function get_user_form() {
        global $CFG, $USER;
        require_once($CFG->dirroot. '/local/designer/locallib.php');
        // Generate dummy data to prevent the warnings from the course form.
        $args = [
            'editoroptions' => '',
            'filemanageroptions' => '',
            'user' => $USER,
        ];
        $editform = new \designer_userform(null, $args);
        return $editform;
    }

    /**
     * Include the icon picker admin setting to set icons for each course custom fields.
     *
     * @param stdclass $settingpage
     * @return void
     */
    public function include_course_customfields_iconconfig(&$settingpage) {
        $setting = new admin_setting_heading('courseiconssss', new \lang_string('configcourseicons', 'format_designer'), "");
        $settingpage->add($setting);
        $elements = $this->get_course_form() ?
            $this->get_course_form()->get_fields_iconconfig_list() : [];
        // Convert the list of configs to the admin settings.
        if (!empty($elements)) {
            self::convert_format_options($elements, $settingpage);
        }
    }

    /**
     * Check the videotime plugin installed and available to integrate.
     *
     * @return bool
     */
    public static function is_videotime_available() {
        $pluginman = \core_plugin_manager::instance();
        $plugininfo = $pluginman->get_plugin_info('videotimeplugin_repository');
        return !empty($plugininfo) ? true : false;
    }

    /**
     * Check the subcourse module installed.
     * @return bool
     */
    public static function is_subcourse_module_available() {
        $pluginman = \core_plugin_manager::instance();
        $plugininfo = $pluginman->get_plugin_info('mod_subcourse');
        return !empty($plugininfo) ? true : false;
    }


    /**
     * Add the form elements to coursemodule form to use the module subcourse image.
     * @param moodle_form $mform
     * @param object $design
     * @return void
     */
    public static function module_subcourse_options($mform, $design) {
        if (self::is_subcourse_module_available()) {
            $mform->addElement('checkbox', 'designer_subcourseuseactivityimage',
                get_string('subcourseuseactivityimage', 'format_designer'));
            $mform->setType('designer_subcourseuseactivityimage', PARAM_INT);
            if (isset($design->useactivityimage)) {
                $mform->setDefault('designer_subcourseuseactivityimage', $design->subcourseuseactivityimage);
            }

            $mform->addElement('checkbox', 'designer_subcoursedisplayprogress',
                get_string('subcoursedisplayprogress', 'format_designer'));
            $mform->setType('designer_subcoursedisplayprogress', PARAM_INT);
            if (isset($design->displayprogress)) {
                $mform->setDefault('designer_subcoursedisplayprogress', $design->subcoursedisplayprogress);
            }
            $mform->disabledIf('designer_subcoursedisplayprogress', 'designer_subcourseuseactivityimage', 'notchecked');
        }
    }


    /**
     * Add the form elements to coursemodule form to use the video preview image as background image.
     *
     * @param moodle_form $mform
     * @param object $design
     * @return void
     */
    public static function videotime_options(&$mform, $design) {

        if (self::is_videotime_available()) {
            $mform->addElement('checkbox', 'designer_useactivityimage', get_string('useactivityimage', 'format_designer'));
            $mform->setType('designer_useactivityimage', PARAM_INT);
            if (isset($design->useactivityimage)) {
                $mform->setDefault('designer_useactivityimage', $design->useactivityimage);
            }

            $mform->addElement('checkbox', 'designer_displayprogress', get_string('displayprogress', 'format_designer'));
            $mform->setType('designer_displayprogress', PARAM_INT);
            if (isset($design->displayprogress)) {
                $mform->setDefault('designer_displayprogress', $design->displayprogress);
            }
            $mform->disabledIf('designer_displayprogress', 'designer_useactivityimage', 'notchecked');
        }
    }


    /**
     * Fetch the video time plugin video Preview image URL and activity progress.
     *
     * @param cm_info $mod
     * @param object $options
     * @return array|null preview image URL and current progress.
     */
    public static function videotime_background_image($mod, $options) {
        global $USER, $OUTPUT;
        $result = [];
        if (self::is_videotime_available()) {
            $instance = \mod_videotime\videotime_instance::instance_by_id($mod->instance);
            $preview = new \videotimeplugin_repository\output\video_preview($instance, $USER->id);
            $template = $preview->export_for_template($OUTPUT);
            if ($options->useactivityimage) {
                $result['videourl'] = isset($template['video']['thumbnail_url']) ? $template['video']['thumbnail_url'] : '';
                if ($options->displayprogress) {
                    $result['progress'] = $OUTPUT->render_from_template('local_designer/videotime_progress', $template);
                }
            }
            return $result;
        }
        return false;
    }

    /**
     * Fetch the video time plugin video Preview image URL and activity progress.
     *
     * @param cm_info $mod
     * @param object $options
     * @return array|null preview image URL and current progress.
     */
    public static function subcourse_background_image($mod, $options) {
        global $USER, $OUTPUT, $DB, $CFG;
        $result = [];
        if (self::is_subcourse_module_available()) {
            $instance = $DB->get_record('subcourse', ['id' => $mod->instance]);
            if ($instance->refcourse) {
                if ($options->subcourseuseactivityimage) {
                    $courseimage = "";
                    $course = get_course($instance->refcourse);
                    $course = new \core_course_list_element($course);
                    foreach ($course->get_course_overviewfiles() as $file) {
                        $isimage = $file->is_valid_image();
                        if ($isimage) {
                            $courseimage = file_encode_url("$CFG->wwwroot/pluginfile.php",
                            '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                            $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                        }
                    }
                    $result['videourl'] = $courseimage;
                }

                if ($options->subcoursedisplayprogress) {
                    $refcourse = get_course($instance->refcourse);
                    // Check the if subcourse was designer to get the criteria progress. others are normal progress.
                    $progress = \core_completion\progress::get_course_progress_percentage($refcourse);
                    if ($refcourse->format == 'designer') {
                        $courseprogress = \format_designer\output\renderer::criteria_progress($refcourse, $USER->id);
                        $progress = isset($courseprogress['percent']) ? $courseprogress['percent'] : 0;
                    }
                    $result['progress'] = $OUTPUT->render_from_template('local_designer/subcourse_progress',
                        ['progress' => ($progress) != null ? round($progress) : 0]);
                }
            }
            return $result;
        }
        return false;
    }

    /**
     * Is format popup plugin installed or not.
     *
     * @return bool
     */
    public static function is_popup_available() {
        $pluginman = \core_plugin_manager::instance();
        $plugininfo = $pluginman->get_plugin_info('format_popups');
        return !empty($plugininfo) ? true : false;
    }

    /**
     * Get additional layout menu.
     *
     * @param stdclass $format
     * @param stdclass $section
     * @param stdclass $course
     * @return void
     */
    public static function get_layout_menu($format, $section, $course) {
        global $CFG;

        $prolayouts = format_designer_get_pro_layouts();
        $layouts = [];
        if (is_array($prolayouts)) {
            foreach ($prolayouts as $sectiontype) {
                $layoutsectionfunc = 'layouts_'.$sectiontype.'_menu';
                if (file_exists($CFG->dirroot.'/local/designer/layouts/'.$sectiontype.'/lib.php')) {
                    require_once($CFG->dirroot.'/local/designer/layouts/'.$sectiontype.'/lib.php');
                    if (function_exists($layoutsectionfunc)) {
                        $options = $layoutsectionfunc($format, $section, $course);
                        if (!empty($options)) {
                            array_push($layouts, $options);
                        }
                    }
                }
            }
        }
        return $layouts;
    }

    /**
     * Converts specific timestamp values in an array to user-readable time format.
     *
     * @param mixed $value The array containing timestamp values to be converted.
     * @param string $key
     */
    public static function convert_valuetime_format(&$value, $key) {
        global $COURSE, $DB, $CFG;

        $key = strtolower($key);
        // Update the timestamp to user readable time.
        if (in_array(strtolower($key), ['timecreated', 'timemodified', 'startdate', 'enddate', 'firstaccess',
            'lastaccess', 'lastlogin', 'currentlogin', 'timecreated', 'starttime', 'endtime', ])) {
            $value = $value ? userdate($value) : '';
        }

        if ($key == 'visible') {
            $value = $value == 1 ? get_string('show') : get_string('hide');
        }

        if (strtolower($key) == 'category') {
            // Use the site as course.
            $course = get_course($COURSE->id);
            $category = $DB->get_record('course_categories', ['id' => $course->category]);
            $value = $category->name;
        }

        if (strtolower($key) == 'lang') {
            // Get the list of translations.
            $translations = get_string_manager()->get_list_of_translations();
            $value = $translations[$value] ?? '';
        }

        if ($key == 'maxbytes') {
            $choices = get_max_upload_sizes($CFG->maxbytes, 0, 0);
            $value = $choices[$value] ?? $value;
        }

        if ($key == 'tags') {
            // Populate course tags.
            $value = \core_tag_tag::get_item_tags_array('core', 'course', $COURSE->id);
        }

        if ($key == 'groupmode') {
            $choices = [];
            $choices[NOGROUPS] = get_string('groupsnone', 'group');
            $choices[SEPARATEGROUPS] = get_string('groupsseparate', 'group');
            $choices[VISIBLEGROUPS] = get_string('groupsvisible', 'group');

            $value = $choices[$value] ?? $value;
        }

        if ($key == 'email') { // Link e-Mail address - DES-855.
            $value = \html_writer::link('mailto:'. $value, $value);
        }

        // Update the status to user readable strings. This should be at last.
        if (in_array(strtolower($key), ['groupmodeforce', 'defaultgroupingid']) || ($value == 1)) {
            $value = $value == 1 ? get_string('enabled', 'format_designer') : get_string('disabled', 'format_designer');
        }

        if (is_array($value)) {
            $value = implode(', ', $value);
        }
    }

    /**
     * Get the image editor options.
     *
     * @return array editor options.
     */
    public static function get_editoroptions() {
        global $CFG;
        return ['maxfiles' => -1, 'maxbytes' => $CFG->maxbytes, 'trusttext' => false, 'noclean' => true];
    }

    /**
     * Convert array of format options to moodle form.
     *
     * @param array $options
     * @param stdclass $page
     *
     * @return void
     */
    public static function convert_format_options($options, &$page) {
        global $PAGE;
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                if (!isset($value['element_type'])) {
                    continue;
                }

                $name = 'format_designer/'.$key;
                $title = $value['label'];
                $hcomponent = (isset($value['help_component'])) ? $value['help_component'] : 'format_designer';
                $desc = (isset($value['help'])) ? get_string($value['help']."_help", $hcomponent) : '';
                $default = ['value' => (isset($value['default']) ? (string) $value['default'] : ''), 'fix' => true];
                if ($value['element_type'] == 'text') {
                    $type = (isset($value['type'])) ? constant('PARAM_'.strtoupper($value['type'])) : PARAM_RAW_TRIMMED;
                    $attr = isset($value['element_attributes'][0]) ? $value['element_attributes'][0] : [];
                    $setting = new \admin_setting_configtext_with_advanced($name, $title, $desc, $default, $type);
                    $page->add($setting);
                } else if ($value['element_type'] == 'select') {
                        $choices = isset($value['element_attributes'][0]) ? $value['element_attributes'][0] : [];
                        $setting = new \admin_setting_configselect_with_advanced($name, $title, $desc, $default, $choices);
                        $page->add($setting);
                } else if ($value['element_type'] == 'filemanager') {
                    $choices = isset($value['element_attributes'][1]) ? $value['element_attributes'][1] : [];
                    $filearea = (isset($value['filearea'])) ? $value['filearea'] : $key;
                    $setting = new \admin_setting_configstoredfile($name, $title, $desc, $filearea, 0, $choices);
                    $page->add($setting);
                } else if ($value['element_type'] == 'checkbox' || $value['element_type'] == 'advcheckbox') {
                    $setting = new \admin_setting_configcheckbox_with_advanced($name, $title, $desc, $default);
                    $page->add($setting);
                } else if ($value['element_type'] == 'designercolorpicker') {
                    $setting = new \admin_setting_configcolourpicker($name, $title, $desc, '');
                    $page->add($setting);
                } else if ($value['element_type'] == 'header') {
                    $setting = new \admin_setting_heading('format_designer_'.$key, $default['value'], $desc);
                    $page->add($setting);
                } else if ($value['element_type'] == 'textarea') {
                    $setting = new \admin_setting_configtextarea($name, $title, $desc, '');
                    $page->add($setting);
                } else if ($value['element_type'] == 'autocomplete') {
                    $setting = new \local_designer_configmultiselect_with_advanced($name, $title, $desc,
                        $default, $value['element_attributes'][0]);
                    $page->add($setting);
                    $PAGE->requires->js_amd_inline("
                        require(['core/form-autocomplete'], function(module) {
                        module.enhance('#id_s_format_designer_coursestaff');
                        }); ");
                }
            }
        }
    }
}
