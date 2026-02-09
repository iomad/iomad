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
 * Local plugin "Designer Pro" - lib file.
 *
 * @package   local_designer
 * @copyright bdecent GmbH 2021
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

define('LOCAL_DESIGNER_ACTIVITY_MASK_AREA', 'activity_mask_image');

define('LOCAL_DESIGNER_SECTION_MASK_AREA', 'section_mask_image');

define('LOCAL_DESIGNER_PREREQUISITEINFO_AREA', 'prerequisiteinfo');

define('LOCAL_DESIGNER_GROUP_DESC', 'description');

define('LOCAL_DESIGNER_SECTIONCARD_CTA', 'sectioncardcta');

define('DESIGNER_PREREQUISITES_ABOVECOURSE', 1);

define('DESIGNER_PREREQUISITES_SEPARATETAB', 2);

define('DESIGNER_PREREQUISITES_AUTOENROLL_ALREADY', 1);

define('DESIGNER_PREREQUISITES_AUTOENROLL_ALWAYS', 2);

define('DESIGNER_COURSE_TYPE_HERO', 1);

define('DESIGNER_COURSE_TYPE_CONTENT', 2);

define('DESIGNER_PROGERSS_TYPE_DONUT', 2);

define('DESIGNER_PROGERSS_TYPE_BAR', 1);


define('DESIGNER_COURSE_HEADER_SUMMARY_TRIMMED', 1);

define('DESIGNER_COURSE_HEADER_SUMMARY_FULL', 2);

define('LOCAL_DESIGNER_ADDITIONAL_CONTENT_AREA', 'additionalcontent');

define('LOCAL_DESIGNER_COURSE_BG_IMAGE_FILE_AREA', 'coursebgimage');

define('LOCAL_DESIGNER_COURSE_HEADER_BG_IMAGE_FILE_AREA', 'courseheaderbgimage');

require_once($CFG->dirroot. "/course/format/designer/lib.php");

use core_course\external\course_summary_exporter;
use local_designer\options;


/**
 * Inject the designer elements into all moodle module settings forms.
 *
 * @param moodleform $formwrapper The moodle quickforms wrapper object.
 * @param MoodleQuickForm $mform The actual form object (required to modify the form).
 */
function local_designer_coursemodule_standard_element($formwrapper, $mform) {
    global $CFG, $DB;
    $cm = $formwrapper->get_coursemodule();
    $course = $formwrapper->get_course();
    if ($course->format == 'designer') {
        $design = $designadv = \format_designer\options::get_default_options();
        if (isset($cm->id) && $cm->id) {
            $design = \local_designer\options::get_options($cm->id);
        }

        if ($cm || !empty($modname = optional_param('add', '', PARAM_TEXT))) {
            if ($cm) {
                $modname = $cm->modname;
            }
            $purposes = options::get_designer_purposes();
            $corepurposes = options::get_default_purposes();
            $purposes = array_combine(array_keys($purposes), array_keys($purposes));

            $mform->addElement('html', get_string('modulepurposes', 'format_designer'));
            $mform->addElement('select', 'designer_purpose', get_string('purpose', 'format_designer'), $purposes);
            if (isset($design->purpose)) {
                $mform->setDefault('designer_purpose', $design->purpose);
            } else {
                $mform->setDefault('designer_purpose', get_config('local_designer', 'purpose_' . $modname));
            }
        }

        require_once($CFG->dirroot.'/local/designer/form/element-colorpicker.php');
        MoodleQuickForm::registerElementType('designercolorpicker', $CFG->dirroot.'/local/designer/form/element-colorpicker.php',
            'moodlequickform_designercolorpicker');

        $mform->addElement('html', get_string('backgroundsection', 'format_designer'));
        // Module background image.
        $mform->addElement('filemanager', 'designer_backimage', get_string('backgroundimage', 'format_designer'),
            null, ["maxfiles" => 1]);
        if (isset($design->backimage)) {
            $mform->setDefault('designer_backimage', $design->backimage);
        }
        // Video time options.
        if ($formwrapper->get_current()->modulename == 'videotime') {
            local_designer\info::videotime_options($mform, $design);
        }

        if ($formwrapper->get_current()->modulename == 'subcourse') {
            local_designer\info::module_subcourse_options($mform, $design);
        }

        $mform->addElement('advcheckbox', 'designer_usecompletionbg', get_string('usecompletionbg', 'format_designer'));
        $mform->setType('designer_usecompletionbg', PARAM_INT);
        if (isset($design->usecompletionbg)) {
            $mform->setDefault('designer_usecompletionbg', $design->usecompletionbg);
        }

        $mform->addElement('filemanager', 'designer_completionbackimage',
            get_string('completionbackgroundimage', 'format_designer'), null, ['filetype' => 'images']);
        $mform->hideIf('designer_completionbackimage', 'designer_usecompletionbg', 'notchecked');
        if (isset($design->completionbackimage)) {
            $mform->setDefault('designer_completionbackimage', $design->completionbackimage);
        }

        // Module background image poisiton.
        $mform->addElement('select', 'designer_bgimagestyle[position]', get_string('backgroundposition', 'format_designer'),
        options::get_position_values());
        $mform->setType('designer_bgimagestyle[position]', PARAM_RAW);
        $mform->addHelpButton('designer_bgimagestyle[position]', 'backgroundposition', 'format_designer');
        if (isset($design->bgimagestyle['position'])) {
            $mform->setDefault('designer_bgimagestyle[position]', $design->bgimagestyle['position']);
        }

        // Module background image custom poisiton.
        $mform->addElement('text', 'designer_bgimagestyle[customposition]',
            get_string('designercustombgposition', 'format_designer'));
        $mform->setType('designer_bgimagestyle[customposition]', PARAM_RAW);
        $mform->addHelpButton('designer_bgimagestyle[customposition]', 'backgroundposition', 'format_designer');
        if (isset($design->bgimagestyle['customposition'])) {
            $mform->setDefault('designer_bgimagestyle[customposition]', $design->bgimagestyle['customposition']);
        }
        $mform->hideIf('designer_bgimagestyle[customposition]', 'designer_bgimagestyle[position]', 'neq', 'custom');
        // Module background image size.
        $mform->addElement('select', 'designer_bgimagestyle[size]', get_string('backgroundsize',
            'format_designer'), options::get_size_values());
        $mform->setType('designer_bgimagestyle[size]', PARAM_RAW);
        $mform->addHelpButton('designer_bgimagestyle[size]', 'backgroundsize', 'format_designer');
        if (isset($design->bgimagestyle['size'])) {
            $mform->setDefault('designer_bgimagestyle[size]', $design->bgimagestyle['size']);
        }

        // Module background image custom size.
        $mform->addElement('text', 'designer_bgimagestyle[customsize]', get_string('designercustombgsize', 'format_designer'));
        $mform->setType('designer_bgimagestyle[customsize]', PARAM_RAW);
        $mform->addHelpButton('designer_bgimagestyle[customsize]', 'backgroundsize', 'format_designer');
        if (isset($design->bgimagestyle['customsize'])) {
            $mform->setDefault('designer_bgimagestyle[customsize]', $design->bgimagestyle['customsize']);
        }
        $mform->hideIf('designer_bgimagestyle[customsize]', 'designer_bgimagestyle[size]', 'neq', 'custom');

        // Module background image repeat.
        $choice = [ 0 => get_string('no'), 1 => get_string('yes') ];
        $mform->addElement('select', 'designer_bgimagestyle[repeat]', get_string('backgroundrepeat', 'format_designer'), $choice);
        $mform->setType('designer_bgimagestyle[repeat]', PARAM_INT);
        $mform->addHelpButton('designer_bgimagestyle[repeat]', 'backgroundrepeat', 'format_designer');

        if (isset($design->bgimagestyle['repeat'])) {
            $mform->setDefault('designer_bgimagestyle[repeat]', $design->bgimagestyle['repeat']);
        }

        // Module background gradient.
        $mform->addElement('text', 'designer_backgradient', get_string('backgroundgradient', 'format_designer'),
            ['class' => 'gradient', 'placeholder' => '', 'size' => 50]);
        $mform->setType('designer_backgradient', PARAM_RAW);
        $mform->addHelpButton('designer_backgradient', 'backgroundgradient', 'format_designer');
        if (isset($design->backgradient)) {
            $mform->setDefault('designer_backgradient', $design->backgradient);
        }

        // MASK image settings.
        $mform->addElement('html', get_string('backgroundmasksection', 'format_designer'));
        // Module mask image.
        $choice = \local_designer\options::get_activity_mask_images();
        $mform->addElement('select', 'designer_maskstyle[image]', get_string('maskimage', 'format_designer'), $choice);
        $mform->setType('designer_maskstyle[image]', PARAM_INT);
        $mform->addHelpButton('designer_maskstyle[image]', 'maskimage', 'format_designer');
        if (isset($design->maskstyle['image'])) {
            $mform->setDefault('designer_maskstyle[image]', $design->maskstyle['image']);
        }

        // Module mask image poisiton.
        $mform->addElement('select', 'designer_maskstyle[position]', get_string('maskposition', 'format_designer'),
            options::get_position_values());
        $mform->setType('designer_maskstyle[position]', PARAM_RAW);
        $mform->addHelpButton('designer_maskstyle[position]', 'maskposition', 'format_designer');

        if (isset($design->maskstyle['position'])) {
            $mform->setDefault('designer_maskstyle[position]', $design->maskstyle['position']);
        }

        // Module mask image custom poisiton.
        $mform->addElement('text', 'designer_maskstyle[customposition]',
            get_string('designercustom_maskposition', 'format_designer'));
        $mform->setType('designer_maskstyle[customposition]', PARAM_RAW);
        $mform->addHelpButton('designer_maskstyle[customposition]', 'maskposition', 'format_designer');
        if (isset($design->maskstyle['customposition'])) {
            $mform->setDefault('designer_maskstyle[customposition]', $design->maskstyle['customposition']);
        }
        $mform->hideIf('designer_maskstyle[customposition]', 'designer_maskstyle[position]', 'neq', 'custom');

        // Module mask image size.
        $mform->addElement('select', 'designer_maskstyle[size]', get_string('masksize', 'format_designer'),
            options::get_size_values());
        $mform->setType('designer_maskstyle[size]', PARAM_RAW);
        $mform->addHelpButton('designer_maskstyle[size]', 'masksize', 'format_designer');
        if (isset($design->maskstyle['size'])) {
            $mform->setDefault('designer_maskstyle[size]', $design->maskstyle['size']);
        }

        // Module mask image custom size.
        $mform->addElement('text', 'designer_maskstyle[customsize]', get_string('designercustom_masksize', 'format_designer'));
        $mform->setType('designer_maskstyle[customsize]', PARAM_RAW);
        $mform->addHelpButton('designer_maskstyle[customsize]', 'masksize', 'format_designer');
        if (isset($design->maskstyle['customsize'])) {
            $mform->setDefault('designer_maskstyle[customsize]', $design->maskstyle['customsize']);
        }
        $mform->hideIf('designer_maskstyle[customsize]', 'designer_maskstyle[size]', 'neq', 'custom');

        // General styles.
        $mform->addElement('html', get_string('generalsectionconfig', 'format_designer'));
        $mform->addElement('designercolorpicker', 'designer_textcolor', get_string('textcolor', 'format_designer'));
        $mform->setType('designer_textcolor', PARAM_RAW);
        if (isset($design->textcolor)) {
            $mform->setDefault('designer_textcolor', $design->textcolor);
        }
        // Module min height.
        $mform->addElement('text', 'designer_minheight', get_string('minheight', 'format_designer'),
            ['class' => 'min-height', 'placeholder' => '200px, 4rem, 4em..']);
        $mform->setType('designer_minheight', PARAM_RAW);
        $mform->addHelpButton('designer_minheight', 'minheight', 'format_designer');

        if (isset($design->minheight)) {
            $mform->setDefault('designer_minheight', $design->minheight);
        }

        $elements = $mform->_elements;
        foreach ($elements as $element) {
            $name = isset($element->_attributes['name']) ? $element->_attributes['name'] : '';
            if ($name == null) {
                continue;
            }
            $adv = str_replace('designer_', '', $name);
            $adv = str_replace('[', '_', $adv);
            $adv = str_replace(']', '', $adv);
            $adv = $adv.'_adv';
            if (isset($designadv->$adv) && $designadv->$adv) {
                $mform->setAdvanced($name);
            }
        }
    }
}

/**
 * Hook the add/edit of the course module.
 *
 * @param stdClass $data Data from the form submission.
 * @param stdClass $course The course.
 */
function local_designer_coursemodule_edit_post_actions($data, $course) {
    global $DB;
    if ($course->format == 'designer') {
        $record = new stdClass;
        $record->cmid = $data->coursemodule;
        $record->courseid = $data->course;
        $fields = [
            'designer_backimage',
            'designer_backgradient',
            'designer_textcolor',
            'designer_minheight',
            'designer_bgimagestyle',
            'designer_completionbackimage',
            'designer_usecompletionbg',
            'designer_maskstyle',
            'designer_useactivityimage',
            'designer_displayprogress',
            'designer_subcourseuseactivityimage',
            'designer_subcoursedisplayprogress',
        ];

        $preventfields = [
            'designer_useactivityimage',
            'designer_subcourseuseactivityimage',
            'designer_displayprogress',
            'designer_subcoursedisplayprogress',
        ];

        foreach ($fields as $field) {
            if (!isset($data->$field)) {
                if (in_array($field, $preventfields)) {
                    $data->$field = false;
                } else {
                    continue;
                }
            }
            $record->name = str_replace('designer_', '', $field);
            if (is_array($data->$field)) {
                $data->$field = json_encode($data->$field);
            }
            $record->value = $data->$field ?? '';
            $record->timemodified = time();
            if ($exitrecord = $DB->get_record('format_designer_options',
                ['cmid' => $data->coursemodule, 'name' => $record->name])) {
                $record->id = $exitrecord->id;
                $record->timecreated = $exitrecord->timecreated;
                $DB->update_record('format_designer_options', $record);
            } else {
                $record->timecreated = time();
                $DB->insert_record('format_designer_options', $record);
            }
        }

        if (isset($data->designer_completionbackimage)) {
            local_designer_update_filesystem_modbg_image(
                $data->designer_completionbackimage,
                $record->cmid,
                'moduledesigncompletionbackimage'
            );
        }
        if (isset($data->designer_backimage)) {
            local_designer_update_filesystem_modbg_image($data->designer_backimage, $record->cmid, 'moduledesignbackground');
        }
    }
    return $data;
}

/**
 * Set up the definitions after data set to the form elements.
 *
 * @param moodle_modform $formwrapper
 * @param moodle_form $mform
 * @return void
 */
function local_designer_coursemodule_definition_after_data($formwrapper, $mform) {
    $cm = $formwrapper->get_coursemodule();
    $elements = ['backimage' => 'moduledesignbackground', 'completionbackimage' => 'moduledesigncompletionbackimage'];
    foreach ($elements as $name => $filearea) {
        $draftitemid = file_get_submitted_draft_itemid($name);
        if (!empty($cm)) {
            $context = context_module::instance($cm->id);
            file_prepare_draft_area($draftitemid, $context->id, 'local_designer', $filearea, 0);
        }
        $mform->setDefault('designer_'.$name, $draftitemid);
    }
}


/**
 * Save the module background image into file.
 * @param int $draftid
 * @param int $cmid coursemodule
 * @param string $filearea Filearea of the module.
 * @return void
 */
function local_designer_update_filesystem_modbg_image($draftid, $cmid, $filearea='') {
    $modulecontext = context_module::instance($cmid);
    file_save_draft_area_files($draftid, $modulecontext->id, 'local_designer', $filearea, 0,
        ['accepted_types' => 'images', 'maxfiles' => 1]);
}



/**
 * Serves file from moduledesignbackground_filearea
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function local_designer_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    require_login();
    $systemfiles = [LOCAL_DESIGNER_SECTION_MASK_AREA, LOCAL_DESIGNER_ACTIVITY_MASK_AREA];
    $coursefile = [
        LOCAL_DESIGNER_GROUP_DESC,
        LOCAL_DESIGNER_PREREQUISITEINFO_AREA,
        LOCAL_DESIGNER_ADDITIONAL_CONTENT_AREA,
        LOCAL_DESIGNER_COURSE_BG_IMAGE_FILE_AREA,
        LOCAL_DESIGNER_COURSE_HEADER_BG_IMAGE_FILE_AREA,
        LOCAL_DESIGNER_SECTIONCARD_CTA,
    ];

    if ($context->contextlevel != CONTEXT_MODULE
        && !($context->contextlevel == CONTEXT_COURSE && in_array($filearea, $coursefile))
        && !($context->contextlevel == CONTEXT_SYSTEM && in_array($filearea, $systemfiles))) {
        return false;
    }

    $area = ["moduledesignbackground", 'moduledesigncompletionbackimage', 'coursebgimage', 'courseheaderbgimage'];
    if (!is_null($cm)) {
        $area2 = "moduledesignbackground". $cm->id;
    }
    $area2 = "moduledesignbackground0"; // Previous file area.
    if (!in_array($filearea, $area) && !in_array($filearea, $systemfiles) && !in_array($filearea, $coursefile)) {
        return false;
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_designer', $filearea, $args[0], '/', $args[1]);
    if (!$file) {
        return false;
    }
    send_stored_file($file, 0, 0, 0, $options);
}


/**
 * Get designer module background imageurl.
 * @param mod_info $mod
 * @param int $itemid
 * @param stdclass $options
 * @return string $url Background image URL.
 */
function local_designer_get_module_bgimage($mod, $itemid, $options) {
    $filearea = "moduledesignbackground";
    if (\format_designer\options::is_mod_completed($mod) && $options->usecompletionbg) {
        $filearea = 'moduledesigncompletionbackimage';
    }
    $modulecontext = context_module::instance($mod->id);
    $files = get_file_storage()->get_area_files(
    $modulecontext->id, 'local_designer', $filearea, false, 'itemid, filepath, filename', false);
    if (empty($files)) {
        return '';
    }
    $file = current($files);
    $fileurl = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename(), false);
    return $fileurl->out(false);
}


/**
 * Definitions of the designer pro additional options that this course format uses for section.
 * @param bool $foreditform Is the options fetched for build the edit Form.
 * @return array
 */
function local_designer_get_pro_section_options($foreditform) {
    global $CFG;
    require_once($CFG->libdir."/formslib.php");
    require_once($CFG->dirroot.'/local/designer/form/element-colorpicker.php');
        MoodleQuickForm::registerElementType('designercolorpicker', $CFG->dirroot.'/local/designer/form/element-colorpicker.php',
            'moodlequickform_designercolorpicker');

    return (new local_designer\options())->get_section_options($foreditform);
}

/**
 * List of classes available for the layouts.
 *
 * @param object $section
 * @return string classes.
 */
function local_designer_layout_columnclasses($section) {

    $desktop = isset($section->layoutdesktopcolumn) ? $section->layoutdesktopcolumn : '';
    $tablet = isset($section->layouttabletcolumn) ? $section->layouttabletcolumn : '';
    $mobile = isset($section->layoutmobilecolumn) ? $section->layoutmobilecolumn : '';

    $layoutclass = [
        '1' => 'one-column',
        '2' => 'two-column',
        '3' => 'three-column',
        '4' => 'four-column',
        '5' => 'five-column',
    ];

    $classes[] = (isset($layoutclass[$desktop])) ? 'desktop-'.$layoutclass[$desktop] : '';
    $classes[] = (isset($layoutclass[$tablet])) ? 'tablet-'.$layoutclass[$tablet] : '';
    $classes[] = (isset($layoutclass[$mobile])) ? 'mobile-'.$layoutclass[$mobile] : '';
    return ' '.implode(' ', $classes);
}

/**
 * Update the pro data to old fields  table to new options table.
 *
 * @return void
 */
function local_designer_update_prodata() {
    global $DB, $USER;
    $dbman = $DB->get_manager();
    if (!$dbman->table_exists('local_designer_fields')) {
        return true;
    }

    $records = $DB->get_records('local_designer_fields');
    $options = [];
    foreach ($records as $key => $value) {
        $field = [
            'courseid' => $value->course,
            'cmid' => $value->cmid,
            'timecreated' => $value->timecreated,
            'timemodified' => $value->timemodified ?: $value->timecreated,
        ];
        if (!$DB->record_exists('format_designer_options', ['name' => 'backimage', 'cmid' => $value->cmid])) {

            // Move the previous activity files to new filearea.
            $filearea = 'moduledesignbackground';
            $modulecontext = context_module::instance($value->cmid);
            if (!empty($value->backimage)) {
                $file = local_designer_get_module_prevbgimage((object) ['id' => $value->cmid], $value->backimage);
            }

            if (isset($file) && !empty($file)) {
                $fs = get_file_storage();

                $draftid = file_get_unused_draft_itemid();
                $userdraft = [
                    'contextid' => context_user::instance($USER->id)->id,
                    'component' => 'user',
                    'filearea' => 'draft',
                    'itemid' => $draftid,
                    'filepath' => '/',
                    'filename' => $file->get_filename(),
                ];

                $fs->create_file_from_storedfile($userdraft, $file);
                local_designer_update_filesystem_modbg_image($draftid, $value->cmid, 'moduledesignbackground');
                $value->backimage = $draftid;
            }

            $options[] = array_merge($field, [
                'name' => 'backimage',
                'value' => $value->backimage,
            ]);
            $options[] = array_merge($field, [
                'name' => 'backgradient',
                'value' => $value->backgradient,
            ]);
            $options[] = array_merge($field, [
                'name' => 'textcolor',
                'value' => $value->textcolor,
            ]);
        }
    }
    if (!empty($options)) {
        $records = $DB->insert_records('format_designer_options', $options);
    }
}

/**
 * Move the module background images from previous filearea to new one.
 * Get designer previous module background image url.
 *
 * @param mod_info $mod
 * @param int $itemid
 * @return object $url Background image URL.
 */
function local_designer_get_module_prevbgimage($mod, $itemid) {
    $filearea = "moduledesignbackground" . $mod->id;
    $modulecontext = context_module::instance($mod->id);
    $files = get_file_storage()->get_area_files(
    $modulecontext->id, 'local_designer', $filearea, false, 'itemid, filepath, filename', false);
    if (empty($files)) {
        return '';
    }
    $file = current($files);
    $fileurl = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename(), false);
    return $file;
}

/**
 * Designer pro course format options list.
 *
 * @return array List of pro format options.
 */
function local_designer_course_format_options_list() {
    $courseformatoptions = [
        'courseprerequisites' => [
            'default' => get_string('courseprerequisites', 'format_designer'),
            'type' => PARAM_TEXT,
        ],
        'displaycourseprerequisites' => [
            'default' => 0,
            'type' => PARAM_INT,
        ],
        'courseprerequisitestabhead' => [
            'default' => '',
            'type' => PARAM_TEXT,
        ],
        'courseprerequisitepos' => [
            'default' => '1',
            'type' => PARAM_INT,
        ],
        'courseprerequisitestitle' => [
            'default' => '',
            'type' => PARAM_TEXT,
        ],
        'prerequisiteinfo' => [
            'default' => '',
            'type' => PARAM_RAW,
        ],
        'prerequisiteinfoformat' => [
            'default' => 0,
            'type' => PARAM_INT,
        ],
        'prerequisitesautostudents' => [
            'default' => 0,
            'type' => PARAM_INT,
        ],
        'prerequisitesunenrolstudents' => [
            'default' => 0,
            'type' => PARAM_INT,
        ],
        'prerequisitesgroupstudents' => [
            'default' => 0,
            'type' => PARAM_INT,
        ],
        'prerequisitesnewtab' => [
            'default' => 0,
            'type' => PARAM_INT,
        ],
        'prerequisitesbackmain' => [
            'default' => 1,
            'type' => PARAM_INT,
        ],
    ];
    return $courseformatoptions;
}

/**
 * Designer course format options list.
 *
 * @return array List of format options.
 */
function local_designer_course_format_options_editlist() {
    global $CFG;
    require_once($CFG->libdir.'/formslib.php');
    $posrange = array_combine(range(-10, 10), range(-10, 10));
    unset($posrange[0]);
    $courseformatoptionsedit = [
        'courseprerequisites' => [
            'label' => new lang_string('courseprerequisites', 'format_designer'),
            'element_type' => 'header',
        ],
        'displaycourseprerequisites' => [
            'label' => new lang_string('displaycourseprerequisites', 'format_designer'),
            'element_type' => 'select',
            'element_attributes' => [
                [
                    0 => new lang_string('disabled', 'format_designer'),
                    DESIGNER_PREREQUISITES_ABOVECOURSE => new lang_string('abovecoursecontents', 'format_designer'),
                    DESIGNER_PREREQUISITES_SEPARATETAB => new lang_string('onseparatetab', 'format_designer'),
                ],
            ],
            'help' => 'displaycourseprerequisites',
            'help_component' => 'format_designer',
        ],
        'courseprerequisitestabhead' => [
            'label' => new lang_string('courseprerequisitestabhead', 'format_designer'),
            'element_type' => 'text',
            'help' => 'courseprerequisitestabhead',
            'help_component' => 'format_designer',
            'hideif' => ['displaycourseprerequisites', 'neq', DESIGNER_PREREQUISITES_SEPARATETAB],
        ],
        'courseprerequisitepos' => [
            'label' => new lang_string('order'),
            'element_type' => 'select',
            'element_attributes' => [$posrange],
            'help' => 'courseprerequisitepos',
            'help_component' => 'format_designer',
            'hideif' => ['displaycourseprerequisites', 'neq', DESIGNER_PREREQUISITES_SEPARATETAB],
        ],
        'courseprerequisitestitle' => [
            'label' => new lang_string('courseprerequisitestitle', 'format_designer'),
            'element_type' => 'text',
            'help' => 'courseprerequisitestitle',
            'help_component' => 'format_designer',
        ],
        'prerequisiteinfo' => [
            'label' => new lang_string('strprerequisiteinfo', 'format_designer'),
            'element_type' => 'editor',
            'element_attributes' => [
                [
                    "rows" => "10",
                    "cols" => "50",
                ],
                [
                    "maxfiles" => -1,
                    "maxbytes" => $CFG->maxbytes,
                    'trusttext' => false,
                    'noclean' => true,
                    'enable_filemanagement' => true,
                ],
            ],
            'help' => 'strprerequisiteinfo',
            'help_component' => 'format_designer',
        ],
        'prerequisiteinfoformat' => [
            'element_type' => 'hidden',
            'label' => 'hidden',
        ],
        'prerequisitesautostudents' => [
            'label' => new lang_string('prerequisitesautostudents', 'format_designer'),
            'element_type' => 'select',
            'element_attributes' => [
                [
                    0 => new lang_string('never', 'format_designer'),
                    DESIGNER_PREREQUISITES_AUTOENROLL_ALREADY => new lang_string('autoenrolalready',
                        'format_designer'),
                    DESIGNER_PREREQUISITES_AUTOENROLL_ALWAYS => new lang_string('autoenrolalways',
                        'format_designer'),
                ],
            ],
            'help' => 'prerequisitesautostudents',
            'help_component' => 'format_designer',
        ],
        'prerequisitesunenrolstudents' => [
            'label' => new lang_string('prerequisitesunenrolstudents', 'format_designer'),
            'element_type' => 'select',
            'element_attributes' => [
                [
                    0 => new lang_string('disable'),
                    1 => new lang_string('enable'),
                ],
            ],
            'help' => 'prerequisitesunenrolstudents',
            'help_component' => 'format_designer',
        ],
        'prerequisitesgroupstudents' => [
            'label' => new lang_string('prerequisitesgroupstudents', 'format_designer'),
            'element_type' => 'select',
            'element_attributes' => [
                [
                    0 => new lang_string('disable'),
                    1 => new lang_string('enable'),
                ],
            ],
            'help' => 'prerequisitesgroupstudents',
            'help_component' => 'format_designer',
        ],
        'prerequisitesnewtab' => [
            'label' => new lang_string('prerequisitesnewtab', 'format_designer'),
            'element_type' => 'select',
            'element_attributes' => [
                [
                    0 => new lang_string('disable'),
                    1 => new lang_string('enable'),
                ],
            ],
            'help' => 'prerequisitesnewtab',
            'help_component' => 'format_designer',
        ],
        'prerequisitesbackmain' => [
            'label' => new lang_string('prerequisitesbackmain', 'format_designer'),
            'element_type' => 'select',
            'element_attributes' => [
                [
                    0 => new lang_string('disable'),
                    1 => new lang_string('enable'),
                ],
            ],
            'help' => 'prerequisitesbackmain',
            'help_component' => 'format_designer',
        ],
    ];
    return $courseformatoptionsedit;
}

/**
 * Get or check the prerequisites courses for current course.
 * @param object $course
 * @param bool $check
 * @return array|bool
 */
function local_designer_is_prerequisites_courses($course, $check = true) {
    $prerequisitescourses = [];
    $completion = new completion_info($course);
    if ($completion->is_enabled()) {
        $criterias = $completion->get_criteria();
        if ($criterias) {
            foreach ($criterias as $criteria) {
                if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_COURSE) {
                    if ($check) {
                         return true;
                    }
                    $prerequisitescourses[] = $criteria->courseinstance;
                }
            }
        }
    }
    if (!$check) {
        return $prerequisitescourses;
    }
    return false;
}

/**
 * Enroll to the prerequisites for current course users.
 * @param object $course
 * @param object|null $user
 * @return void
 */
function local_designer_prerequisites_autoenrol($course, $user = null) {
    $coursecontext = context_course::instance($course->id);
    $groupid = 0;
    if ($course->prerequisitesautostudents) {
        $precourses = local_designer_is_prerequisites_courses($course, false);
        if (!empty($precourses)) {
            $roleid = get_config('local_designer', 'prerequisites_role');
            foreach ($precourses as $precourseid) {
                $precourse = get_course($precourseid);
                if ($course->prerequisitesgroupstudents) {
                    $groupid = local_designer_create_maingroup($precourse, $course->shortname);
                }
                // Only if not already enrolled.
                if ($course->prerequisitesautostudents == DESIGNER_PREREQUISITES_AUTOENROLL_ALREADY) {
                    local_designer_enrol_to_prerequisites_course($precourseid, $roleid, $user, $coursecontext, $groupid, true);
                } else if ($course->prerequisitesautostudents == DESIGNER_PREREQUISITES_AUTOENROLL_ALWAYS) { // Always.
                    local_designer_enrol_to_prerequisites_course($precourseid, $roleid, $user, $coursecontext, $groupid);
                }
            }
        }
    }
}

/**
 * Create group for prerequisites main group
 * @param object $course
 * @param string $groupname
 * @return int groupid
 */
function local_designer_create_maingroup($course, $groupname) {
    global $DB, $CFG;
    require_once($CFG->dirroot. "/group/lib.php");
    $groupidnumer = $groupname . '_prerequisites';
    if ($pregroup = $DB->get_record('groups', ['courseid' => $course->id, 'idnumber' => $groupidnumer])) {
        return $pregroup->id;
    } else {
        $group = new stdClass();
        $group->name = $groupname;
        $group->courseid = $course->id;
        $group->idnumber = $groupidnumer;
        $group->description = '';
        return groups_create_group($group);
    }
}

/**
 * Enroll to prerequisites courses and assign to groups
 * @param int $courseid
 * @param int $roleid
 * @param object $user
 * @param object $coursecontext
 * @param int $groupid
 * @param bool $checkalreadyenrol
 * @return void
 */
function local_designer_enrol_to_prerequisites_course($courseid, $roleid, $user, $coursecontext,
    $groupid, $checkalreadyenrol = false) {
    global $USER, $DB;
    $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual']);
    $enrolmanual = enrol_get_plugin('manual');
    if ($enrolmanual &&  !empty($instance)) {
        if ($enrolmanual->allow_enrol($instance)) {
            if ($user == null) {
                $users = get_users_by_capability($coursecontext, 'local/designer:autoenrolcontext');
                if ($users) {
                    foreach ($users as $user) {
                        if ($checkalreadyenrol) {
                            if ($DB->record_exists('user_enrolments', ['enrolid' => $instance->id, 'userid' => $user->id])) {
                                continue;
                            }
                        }
                        $enrolmanual->enrol_user($instance, $user->id, $roleid);
                        if ($groupid) {
                            groups_add_member($groupid, $user->id);
                        }
                    }
                }
            } else {
                if (has_capability("local/designer:autoenrolcontext", $coursecontext, $user)) {
                    if ($checkalreadyenrol) {
                        if ($DB->record_exists('user_enrolments', ['enrolid' => $instance->id, 'userid' => $user->id])) {
                            return;
                        }
                    }
                    $enrolmanual->enrol_user($instance, $user->id, $roleid);
                    if ($groupid) {
                        groups_add_member($groupid, $user->id);
                    }
                }
            }
        }
    }
}

/**
 * Get user recent accessed courses.
 * @param int $userid
 * @return object courses.
 */
function local_designer_get_recent_courses(int $userid = null) {
    global $USER, $DB;
    if (empty($userid)) {
        $userid = $USER->id;
    }
    $records = $DB->get_records('user_lastaccess', ['userid' => $userid], 'timeaccess DESC', 'courseid');
    return $records;
}

/**
 * This function extends the navigation with prerequisites courses.
 * @param navigation_node $coursenode The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $coursecontext The context of the course
 * @return void
 */
function local_designer_extend_navigation_course($coursenode, $course, $coursecontext) {
    global $PAGE;
    if ($PAGE->context->contextlevel == CONTEXT_COURSE || $PAGE->context->contextlevel == CONTEXT_MODULE) {
        $course = course_get_format($PAGE->course->id)->get_course();
        if ($course->format == 'designer') {
            // Check the prerequisites courses.
            if (local_designer_is_prerequisites_courses($course)) {
                if ($course->displaycourseprerequisites == DESIGNER_PREREQUISITES_SEPARATETAB) {
                    $prerequisitestitle = !empty($course->courseprerequisitestabhead) ?
                        $course->courseprerequisitestabhead : get_string('strprerequisites', 'format_designer');
                    $url = new moodle_url('/local/designer/prerequisites.php', ['id' => $course->id]);
                    $node = navigation_node::create($prerequisitestitle,
                    $url,
                    navigation_node::TYPE_SETTING, null, 'prerequisites');
                    $node->make_inactive();
                    $node->add_class('prerequisites-course');
                    $coursenode->add_node($node);
                }
            }
            // Check current course maincourse or not.
            if ($course->prerequisitesbackmain
                && $maincourse = local_designer_is_prerequisites_maincourse($course)) {
                $url = new moodle_url('/course/view.php', ['id' => $maincourse->id]);
                $node = navigation_node::create(get_string('backtomaincourse', 'format_designer'),
                $url,
                navigation_node::TYPE_SETTING, null, 'backtomaincourse');
                $node->make_inactive();
                $node->add_class('backmain-course');
                $coursenode->add_node($node);
            }

            if (has_capability("local/designer:manageprerequisitesgroup", $coursecontext)) {
                $url = new moodle_url('/local/designer/pregrouplist.php', ['courseid' => $course->id]);
                $settingsnode = navigation_node::create(get_string('listprereqgroup', 'format_designer'), $url,
                    navigation_node::TYPE_SETTING, null, null, new pix_icon('i/users', ''));
                $coursenode->add_node($settingsnode);
            }
        }
    }
}

/**
 * Get main course for prerequisites courses.
 * @param object $course
 * @return object course.
 */
function local_designer_is_prerequisites_maincourse($course) {
    global $DB;
    $maincourses = $DB->get_records('course_completion_criteria', [
        'criteriatype' => COMPLETION_CRITERIA_TYPE_COURSE,
        'courseinstance' => $course->id,
    ], '', 'course');
    $maincourses = array_keys($maincourses);
    if ($maincourses) {
        $courses = local_designer_get_recent_courses();
        if ($courses) {
            foreach ($courses as $course) {
                if (in_array($course->courseid, $maincourses)) {
                    return get_course($course->courseid);
                }
            }
        }
        rsort($maincourses);
        $maincourse = current($maincourses);
        return get_course($maincourse);
    }
    return false;
}

/**
 * Check the user hase enroll the all main courses for prerequisites course.
 *
 * @param object $course course data.
 * @param int $userid user id.
 * @return object course.
 */
function local_designer_is_user_enrolled_maincourses($course, $userid) {
    global $DB;
    $maincourses = $DB->get_records('course_completion_criteria', ['criteriatype' => COMPLETION_CRITERIA_TYPE_COURSE,
        'courseinstance' => $course->id, ], '', 'course');
    foreach ($maincourses as $maincourse) {
        $context = context_course::instance($maincourse->course);
        if (is_enrolled($context, $userid)) {
            return true;
        } else {
            return false;
        }
    }
    return true;
}

/**
 * Get template data the prerequisites for course.
 * @param object $course
 * @return array template context.
 */
function local_designer_import_prerequisites_courses($course) {
    global $OUTPUT, $CFG, $PAGE, $DB;
    require_once($CFG->dirroot."/course/classes/external/course_summary_exporter.php");
    $templatecontext = [];
    $nongroupcourses = [];
    $groupcourseinfo = [];
    $nongroupcourseinfo = [];
    $groupsinfo = local_designer_get_group_info($course->id);
    $nongroupcourses = $DB->get_field('course_format_options', 'value', [
        'courseid' => $course->id,
        'name' => 'prerequisitecourses',
    ]);
    $coursecontext = context_course::instance($course->id);
    $nongroupcourses = !empty($nongroupcourses) ? explode(",", $nongroupcourses) : [];
    if (local_designer_is_prerequisites_courses($course)) {
        // Check for non group courses.
        if (!empty($nongroupcourses)) {
            foreach ($nongroupcourses as $courseid) {
                $list = local_designer_get_course_info($courseid);
                $list->group = false;
                $nongroupcourseinfo[] = $list;
            }
        }
        // Check for group courses.
        if (!empty($groupsinfo)) {
            foreach ($groupsinfo as $groupinfo) {
                if (!empty($groupinfo)) {
                    $list = new stdClass();
                    $groupcourses = [];
                    if (!empty($groupinfo['courses'])) {
                        foreach ($groupinfo['courses'] as $courseid) {
                            $groupcourses[] = local_designer_get_course_info($courseid);
                        }
                    }
                    $list->groupid = $groupinfo['id'];
                    $list->groupname = $groupinfo['name'];
                    $list->groupintro = $groupinfo['descripition'];
                    $list->courses = $groupcourses;
                    $list->hascourse = !empty($groupcourses) ? true : false;
                    $groupcourseinfo[] = $list;
                }
            }
        }
        $templatecontext['prerequisitecollapse'] = ($course->coursetype == DESIGNER_TYPE_FLOW ||
            $course->coursetype == DESIGNER_TYPE_COLLAPSIBLE) ? true : false;
        $templatecontext['nongroupcourseinfo'] = $nongroupcourseinfo;
        $templatecontext['groupcourseinfo'] = $groupcourseinfo;
        $templatecontext['prerequisitesnewtab'] = $course->prerequisitesnewtab;
        $templatecontext['prerequisitestitle'] = !empty($course->courseprerequisitestitle) ?
        $course->courseprerequisitestitle : get_string('strprerequisites', 'format_designer');
        $context = context_course::instance($course->id);
        $prerequisiteinfo = $DB->get_field('course_format_options', 'value', [
                        'courseid' => $course->id, 'name' => 'prerequisiteinfo', 'format' => 'designer', ]);
        $prerequisiteinfoformat = $DB->get_field('course_format_options', 'value', ['courseid' => $course->id,
                        'name' => 'prerequisiteinfoformat', 'format' => 'designer', ]);
        $prerequisiteinfo = file_rewrite_pluginfile_urls($prerequisiteinfo, 'pluginfile.php',
            $context->id, 'local_designer', 'prerequisiteinfo', 0);

        $prerequisiteinfo = format_text($prerequisiteinfo, $prerequisiteinfoformat);

        $templatecontext['prerequisiteinfo'] = $prerequisiteinfo;
        $presectionhead = new moodle_url('/course/view.php', ['id' => $course->id]);
        $templatecontext['presectionhead'] = $presectionhead->out(false);
        $templatecontext['prerequisites'] = true;
        if ($PAGE->user_is_editing() && has_capability('local/designer:orderprerequisites', $coursecontext)) {
            $PAGE->requires->strings_for_js([
                'move_item',
            ], 'format_designer');
            $PAGE->requires->yui_module('moodle-local_designer-dragdrop', 'M.local_designer.init_dragdrop',
                [['courseid' => $course->id]]);
        }
    }
    return $templatecontext;
}

/**
 * Get course data.
 * @param int $courseid
 * @return array data
 */
function local_designer_get_course_info($courseid) {
    global $PAGE;
    $precourse = get_course($courseid);
    $context = context_course::instance($precourse->id);
    $exporter = new course_summary_exporter($precourse, ['context' => $context]);
    $list = $exporter->export($PAGE->get_renderer('core'));
    $list->progress = local_designer_course_enhanced_progress($precourse);
    $list->courseid = $precourse->id;
    $list->coursename = $precourse->shortname;
    return $list;
}



/**
 * Unenrol the prerequisites course when user unrol for maincourse.
 * @param object $course
 * @param object $user
 * @return void
 */
function local_designer_prerequisites_unenrol($course, $user) {
    global $DB;
    $enrolmanual = enrol_get_plugin('manual');
    if (local_designer_is_prerequisites_courses($course) && $enrolmanual) {
        $precourses = local_designer_is_prerequisites_courses($course, false);
        if (!empty($precourses)) {
            foreach ($precourses as $courseid) {
                $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual']);
                if ($instance) {
                    $enrolmanual->unenrol_user($instance, $user->id);
                }
            }
        }
    }
}

/**
 * Update the prerequisitecourse using ajax
 * @param string $itemorder
 * @param int $courseid
 * @param int $groupid
 * @return string data
 */
function local_designer_ajax_saveitemorder($itemorder, $courseid, $groupid) {
    global $DB;
    $result = true;
    $position = 0;
    if ($itemorder) {
        if ($groupid) {
            $result && $DB->set_field('local_designer_pregroups',
                                    'coursesorder',
                                    $itemorder,
                                    [
                                        'id' => $groupid,
                                    ]
            );
        } else {
            $result = $result && $DB->set_field('course_format_options',
                                                'value',
                                                $itemorder,
                                                [
                                                    'name' => "prerequisitecourses",
                                                    'format' => "designer",
                                                    'courseid' => $courseid,
                                                ]);
        }
    }
    return $result;
}

/**
 * Returns the groupid of a group with the name specified for the course.
 * Group names should be unique in course
 *
 * @category group
 * @param int $courseid The id of the course
 * @param string $name name of group (without magic quotes)
 * @return int $groupid
 */
function local_designer_get_pregroup_by_name($courseid, $name) {
    $data = local_designer_get_course_data($courseid);
    foreach ($data->prerequisitegroups as $group) {
        if ($group->name == $name) {
            return $group->id;
        }
    }
    return false;
}


/**
 * Get course enhanced progress for current course.
 * @param object $course
 * @return int progress
 */
function local_designer_course_enhanced_progress($course) {
    global $DB, $CFG, $USER;
    require_once($CFG->dirroot.'/mod/lesson/locallib.php');
    $completion = new \completion_info($course);
    // First, let's make sure completion is enabled.
    if (!$completion->is_enabled()) {
        return null;
    }
    $result = [];
    // Get the number of modules that support completion.
    $modules = $completion->get_activities();
    $progress = 0;
    if ($modules) {
        foreach ($modules as $mod) {
            if ($mod->modname == 'lesson') {
                $lesson = new lesson($DB->get_record('lesson', ['id' => $mod->instance],
                    '*', MUST_EXIST), $mod, $mod->get_course());
                $lessontimer = $lesson->get_user_timers($USER->id, 'id DESC', '*', 0, 1);
                $userlessontimer = current($lessontimer);
                $lessonprogress = 0;
                if (!empty($lesson->load_all_pages())) {
                    $lessonprogress = (int) $lesson->calculate_progress();
                    if (!empty($userlessontimer)) {
                        if ($userlessontimer->completed) {
                            $lessonprogress = 100;
                        }
                    }
                }
                $progress += $lessonprogress;
            } else {
                $data = $completion->get_data($mod);
                $progress += $data->completionstate == COMPLETION_INCOMPLETE ? 0 : 100;
            }
        }
        $progress = $progress / count($modules);
        $progress = ($progress) != null ? round($progress) : 0;
    }
    return $progress;
}




/**
 * Gets group data for a course.
 *
 * This returns an object with the following properties:
 *   - groups : An array of all the groups in the course.
 *   - groupings : An array of all the groupings within the course.
 *   - mappings : An array of group to grouping mappings.
 *
 * @param int $courseid The course id to get data for.
 * @param cache $cache The cache if it has already been initialised. If not a new one will be created.
 * @return stdClass
 */
function local_designer_get_course_data($courseid, cache $cache = null) {
    if ($cache === null) {
        // Initialise a cache if we wern't given one.
        $cache = cache::make('local_designer', 'pregroupdata');
    }
    // Try to retrieve it from the cache.
    $data = $cache->get($courseid);
    if ($data === false) {
        $data = local_designer_cache_groupdata($courseid, $cache);
    }
    return $data;
}

/**
 * Caches group data for a particular course to speed up subsequent requests.
 *
 * @param int $courseid The course id to cache data for.
 * @param cache $cache The cache if it has already been initialised. If not a new one will be created.
 * @return stdClass A data object containing groups, groupings, and mappings.
 */
function local_designer_cache_groupdata($courseid, cache $cache = null) {
    global $DB;

    if ($cache === null) {
        // Initialise a cache if we wern't given one.
        $cache = cache::make('local_designer', 'pregroupdata');
    }

    // Get the groups that belong to the course.
    $groups = $DB->get_records('local_designer_pregroups', ['courseid' => $courseid], 'name ASC');

    if (!is_array($groups)) {
        $groups = [];
    }

    // Prepare the data array.
    $data = new stdClass;
    $data->prerequisitegroups = $groups;
    // Cache the data.
    $cache->set($courseid, $data);
    // Finally return it so it can be used if desired.
    return $data;
}

/**
 * Returns the groupid of a group with the idnumber specified for the course.
 * Group idnumbers should be unique within course
 *
 * @category group
 * @param int $courseid The id of the course
 * @param string $idnumber idnumber of group
 * @return group object
 */
function local_designer_get_group_by_idnumber($courseid, $idnumber) {
    if (empty($idnumber)) {
        return false;
    }
    $data = local_designer_get_course_data($courseid);
    foreach ($data->prerequisitegroups as $group) {
        if ($group->idnumber == $idnumber) {
            return $group;
        }
    }
    return false;
}


/**
 * Add a new group
 *
 * @param stdClass $data group properties
 * @param stdClass $editform
 * @param array $editoroptions
 * @return id of group or false if error
 */
function local_designer_create_group($data, $editform = false, $editoroptions = false) {
    global $CFG, $DB, $USER;
    // Check that courseid exists.
    $course = $DB->get_record('course', ['id' => $data->courseid], '*', MUST_EXIST);
    $context = context_course::instance($course->id);

    $data->timecreated  = time();
    $data->timemodified = $data->timecreated;
    $data->name         = trim($data->name);
    if (isset($data->idnumber)) {
        $data->idnumber = trim($data->idnumber);
        if (local_designer_get_group_by_idnumber($course->id, $data->idnumber)) {
            throw new moodle_exception('idnumbertaken');
        }
    }

    if ($editform && $editoroptions) {
        $data->description = $data->description_editor['text'];
        $data->descriptionformat = $data->description_editor['format'];
    }
    $data->coursesorder = implode(",", $data->precourses);
    $data->id = $DB->insert_record('local_designer_pregroups', $data);

    // Assign courses to groups.
    local_designer_precourses_assign_group($data->id, $data->precourses);

    if ($editform && $editoroptions) {
        // Update description from editor with fixed files.
        $data = file_postupdate_standard_editor($data, 'description', $editoroptions, $context,
            'local_designer', 'description', $data->id);
        $upd = new stdClass();
        $upd->id                = $data->id;
        $upd->description       = $data->description;
        $upd->descriptionformat = $data->descriptionformat;
        $DB->update_record('local_designer_pregroups', $upd);
    }

    $group = $DB->get_record('local_designer_pregroups', ['id' => $data->id]);

    // Invalidate the grouping cache for the course.
    cache_helper::invalidate_by_definition('local_designer', 'pregroupdata', [], [$course->id]);

    return $group->id;
}

/**
 * Update group.
 *
 * @param stdClass $data group properties (with magic quotes)
 * @param stdClass $editform
 * @param array $editoroptions
 * @return bool true or exception
 */
function local_designer_update_group($data, $editform = false, $editoroptions = false) {
    global $CFG, $DB, $USER;

    $context = context_course::instance($data->courseid);

    $data->timemodified = time();
    if (isset($data->name)) {
        $data->name = trim($data->name);
    }
    if (isset($data->idnumber)) {
        $data->idnumber = trim($data->idnumber);
        if (($existing = groups_get_group_by_idnumber($data->courseid, $data->idnumber))
            && $existing->id != $data->id) {
            throw new moodle_exception('idnumbertaken');
        }
    }

    if ($editform && $editoroptions) {
        $data = file_postupdate_standard_editor($data, 'description', $editoroptions, $context,
            'local_designer', 'description', $data->id);
    }
    // Delete records for current group.
    $DB->delete_records('local_designer_groupcourses', ['pregroupid' => $data->id]);
    // Assign courses to groups.
    local_designer_precourses_assign_group($data->id, $data->precourses);
    $existgroupcourses = explode(",", $DB->get_field('local_designer_pregroups', 'coursesorder', ['id' => $data->id]));
    // Addeing the new course.
    if ($addgroupcourses = array_diff($data->precourses, $existgroupcourses)) {
        $existgroupcourses = array_merge($existgroupcourses, $addgroupcourses);
    }

    // Removeing the exist one.
    if ($removegroupcourses = array_diff( $existgroupcourses, $data->precourses)) {
        $existgroupcourses = array_diff($existgroupcourses, $removegroupcourses);
    }

    $data->coursesorder = implode(",", $existgroupcourses);

    $DB->update_record('local_designer_pregroups', $data);

    // Invalidate the group data.
    cache_helper::invalidate_by_definition('local_designer', 'pregroupdata', [], [$data->courseid]);

    return true;
}

/**
 * Get course prerequisite groups.
 * @param int $courseid
 * @return array list
 */
function local_designer_get_group_info($courseid) {
    global $DB;
    $data = [];
    $groups = $DB->get_records('local_designer_pregroups', ['courseid' => $courseid]);
    $context = context_course::instance($courseid);
    if ($groups) {
        foreach ($groups as $group) {
            $data[$group->id] = [
                'id' => $group->id,
                'name' => $group->name,
                'descripition' => format_text(file_rewrite_pluginfile_urls($group->description,
                                        'pluginfile.php',
                                        $context->id,
                                        'local_designer',
                                        'description',
                                        $group->id), $group->descriptionformat),
                'courses' => !empty($group->coursesorder) ? explode(",", $group->coursesorder) : [],
            ];
        }
    }
    return $data;
}

/**
 * Assign prerequisites course into groups.
 * @param int $groupid
 * @param array $precourses
 * @return void
 */
function local_designer_precourses_assign_group($groupid, $precourses) {
    global $DB;
    if (!empty($precourses)) {
        foreach ($precourses as $precourseid) {
            if (!$DB->record_exists('local_designer_groupcourses', ['courseid' => $precourseid,
                'pregroupid' => $groupid, ])) {
                    $record = new stdClass();
                    $record->pregroupid = $groupid;
                    $record->courseid = $precourseid;
                    $record->timeadded = time();
                    $DB->insert_record('local_designer_groupcourses', $record);
            }
        }
    }
}

/**
 * Get group courses
 * @param int $groupid
 * @return object record
 */
function local_designer_get_pregroup_courses($groupid) {
    global $DB;
    $records = $DB->get_records_list('local_designer_groupcourses', 'pregroupid', [$groupid], '', 'courseid');
    return  array_keys($records);
}

/**
 * Get course groups
 * @param int $courseid
 * @return object record
 */
function local_designer_get_pregroups($courseid) {
    global $DB;
    $records = $DB->get_records_list('local_designer_pregroups', 'courseid', [$courseid], '', 'id');
    return  array_keys($records);
}

/**
 * Remove the course clear criteria.
 * @param object $course
 */
function local_designer_clear_criteria_precourses($course) {
    global $DB;
    $params = [
        'pattern1' => "%".$course->id.",%",
        'pattern2' => "%,".$course->id."%",
        'iden' => 'prerequisitecourses',
    ];
    $sql = "SELECT * FROM {course_format_options} cfo
         WHERE cfo.name = :iden AND cfo.value LIKE :pattern1 OR cfo.value LIKE :pattern2";
    $relatedinstance = $DB->get_records_sql($sql, $params);
    if (!empty($relatedinstance)) {
        foreach ($relatedinstance as $instance) {
            $precourses = explode(",", $instance->value);
            if (($key = array_search($course->id, $precourses)) !== false) {
                unset($precourses[$key]);
            }
            $instance->value = implode(",", $precourses);
            $DB->update_record('course_format_options', $instance);

        }
    }
}

/**
 * Remove the course clear criteria.
 * @param object $course
 */
function local_designer_clear_criteria_groupcourses($course) {
    global $DB;
    $relatedinfo = $DB->get_records('local_designer_groupcourses', ['courseid' => $course->id]);
    if (!empty($relatedinfo)) {
        foreach ($relatedinfo as $info) {
            if ($groupsorder = $DB->get_record('local_designer_pregroups',  ['id' => $info->pregroupid])) {
                $groupcourses = explode(",", $groupsorder->coursesorder);
                if (($key = array_search($course->id, $groupcourses)) !== false) {
                    unset($groupcourses[$key]);
                }
                $groupsorder->coursesorder = implode(",", $groupcourses);
                $DB->update_record('local_designer_pregroups', $groupsorder);
            }
        }
        $DB->delete_records('local_designer_groupcourses', ['courseid' => $course->id]);
    }
}

/**
 * Remove the exist prerequisite courses.
 * @param object $course
 * @return void
 */
function local_designer_remove_exist_precourses($course) {
    global $DB;
    $existrecord = $DB->get_record('course_format_options', ['courseid' => $course->id,
                        'name' => 'prerequisitecourses', 'format' => 'designer', ]);
    if ($existrecord) {
        $existrecord->value = "";
        $DB->update_record('course_format_options', $existrecord);
    }
}

/**
 * Get the course to prerequisites for non assign groups.
 * @param object $course
 * @return array
 */
function local_designer_is_nongroup_assign_precourses($course) {
    global $DB;
    $data = [];
    $precourses = local_designer_is_prerequisites_courses($course, false);
    $precourses = array_flip($precourses);
    $groups = local_designer_get_pregroups($course->id);
    if ($precourses && !empty($groups)) {
        list($groupsql, $groupparam) = $DB->get_in_or_equal($groups, SQL_PARAMS_NAMED);
        foreach ($precourses as $precourse => $value) {
            $groupparam['courseid'] = $precourse;
            // Check the precourse exist in group courses.
            // If exist course remove the prerequisites courses.
            $sql = "SELECT * FROM {local_designer_groupcourses} WHERE courseid = :courseid AND pregroupid $groupsql";
            if ($DB->record_exists_sql($sql, $groupparam)) {
                unset($precourses[$precourse]);
            }
        }
    }
    return $precourses;
}

/**
 * Delete a group best effort, first removing members and links with courses and groupings.
 * Removes group avatar too.
 *
 * @param mixed $grouporid The id of group to delete or full group object
 * @param int $courseid
 * @return bool True if deletion was successful, false otherwise
 */
function local_designer_delete_group($grouporid, $courseid) {
    global $CFG, $DB;
    require_once("$CFG->libdir/gdlib.php");

    if (is_object($grouporid)) {
        $groupid = $grouporid->id;
        $group   = $grouporid;
    } else {
        $groupid = $grouporid;
        if (!$group = $DB->get_record('local_designer_pregroups', ['id' => $groupid])) {
            // Silently ignore attempts to delete missing already deleted groups ;-).
            return true;
        }
    }

    $context = context_course::instance($courseid);

    $groupcourses = $DB->get_records('local_designer_groupcourses', ['pregroupid' => $groupid], '', 'courseid');
    local_designer_add_remove_prerequisites_courses(array_keys($groupcourses), $courseid, false);

    // Delete members.
    $DB->delete_records('local_designer_groupcourses', ['pregroupid' => $groupid]);

    // Group itself last.
    $DB->delete_records('local_designer_pregroups', ['id' => $groupid]);

    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'local_designer', 'description', $groupid);

    // Invalidate the grouping cache for the course.
    cache_helper::invalidate_by_definition('local_designer', 'pregroupdata', [], [$group->courseid]);
    return true;
}

/**
 * Display group courses.
 * @param int $groupid
 * @return string
 */
function local_designer_group_coursescontent($groupid) {
    $courses = local_designer_group_courses($groupid);
    $fullnames = array_column($courses, 'fullname');
    $content = '';
    if (count($fullnames) > 3) {
        $show = array_slice($fullnames, 0, 3);
        $more = array_slice($fullnames, 3);
        $content .= implode(',', $show);
        $content .= "<details><summary>" . get_string('more', 'format_designer', count($more)) . "</summary>";
        $content .= implode(',', $more). "</details>";
    } else {
        $content .= implode(',', $fullnames);
    }
    return $content;

}

/**
 * Get group courses info.
 * @param int $groupid
 * @return object record
 */
function local_designer_group_courses($groupid) {
    global $DB;
    $courseids = [];
    $courses = $DB->get_records('local_designer_groupcourses', ['pregroupid' => $groupid], '', 'courseid');
    if (!empty($courses)) {
        $courseids = array_keys($courses);
        list($sqlcourseids, $params) = $DB->get_in_or_equal(array_unique($courseids), SQL_PARAMS_NAMED);
        $sql = "SELECT * FROM {course} WHERE id $sqlcourseids";
        return $DB->get_records_sql($sql, $params);
    }
    return $courseids;
}

/**
 * Add/Remove to the prerequisites_courses.
 * @param array $groupcourses
 * @param int $courseid
 * @param bool $remove
 * @return void
 */
function local_designer_add_remove_prerequisites_courses($groupcourses, $courseid, $remove = true) {
    global $DB;
    if (!empty($groupcourses)) {
        $precourses = $DB->get_field('course_format_options',
                                                'value',
                                                [
                                                    'name' => "prerequisitecourses",
                                                    'format' => "designer",
                                                    'courseid' => $courseid,
                                                ]);
        $precourses = !empty($precourses) ? explode(",", $precourses) : [];
        if ($remove) {
            foreach ($groupcourses as $course) {
                if (($key = array_search($course, $precourses)) !== false) {
                    unset($precourses[$key]);
                }
            }
        } else {
            foreach ($groupcourses as $course) {
                $precourses[] = $course;
            }
        }
        $itemorder = implode(",", $precourses);
        $DB->set_field('course_format_options',
            'value',
            $itemorder,
            [
                'name' => "prerequisitecourses",
                'courseid' => $courseid,
            ]);
    }
}

/**
 * Update courses to group.
 * @param object $data
 * @param int $courseid
 * @return void
 */
function local_designer_update_group_courses($data, $courseid) {
    global $DB;
    $groupcourses = local_designer_get_pregroup_courses($data->id);
    $precourses = $DB->get_field('course_format_options', 'value', [
                            'name' => "prerequisitecourses",
                            'format' => "designer",
                            'courseid' => $courseid,
                ]);
    $precourses = !empty($precourses) ? explode(",", $precourses) : [];
    if (!empty($groupcourses)) {
        foreach ($groupcourses as $groupcourse) {
            if (!in_array($groupcourse, $data->precourses)) {
                $precourses[] = $groupcourse;
            }
        }
    }
    $itemorder = implode(",", $precourses);
    $DB->set_field('course_format_options', 'value', $itemorder, [
            'name' => "prerequisitecourses",
            'courseid' => $courseid,
        ]);
}


/**
 * Inlcude the Course background styles to the page.
 *
 * @param moodle_page $page return the css to the page.
 * @return void
 */
function local_designer_include_style($page) {
    global $PAGE;
    // Build the CSS file URL.
    $includestyle = new \moodle_url('/local/designer/styles.php', ['id' => $page->course->id, 'rev' => theme_get_revision()]);
    $page->requires->css($includestyle);
}

/**
 * Fetches the list of icons and creates an icon suggestion list to be sent to a fragment.
 *
 * @param array $args An array of arguments.
 *
 * @return string The rendered HTML of the icon suggestion list.
 */
function local_designer_output_fragment_icons_list($args) {
    global $OUTPUT, $PAGE;

    // Proceed only if a context was given as argument.
    if ($args['context']) {
        // Initialize rendered icon list.
        $icons = [];
        // Load the theme config.
        $theme = \theme_config::load($PAGE->theme->name);
        // Get the FA system.
        $faiconsystem = \core\output\icon_system_fontawesome::instance($theme->get_icon_system());
        // Get the icon list.
        $iconlist = $faiconsystem->get_core_icon_map();
        // Add an empty element to the beginning of the icon list.
        array_unshift($iconlist, '');
        // Iterate over the icons.
        foreach ($iconlist as $iconkey => $icontxt) {
            // Split the component from the icon key.
            $icon = explode(':', $iconkey);
            // Pick the icon key.
            $iconstr = isset($icon[1]) ? $icon[1] : 'moodle';
            // Pick the component.
            $component = isset($icon[0]) ? $icon[0] : '';
            // Render the pix icon.
            $icon = new \pix_icon($iconstr,  "", $component);
            $icons[] = [
                'icon' => $faiconsystem->render_pix_icon($OUTPUT, $icon),
                'value' => $iconkey,
                'label' => $icontxt,
            ];
        }
        // Return the rendered icon list.
        return $OUTPUT->render_from_template('local_designer/fontawesome-iconpicker-popover', ['options' => $icons]);
    }
}
