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
 * This file contains main class for Designer course format.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot. '/course/format/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

use core\output\inplace_editable;

if (format_designer_has_pro()) {
    require_once($CFG->dirroot . '/local/designer/classes/courseoptions.php');
}

/**
 * Collapsible format settings: Expand all the sections in intial state.
 */
define('SECTION_EXPAND', 1);
/**
 * Collapsible format settings: Collapse all the sections in intial state.
 */
define('SECTION_COLLAPSE', 2);
/**
 * Collapsible format settings: Expand the first the section only in intial state.
 */
define('FIRST_EXPAND', 3);

define('DESIGNER_ENABLE_POPUPACTIVITIES', 1);

define('DESIGNER_DISABLE_POPUPACTIVITIES', 0);

define('DESIGNER_TYPE_NORMAL', 0);

define('DESIGNER_TYPE_KANBAN', 1);

define('DESIGNER_TYPE_COLLAPSIBLE', 2);

define('DESIGNER_TYPE_FLOW', 3);

define('DESIGNER_HERO_ZERO_HIDE', 1);

define('DESIGNER_HERO_ZERO_VISIBLE', 2);

define('DESIGNER_HERO_ACTVITIY_DISABLED', 0);

define('DESIGNER_HERO_ACTVITIY_EVERYWHERE', 1);

define('DESIGNER_HERO_ACTVITIY_COURSEPAGE', 2);

define('DESIGNER_MOD_TEXT_TRIMM', 0);

define('DESIGNERCOURSEANDSECTIONPAGE', 'courseandsection');

define('DESIGNERCOURSEPAGE', 'course');

define('DESIGNERSECTIONPAGE', 'section');

define('DESIGNER_PROGRESS_RELEVANTACTIVITIES', 'relevantmods');

define('DESIGNER_PROGRESS_ALLACTIVITIES', 'allmods');

define('DESIGNER_PROGRESS_SECTIONS', 'sections');

define('DESIGNER_CMPIND_DISABLED', 'disabled');

define('DESIGNER_CMPIND_BELOWPROGRESS', 'belowcourseprogress');

define('DESIGNER_CMPIND_METADATA', 'coursemetadata');

define('DESIGNER_PROGRESS_CRITERIA', 'criteria');

/**
 * Main class for the Designer course format.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_designer extends \core_courseformat\base {

    /**
     * Hide the course index bar in course pages only.
     */
    const HIDE_ON_COURSEPAGE = 1;

    /**
     * Hide the course index bar everywhere.
     */
    const HIDE_EVERYWHERE = 2;

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Returns true if this course format uses course index
     *
     * This function may be called without specifying the course id
     * i.e. in course_index_drawer()
     *
     * @return bool
     */
    public function uses_course_index() {
        global $PAGE;
        $course = $this->get_course();
        $index = isset($course->courseindex) ?
            ($course->courseindex == self::HIDE_EVERYWHERE ? false :
            ($course->courseindex == 1 && $PAGE->cm == null ? false : true) ) : true;
        return $index;
    }

    /**
     * Returns true if this course format uses activity indentation.
     *
     * Indentation is not supported by core formats anymore and may be deprecated in the future.
     * This method will keep a default return "true" for legacy reasons but new formats should override
     * it with a return false to prevent future deprecations.
     *
     * A message in a bottle: if indentation is finally deprecated, both behat steps i_indent_right_activity
     * and i_indent_left_activity should be removed as well. Right now no core behat uses them but indentation
     * is not officially deprecated so they are still available for the contrib formats.
     *
     * @return bool if the course format uses indentation.
     */
    public function uses_indentation(): bool {
        return false;
    }

    /**
     * Returns true if this course format is compatible with content components.
     *
     * Using components means the content elements can watch the frontend course state and
     * react to the changes. Formats with component compatibility can have more interactions
     * without refreshing the page, like having drag and drop from the course index to reorder
     * sections and activities.
     *
     * @return bool if the format is compatible with components.
     */
    public function supports_components() {
        return true;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #").
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                ['context' => context_course::instance($this->courseid)]);
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Returns if an specific section is visible to the current user.
     *
     * Formats can overrride this method to implement any special section logic.
     *
     * @param section_info $section the section modinfo
     * @param bool $inculdehidesections
     * @return bool;
     */
    public function is_section_visible(section_info $section, $inculdehidesections = true): bool {
        // Previous to Moodle 4.0 thas logic was hardcoded. To prevent errors in the contrib plugins
        // the default logic is the same required for topics and weeks format and still uses
        // a "hiddensections" format setting.
        $course = $this->get_course();
        if ($inculdehidesections) {
            $hidesections = $course->hiddensections ?? true;
        } else {
            $hidesections = true;
        }
        // Show the section if the user is permitted to access it, OR if it's not available
        // but there is some available info text which explains the reason & should display,
        // OR it is hidden but the course has a setting to display hidden sections as unavailable.
        return $section->uservisible ||
            ($section->visible && !$section->available && !empty($section->availableinfo)) ||
            (!$section->visible && !$hidesections);
    }

    /**
     * Returns the default section name for the Designer course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, the base implementation of format_base::get_default_section_name which uses
     * the string with the key = 'sectionname' from the course format's lang file + the section number will be used.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        if ($section->section == 0) {
            // Return the general section.
            return get_string('section0name', 'format_designer');
        } else {
            // Use format_base::get_default_section_name implementation which
            // will display the section name in "Topic n" format.
            return parent::get_default_section_name($section);
        }
    }

    /**
     * The URL to use for the specified course (with section).
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = []) {
        global $CFG;
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', ['id' => $course->id]);

        $sr = null;
        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }
        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ($sectionno !== null) {
            if ($sr !== null) {
                if ($sr) {
                    $usercoursedisplay = COURSE_DISPLAY_MULTIPAGE;
                    $sectionno = $sr;
                } else {
                    $usercoursedisplay = COURSE_DISPLAY_SINGLEPAGE;
                }
            } else {
                $usercoursedisplay = $course->coursedisplay;
            }
            if ($sectionno != 0 && $usercoursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                $url->param('section', $sectionno);
            } else {
                if (empty($CFG->linkcoursesections) && !empty($options['navigation'])) {
                    return null;
                }
                $url->set_anchor('section-'.$sectionno);
            }
        }
        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Returns course-specific information to be output on any course page in the header area
     * (for the current course)
     *
     * @return string
     */
    public function course_header() {
        if (format_designer_has_pro() && class_exists('\local_designer\courseheader')) {
            return local_designer\courseheader::get_header_instance($this);
        }
    }

    /**
     * Add class to the body element for style purpose.
     *
     * @param moodle_page $page
     * @return void
     */
    public function page_set_course(moodle_page $page) {
        $course = $this->get_course();
        if ($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            $page->add_body_class('format-designer-single-section');
        }
        if (format_designer_has_pro()) {
            // Fetch classes from pro designer and attach them to the body.
            $classes = \local_designer\info::create()->generate_body_classes($course, $this);
            $page->add_body_class($classes);
        }
    }
    /**
     * Loads all of the course sections into the navigation.
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     * @return void
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;
        // If section is specified in course/view.php, make sure it is expanded in navigation.
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                    $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        // Check if there are callbacks to extend course navigation.
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $modinfo = get_fast_modinfo($this->get_course());
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }
    }

    /**
     * Custom action after section has been moved in AJAX mode.
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    public function ajax_section_move() {
        global $PAGE;
        $titles = [];
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return ['sectiontitles' => $titles, 'action' => 'move'];
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course.
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => [],
        ];
    }

    /**
     * Definitions of the additional options that this course format uses for course.
     *
     * Designer format uses the following options:
     * - coursedisplay
     * - hiddensections
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function designer_course_format_options($foreditform = false) {
        global $PAGE, $COURSE;
        static $courseformatoptions = false;
        $courseformatoptions = self::course_format_options_list($foreditform);
        if ($foreditform) {
            // Backward compatibility, Changed to timemanagement setting.
            if ($this->designer_completion_enabled()) {
                $courseformatoptionsedit['coursecompletiondateinfo'] = [
                    'element_type' => 'hidden',
                ];
            } else {
                $courseformatoptionsedit['coursecompletiondateinfo'] = [
                    'element_type' => 'static',
                ];
            }

            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
            // Set designer default options to course config.
            $design = \format_designer\options::get_default_options();
            foreach ($courseformatoptions as $name => $value) {
                if (isset($design->$name)) {
                    $courseformatoptions[$name]['default'] = $design->$name;
                }
                $adv = $name.'_adv';
                if (isset($design->$adv) && $design->$adv) {
                    $courseformatoptions[$name]['adv'] = true;
                }
            }
        }
        return $courseformatoptions;
    }

    /**
     * Definitions of the additional options that this course format uses for course.
     *
     * Designer format uses the following options:
     * - coursedisplay
     * - hiddensections
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        return $this->designer_course_format_options($foreditform);
    }

    /**
     * Designer course format options list.
     *
     * @param bool $foreditform
     * @return array List of format options.
     */
    public static function course_format_options_list($foreditform = false) {
        global $CFG, $PAGE;
        static $courseformatoptions = false;
        $teacher = get_archetype_roles('editingteacher');
        $teacher = reset($teacher);
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = [
                'coursetype' => [
                    'default' => 0,
                    'type' => PARAM_INT,
                ],
                'popupactivities' => [
                    'default' => 0,
                    'type' => PARAM_INT,
                ],
                'addnavigation' => [
                    'default' => 0,
                    'type' => PARAM_INT,
                ],
                'popupactivitiesinfo' => [
                    'default' => get_string('popupactivitiesnotinstalled', 'format_designer'),
                    'type' => PARAM_RAW,
                    'label' => get_string('popupactivities', 'format_designer'),
                ],
                'coursedisplay' => [
                    'default' => $courseconfig->coursedisplay,
                    'type' => PARAM_INT,
                ],
                'hiddensections' => [
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ],
                'showanimation' => [
                    'default' => true,
                    'type' => PARAM_INT,
                ],
                'flowsize' => [
                    'default' => 0,
                    'type' => PARAM_INT,
                ],
                'accordion' => [
                    'default' => 0,
                    'type' => PARAM_INT,
                ],
                'initialstate' => [
                    'default' => 3,
                    'type' => PARAM_INT,
                ],
                'listwidth' => [
                    'default' => '400px',
                    'type' => PARAM_ALPHANUMEXT,
                ],
                'courseindex' => [
                    'default' => 0,
                    'type' => PARAM_INT,
                ],
                'secondarymenutocourse' => [
                    'default' => 0,
                    'type' => PARAM_INT,
                ],
            ];

            // Include course header config.
            if (format_designer_has_pro()) {
                $courseoptions = new local_designer\courseoptions($PAGE->course);
                if (method_exists($courseoptions, 'course_format_options_list')) {
                    $courseformatoptions += $courseoptions->course_format_options_list();
                }
            }

            $courseformatoptions += [
                'courseheader' => [
                    'default' => get_string('courseheader', 'format_designer'),
                    'type' => PARAM_TEXT,
                ],

                'activityprogress' => [
                    'default' => 0,
                    'type' => PARAM_INT,
                ],
            ];

            // Include course header config.
            if (format_designer_has_pro()) {
                $courseformatoptions += (new local_designer\courseoptions($PAGE->course))->course_header_options_format_list();
            }
            $courseformatoptions += [
                'coursecompletiondateinfo' => [
                    'default' => get_string('completiontrackingmissing', 'format_designer'),
                    'type' => PARAM_TEXT,
                    'label' => new lang_string('coursecompletiondate', 'format_designer'),
                ],
                'timemanagement' => [
                    'default' => '',
                    'type' => PARAM_TEXT,
                ],
                'courseduedateinfo' => [
                    'default' => get_string('timemanagementmissing', 'format_designer'),
                    'type' => PARAM_RAW_TRIMMED,
                    'label' => new lang_string('courseduedate', 'format_designer'),
                ],
                'coursestaff' => [
                    'default' => $teacher->id,
                    'type' => PARAM_TEXT,
                ],
            ];

            if (format_designer_has_pro() != 1 ) {
                $userprofilefields = profile_get_user_fields_with_data(0);
                if (!empty($userprofilefields)) {
                    foreach ($userprofilefields as $field) {
                        $courseformatoptions[$field->inputname] = [
                            'default' => 0,
                            'type' => PARAM_INT,
                        ];
                    }
                }
            }

            $courseformatoptions['courseheroactivityheader'] = [
                'default' => get_string('heroactivity', 'format_designer'),
                'type' => PARAM_TEXT,
            ];
            $courseformatoptions['sectionzeroactivities'] = [
                'default' => 0,
                'type' => PARAM_INT,
            ];
            $courseformatoptions['heroactivity'] = [
                'default' => 0,
                'type' => PARAM_INT,
            ];
            $courseformatoptions['heroactivitypos'] = [
                'default' => 0,
                'type' => PARAM_INT,
            ];
        }
        if (format_designer_has_pro()) {
            require_once($CFG->dirroot."/local/designer/lib.php");
            if (function_exists('local_designer_course_format_options_list')) {
                $courseformatoptions += local_designer_course_format_options_list();
            }

            $courseformatoptions += (new local_designer\courseoptions($PAGE->course))->course_background_options_format_list();
        }

        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $courseformatoptionsedit = [
                'hiddensections' => [
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible'),
                        ],
                    ],
                ],
                'coursedisplay' => [
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            COURSE_DISPLAY_SINGLEPAGE => new lang_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi'),
                        ],
                    ],
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                    'disabledif' => [['coursetype', 'eq', DESIGNER_TYPE_KANBAN]],
                ],

                'accordion' => [
                    'label' => new lang_string('accordion', 'format_designer'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                           0 => new lang_string('disable'),
                           1 => new lang_string('enable'),
                        ],
                    ],
                    'disabledif' => [
                        ['coursetype', 'eq', DESIGNER_TYPE_KANBAN],
                        ['coursetype', 'eq', 0],
                    ],
                ],

                'initialstate' => [
                    'label' => new lang_string('initialstate', 'format_designer'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            SECTION_EXPAND => new lang_string('expand', 'format_designer'),
                            SECTION_COLLAPSE => new lang_string('collapse', 'format_designer'),
                            FIRST_EXPAND => new lang_string('firstexpand', 'format_designer'),
                        ],
                    ],
                    'disabledif' => [
                        ['coursetype', 'eq', DESIGNER_TYPE_KANBAN],
                        ['coursetype', 'eq', 0],
                    ],
                ],

                'timemanagement' => [
                    'label' => new lang_string('courseheadertimemanagement', 'format_designer'),
                    'element_type' => 'autocomplete',
                    'element_attributes' => [
                        [
                            'enrolmentstartdate' => new lang_string('enrolmentstartdate', 'format_designer'),
                            'enrolmentenddate' => new lang_string('enrolmentenddate', 'format_designer'),
                            'courseduedate' => new lang_string('courseduedate', 'format_designer'),
                            'coursecompletiondate' => new lang_string('coursecompletiondate', 'format_designer'),

                        ], ['multiple' => true],
                    ],
                    'help' => 'courseheadertimemanagement',
                    'help_component' => 'format_designer',
                ],
                'activityprogress' => [
                    'label' => new lang_string('activityprogress', 'format_designer'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            1 => new lang_string('show'),
                            0 => new lang_string('hide'),
                        ],
                    ],
                    'help' => 'activityprogress',
                    'help_component' => 'format_designer',
                    'disabledif' => [['enablecompletion', 'neq', 1]],
                ],
                'coursetype' => [
                    'label' => new lang_string('coursetype', 'format_designer'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            0 => new lang_string('normal'),
                            DESIGNER_TYPE_KANBAN => new lang_string('kanbanboard', 'format_designer'),
                            DESIGNER_TYPE_COLLAPSIBLE => new lang_string('collapsiblesections', 'format_designer'),
                            DESIGNER_TYPE_FLOW => new lang_string('type_flow', 'format_designer'),
                        ],
                    ],
                    'help' => 'coursetype',
                    'help_component' => 'format_designer',
                ],
                'showanimation' => [
                    'label' => new lang_string('showanimation', 'format_designer'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            0 => new lang_string('disable'),
                            1 => new lang_string('enable'),
                        ],
                    ],
                    'help' => 'showanimation',
                    'help_component' => 'format_designer',
                    'disabledif' => [
                        ['coursetype', 'neq', DESIGNER_TYPE_FLOW],
                    ],
                ],
                'flowsize' => [
                    'label' => new lang_string('flowsize', 'format_designer'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            0 => new lang_string('small', 'format_designer'),
                            1 => new lang_string('medium', 'format_designer'),
                            2 => new lang_string('large', 'format_designer'),
                        ],
                    ],
                    'help' => 'flowsize',
                    'help_component' => 'format_designer',
                    'disabledif' => [
                        ['coursetype', 'neq', DESIGNER_TYPE_FLOW],
                    ],
                ],
                'courseheader' => [
                    'label' => new lang_string('courseheader', 'format_designer'),
                    'element_type' => 'header',
                ],
                'listwidth' => [
                    'label' => new lang_string('listwidth', 'format_designer'),
                    'element_type' => 'text',
                    'hideif' => ['coursetype', 'neq', DESIGNER_TYPE_KANBAN],
                ],
                'courseindex' => [
                    'label' => new lang_string('courseindex', 'format_designer'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            0 => new lang_string('show'),
                            self::HIDE_ON_COURSEPAGE => new lang_string('hideoncourses', 'format_designer'),
                            self::HIDE_EVERYWHERE => new lang_string('hideeverywhere', 'format_designer'),
                        ],
                    ],
                    'help' => 'courseindex',
                    'help_component' => 'format_designer',
                ],
                'secondarymenutocourse' => [
                    'label' => new lang_string('strsecondarymenutocourse', 'format_designer'),
                    'element_type' => 'advcheckbox',
                    'help' => 'strsecondarymenutocourse',
                    'help_component' => 'format_designer',
                ],
            ];
            if (format_designer_has_pro()) {
                $courseoptions = new local_designer\courseoptions($PAGE->course);
                if (method_exists($courseoptions, 'course_format_options_editlist')) {
                    $courseformatoptionsedit += $courseoptions->course_format_options_editlist();
                }
                if (method_exists($courseoptions, 'course_header_options_editlist')) {
                    $courseformatoptionsedit += $courseoptions->course_header_options_editlist();
                }
            }
            if (format_designer_popup_installed()) {
                $courseformatoptionsedit['popupactivities'] = [
                    'label' => new lang_string('popupactivities', 'format_designer'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            DESIGNER_DISABLE_POPUPACTIVITIES => new lang_string('disable'),
                            DESIGNER_ENABLE_POPUPACTIVITIES => new lang_string('enable'),
                        ],
                    ],
                    'help' => 'popupactivities',
                    'help_component' => 'format_designer',
                ];
                $courseformatoptionsedit['popupactivitiesinfo'] = [
                    'element_type' => 'hidden',
                ];
                $courseformatoptionsedit['addnavigation'] = [
                    'label' => new lang_string('addnavigation', 'format_popups'),
                    'element_type' => 'advcheckbox',
                    'help' => 'addnavigation',
                    'help_component' => 'format_popups',
                ];
            } else {
                $courseformatoptionsedit['popupactivitiesinfo'] = [
                    'element_type' => 'static',
                ];
                $courseformatoptionsedit['addnavigation'] = [
                    'element_type' => 'hidden',
                    'label' => get_string('addnavigation', 'format_designer'),
                ];
                $courseformatoptionsedit['popupactivities'] = [
                    'element_type' => 'hidden',
                    'label' => get_string('popupactivities', 'format_designer'),
                ];
            }

            if (format_designer_timemanagement_installed()) {
                $courseformatoptionsedit['courseduedateinfo'] = [
                    'element_type' => 'hidden',
                ];
            } else {
                $courseformatoptionsedit['courseduedateinfo'] = [
                    'element_type' => 'static',
                    'help' => 'courseduedate',
                    'help_component' => 'format_designer',
                ];
            }
            $coursestaffroles = get_default_enrol_roles(\context_system::instance());
            $courseformatoptionsedit['coursestaff'] = [
                'label' => new lang_string('displayheaderroleusers', 'format_designer'),
                'element_type' => 'autocomplete',
                'element_attributes' => [$coursestaffroles, ['multiple' => true]],
                'help' => 'displayheaderroleusers',
                'help_component' => 'format_designer',
            ];

            if (format_designer_has_pro() != 1 ) {
                $userprofilefields = profile_get_user_fields_with_data(0);
                if (!empty($userprofilefields)) {
                    foreach ($userprofilefields as $field) {
                        $courseformatoptionsedit[$field->inputname] = [
                            'label' => $field->field->name,
                            'element_type' => 'advcheckbox',
                            'help' => 'profilefieditem',
                            'help_component' => 'format_designer',
                        ];
                    }
                }
            }

            $courseformatoptionsedit['courseheroactivityheader'] = [
                'label' => new lang_string('heroactivity', 'format_designer'),
                'element_type' => 'header',
                'help' => 'heroactivity',
                'help_component' => 'format_designer',
            ];

            $courseformatoptionsedit['sectionzeroactivities'] = [
                'label' => new lang_string('sectionzeroactivities', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [
                    [
                        0 => new lang_string('disabled', 'format_designer'),
                        1 => new lang_string('makeherohide', 'format_designer'),
                        2 => new lang_string('makeherovisible', 'format_designer'),
                    ],
                ],
                'help' => 'sectionzeroactivities',
                'help_component' => 'format_designer',
            ];

            $courseformatoptionsedit['heroactivity'] = [
                'label' => new lang_string('showastab', 'format_designer'),
                'element_type' => 'select',
                'element_attributes' => [
                    [
                        0 => new lang_string('disabled', 'format_designer'),
                        1 => new lang_string('everywhere', 'format_designer'),
                        2 => new lang_string('onlycoursepage', 'format_designer'),
                    ],
                ],
                'help' => 'showastab',
                'help_component' => 'format_designer',
            ];
            $posrange = array_combine(range(-10, 10), range(-10, 10));
            $courseformatoptionsedit['heroactivitypos'] = [
                'label' => new lang_string('order'),
                'element_type' => 'select',
                'element_attributes' => [$posrange],
                'help' => 'heroactivitypos',
                'help_component' => 'format_designer',
            ];

            if (format_designer_has_pro()) {
                require_once($CFG->dirroot."/local/designer/lib.php");
                if (function_exists('local_designer_course_format_options_editlist')) {
                    $courseformatoptionsedit += local_designer_course_format_options_editlist();
                }
                // Course background format options.
                $courseformatoptionsedit += (new local_designer\courseoptions($PAGE->course))->course_background_options_editlist();
                // Course fields selectors.
                $courseformatoptionsedit += local_designer\courseoptions::create($PAGE->course)->course_fields_editlist();
            }
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Find the course completion enabled for the current course.
     *
     * @return void
     */
    public function designer_completion_enabled() {
        global $COURSE;
        if ($COURSE->enablecompletion) {
            return true;
        }
        return false;
    }


    /**
     * Fetch the context of the current course.
     *
     * @return \context_course
     */
    public function get_course_context() {
        return $this->get_course() ? \context_course::instance($this->get_course()->id) : context_system::instance();
    }

    /**
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@see course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $COURSE, $PAGE, $CFG;

        $elements = parent::create_edit_form_elements($mform, $forsection);
        if (format_designer_has_pro()) {
            // Update the pro fields course values strucuture, Prepare files.
            local_designer\options::load_course_prepare_file($COURSE, $mform);
        }
        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            // Add "numsections" element to the create course form - it will force new course to be prepopulated
            // with empty sections.
            // The "Number of sections" option is no longer available when editing course, instead teachers should
            // delete and add sections when needed.
            $courseconfig = get_config('moodlecourse');
            $max = (int)$courseconfig->maxsections;
            $element = $mform->addElement('select', 'numsections', get_string('numberweeks'), range(0, $max ?: 52));
            $mform->setType('numsections', PARAM_INT);
            if (is_null($mform->getElementValue('numsections'))) {
                $mform->setDefault('numsections', $courseconfig->numsections);
            }
            array_unshift($elements, $element);
        }

        if ($forsection) {
            $options = $this->section_format_options(true);
        } else {
            $options = $this->designer_course_format_options(true);
        }

        $design = \format_designer\options::get_default_options();
        foreach ($options as $optionname => $option) {
            if (isset($option['disabledif'])) {
                $disabledif = $option['disabledif'];
                foreach ($disabledif as $disable) {
                    if (isset($disable[2])) {
                        $mform->disabledif($optionname, $disable[0], $disable[1], $disable[2]);
                    }
                }
            }
            if (isset($option['hideif'])) {
                $hideif = $option['hideif'];
                if (isset($hideif[1])) {
                    $hide = (isset($hideif[2]))
                        ? $mform->hideif($optionname, $hideif[0], $hideif[1], $hideif[2])
                        : $mform->hideif($optionname, $hideif[0], $hideif[1]);
                }
            }
            if (isset($option['adv'])) {
                $mform->setAdvanced($optionname);
            }

            if ($optionname == 'coursestaff' && isset($design->coursestaff)) {
                $select = $mform->getElement($optionname);
                $select->setSelected($design->{$optionname});
            }
        }

        if ($forsection) {
            $PAGE->requires->js_init_code('
                require(["core/config"], function(CFG) {
                    document.querySelectorAll("input[name$=\"width\"]").forEach((v) => {
                        var px = document.createElement("label");
                        px.classList.add("px-string");
                        px.innerHTML = "Px";
                        v.parentNode.append(px);
                    })

                    if (document.querySelectorAll("input[name=\"sectiontype\"]").length > 0) {
                        var sectionType = document.querySelectorAll("input[name=\"sectiontype\"]")[0].value;
                        if (sectionType !== "circles" && sectionType != "horizontal_circles"
                            && document.querySelectorAll("select[name=\"circlesize\"]").length > 0) {
                            document.querySelectorAll("select[name=\"circlesize\"]")[0].setAttribute("disabled", "disabled");
                        }
                    }
                })
            ');
        }
        return $elements;
    }

    /**
     * Return an instance of moodleform to edit a specified section
     *
     * Default implementation returns instance of editsection_form that automatically adds
     * additional fields defined in course_format::section_format_options()
     *
     * Format plugins may extend editsection_form if they want to have custom edit section form.
     *
     * @param mixed $action the action attribute for the form. If empty defaults to auto detect the
     *              current url. If a moodle_url object then outputs params as hidden variables.
     * @param array $customdata the array with custom data to be passed to the form
     *     /course/editsection.php passes section_info object in 'cs' field
     *     for filling availability fields
     * @return moodleform
     */
    public function editsection_form($action, $customdata = []) {
        global $CFG;
        require_once($CFG->dirroot. '/course/format/designer/editsection_form.php');
        if (!array_key_exists('course', $customdata)) {
            $customdata['course'] = $this->get_course();
        }
        return new editsection_form($action, $customdata);
    }

    /**
     * Definitions of the additional options that this course format uses for section
     *
     * See course_format::course_format_options() for return array definition.
     *
     * Additionally section format options may have property 'cache' set to true
     * if this option needs to be cached in get_fast_modinfo(). The 'cache' property
     * is recommended to be set only for fields used in course_format::get_section_name(),
     * course_format::extend_course_navigation() and course_format::get_view_url()
     *
     * For better performance cached options are recommended to have 'cachedefault' property
     * Unlike 'default', 'cachedefault' should be static and not access get_config().
     *
     * Regardless of value of 'cache' all options are accessed in the code as
     * $sectioninfo->OPTIONNAME
     * where $sectioninfo is instance of section_info, returned by
     * get_fast_modinfo($course)->get_section_info($sectionnum)
     * or get_fast_modinfo($course)->get_section_info_all()
     *
     * All format options for particular section are returned by calling:
     * $this->get_format_options($section);
     *
     * @param bool $foreditform
     * @return array
     */
    public function section_format_options($foreditform = false) {
        global $PAGE, $COURSE;
        $sectionid = optional_param('id', 0, PARAM_INT);
        if ($sectionid && $PAGE->pagetype == 'course-editsection') {
            $sectionbackdraftid = 0;
            $sectioncompletionbackdraftid = 0;
            $coursecontext = \context_course::instance($COURSE->id);
            $format = course_get_format($COURSE);
            file_prepare_draft_area($sectionbackdraftid, $coursecontext->id, 'format_designer',
                    'sectiondesignbackground', $sectionid, ['accepted_types' => 'images',
                    'maxfiles' => 1,
                    ],
                );
            file_prepare_draft_area($sectioncompletionbackdraftid, $coursecontext->id, 'format_designer',
                    'sectiondesigncompletionbackground', $sectionid, ['accepted_types' => 'images',
                    'maxfiles' => 1,
                ],
            );
            $format->set_section_option($sectionid, 'sectiondesignerbackgroundimage', $sectionbackdraftid);
            $format->set_section_option($sectionid, 'sectiondesignercompletionbg',
                $sectioncompletionbackdraftid);
        }
        return self::section_format_options_list($foreditform);
    }

    /**
     * Config list for sections. Used in global settings and section edit.
     *
     * @param bool $foreditform
     * @return array List of section settings.
     */
    public static function section_format_options_list($foreditform) {
        global $CFG, $PAGE;
        $design = \format_designer\options::get_default_options();
        $sectionoptions = [
            'sectiontype' => [
                'type' => PARAM_ALPHANUMEXT,
                'label' => '',
                'element_type' => 'hidden',
                'default' => get_config('format_designer', 'sectiontype'),
            ],
        ];
        $width = [
            0 => '100%',
            1 => '50%',
            2 => '33%',
            3 => '25%',
            4 => '20%',
        ];
        $sectionoptions['sectionlayoutheader'] = [
            'type' => PARAM_TEXT,
            'element_type' => 'header',
            'default' => get_string('sectionlayouts', 'format_designer'),
            'label' => '',
        ];

        $course = course_get_format($PAGE->course)->get_course();
        $settingspage = ($PAGE->course->id == SITEID);
        if ($settingspage || (isset($course->coursetype) && $course->coursetype != DESIGNER_TYPE_FLOW)) {
            $lists = [
                'desktop' => ['size' => 5, 'default' => '2'],
                'tablet' => ['size' => 3, 'default' => 1],
                'mobile' => ['size' => 2, 'default' => '2'],
            ];

            foreach ($lists as $name => $options) {
                $name = $name.'width';
                $availablewidth = array_slice($width, 0, $options['size']);
                $widthdefaultvalue = isset($design->$name) ? $width[$design->$name] : '';
                $sectionoptions[$name] = [
                    'default' => (isset($design->$name) ||
                    (isset($course->coursetype) && $course->coursetype != DESIGNER_TYPE_NORMAL))
                        ? $widthdefaultvalue : $options['default'],
                    'type' => PARAM_INT,
                    'label' => new lang_string($name, 'format_designer'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        $availablewidth,
                    ],
                    'help' => $name,
                    'help_component' => 'format_designer',
                ];
                $adv = $name.'_adv';
                if (isset($design->$adv) && $design->$adv) {
                    $sectionoptions[$name]['adv'] = true;
                }
            }
        }

        // Include pro feature options for section.
        if (format_designer_has_pro()) {
            require_once($CFG->dirroot."/local/designer/lib.php");
            $prosectionoptions = local_designer_get_pro_section_options($foreditform);
            $sectionoptions = array_merge($sectionoptions, $prosectionoptions);
        }
        return $sectionoptions;
    }


    /**
     * Duplicate a section
     *
     * @param section_info $originalsection The section to be duplicated
     * @return section_info The new duplicated section
     * @since Moodle 4.2
     */
    public function duplicate_section(section_info $originalsection): section_info {
        global $USER, $CFG;
        $course = $this->get_course();

        $fileareasections = [
            'sectiondesignerbackgroundimage' => [
                'filearea' => 'sectiondesignbackground',
                'component' => 'format_designer',
            ],
            'sectiondesignercompletionbg' => [
                'filearea' => 'sectiondesigncompletionbackground',
                'component' => 'format_designer',
            ],
            'sectioncardcta' => [
                'filearea' => 'sectioncardcta',
                'component' => 'local_designer',
            ],
        ];
        $sectioninfo = parent::duplicate_section($originalsection);
        $oldsection = get_fast_modinfo($course)->get_section_info($originalsection->section);
        $oldsectionoptions = $this->get_section_options($oldsection->id);
        $coursecontext = \context_course::instance($course->id);
        $fs = get_file_storage();
        if (!empty($oldsectionoptions)) {
            foreach ($oldsectionoptions as $option => $value) {
                if ($value) {
                    $this->set_section_option($sectioninfo->id, $option, $value);
                }

                if (in_array($option, array_keys($fileareasections))) {
                    $files = $fs->get_area_files($coursecontext->id, $fileareasections[$option]['component'],
                    $fileareasections[$option]['filearea'], $oldsection->id, 'itemid, filepath, filename', false);
                    $file = current($files);
                    if ($file) {
                        $userdraft = [
                            'contextid' => $coursecontext->id,
                            'component' => $fileareasections[$option]['component'],
                            'filearea' => $fileareasections[$option]['filearea'],
                            'itemid' => $sectioninfo->id,
                            'filepath' => '/',
                            'filename' => $file->get_filename(),
                        ];
                        $fs->create_file_from_storedfile($userdraft, $file);
                    }
                }
            }
        }

        // Prepare the section summary.
        $files = $fs->get_area_files(
            $coursecontext->id, 'course', 'section', $oldsection->id, 'itemid, filepath, filename', false);
        $file = current($files);
        if ($file) {
            $userdraft = [
                'contextid' => $coursecontext->id,
                'component' => 'course',
                'filearea' => 'section',
                'itemid' => $sectioninfo->id,
                'filepath' => '/',
                'filename' => $file->get_filename(),
            ];
            $fs->create_file_from_storedfile($userdraft, $file);
        }
        return $sectioninfo;
    }


    /**
     * Updates format options for a section
     *
     * Section id is expected in $data->id (or $data['id'])
     * If $data does not contain property with the option name, the option will not be updated
     *
     * @param stdClass|array $data return value from {@see moodleform::get_data()} or array with data
     * @return bool whether there were any changes to the options values
     */
    public function update_section_format_options($data) {
        global $COURSE;
        $data = (array) $data;
        if (empty($data['sectionlayoutheader'])) {
            $data['sectionlayoutheader'] = get_string('sectionlayouts', 'format_designer');
        }
        if (format_designer_has_pro()) {
            local_designer\options::update_section_format_options($data);
        }
        return $this->update_format_options($data, $data['id']);
    }

    /**
     * Updates format options for a course or section
     *
     * If $data does not contain property with the option name, the option will not be updated
     *
     * @param stdClass|array $data return value from moodleform::get_data() or array with data
     * @param null|int $sectionid null if these are options for course or section id (course_sections.id)
     *     if these are options for section
     * @return bool whether there were any changes to the options values
     */
    protected function update_format_options($data, $sectionid = null) {
        global $DB;
        $data = $this->validate_format_options((array)$data, $sectionid);
        if (!$sectionid) {
            $allformatoptions = $this->course_format_options();
            $sectionid = 0;
        } else {
            $allformatoptions = $this->section_format_options();
        }
        if (empty($allformatoptions)) {
            // Nothing to update anyway.
            return false;
        }
        if (isset($allformatoptions['sectioncardcta_editor'])) {
            unset($allformatoptions['sectioncardcta_editor']);
        }
        $defaultoptions = [];
        $cached = [];
        foreach ($allformatoptions as $key => $option) {
            $defaultoptions[$key] = null;
            if (array_key_exists('default', $option)) {
                $defaultoptions[$key] = $option['default'];
            }
            expand_value($defaultoptions, $defaultoptions, $option, $key);
            $cached[$key] = ($sectionid === 0 || !empty($option['cache']));
        }
        $records = $DB->get_records('course_format_options',
                ['courseid' => $this->courseid,
                      'format' => $this->format,
                      'sectionid' => $sectionid,
                ], '', 'name,id,value');
        $changed = $needrebuild = false;
        foreach ($defaultoptions as $key => $value) {
            if (isset($records[$key])) {
                if (array_key_exists($key, $data) && $records[$key]->value != $data[$key]) {
                    $DB->set_field('course_format_options', 'value',
                            $data[$key], ['id' => $records[$key]->id]);
                    $changed = true;
                    $needrebuild = $needrebuild || $cached[$key];
                }
            } else {
                if (array_key_exists($key, $data) && $data[$key] !== $value) {
                    $newvalue = $data[$key];
                    $changed = true;
                    $needrebuild = $needrebuild || $cached[$key];
                } else {
                    $newvalue = $value;
                    // We still insert entry in DB but there are no changes from user point of
                    // view and no need to call rebuild_course_cache().
                }
                $DB->insert_record('course_format_options', [
                    'courseid' => $this->courseid,
                    'format' => $this->format,
                    'sectionid' => $sectionid,
                    'name' => $key,
                    'value' => $newvalue,
                ]);
            }
        }
        if ($needrebuild) {
            if ($sectionid) {
                // Invalidate the section cache by given section id.
                course_modinfo::purge_course_section_cache_by_id($this->courseid, $sectionid);
                // Partial rebuild sections that have been invalidated.
                rebuild_course_cache($this->courseid, true, true);
            } else {
                // Full rebuild if sectionid is null.
                rebuild_course_cache($this->courseid);
            }
        }
        if ($changed) {
            // Reset internal caches.
            if (!$sectionid) {
                $this->course = false;
            }
            unset($this->formatoptions[$sectionid]);
        }
        return $changed;
    }

    /**
     * Updates format options for a course.
     *
     * In case if course format was changed to 'designer', we try to copy options
     * 'coursedisplay' and 'hiddensections' from the previous format.
     *
     * @param stdClass|array $data return value from {@see moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@see update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        global $CFG;
        $data = (array)$data;
        if ($oldcourse !== null) {
            $oldcourse = (array)$oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    }
                }
                if ($key == 'courseduedateinfo') {
                    $data[$key] = get_string('timemanagementmissing', 'format_designer');
                }
                if ($key == 'coursecompletiondateinfo') {
                    $data[$key] = get_string('completiontrackingmissing', 'format_designer');
                }
                if ($key == 'popupactivities' && !format_designer_popup_installed()) {
                    $data[$key] = false;
                }
            }

            if (isset($oldcourse['coursetype'])
                && $oldcourse['coursetype'] != DESIGNER_TYPE_KANBAN
                && isset($data['coursetype'])
                && $data['coursetype'] == DESIGNER_TYPE_KANBAN) {
                    $this->setup_kanban_layouts($oldcourse);
            }
            if (isset($data['coursetype']) && $data['coursetype'] == DESIGNER_TYPE_KANBAN) {
                $data['coursedisplay'] = 0;
            }

        } else {
            if (isset($data['coursetype']) && $data['coursetype'] == DESIGNER_TYPE_KANBAN) {
                $this->setup_kanban_layouts($data);
                $data['coursedisplay'] = 0;
            }
        }
        unset($data['courseheader']);
        unset($data['popupactivitiesinfo']);
        unset($data['courseprerequisites']);

        // Convert the user staff roles list into string to update in db.
        if (isset($data['coursestaff']) && is_array($data['coursestaff'])) {
            $data['coursestaff'] = implode(",", $data['coursestaff']);
        }

        // Time management implode the array to string.
        if (isset($data['timemanagement']) && is_array($data['timemanagement']) ) {
            $data['timemanagement'] = implode(',', $data['timemanagement']);
        }

        if (isset($data['prerequisiteinfo']) && is_array($data['prerequisiteinfo'])) {
            $editoroptions = ['maxfiles' => -1, 'maxbytes' => $CFG->maxbytes, 'trusttext' => false,
                'noclean' => true,
                ];
            $context = context_course::instance($this->courseid, MUST_EXIST);
            // Setup the editor to save areafiles. hack.
            $data['prerequisiteinfo_editor'] = $data['prerequisiteinfo'];
            $data = file_postupdate_standard_editor(
                // The submitted data.
                (object) $data,
                // The field name in the database.
                'prerequisiteinfo',
                // The options.
                $editoroptions,
                // The combination of contextid, component, filearea, and itemid.
                $context,
                'local_designer',
                'prerequisiteinfo',
                0
            );
        }

        // Update the designer pro options, before update.
        if (format_designer_has_pro()) {
            local_designer\options::update_course_format_options($data, $this->courseid);
        }
        theme_reset_all_caches();
        return $this->update_format_options($data);
    }

    /**
     * Update the kanban layouts default options when layout changed to kanban mode.
     *
     * @param array $course
     * @return void
     */
    public function setup_kanban_layouts($course) {
        global $DB;
        $sections = $DB->get_records('course_sections', ['course' => $course['id']]);
        foreach ($sections as $section) {
            if ($section->section == 0) {
                continue;
            }
            $this->set_section_option($section->id, 'sectiontype', 'cards');
            $this->set_section_option($section->id, 'layoutmobilecolumn', '1');
            $this->set_section_option($section->id, 'layouttabletcolumn', '1');
            $this->set_section_option($section->id, 'layoutdesktopcolumn', '1');
        }
    }

    /**
     * Whether this format allows to delete sections.
     *
     * Do not call this function directly, instead use {@see course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Prepares the templateable object to display section name.
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @param null|lang_string|string $edithint
     * @param null|lang_string|string $editlabel
     * @return inplace_editable
     */
    public function inplace_editable_render_section_name($section, $linkifneeded = true,
            $editable = null, $edithint = null, $editlabel = null) {
        global $USER;
        if ($editable === null) {
            $editable = !empty($USER->editing) && has_capability('moodle/course:update',
                    context_course::instance($section->course));
        }
        if (empty($edithint)) {
            $edithint = new lang_string('editsectionname', 'format_designer');
        }
        if (empty($editlabel)) {
            $title = get_section_name($section->course, $section);
            $editlabel = new lang_string('newsectionname', 'format_designer', $title);
        }
        $style = '';
        if (format_designer_has_pro()) {
            if (isset($section->sectiondesignertextcolor)) {
                if ($section->sectiondesignertextcolor) {
                    $style = "color: $section->sectiondesignertextcolor" . ";";
                }
            }
        }
        $displayvalue = $title = get_section_name($section->course, $section);
        if ($linkifneeded) {
            // Display link under the section name if the course format setting is to display one section per page.
            $url = course_get_url($section->course, $section->section, ['navigation' => true]);
            if ($url) {
                $displayvalue = html_writer::link($url, $title, ['style' => $style]);
            }
            $itemtype = 'sectionname';
        } else {
            // If $linkifneeded==false, we never display the link (this is used when rendering the section header).
            // Itemtype 'sectionnamenl' (nl=no link) will tell the callback that link should not be rendered -
            // there is no other way callback can know where we display the section name.
            $itemtype = 'sectionnamenl';
        }
        return new \core\output\inplace_editable('format_' . $this->format, $itemtype, $section->id, $editable,
            $displayvalue, $section->name, $edithint, $editlabel);
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
    }

    /**
     * Callback used in WS core_course_edit_section when teacher performs an AJAX action on a section (show/hide).
     *
     * Access to the course is already validated in the WS but the callback has to make sure
     * that particular action is allowed by checking capabilities
     *
     * Course formats should register.
     *
     * @param section_info|stdClass $section
     * @param string $action
     * @param int $sr
     * @return null|array any data for the Javascript post-processor (must be json-encodeable)
     */
    public function section_action($section, $action, $sr) {
        global $PAGE;
        $modinfo = $this->get_modinfo();
        if (!($section instanceof section_info)) {
            $section = $modinfo->get_section_info($section->section);
        }

        if ($action == 'refresh') {
            $renderer = $this->get_renderer($PAGE);
            return [
                'content' => $renderer->course_section_updated($this, $section),
            ];
        } else {
            return parent::section_action($section, $action, $sr);
        }
    }


    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of configuration settings
     * @since Moodle 3.5
     */
    public function get_config_for_external() {
        // Return everything (nothing to hide).
        return $this->get_format_options();
    }

    /**
     * Set any arbitrary/custom option on this format, for a section.
     *
     * @param int $sectionid Course section number to set option for.
     * @param string $name Option name.
     * @param string $value Option value.
     * @return int Option record ID.
     * @throws dml_exception
     */
    public function set_section_option(int $sectionid, string $name, string $value): int {
        global $DB;

        $common = [
            'courseid' => $this->courseid,
            'format' => 'designer',
            'sectionid' => $sectionid,
            'name' => $name,
        ];

        if ($existingoption = $DB->get_record('course_format_options', $common)) {
            $existingoption->value = $value;
            $DB->update_record('course_format_options', $existingoption);
            return $existingoption->id;
        } else {
            $option = (object)$common;
            $option->value = $value;
            return $DB->insert_record('course_format_options', $option);
        }
    }

    /**
     * Get section option.
     *
     * @param int $sectionid Course section number to get option for.
     * @param string $name Option name.
     * @return string|null
     * @throws dml_exception
     */
    public function get_section_option(int $sectionid, string $name): ?string {
        global $DB;

        return $DB->get_field('course_format_options', 'value', [
            'courseid' => $this->courseid,
            'format' => 'designer',
            'sectionid' => $sectionid,
            'name' => $name,
        ]) ?: null;
    }

    /**
     * Get course module secondary navigation title.
     * @param object $cm
     * @return string title.
     */
    public function get_cm_secondary_title($cm) {
        $secondarytitletype = \format_designer\options::get_option($cm->id, 'secondarytype');
        $title = '';
        if ($secondarytitletype == 'activitytitle') {
            $title = $cm->name;
        } else if ($secondarytitletype == 'activitytype') {
            $title = get_string('modulename', $cm->modname);
        } else {
            // Custom title.
            $title = \format_designer\options::get_option($cm->id, 'secondarycustomtitle');
        }
        return !empty($title) ? $title : $cm->name;
    }

    /**
     * Get all options for section.
     *
     * @param int $sectionid
     * @return array Options
     */
    public function get_section_options(int $sectionid): array {
        global $DB;

        return $DB->get_records_menu('course_format_options', [
            'courseid' => $this->courseid,
            'format' => 'designer',
            'sectionid' => $sectionid,
        ], '', 'name, value');
    }

    /**
     * Returns a record from course database table plus additional fields
     * that course format defines
     *
     * @return stdClass
     */
    public function get_course() {
        global $CFG, $PAGE, $DB;
        $course = parent::get_course();
        // Course fields.
        if (isset($course->coursefields)) {
            $course->coursefields = is_string($course->coursefields) ? explode(',', $course->coursefields) : $course->coursefields;
        }

        // Convert the Time management to array.
        if (isset($course->timemanagement)) {
            $timemanagement = $course->timemanagement;
            $course->timemanagement = is_string($timemanagement) ? explode(',', $timemanagement) : $timemanagement;
        }

        if ($PAGE->pagetype == 'course-edit' && format_designer_has_pro()) {
            // Update the pro fields course values strucuture, Prepare files.
            local_designer\options::update_structure_get_course($course);
        }
        return $course;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return inplace_editable
 */
function format_designer_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'designer'], MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

/**
 * Format date based on format defined in settings.
 *
 * @param int $timestamp
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 */
function format_designer_format_date(int $timestamp) {
    if ($format = get_config('format_designer', 'dateformat')) {
        $component = strpos($format, 'strf') === 0 ? '' : 'format_designer';
    } else {
        $format = 'usstandarddate';
        $component = 'format_designer';
    }

    return userdate($timestamp, get_string($format, $component));
}

/**
 * Cut the Course content.
 *
 * @param string $str String to trim.
 * @param int $n
 * @return string
 */
function format_designer_modcontent_trim_char($str, $n = 25) {
    if (str_word_count($str) < $n) {
        return $str;
    }
    $arrstr = explode(" ", $str);
    $slicearr = array_slice($arrstr, 0, $n);
    $strarr = implode(" ", $slicearr);
    $strarr .= '...';
    return $strarr;
}

/**
 * Check if Designer Pro is installed.
 *
 * @return bool
 */
function format_designer_has_pro() {
    global $CFG;
    static $result;

    if ($result == null) {
        if (array_key_exists('designer', core_component::get_plugin_list('local'))) {
            require_once($CFG->dirroot.'/local/designer/lib.php');
            $result = true;
        } else {
            $result = false;
        }
    }

    return $result;
}

/**
 * Get the designer format custom layouts.
 * @return array list of available module pro layouts.
 */
function format_designer_get_pro_layouts() {
    $layouts = array_keys(core_component::get_plugin_list('layouts'));
    return $layouts;
}

/**
 * Get the designer format custom layouts
 * @return array
 */
function format_designer_get_all_layouts() {
    $layouts = [
        'default' => get_string('link', 'format_designer'),
        'list' => get_string('list', 'format_designer'),
        'cards' => get_string('cards', 'format_designer')
    ];
    $prolayouts = array_keys(core_component::get_plugin_list('layouts'));
    $prolayouts = (array) get_strings($prolayouts, 'format_designer');
    return array_merge($layouts, $prolayouts);
}

/**
 * Get section background image url.
 *
 * @param \section_info $section section info class instance.
 * @param stdclass $course Course record object.
 * @param course_modinfo $modinfo Course module info class instance.
 * @return string Section background image URL.
 */
function format_designer_get_section_background_image($section, $course, $modinfo) {
    if (!empty($section->sectiondesignerbackgroundimage)) {
        $coursecontext = \context_course::instance($course->id);
        $itemid = $section->id;
        $filearea = 'sectiondesignbackground';
        $realtiveactivities = isset($course->calsectionprogress) &&
        ($course->calsectionprogress == DESIGNER_PROGRESS_RELEVANTACTIVITIES) ? true : false;
        if (\format_designer\options::is_section_completed($section, $course, $modinfo, true, $realtiveactivities)
            && (isset($section->sectiondesignerusecompletionbg) && $section->sectiondesignerusecompletionbg)) {
            $filearea = 'sectiondesigncompletionbackground';
        }
        $files = get_file_storage()->get_area_files(
            $coursecontext->id, 'format_designer', $filearea,
            $itemid, 'itemid, filepath, filename', false);
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
}


/**
 * Serves file from sectiondesignbackground_filearea
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context Context used in the file.
 * @param string $filearea Filearea the file stored
 * @param array $args Arguments
 * @param bool $forcedownload Force download the file instead of display.
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function format_designer_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    require_login();
    if ($context->contextlevel != CONTEXT_COURSE && $filearea != 'sectiondesignbackground') {
        return false;
    }

    $areas = ['sectiondesignbackground', 'sectiondesigncompletionbackground'];
    if (!in_array($filearea, $areas)) {
        return false;
    }
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'format_designer', $filearea, $args[0], '/', $args[1]);
    if (!$file) {
        return false;
    }
    send_stored_file($file, 0, 0, 0, $options);
}

/**
 * Include the pro settings
 *
 * @param admin_settingspage $settings Admin format settings.
 * @return void
 */
function format_designer_include_prosettings($settings) {
    global $CFG, $DB;
    if (format_designer_has_pro() && file_exists($CFG->dirroot.'/local/designer_pro/setting.php')) {
        require_once($CFG->dirroot.'/local/designer_pro/setting.php');
    }
}

/**
 * Inject the designer elements into all moodle module settings forms.
 *
 * @param moodleform $formwrapper The moodle quickforms wrapper object.
 * @param MoodleQuickForm $mform The actual form object (required to modify the form).
 */
function format_designer_coursemodule_standard_elements($formwrapper, $mform) {
    global $CFG, $DB;
    $cm = $formwrapper->get_coursemodule();
    $course = $formwrapper->get_course();
    if ($course->format == 'designer') {
        $design = $designadv = \format_designer\options::get_default_options();
        if (isset($cm->id) && $cm->id) {
            $design = \format_designer\options::get_options($cm->id);
        }

        // Activity elements list to manage the visibility.
        $elements = [
            'icon' => 1,
            'visits' => 4,
            'calltoaction' => 4,
            'title' => 1,
            'description' => 1,
            'modname'  => 4,
            'completionbadge' => 1,
        ];
        $choice = [
            0 => get_string('hide'),
            1 => get_string('show'),
            2 => get_string('showonhover', 'format_designer'),
            3 => get_string('hideonhover', 'format_designer'),
            4 => get_string('remove'),
        ];

        $mform->addElement('header', 'moduledesign', get_string('activitydesign', 'format_designer'));
        $mform->addElement('html', get_string('activityelementsdisplay', 'format_designer'));
        foreach ($elements as $element => $defalut) {
            // Module background image repeat.
            $name = 'designer_activityelements['.$element.']';
            $title = get_string('activity:'.$element, 'format_designer');
            $mform->addElement('select', $name, $title, $choice);
            $mform->setType($name, PARAM_INT);
            $mform->setDefault($name, $defalut);
            if (isset($design->activityelements[$element])) {
                $mform->setDefault($name, $design->activityelements[$element]);
            }
            $adv = 'activityelements_'.$element.'_adv';
            if (isset($designadv->$adv) && $designadv->$adv) {
                $mform->setAdvanced($name);
            }
        }

        // Include the pro additional module fields.
        if (format_designer_has_pro()) {
            local_designer_coursemodule_standard_element($formwrapper, $mform);
        }

        // Secondary menu.
        $mform->addElement('html',  get_string('secondarymenu', 'format_designer'));
        $types = [
            'activitytitle' => get_string('stractivitytitle', 'format_designer'),
            'activitytype' => get_string('stractivitytype', 'format_designer'),
            'custom' => get_string('strcustom', 'format_designer'),
        ];
        $mform->addElement('select', 'designer_secondarytype', get_string('secondarymeu_title', 'format_designer'), $types);
        $mform->setType('designer_secondarytype', PARAM_TEXT);
        if (isset($design->secondarytype)) {
            $mform->setDefault('designer_secondarytype', $design->secondarytype);
        }

        $mform->addElement('text', 'designer_secondarycustomtitle', get_string('strcustomtitle', 'format_designer'));
        $mform->setType('designer_secondarycustomtitle', PARAM_TEXT);
        if (isset($design->secondarycustomtitle)) {
            $mform->setDefault('designer_secondarycustomtitle', $design->secondarycustomtitle);
        }
        $mform->hideIf('designer_secondarycustomtitle', 'designer_secondarytype', 'eq', 'activitytitle');
        $mform->hideIf('designer_secondarycustomtitle', 'designer_secondarytype', 'eq', 'activitytype');

        $mform->addElement('advcheckbox', 'designer_customtitleusecourseindex',
            get_string('customnameincourseindex', 'format_designer'));
        $mform->setType('designer_customtitleusecourseindex', PARAM_INT);
        if (isset($design->customtitleusecourseindex)) {
            $mform->setDefault('designer_customtitleusecourseindex', $design->customtitleusecourseindex);
        }
        $mform->hideIf('designer_customtitleusecourseindex', 'designer_secondarytype', 'eq', 'activitytitle');
        $mform->hideIf('designer_customtitleusecourseindex', 'designer_secondarytype', 'eq', 'activitytype');

        $mform->addElement('advcheckbox', 'designer_customtitleuseactivityitem',
            get_string('customnameinactivityitem', 'format_designer'));
        $mform->setType('designer_customtitleuseactivityitem', PARAM_INT);
        if (isset($design->customtitleuseactivityitem)) {
            $mform->setDefault('designer_customtitleuseactivityitem', $design->customtitleuseactivityitem);
        }
        $mform->hideIf('designer_customtitleuseactivityitem', 'designer_secondarytype', 'eq', 'activitytitle');
        $mform->hideIf('designer_customtitleuseactivityitem', 'designer_secondarytype', 'eq', 'activitytype');

        // Show tab.
        $tabs = [
            0 => get_string('disabled', 'format_designer'),
            1 => get_string('everywhere', 'format_designer'),
            2 => get_string('onlycoursepage', 'format_designer'),
        ];
        $mform->addElement('header', 'moduleheroactivity', get_string('heroactivity', 'format_designer'));
        $mform->addElement('select', 'designer_heroactivity', get_string('showastab', 'format_designer'), $tabs);
        $mform->setType('designer_heroactivity', PARAM_INT);
        if (isset($design->heroactivity)) {
            $mform->setDefault('designer_heroactivity', $design->heroactivity);
        }
        $posrange = array_combine(range(-10, 10), range(-10, 10));
        unset($posrange[0]);
        $mform->addElement('select', 'designer_heroactivitypos', get_string('order'), $posrange);
        $mform->setType('designer_heroactivitypos', PARAM_INT);
        $mform->setDefault('designer_heroactivitypos', 0);
        if (isset($design->heroactivitypos)) {
            $mform->setDefault('designer_heroactivitypos', $design->heroactivitypos);
        }
    }
}


/**
 * Hook the add/edit of the course module.
 *
 * @param stdClass $data Data from the form submission.
 * @param stdClass $course The course.
 */
function format_designer_coursemodule_edit_post_actions($data, $course) {
    global $DB;
    $cmid = $data->coursemodule;
    if ($course->format == 'designer') {
        $fields = [
            'designer_activityelements',
            'designer_secondarytype',
            'designer_secondarycustomtitle',
            'designer_customtitleusecourseindex',
            'designer_customtitleuseactivityitem',
            'designer_heroactivity',
            'designer_heroactivitypos',
            'designer_purpose',
        ];
        foreach ($fields as $field) {
            if (!isset($data->$field)) {
                continue;
            }
            $name = str_replace('designer_', '', $field);
            if (isset($data->$field)) {
                if (is_array($data->$field)) {
                    $value = json_encode($data->$field);
                } else {
                    $value = $data->{$field};
                }
                \format_designer\options::insert_option($cmid, $course->id, $name, $value);
            }
        }
    }
    return $data;
}

/**
 * Find the time management tool installed and enabled in the learningtools.
 *
 * @return bool result of the time management plugin availability.
 */
function format_designer_timemanagement_installed() {
    global $DB, $CFG;
    $tools = \core_plugin_manager::instance()->get_subplugins_of_plugin('local_learningtools');
    if (in_array('ltool_timemanagement', array_keys($tools))) {
        $status = $DB->get_field('local_learningtools_products', 'status', ['shortname' => 'timemanagement']);
        if ($status) {
            require_once($CFG->dirroot.'/local/learningtools/ltool/timemanagement/lib.php');
        }
        return ($status) ? true : false;
    }
    return false;
}


/**
 * Fix the edit settings dropdown menu. due to the Moodle CI, can't able to add it to the styles.css
 *
 * @param moodle_page $page
 * @return void
 */
function format_designer_editsetting_style($page) {
    if ($page->user_is_editing()) {
        // Fixed the overlapping issue by make this css rule as important. Moodle CI doesn't allow important.
        $style = '.format-designer .course-content ul.designer .kanban-board-activities li.section:first-child .right .dropdown
         .dropdown-menu .dropdown-subpanel .dropdown-menu {';
        $style .= 'left: 100% !important;';
        $style .= '}';
        echo html_writer::tag('style', $style, []);
    }
}

/**
 * Get modules layout class Moodle CI not allowed to add li in mustache.
 *
 * @param object $format
 * @param section_info $section
 * @return string|null
 */
function format_designer_get_module_layoutclass($format, $section) {
    $sectiontype = $format->get_section_option($section->id, 'sectiontype') ?: get_config('format_designer', 'sectiontype');

    if ($sectiontype == 'list') {
        $sectionlayoutclass = " position-relative ";
    } else if ($sectiontype == 'cards') {
        $sectionlayoutclass = ' card ';
    }

    if ($format->get_course()->coursetype == DESIGNER_TYPE_FLOW) {
        $sectionlayoutclass = 'card';
        $sectiontype = 'cards';
    }

    $prolayouts = format_designer_get_pro_layouts();
    if (in_array($sectiontype, $prolayouts)) {
        if (format_designer_has_pro()) {
            if ($sectiontype == 'circles') {
                $sectionlayoutclass = ' circle-layout card ';
            } else if ($sectiontype == 'horizontal_circles') {
                $sectionlayoutclass = ' horizontal_circles circle-layout card ';
            }
        }
    }

    return $sectionlayoutclass ?? '';
}

/**
 * Find the plugin format_popup installed.
 *
 * @return bool
 */
function format_designer_popup_installed() {
    $pluginman = \core_plugin_manager::instance();
    $plugininfo = $pluginman->get_plugin_info('format_popups');
    return !empty($plugininfo) ? true : false;
}

/**
 * Check course has heroactivity condition or not.
 * @param object $course
 * @return bool
 */
function format_designer_course_has_heroactivity($course) {
    global $DB, $PAGE;
    $iscourseheroactivity = ($course->sectionzeroactivities &&
        $course->heroactivity == DESIGNER_HERO_ACTVITIY_EVERYWHERE) ? true : false;
    $sql = "SELECT fd.value FROM {format_designer_options} fd
        WHERE fd.courseid = :courseid AND fd.name = :optionname AND fd.value = :optionvalue AND fd.cmid != :currentcm";
    $iscoursemodheroactivity = $DB->record_exists_sql($sql, ['optionname' => 'heroactivity',
        'optionvalue' => 1, 'courseid' => $course->id, 'currentcm' => $PAGE->cm->id, ]
    );
    return ($iscourseheroactivity || $iscoursemodheroactivity) ? true : false;
}

/**
 * Check the video time plugin in designer course format selected courses.
 *
 * @param object $course
 * @return bool.
 */
function format_designer_course_has_videotime($course) {
    global $DB;
    $pluginman = \core_plugin_manager::instance();
    $plugininfo = $pluginman->get_plugin_info('mod_videotime');
    if (!empty($plugininfo)) {
        $videotime = $DB->get_record("modules", ['name' => 'videotime']);
        if ($DB->record_exists('course_modules', ['course' => $course->id, 'module' => $videotime->id])) {
            return true;
        }
    }
    return false;
}

/**
 * This function extends the navigation with the hero activities items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function format_designer_extend_navigation_course($navigation, $course, $context) {
    global $DB, $PAGE, $COURSE;

    if ($course->format != 'designer') {
        return;
    }
    $format = course_get_format($COURSE);
    $course = $format->get_course();

    // Include the designer section js.
    $ispopupactivities = isset($course->popupactivities) && $course->popupactivities;
    $isvideotime = format_designer_course_has_videotime($course);
    $jsparams = [
        'courseid' => $course->id,
        'contextid' => $context->id,
        'popupactivities' => $ispopupactivities,
        'isvideotime' => $isvideotime,
        'issubpanel' => format_designer_is_support_subpanel(),
        'sectionreturn' => optional_param('section', 0, PARAM_INT),
    ];
    $PAGE->requires->js_call_amd('format_designer/designer_section', 'init', $jsparams);

    if (format_designer_has_pro()) {
        // Include the designer pro styles.
        $styleurl = \local_designer\courseoptions::create($course)->designer_include_style();
        $PAGE->requires->css($styleurl);
    }

    $isaddsecondary = ($navigation->children->count() <= 1 && $PAGE->context->contextlevel == CONTEXT_MODULE) &&
        (format_designer_course_has_heroactivity($course) || $course->secondarymenutocourse);
    $currentmodname = isset($PAGE->cm->modname) ? get_string('modulename', $PAGE->cm->modname) : '';
    $curentmodurl = isset($PAGE->cm->id) ? new moodle_url("/mod/{$PAGE->cm->modname}/view.php", ['id' => $PAGE->cm->id]) : '';
    $secondarycontent = html_writer::start_div('secondary-navigation d-print-none');
    $secondarycontent .= html_writer::start_tag('nav', ['class' => 'moremenu navigation observed']);
    $secondarycontent .= html_writer::start_tag('ul', ['id' => 'moremenu-63f8473d27694-nav-tabs',
        'class' => 'nav more-nav nav-tabs', 'role' => 'menubar', ]
    );
        $secondarycontent .= html_writer::start_tag('li', ['data-key' => 'modulepage', 'class' => 'nav-item', 'role' => 'none',
            'data-forceintomoremenu' => 'false', ]
        );
        $secondarycontent .= html_writer::link($curentmodurl, $currentmodname, ['role' => 'menuitem',
            'class' => 'nav-link active active_tree_node', 'aria-current' => 'true', ]
        );
        $secondarycontent .= html_writer::end_tag('li');
        $secondarycontent .= html_writer::start_tag('li', ['role' => 'none',
            'class' => 'nav-item dropdown dropdownmoremenu d-none', 'data-region' => 'morebutton', ]
        );
            $secondarycontent .= html_writer::link('#', get_string('moremenu'), ['class' => 'dropdown-toggle nav-link',
                'id' => 'moremenu-dropdown-63f8639161cce', 'role' => 'menuitem', 'data-toggle' => 'dropdown',
                'aria-haspopup' => 'true', 'aria-expanded' => 'false', 'tabindex' => -1, ]
            );
            $secondarycontent .= html_writer::start_tag('ul', ['class' => 'dropdown-menu dropdown-menu-left',
                'data-region' => 'moredropdown', 'aria-labelledby' => 'moremenu-dropdown-63f8639161cce', 'role' => 'menu', ]
            );
            $secondarycontent .= html_writer::end_tag('ul');
        $secondarycontent .= html_writer::end_tag('li');
    $secondarycontent .= html_writer::end_tag('ul');
    $secondarycontent .= html_writer::end_tag('nav');
    $secondarycontent .= html_writer::end_div('');

    // Add the course menu opition for all course pages.
    $secondarymenutocoursecontent = '';
    // Add the module page to visible the back to main course.
    $modbacktomain = '';
    if ($course->secondarymenutocourse) {
        $secondarymenutocoursecontent .= html_writer::start_tag("li", ["data-key" => 'designercoursehome',
        "class" => "nav-item", "role" => "none", "data-forceintomoremenu" => "true", ]
        );
        $secondarymenutocoursecontent .= html_writer::link(new moodle_url('/course/view.php', ['id' => $course->id]),
        get_string('strsecondarymenucourse', 'format_designer'), ['role' => 'menuitem',
            'class' => 'designercoursehome', "tabindex" => "-1" ]);
        $secondarymenutocoursecontent .= html_writer::end_tag("li");

        if (format_designer_has_pro() && $course->prerequisitesbackmain
            && $maincourse = local_designer_is_prerequisites_maincourse($course)) {
            $modbacktomain .= html_writer::start_tag("li", ["data-key" => 'backtomaincourse',
            "class" => "nav-item", "role" => "none", "data-forceintomoremenu" => "false", ]);
            $modbacktomain .= html_writer::link(new moodle_url('/course/view.php', ['id' => $maincourse->id]),
            get_string('backtomaincourse', 'format_designer'), ['role' => 'menuitem',
                'class' => 'backmain-course', "tabindex" => "-1", ]);
            $modbacktomain .= html_writer::end_tag("li");
        }
    }

    $sql = "SELECT fd.* FROM
        {format_designer_options} fd
        JOIN {course_modules} cm ON fd.cmid = cm.id
        WHERE courseid = :courseid AND cm.deletioninprogress = 0 AND
        (fd.name='heroactivity' OR fd.name='heroactivitypos') ORDER BY fd.cmid DESC";
    $records = $DB->get_records_sql($sql, ['courseid' => $course->id]);
    $neg = [];
    $pos = [];
    $reports = [];
    if ($records) {
        foreach ($records as $record) {
            $reports[$record->cmid][$record->name] = $record->value;
            $reports[$record->cmid]['cmid'] = $record->cmid;
        }
    }
    $neg = [];
    $pos = [];
    $reports = format_designer_section_zero_tomake_hero($reports, $course);
    if ($reports) {
        foreach ($reports as $report) {
            if ($report['heroactivitypos'] < 0) {
                $neg[] = $report;
            } else {
                $pos[] = $report;
            }
        }
        usort($neg, function($a, $b) {
            return $b['heroactivitypos'] - $a['heroactivitypos'];
        });
        usort($pos, function($a, $b) {
            return $a['heroactivitypos'] - $b['heroactivitypos'];
        });
    }

    $content = '';
    $modulecontent = false;
    $ishidecurrentcmid = false;
    $heroactivityduplicate = get_config("format_designer", "avoidduplicate_heromodentry") == 1 ? true : false;

    if ($reports) {
        $reports = array_merge($neg, $pos);

        foreach ($reports as $report) {
            if ($report['heroactivity']) {
                $cm = get_coursemodule_from_id('', $report['cmid']);
                $modurl = new moodle_url("/mod/$cm->modname/view.php", ['id' => $cm->id]);
                $nodepos = $report['heroactivitypos'];
                $cmtitle = $format->get_cm_secondary_title($cm);
                if ($PAGE->context->contextlevel == CONTEXT_MODULE) {
                    if ($report['heroactivity'] == DESIGNER_HERO_ACTVITIY_EVERYWHERE) {
                        if ($cm->id == $PAGE->cm->id && $heroactivityduplicate) {
                            $ishidecurrentcmid = true;
                        }
                        $content .= html_writer::start_tag("li", ["data-key" => $cm->id, "class" => "nav-item",
                            "role" => "none", "data-forceintomoremenu" => "true", ]
                        );
                        $linkclass = "designer-hero-activity position_$nodepos dropdown-item";
                        $content .= html_writer::link($modurl, $cmtitle, ['role' => 'menuitem', 'class' => $linkclass,
                            "tabindex" => "-1", "data-mod" => $cm->name, "data-cm" => $cm->id, ]
                        );
                        $content .= html_writer::end_tag("li");
                        $modulecontent = true;
                    }
                } else {
                    $node = $navigation->create($cmtitle, $modurl, navigation_node::TYPE_SETTING, $cm->name, $cm->id);
                    $node->add_class('designer-hero-activity');
                    $node->add_class("position_$nodepos");
                    $navigation->add_node($node);
                }
            }
        }
    }

    $designerpro = 0;
    $prerequisitebnewtab = 0;
    $courseprerequisitepos = 0;
    if (format_designer_has_pro()) {
        $course = course_get_format($course->id)->get_course();
        $prerequisitebnewtab = $course->prerequisitesnewtab;
        $courseprerequisitepos = ($course->courseprerequisitepos > 0) ? $course->courseprerequisitepos : 0;
        $designerpro = true;
    }

    $currentmodclass = ($PAGE->context->contextlevel == CONTEXT_MODULE) ? "nav.moremenu li[data-key=\"{$PAGE->cm->id}\"]" : "";
    $currentcmid = ($PAGE->context->contextlevel == CONTEXT_MODULE) ? $PAGE->cm->id : 0;
    $PAGE->requires->js_amd_inline("
        require(['jquery', 'core/moremenu'], function($, MenuMore) {
            $(document).ready(function() {
                // Added the secondary navigation when menu is empty.
                if ('$isaddsecondary' && !document.querySelector('.secondary-navigation')) {
                    $('$secondarycontent').insertAfter('#page-header');
                }
                var moremenu = document.querySelector('.secondary-navigation ul.nav-tabs .dropdownmoremenu ul');
                // Added the hero activities on the module page.
                if ('$modulecontent') {
                    if (moremenu) {
                        $(moremenu).append('$content');
                    }
                }

                // Added the course menu on the module page.
                var coursehome = document.querySelectorAll('nav.moremenu li[data-key=coursehome]')[0];
                if ('$secondarymenutocoursecontent' && !coursehome) {
                    if (moremenu) {
                        $(moremenu).append('$secondarymenutocoursecontent');
                    }
                }

                // Added the course menu on the module page.
                var backtomaincourse = document.querySelectorAll('nav.moremenu li[data-key=backtomaincourse]')[0];
                if ('$modbacktomain' && !backtomaincourse) {
                    if (moremenu) {
                        $(moremenu).append('$modbacktomain');
                    }
                }

                var secondarynav = document.querySelector('.secondary-navigation ul.nav-tabs');
                // Return false when the secondary nav is empty.
                if (secondarynav == undefined) {
                    return false;
                }


                function checkPosition(element, pos) {
                    if ($(element.children).hasClass('designer-hero-activity')) {
                        pos += 1;
                        return checkPosition(secondarynav.children[pos], pos);
                    } else {
                        return pos;
                    }
                }

                var heroActivity = document.querySelectorAll('.secondary-navigation .designer-hero-activity');
                var i = 0;
                var baseTab = document.querySelectorAll('.secondary-navigation ul.nav-tabs li')[0];
                if (baseTab) {
                    var baseDataElement = baseTab.getAttribute('data-key');
                }
                var dropdownmenu = document.querySelectorAll('.secondary-navigation .dropdownmoremenu .nav-item');
                var morebutton = document.querySelector('.secondary-navigation .nav li[data-region=morebutton]');

                // Remove the dropdown menu and push to the all elements in the outer nav item.
                if (heroActivity.length && dropdownmenu) {
                    dropdownmenu.forEach((e) => {
                        e.classList.remove('nav-link');
                        e.children[0].classList.remove('dropdown-item');
                        e.children[0].classList.add('nav-link');
                        e.setAttribute('data-forceintomoremenu', 'false');
                        secondarynav.insertBefore(e, morebutton);
                    });
                    $('.secondary-navigation .dropdownmoremenu .nav-item').remove();
                }


                // Check the hero activity or not to change the position.
                if (heroActivity) {
                    heroActivity.forEach((e) => {
                        e.classList.remove('dropdown-item');
                        e.classList.add('nav-link');
                        // Get the postion class.
                        var posClass = Array.from(e.classList).find(element => {
                            if (element.includes('position_')) {
                                return true;
                            }
                        });
                        // Convert the position.
                        var pos = posClass.substr(9, 3);
                        pos = Number(pos);
                        parent = e.parentNode;
                        parent.setAttribute('data-forceintomoremenu', 'false');
                        // Check to position wheather add to basenode after or before.
                        if (pos < 0) {
                            pos = 0;
                        } else {
                            pos += i;
                        }
                        let poselement = secondarynav.children[pos];
                        if (secondarynav.children[pos] == null) {
                            poselement = secondarynav.children[0];
                        }
                        pos = checkPosition(poselement, pos);
                        // Insert the heroactivity to current position.
                        if (secondarynav.children[pos] !== undefined) {
                            secondarynav.insertBefore(parent, secondarynav.children[pos]);
                        } else {
                            secondarynav.insertBefore(parent, morebutton);
                        }
                        var nodes = Array.prototype.slice.call(secondarynav.children);
                        var baseSelector = '.secondary-navigation ul.nav-tabs li'+'[data-key='+baseDataElement+']';
                        var baseHandler = document.querySelector(baseSelector);
                        i = nodes.indexOf(baseHandler);
                    });
                }

                // Remove the dupilcate entry.
                var modulepage = document.querySelectorAll('nav.moremenu li[data-key=modulepage]')[0];
                if ('$heroactivityduplicate' && modulepage) {
                    let moduleurl = modulepage.children[0].getAttribute('href');
                    let paramString = moduleurl.split('?')[1];
                    let moduleurlparams = new URLSearchParams(paramString);
                    let cmid = moduleurlparams.get('id');
                    if (cmid == '$currentcmid' && '$ishidecurrentcmid') {
                        isActive = modulepage.children[0].classList.contains('active');
                        modulepage.remove();
                        var currentdesignermod = document.querySelectorAll('$currentmodclass')[0];
                        if (currentdesignermod && isActive) {
                            $(currentdesignermod).find('.nav-link').addClass('active');
                        }
                    }
                }


                if ($designerpro) {
                    var prerequisites = document.querySelectorAll('.prerequisites-course')[0];
                    var moremenulink = document.querySelector('.secondary-navigation ul.nav-tabs .dropdownmoremenu a');
                    if (moremenulink) {
                        moremenulink.classList.remove('active');
                    }
                    if (prerequisites) {
                        prerequisites.classList.remove('dropdown-item');
                        prerequisites.classList.add('nav-link');
                        if ($prerequisitebnewtab) {
                            prerequisites.setAttribute('target', '_blank');
                        }
                        let parent = prerequisites.parentNode;
                        parent.setAttribute('data-forceintomoremenu', 'false');
                        secondarynav.insertBefore(parent, secondarynav.children[$courseprerequisitepos]);
                    }
                }

                var designercoursehome = document.querySelectorAll('.moremenu .designercoursehome')[0];
                if (designercoursehome) {
                        designercoursehome.classList.remove('dropdown-item');
                        designercoursehome.classList.add('nav-link');
                        let parent = designercoursehome.parentNode;
                        parent.setAttribute('data-forceintomoremenu', 'false');
                        secondarynav.insertBefore(parent, secondarynav.children[0]);
                }

                // Insert the prerequisite course link to secondary nav.
                if ($designerpro) {
                    var backmaincourse = document.querySelectorAll('.backmain-course')[0];
                    if (backmaincourse) {
                        backmaincourse.classList.remove('dropdown-item');
                        backmaincourse.classList.add('nav-link');
                        let parent = backmaincourse.parentNode;
                        parent.setAttribute('data-forceintomoremenu', 'false');
                        secondarynav.insertBefore(parent, secondarynav.children[0]);
                    }
                }
                MenuMore(secondarynav);
                return true;
            });
        });
    ");
}

/**
 * Set the section zero to hero activties.
 * @param array $reports
 * @param object $course
 * @return array reports
 */
function format_designer_section_zero_tomake_hero($reports, $course) {
    global $PAGE, $DB;
    $course = course_get_format($course->id)->get_course();
    if ($course->sectionzeroactivities) {
        $modinfo = get_fast_modinfo($course);
        if (isset($modinfo->sections[0])) {
            foreach ($modinfo->sections[0] as $modnumber) {
                if ($DB->record_exists('course_modules', ['deletioninprogress' => 0, 'id' => $modnumber])) {
                    if (isset($reports[$modnumber]) && !$reports[$modnumber]['heroactivity']) {
                        $reports[$modnumber]['heroactivity'] = ($course->heroactivity == DESIGNER_HERO_ACTVITIY_COURSEPAGE
                            && isset($PAGE->cm->id)) ? 0 : ($course->heroactivity == true);
                        $reports[$modnumber]['heroactivitypos'] = $course->heroactivitypos;
                    } else if (!isset($reports[$modnumber])) {
                        $reports[$modnumber]['heroactivity'] = ($course->heroactivity == DESIGNER_HERO_ACTVITIY_COURSEPAGE
                            && isset($PAGE->cm->id)) ? 0 : ($course->heroactivity == true);
                        $reports[$modnumber]['heroactivitypos'] = $course->heroactivitypos;
                        $reports[$modnumber]['cmid'] = $modnumber;
                    }
                }
            }
        }
    }
    return $reports;
}

/**
 * Get course type.
 * @return array coursetypes.
 */
function format_designer_get_coursetypes() {
    $coursetypes = [
        0 => get_string('normal'),
        DESIGNER_TYPE_KANBAN => get_string('kanbanboard', 'format_designer'),
        DESIGNER_TYPE_COLLAPSIBLE => get_string('collapsiblesections', 'format_designer'),
        DESIGNER_TYPE_FLOW => get_string('type_flow', 'format_designer'),
    ];
    return $coursetypes;
}

/**
 * Update the custom or other selected values.
 *
 * @param [object] $data
 * @param [string] $name
 * @param [string] $custom
 * @param [string] $csselement
 * @return void
 */
function format_designer_fill_custom_values($data, $name, $custom, $csselement) {
    if ((isset($data->{$name}) && $data->{$name})) {
        if ($data->{$name} == 'custom') {
            $value = $data->{$custom};
        } else {
            $value = $data->{$name};
        }
        if ($csselement) {
            return sprintf("$csselement: %s;", $value);
        } else {
            return $value;
        }
    }
    return "";
}

/**
 * Check the subpanel class exit or not.
 *
 * @return boolean
 */
function format_designer_is_support_subpanel() {
    if (class_exists('\core\output\local\action_menu\subpanel')) {
        return true;
    }
    return false;
}
