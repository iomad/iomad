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
 * File contains  course background and course header options definition of designer pro.
 *
 * @package   local_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_designer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/lib.php');
require_once("$CFG->libdir/formslib.php");

use lang_string;
use context_course;
use editor_tiny\lang;
use html_writer;
use moodle_url;
use stdClass;

/**
 * Class defines course background and Course header options for designer pro.
 */
class courseoptions {

    /**
     * Course format class instance
     *
     * @var \format_designer
     */
    public $format;

    /**
     * Course record.
     *
     * @var \stdclass
     */
    public $course;

    /**
     * User data record.
     *
     * @var object
     */
    protected $user = null;

    /**
     * Local designer info class instance.
     *
     * @var local_designer\info
     */
    protected $info;


    /**
     * Course header summary disabled option value.
     */
    const COURSEHEADER_SUMMARYDISABLED = 0;

    /**
     * Course header summary trimmed option value.
     */
    const COURSEHEADER_SUMMARYTRIMMED = 1;

    /**
     * Course header summary full option value.
     */
    const COURSEHEADER_SUMMARYFULL = 2;

    /**
     * Setup course, completioninfo and formats.
     *
     * @param stdclass $course
     */
    public function __construct($course) {
        global $USER;
        $this->format = course_get_format($course);
        $this->course = $this->format->get_course();
        $this->user = $USER;
        $this->info = info::create();
    }

    /**
     * Create instance of course options class.
     *
     * @param stdclass $course
     * @return self
     */
    public static function create($course) {
        static $instance;
        if ($instance == null) {
            $instance = new self($course);
        }
        return $instance;
    }

    /**
     * Return the Course background settings.
     *
     * @return array $coursebackgroundoptions course background options.
     */
    public function course_background_options_editlist() {
        global $CFG;
        require_once($CFG->dirroot.'/local/designer/form/element-colorpicker.php');
            \MoodleQuickForm::registerElementType('designercolorpicker', $CFG->dirroot.
                '/local/designer/form/element-colorpicker.php',
                'moodlequickform_designercolorpicker');
        $coursebackgroundoptions = [
            'coursebackground' => [
                'label' => new lang_string('coursebackground', 'format_designer'),
                'element_type' => 'header',
            ],
            'coursebackgroundcolor' => [
                'label' => new lang_string('coursebackgroundcolor', 'format_designer'),
                'element_type' => 'designercolorpicker',
                'help' => 'coursebackgroundcolor',
                'help_component' => 'format_designer',
            ],
            'coursebgimage_filemanager' => [
                'label' => new lang_string('coursebackgroundimage', 'format_designer'),
                'element_type' => 'filemanager',
                'element_attributes' => [[],
                    [
                    'subdirs' => 0,
                    'maxfiles' => 1,
                    'filetype' => 'image',
                    ],
                ],
                'help' => 'coursebackgroundimage',
                'help_component' => 'format_designer',
            ],
            'coursebackgroundtransparent' => [
                'label' => new lang_string('coursebackgroundtransparent', 'format_designer'),
                'element_type' => 'advcheckbox',
                'help' => 'coursebackgroundtransparent',
                'help_component' => 'format_designer',
            ],
        ];
        return $coursebackgroundoptions;
    }

    /**
     * Return the course background options format list.
     *
     * @return array $courseformatoptions course background format list options.
     */
    public function course_background_options_format_list() {
        $courseformatoptions = [
            'coursebackground' => [
                'default' => new lang_string('coursebackground', 'format_designer'),
                'type' => PARAM_TEXT,
            ],
            'coursebgimage_filemanager' => [
                'default ' => 0,
                'type' => PARAM_FILE,
            ],
            'coursebackgroundcolor' => [
                'default ' => 0,
                'type' => PARAM_RAW,
            ],
            'coursebackgroundtransparent' => [
                'default ' => 0,
                'type' => PARAM_INT,
            ],
        ];
        return $courseformatoptions;
    }

    /**
     * Get image for the given filearea in the format designer component, by default it uses the course context.
     * Using the context parameter, we can get other contexts images for this format.
     *
     * @param string $filearea Filearea of the image.
     * @param int $itemid Section id or Module id.
     * @param \context_instance $context Any Context.
     * @return void
     */
    public function get_coursebg_images($filearea, $itemid=0, $context=null) {
        global $PAGE;
        $context = \context_course::instance($this->course->id);
        $files = get_file_storage()->get_area_files(
            $context->id, 'local_designer', $filearea,
            $itemid, 'itemid, filepath, filename', false);
        if (empty($files) ) {
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
     * Return the course header options edit list.
     *
     * @return void
     */
    public function course_format_options_editlist() {
        $courseheaderoptions = [
            'displayunavailableactivities' => [
                'label' => new lang_string('displayunavailableactivities', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [
                    [
                        0 => new lang_string('hide'),
                        1 => new lang_string('show'),
                    ],
                ],
                'help' => 'displayunavailableactivities',
                'help_component' => 'format_designer',
                'hideif' => ['coursedisplay', 'neq', 1],
            ],
            'activitydisplaymode' => [
                'label' => new lang_string('activitydisplaymode', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [
                    [
                        'bytype' => new lang_string('bytype', 'format_designer'),
                        'bypurpose' => new lang_string('bypurpose', 'format_designer'),
                    ],
                ],
                'help' => 'activitydisplaymode',
                'help_component' => 'format_designer',
                'hideif' => ['coursedisplay', 'neq', 1],
            ],
        ];
        return $courseheaderoptions;
    }

    /**
     * Return the course header options edit list.
     *
     * @return array $courseheaderoptions header options.
     */
    public function course_header_options_editlist() {
        global $CFG;

        require_once($CFG->dirroot.'/local/designer/form/element-colorpicker.php');

        \MoodleQuickForm::registerElementType('designercolorpicker', $CFG->dirroot.'/local/designer/form/element-colorpicker.php',
            'moodlequickform_designercolorpicker');

        // Get user fields edit list.
        $usersfields = $this->get_userfields_editlist();

        $courseheaderoptions = [
            'courseheadertype' => [
                'label' => new lang_string('courseheadertype', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [
                    [
                        0 => new lang_string('none'),
                        DESIGNER_COURSE_TYPE_HERO => new lang_string('hero', 'format_designer'),
                        DESIGNER_COURSE_TYPE_CONTENT => new lang_string('content', 'format_designer'),
                    ],
                ],
                'help' => 'courseheadertype',
                'help_component' => 'format_designer',
            ],
            'courseprogresstype' => [
                'label' => new lang_string('courseprogresstype', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [
                    [
                        0 => new lang_string('disabled', 'format_designer'),
                        DESIGNER_PROGERSS_TYPE_BAR => new lang_string('progressbar', 'format_designer'),
                        DESIGNER_PROGERSS_TYPE_DONUT => new lang_string('donut', 'format_designer'),
                    ],
                ],
                'help' => 'courseprogresstype',
                'help_component' => 'format_designer',
            ],
            'completioncheckmark' => [
                'label' => new lang_string('completioncheckmark', 'format_designer'),
                'element_type' => 'advcheckbox',
                'help' => 'completioncheckmark',
                'help_component' => 'format_designer',
                'hideif' => ['courseprogresstype', 'neq', DESIGNER_PROGERSS_TYPE_DONUT],
            ],
            'calcourseprogress' => [
                'label' => new lang_string('calcourseprogress', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [
                    [
                        DESIGNER_PROGRESS_CRITERIA => new lang_string('completioncriteria', 'format_designer'),
                        DESIGNER_PROGRESS_RELEVANTACTIVITIES => new lang_string('relevantactivities', 'format_designer'),
                        DESIGNER_PROGRESS_ALLACTIVITIES => new lang_string('allactivities', 'format_designer'),
                        DESIGNER_PROGRESS_SECTIONS => new lang_string('sections', 'format_designer'),
                    ],
                ],
                'help' => 'calcourseprogress',
                'help_component' => 'format_designer',
            ],
            'sectionprogresstype' => [
                'label' => new lang_string('sectionprogresstype', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [
                    [
                        0 => new lang_string('disabled', 'format_designer'),
                        DESIGNER_PROGERSS_TYPE_BAR => new lang_string('progressbar', 'format_designer'),
                        DESIGNER_PROGERSS_TYPE_DONUT => new lang_string('donut', 'format_designer'),
                    ],
                ],
                'help' => 'sectionprogresstype',
                'help_component' => 'format_designer',
            ],
            'completionindicator' => [
                'label' => new lang_string('completionstatusindicator', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [
                    [
                        'disabled' => new lang_string('disabled', 'format_designer'),
                        'belowcourseprogress' => new lang_string('belowcourseprogress', 'format_designer'),
                        'coursemetadata' => new lang_string('withcoursemetadata', 'format_designer'),
                    ],
                ],
                'help' => 'completionindicator',
                'help_component' => 'format_designer',
            ],
            'calsectionprogress' => [
                'label' => new lang_string('calsectionprogress', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [
                    [
                        DESIGNER_PROGRESS_RELEVANTACTIVITIES => new lang_string('relevantactivities', 'format_designer'),
                        DESIGNER_PROGRESS_ALLACTIVITIES => new lang_string('allactivities', 'format_designer'),
                    ],
                ],
                'help' => 'calsectionprogress',
                'help_component' => 'format_designer',
            ],
            'courseheadersummary' => [
                'label' => new lang_string('courseheadersummary', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [
                    [
                        self::COURSEHEADER_SUMMARYDISABLED => new lang_string('disabled', 'format_designer'),
                        self::COURSEHEADER_SUMMARYTRIMMED => new lang_string('trimmed', 'format_designer'),
                        self::COURSEHEADER_SUMMARYFULL => new lang_string('full', 'format_designer'),
                    ],
                ],
                'help' => 'courseheadersummary',
                'help_component' => 'format_designer',
            ],
            'additionalcontent' => [
                'label' => new lang_string('additionalcontent', 'format_designer'),
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
                'help' => 'additionalcontent',
                'help_component' => 'format_designer',
            ],
            'additionalcontentformat' => [
                'element_type' => 'hidden',
                'label' => 'hidden',
            ],
            'userfields' => [
                'label' => new lang_string('userfields', 'format_designer'),
                'element_type' => 'autocomplete',
                'element_attributes' => [$usersfields, ['multiple' => true]],
                'help' => 'userfields',
                'help_component' => 'format_designer',
            ],
            'courseheaderbgcolor' => [
                'label' => new lang_string('courseheaderbgcolor', 'format_designer'),
                'element_type' => 'designercolorpicker',
                'help' => 'courseheaderbgcolor',
                'help_component' => 'format_designer',
            ],
            'courseheadertextcolor' => [
                'label' => new lang_string('courseheadertextcolor', 'format_designer'),
                'element_type' => 'designercolorpicker',
                'help' => 'courseheadertextcolor',
                'help_component' => 'format_designer',
            ],
            'courseheaderbgimage_filemanager' => [
                'label' => new lang_string('courseheaderbgimage', 'format_designer'),
                'element_type' => 'filemanager',
                'element_attributes' => [[],
                    [
                    'subdirs' => 0,
                    'maxfiles' => 1,
                    'accepted_types' => array('web_image'),
                    ],
                ],
                'help' => 'courseheaderbgimage',
                'help_component' => 'format_designer',
            ],
            'courseheaderheight' => [
                'label' => new lang_string('courseheaderheight', 'format_designer'),
                'element_type' => 'text',
                'help' => 'courseheaderheight',
                'help_component' => 'format_designer',
            ],
            'courseheaderfullscreen' => [
                'label' => new lang_string('courseheadersize', 'format_designer'),
                'element_type' => 'advcheckbox',
                'help' => 'courseheadersize',
                'help_component' => 'format_designer',
            ],
        ];
        return $courseheaderoptions + $this->course_fields_editlist();
    }

    /**
     * Course fields selector default and type defined.
     *
     * @return string
     */
    public function course_fields_formatlist() {
        return [
            'coursefields' => [
                'default' => '',
                'type' => PARAM_TEXT,
            ],
        ];
    }

    /**
     * Course fields selector config with available course fields.
     *
     * @return string
     */
    public function course_fields_editlist() {
        global $CFG;

        // Don't need to include the course fields before setup the category. It prevents issue during the installation.
        if (!isset($this->course->category)) {
            return [];
        }

        // Course form elements.
        $elements = $this->info->get_course_field_labels();
        // Generate elements to the options.
        $preventfields = [
            'showactivitydates', 'showreports', 'showgrades', 'buttonar', 'overviewfiles_filemanager',
            'enablecompletion', 'showcompletionconditions', 'groupmodeforce', 'defaultgroupingid',
        ];

        $coursefields = [];

        // Course fields.
        foreach ($elements as $element => $elementlabel) {
            // Remove un used fields.
            if (in_array($element, $preventfields)) {
                continue;
            }
            $coursefields[$element] = $elementlabel;
        }

        // Get list of custom fields for this course as object.
        $customfields = \core_course\customfield\course_handler::create()->get_instance_data($this->course->id);
        // Merge the custom fields to the course fileds.
        foreach ($customfields as $data) {
            $fd = new \core_customfield\output\field_data($data);
            $label = $fd->get_shortname();
            $name = $fd->get_name();
            $coursefields['customfield_'.$label] = $name;
        }

        return [
            'coursefields' => [
                'label' => new lang_string('configcoursefields', 'format_designer'),
                'element_type' => 'autocomplete',
                'element_attributes' => [$coursefields, ['multiple' => true]],
                'help' => 'configcoursefields',
                'help_component' => 'format_designer',
            ],
        ];
    }

    /**
     * Inlcude the Course background styles to the page.
     *
     * @return void
     */
    public function designer_include_style() {
        global $PAGE;
        // Include slick css.
        $PAGE->requires->css('/local/designer/style/slick.css');

        // Build the CSS file URL.
        $includestyleurl = new \moodle_url('/local/designer/styles.php',
            [
            'id' => $this->course->id,
            'rev' => theme_get_revision(),
            ]
        );
        return $includestyleurl;
    }

    /**
     * Get course custom fields with data.
     *
     * @return array
     */
    public function get_coursefields_data() {
        global $OUTPUT, $DB;

        // Don't need to include the course fields before setup the category. It prevents issue during the installation.
        if (!isset($this->course->category)) {
            return [];
        }

        // Get course record with format options.
        $format = course_get_format($this->course->id);
        $courserecord = $format->get_course();
        $course = new \core_course_list_element($courserecord);
        $courserecord->summary_editor = (new \coursecat_helper())->get_course_formatted_summary($course);

        // Inject current role names to course.
        $context = context_course::instance($course->id);
        $aliases = $DB->get_records('role_names', ['contextid' => $context->id]);
        foreach ($aliases as $alias) {
            $courserecord->{'role_'.$alias->roleid} = $alias->name;
        }

        $output = [];
        $coursefields = $courserecord->coursefields ?? []; // Course fields to shown in course header.

        if (empty($coursefields)) {
            return [];
        }

        // Get list of custom fields for this course as object.
        $customfields = \core_course\customfield\course_handler::create()->get_instance_data($this->course->id);
        // Merge the custom fields to the course record.
        foreach ($customfields as $data) {
            $fd = new \core_customfield\output\field_data($data);
            $label = $fd->get_shortname();
            $courserecord->{"customfield_$label"} = $fd->get_value();
        }

        // Generate the course fields with icon and its values.
        foreach ($coursefields as $field) {

            // Value of this field.
            $value = $courserecord->$field ?? '';
            // Convert the values time format.
            $this->info->convert_valuetime_format($value, $field);

            if ($value == '') {
                continue;
            }
            // Find the field index and get the element from elements list.
            // Fetech the field name.
            $elements = $this->info->get_course_field_labels();
            foreach ($elements as $element => $courselabel) {
                if ($element == $field) {
                    $label = $courselabel;
                }
            }
            // Convert the icon identifier to fontawesome icon.
            $icon = get_config('format_designer', 'icon_course_'.$field);
            $icon = explode(':', $icon);
            $iconstr = isset($icon[1]) ? $icon[1] : ''; // Icon string.
            $component = isset($icon[0]) ? $icon[0] : ''; // Icon component.
            // Render the pix icon.
            $icon = $iconstr ? $OUTPUT->pix_icon($iconstr,  $label, $component) : '';

            // Final value for the field.
            $output[] = [
                'fieldname' => $label ?? $field,
                'value' => format_string($value),
                'shortname' => $field,
                'icon' => $icon,
            ];
        }

        return $output ?? [];
    }

    /**
     * Course fields selector config with available course fields.
     *
     * @return string
     */
    public function get_userfields_editlist() {
        global $CFG;

        require_once($CFG->dirroot.'/local/designer/locallib.php');
        // Get User form.
        $editform = $this->info->get_user_form();
        // User form elements.
        $elements = $editform->get_form_elements();
        // Generate elements to the options.
        $userfields = [];

        // Prevent these fields in the listing.
        $preventfields = ['imagealt', 'imagefile', 'maildisplay', 'lastname', 'firstname', 'buttonar'];
        foreach ($elements as $element) {
            if (in_array($element->getName(), $preventfields)) {
                continue;
            }
            $userfields[$element->getName()] = $element->getLabel();
        }
        return $userfields;
    }

    /**
     * Return the course header options format list.
     *
     * @return array $courseformatoptions course header format list options.
     */
    public function course_header_options_format_list() {
        $courseformatoptions = [
            'courseprogresstype' => [
                'default ' => 0,
                'type' => PARAM_INT,
            ],
            'completioncheckmark' => [
                'default ' => 0,
                'type' => PARAM_INT,
            ],
            'calcourseprogress' => [
                'default ' => DESIGNER_PROGRESS_CRITERIA,
                'type' => PARAM_TEXT,
            ],
            'sectionprogresstype' => [
                'default ' => 0,
                'type' => PARAM_INT,
            ],
            'calsectionprogress' => [
                'default ' => DESIGNER_PROGRESS_RELEVANTACTIVITIES,
                'type' => PARAM_TEXT,
            ],
            'completionindicator' => [
                'default ' => 'disabled',
                'type' => PARAM_TEXT,
            ],
            'courseheadertype' => [
                'default ' => 0,
                'type' => PARAM_INT,
            ],
            'courseheadersummary' => [
                'default ' => 0,
                'type' => PARAM_INT,
            ],
            'additionalcontent' => [
                'default ' => 0,
                'type' => PARAM_RAW,
            ],
            'additionalcontentformat' => [
                'default' => 0,
                'type' => PARAM_INT,
            ],
            'courseheaderbgcolor' => [
                'default ' => 0,
                'type' => PARAM_RAW,
            ],
            'courseheadertextcolor' => [
                'default ' => 0,
                'type' => PARAM_RAW,
            ],
            'courseheaderbgimage_filemanager' => [
                'default ' => 0,
                'type' => PARAM_FILE,
            ],
            'courseheaderheight' => [
                'default ' => 0,
                'type' => PARAM_INT,
            ],
            'courseheaderfullscreen' => [
                'default ' => 0,
                'type' => PARAM_INT,
            ],
            'coursefields' => [
                'default' => '',
                'type' => PARAM_TEXT,
            ],
            'userfields' => [
                'default' => 0,
                'type' => PARAM_TEXT,
            ],
        ];
        return $courseformatoptions;
    }

    /**
     * Get designer pro course format options.
     *
     * @return void
     */
    public function course_format_options_list() {
        $courseformatoptions = [
            'displayunavailableactivities' => [
                'default' => 0,
                'type' => PARAM_TEXT,
            ],
            'activitydisplaymode' => [
                'default' => 0,
                'type' => PARAM_TEXT,
            ],
        ];
        return $courseformatoptions;
    }
}
