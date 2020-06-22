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
 * Course renderer.
 *
 * @package    theme_noanme
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_iomadarmm\output\core;
defined('MOODLE_INTERNAL') || die();

// ===================================================================== //
// Make sure to include these items or it wont work properly!!!!!!
// ===================================================================== //

use moodle_url;
use coursecat_helper;
use html_writer;
use cm_info;
use cm_name;
use core_text;
use coursecat;
use course_in_list;
use stdClass;
use lang_string;
use completion_info;

require_once($CFG->dirroot . '/course/renderer.php');

/**
 * Course renderer class.
 *
 * @package    theme_noanme
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_renderer extends \core_course_renderer {

    /**
     * Renders html to display a course search form.
     *
     * @param string $value default value to populate the search field
     * @param string $format display format - 'plain' (default), 'short' or 'navbar'
     * @return string
     */
    public function course_search_form($value = '', $format = 'plain') {
        static $count = 0;
        $formid = 'coursesearch';
        if ((++$count) > 1) {
            $formid .= $count;
        }

        switch ($format) {
            case 'navbar' :
                $formid = 'coursesearchnavbar';
                $inputid = 'navsearchbox';
                $inputsize = 20;
                break;
            case 'short' :
                $inputid = 'shortsearchbox';
                $inputsize = 12;
                break;
            default :
                $inputid = 'coursesearchbox';
                $inputsize = 30;
        }

        $data = (object) [
            'searchurl' => (new moodle_url('/course/search.php'))->out(false),
            'id' => $formid,
            'inputid' => $inputid,
            'inputsize' => $inputsize,
            'value' => $value
        ];

        return $this->render_from_template('theme_iomadarmm/course_search_form', $data);
    }


    /* ========================================================== */
    /* HTM Custom Course Renderers
    /* ========================================================== */

    // coursecat_coures
    // Outputs the surrounding HTML for the course list

   

    /**
     * Renders the list of courses
     *
     * This is internal function, please use {@link core_course_renderer::courses_list()} or another public
     * method from outside of the class
     *
     * If list of courses is specified in $courses; the argument $chelper is only used
     * to retrieve display options and attributes, only methods get_show_courses(),
     * get_courses_display_option() and get_and_erase_attributes() are called.
     *
     * @param coursecat_helper $chelper various display options
     * @param array $courses the list of courses to display
     * @param int|null $totalcount total number of courses (affects display mode if it is AUTO or pagination if applicable),
     *     defaulted to count($courses)
     * @return string
     */
    protected function coursecat_courses(coursecat_helper $chelper, $courses, $totalcount = null) {
        global $CFG;
        if ($totalcount === null) {
            $totalcount = count($courses);
        }
        if (!$totalcount) {
            // Courses count is cached during courses retrieval.
            return '';
        }

        if ($chelper->get_show_courses() == self::COURSECAT_SHOW_COURSES_AUTO) {
            // In 'auto' course display mode we analyse if number of courses is more or less than $CFG->courseswithsummarieslimit
            if ($totalcount <= $CFG->courseswithsummarieslimit) {
                $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED);
            } else {
                $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_COLLAPSED);
            }
        }

        // prepare content of paging bar if it is needed
        $paginationurl = $chelper->get_courses_display_option('paginationurl');
        $paginationallowall = $chelper->get_courses_display_option('paginationallowall');
        if ($totalcount > count($courses)) {
            // there are more results that can fit on one page
            if ($paginationurl) {
                // the option paginationurl was specified, display pagingbar
                $perpage = $chelper->get_courses_display_option('limit', $CFG->coursesperpage);
                $page = $chelper->get_courses_display_option('offset') / $perpage;
                $pagingbar = $this->paging_bar($totalcount, $page, $perpage,
                        $paginationurl->out(false, array('perpage' => $perpage)));
                if ($paginationallowall) {
                    $pagingbar .= html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => 'all')),
                            get_string('showall', '', $totalcount)), array('class' => 'paging paging-showall'));
                }
            } else if ($viewmoreurl = $chelper->get_courses_display_option('viewmoreurl')) {
                // the option for 'View more' link was specified, display more link
                $viewmoretext = $chelper->get_courses_display_option('viewmoretext', new lang_string('viewmore'));
                $morelink = html_writer::tag('div', html_writer::link($viewmoreurl, $viewmoretext),
                        array('class' => 'paging paging-morelink'));
            }
        } else if (($totalcount > $CFG->coursesperpage) && $paginationurl && $paginationallowall) {
            // there are more than one page of results and we are in 'view all' mode, suggest to go back to paginated view mode
            $pagingbar = html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => $CFG->coursesperpage)),
                get_string('showperpage', '', $CFG->coursesperpage)), array('class' => 'paging paging-showperpage'));
        }

        // display list of courses
        $attributes = $chelper->get_and_erase_attributes('courses');
        $content = html_writer::start_tag('div', $attributes);

        if (!empty($pagingbar)) {
            $content .= $pagingbar;
        }

        $coursecount = 0;
        foreach ($courses as $course) {
            $coursecount ++;
            $classes = ($coursecount%2) ? 'odd' : 'even';
            if ($coursecount == 1) {
                $classes .= ' first';
            }
            if ($coursecount >= count($courses)) {
                $classes .= ' last';
            }
            $content .= $this->coursecat_coursebox($chelper, $course, $classes);
        }

        if (!empty($pagingbar)) {
            $content .= $pagingbar;
        }
        if (!empty($morelink)) {
            $content .= $morelink;
        }

        $content .= html_writer::end_tag('div'); // .courses
        return $content;
    }

    
    // coursecat_coursebox
    // Outputs the HTML for the course box

    /**
     * Displays one course in the list of courses.
     *
     * This is an internal function, to display an information about just one course
     * please use {@link core_course_renderer::course_info_box()}
     *
     * @param coursecat_helper $chelper various display options
     * @param course_in_list|stdClass $course
     * @param string $additionalclasses additional classes to add to the main <div> tag (usually
     *    depend on the course position in list - first/last/even/odd)
     * @return string
     */
    protected function coursecat_coursebox(coursecat_helper $chelper, $course, $additionalclasses = '') {
        global $CFG;
        if (!isset($this->strings->summary)) {
            $this->strings->summary = get_string('summary');
        }

        if ($chelper->get_show_courses() <= self::COURSECAT_SHOW_COURSES_COUNT) {
            return '';
        }

        if ($course instanceof stdClass) {
            require_once($CFG->libdir. '/coursecatlib.php');
            $course = new course_in_list($course);
        }

        $content = '';
        $classes = trim($additionalclasses);
        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));

        // Creates the main course box content divs
        $content .= html_writer::start_tag('div', array('class' => 'course-item col-xs-12 col-md-6'));
            $content .= html_writer::start_tag('div', array('class' => 'course-item__inner'));
                $content .= $this->coursecat_coursebox_content($chelper, $course);
            $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('div');

        return $content;

    }

    // coursecat_coursebox_content 
    // Outputs the content of the course box

    /**
     * Returns HTML to display course content (summary, course contacts and optionally category name)
     *
     * This method is called from coursecat_coursebox() and may be re-used in AJAX
     *
     * @param coursecat_helper $chelper various display options
     * @param stdClass|course_in_list $course
     * @return string
     */
    protected function coursecat_coursebox_content(coursecat_helper $chelper, $course) {
        global $CFG;
        
        if ($course instanceof stdClass) {
            require_once($CFG->libdir. '/coursecatlib.php');
            $course = new course_in_list($course);
        }
        $content = '';

        $coursename = $chelper->get_course_formatted_name($course);
        $courseoverviewfiles = $course->get_course_overviewfiles();
        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
        $hasimg = false;
        if(isset($courseoverviewfiles)) {
            foreach($courseoverviewfiles as $file) {
                $isimage = $file->is_valid_image();
                if($isimage) {
                    $imageurl = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
                    $hasimg = true;
                }
            }
        }
        
        // Start course-item__inner_image
        if ( $hasimg ) {
            $content .= html_writer::start_tag('figure', array('class' => 'course-item__inner__image'));
                $content .= html_writer::start_tag('img', array('src' => $imageurl));
            $content .= html_writer::end_tag('figure');
        }
        

        // Start course-item__inner__content
        $content .= html_writer::start_tag('div', array( 'class' => 'course-item__inner__content'));

            $content .= html_writer::start_tag('h4', array('class' => 'course-title'));
                $content .= $coursename;
            $content .= html_writer::end_tag('h4'); // /h4.course-title
            // Checks $course has a course summry and outputs if necessary
            if($course->has_summary()) {
                $content .= $chelper->get_course_formatted_summary($course, array('overflowdiv' => true, 'noclean' => true, 'para' => false));
            }

            $content .= html_writer::start_tag('div', array('class' => 'course-btn-wrapper'));
                $content .= html_writer::start_tag('a', array('class' => 'btn btn-primary', 'href' => $courseurl));
                    $content .= new lang_string('coursebtn', 'theme_iomadarmm');
                $content .= html_writer::end_tag('a');
            $content .= html_writer::end_tag('div');
            
            // display course contacts. See course_in_list::get_course_contacts()
            if ($course->has_course_contacts()) {
                $content .= html_writer::start_tag('div', array('class' => 'teachers-container'));
                    $content .= html_writer::start_tag('ul', array('class' => 'teachers list-unstyled'));
                    foreach ($course->get_course_contacts() as $userid => $coursecontact) {
                        $name = $coursecontact['rolename'];
                        $name .= ': ';
                        $name .= html_writer::link(new moodle_url('/user/view.php',
                                        array('id' => $userid, 'course' => SITEID)),
                                    $coursecontact['username']);
                        $content .= html_writer::tag('li', $name);
                    }
                    $content .= html_writer::end_tag('ul'); // .teachers
                    
                $content .= html_writer::end_tag('div'); // .teachers-container
            }

            // display course category if necessary (for example in search results)
            if ($chelper->get_show_courses() == self::COURSECAT_SHOW_COURSES_EXPANDED_WITH_CAT) {
                require_once($CFG->libdir. '/coursecatlib.php');
                if ($cat = coursecat::get($course->category, IGNORE_MISSING)) {
                    $content .= html_writer::start_tag('div', array('class' => 'coursecat'));
                    $content .= get_string('category').': '.
                            html_writer::link(new moodle_url('/course/index.php', array('categoryid' => $cat->id)),
                                    $cat->get_formatted_name(), array('class' => $cat->visible ? '' : 'dimmed'));
                    $content .= html_writer::end_tag('div'); // .coursecat
                }
            }

        $content .= html_writer::end_tag('div'); // /.course-item__content

        return $content;
    }


    /**
     * Renders HTML to display a list of course modules in a course section
     * Also displays "move here" controls in Javascript-disabled mode
     *
     * This function calls {@link core_course_renderer::course_section_cm()}
     *
     * @param stdClass $course course object
     * @param int|stdClass|section_info $section relative section number or section object
     * @param int $sectionreturn section number to return to
     * @param int $displayoptions
     * @return void
     */
    public function course_section_cm_list($course, $section, $sectionreturn = null, $displayoptions = array()) {
        global $USER;

        $output = '';
        $modinfo = get_fast_modinfo($course);
        if (is_object($section)) {
            $section = $modinfo->get_section_info($section->section);
        } else {
            $section = $modinfo->get_section_info($section);
        }
        $completioninfo = new completion_info($course);

        // check if we are currently in the process of moving a module with JavaScript disabled
        $ismoving = $this->page->user_is_editing() && ismoving($course->id);
        if ($ismoving) {
            $movingpix = new pix_icon('movehere', get_string('movehere'), 'moodle', array('class' => 'movetarget'));
            $strmovefull = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
        }

        // Get the list of modules visible to user (excluding the module being moved if there is one)
        $moduleshtml = array();
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];

                if ($ismoving and $mod->id == $USER->activitycopy) {
                    // do not display moving mod
                    continue;
                }

                if ($modulehtml = $this->course_section_cm_list_item($course,
                        $completioninfo, $mod, $sectionreturn, $displayoptions)) {
                    $moduleshtml[$modnumber] = $modulehtml;
                }
            }
        }

        $sectionoutput = '';
        if (!empty($moduleshtml) || $ismoving) {
            foreach ($moduleshtml as $modnumber => $modulehtml) {
                if ($ismoving) {
                    $movingurl = new moodle_url('/course/mod.php', array('moveto' => $modnumber, 'sesskey' => sesskey()));
                    $sectionoutput .= html_writer::tag('li',
                            html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
                            array('class' => 'movehere'));
                }

                $sectionoutput .= $modulehtml;
            }

            if ($ismoving) {
                $movingurl = new moodle_url('/course/mod.php', array('movetosection' => $section->id, 'sesskey' => sesskey()));
                $sectionoutput .= html_writer::tag('li',
                        html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
                        array('class' => 'movehere'));
            }
        }

        // Always output the section module list.
        $output .= html_writer::tag('ul', $sectionoutput, array('class' => 'section d-flex flex-row flex-wrap img-text'));

        return $output;
    }

    /**
     * Renders HTML to display one course module for display within a section.
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return String
     */
    public function course_section_cm_list_item($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {

        $courseTilesClass = $output = '';

        if($this->page->theme->settings->activity_tiles && $mod->modname != 'label') {
            $courseTilesClass = 'col-xs-12 col-md-6 col-lg-4 htm-tiles';
        } else if ($this->page->theme->settings->activity_tiles) {
            $courseTilesClass = 'col-xs-12 htm-tiles';
        } else {
            $courseTilesClass = 'col-xs-12';
        }
        
        if ($modulehtml = $this->course_section_cm($course, $completioninfo, $mod, $sectionreturn, $displayoptions)) {
            $modclasses = 'activity ' . $courseTilesClass . ' ' . $mod->modname . ' modtype_' . $mod->modname . ' ' . $mod->extraclasses;
            $output .= html_writer::tag('li', $modulehtml, array('class' => $modclasses, 'id' => 'module-' . $mod->id));
        }
        return $output;
    }







    /**
     * Returns the CSS classes for the activity name/content
     *
     * For items which are hidden, unavailable or stealth but should be displayed
     * to current user ($mod->is_visible_on_course_page()), we show those as dimmed.
     * Students will also see as dimmed activities names that are not yet available
     * but should still be displayed (without link) with availability info.
     *
     * @param cm_info $mod
     * @return array array of two elements ($linkclasses, $textclasses)
     */
    protected function course_section_cm_classes(cm_info $mod) {
        $linkclasses = '';
        $textclasses = '';
        if ($mod->uservisible) {
            $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
            $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
                has_capability('moodle/course:viewhiddenactivities', $mod->context);
            if ($accessiblebutdim) {
                $linkclasses .= ' dimmed';
                $textclasses .= ' dimmed_text';
                if ($conditionalhidden) {
                    $linkclasses .= ' conditionalhidden';
                    $textclasses .= ' conditionalhidden';
                }
            }
            if ($mod->is_stealth()) {
                // Stealth activity is the one that is not visible on course page.
                // It still may be displayed to the users who can manage it.
                $linkclasses .= ' stealth';
                $textclasses .= ' stealth';
            }
        } else {
            $linkclasses .= ' dimmed';
            $textclasses .= ' dimmed_text';
        }
        return array($linkclasses, $textclasses);
    }


    /**
     * Renders HTML to display one course module in a course section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm_name()}
     * {@link core_course_renderer::course_section_cm_text()}
     * {@link core_course_renderer::course_section_cm_availability()}
     * {@link core_course_renderer::course_section_cm_completion()}
     * {@link course_get_cm_edit_actions()}
     * {@link core_course_renderer::course_section_cm_edit_actions()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {
        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->is_visible_on_course_page()) {
            return $output;
        }

        $hasTiles = false;
        if ($this->page->theme->settings->activity_tiles) {
            $hasTiles = true;
        }

        $indentclasses = 'mod-indent';
        if (!$hasTiles) {
            if (!empty($mod->indent)) {
                $indentclasses .= ' mod-indent-'.$mod->indent;
                if ($mod->indent > 15) {
                    $indentclasses .= ' mod-indent-huge';
                }
            }
        }

        $modicons = '';
        if ($this->page->user_is_editing()) {
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $modicons .= ' '. $this->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $modicons .= $mod->afterediticons;
        }
        $modicons .= $this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);
        
        $output .= html_writer::start_tag('div', array('class' => 'activity-wrapper'));
        $activityname = $mod->modfullname;
        $output .= html_writer::start_tag( 'span', array( 'class' => 'activity-label' ) );
            $output .= $activityname;
        $output .= html_writer::end_tag( 'span' );
        if ($this->page->user_is_editing()) {
            $output .= course_get_cm_move($mod, $sectionreturn);
        }
        $output .= html_writer::start_tag('div', array('class' => 'mod-indent-outer'));
            // This div is used to indent the content.
            $output .= html_writer::div('', $indentclasses);
                // Start a wrapper for the actual content to keep the indentation consistent
                $output .= html_writer::start_tag('div');
                    // Display the link to the module (or do nothing if module has no url)
                    $cmname = $this->course_section_cm_name($mod, $displayoptions);

                    if (!empty($cmname)) {
                        // Start the div for the activity title, excluding the edit icons.
                        $additionalclasses = '';
                        if (!empty($modicons)) {
                            $additionalclasses = ' hasactions';
                        }
                        $output .= html_writer::start_tag('div', array('class' => 'activityinstance' . $additionalclasses));
                            $output .= $cmname;
                            // Module can put text after the link (e.g. forum unread)
                            $output .= $mod->afterlink;
                            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
                        $output .= html_writer::end_tag('div'); // .activityinstance
                    }
                    
                    // If there is content but NO link (eg label), then display the
                    // content here (BEFORE any icons). In this case cons must be
                    // displayed after the content so that it makes more sense visually
                    // and for accessibility reasons, e.g. if you have a one-line label
                    // it should work similarly (at least in terms of ordering) to an
                    // activity.
                    $contentpart = $this->course_section_cm_text($mod, $displayoptions);
                    $url = $mod->url;
                    if (empty($url)) {
                        $output .= $contentpart;
                    }
                    
                    if (!empty($modicons)) {
                        $output .= html_writer::span($modicons, 'actions');
                    }
                    // Show availability info (if module is not available).
                    $output .= $this->course_section_cm_availability($mod, $displayoptions);
                    // If there is content AND a link, then display the content here
                    // (AFTER any icons). Otherwise it was displayed before
                    if (!empty($url)) {
                        $output .= $contentpart;
                    }
                $output .= html_writer::end_tag('div'); // $indentclasses
                // End of indentation div.
            $output .= html_writer::end_tag('div');
            $output .= html_writer::start_tag('div', array('class' => 'activity-link'));
                $output .= html_writer::start_tag( 'a', array( 'href' => $url ) );
                    $output .= 'Enter Activity';
                $output .= html_writer::end_tag( 'a' );
            $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        

        return $output;
    }

    /**
     * Renders html to display a name with the link to the course module on a course page
     *
     * If module is unavailable for user but still needs to be displayed
     * in the list, just the name is returned without a link
     *
     * Note, that for course modules that never have separate pages (i.e. labels)
     * this function return an empty string
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_name_title(cm_info $mod, $displayoptions = array()) {
        $output = '';
        $url = $mod->url;
        if (!$mod->is_visible_on_course_page() || !$url) {
            // Nothing to be displayed to the user.
            return $output;
        }

        //Accessibility: for files get description via icon, this is very ugly hack!
        $instancename = $mod->get_formatted_name();
        $altname = $mod->modfullname;
        // Avoid unnecessary duplication: if e.g. a forum name already
        // includes the word forum (or Forum, etc) then it is unhelpful
        // to include that in the accessible description that is added.
        if (false !== strpos(core_text::strtolower($instancename),
                core_text::strtolower($altname))) {
            $altname = '';
        }
        // File type after name, for alphabetic lists (screen reader).
        if ($altname) {
            $altname = get_accesshide(' '.$altname);
        }

        list($linkclasses, $textclasses) = $this->course_section_cm_classes($mod);

        // Get on-click attribute value if specified and decode the onclick - it
        // has already been encoded for display (puke).
        $onclick = htmlspecialchars_decode($mod->onclick, ENT_QUOTES);
        
        if ($this->page->theme->settings->activity_tiles) {

            $activitylink = '';
            // Activity Name Label
            $activityname = $mod->modfullname;
            $activitylink .= html_writer::start_tag( 'span', array( 'class' => 'activity-label' ) );
                $activitylink .= $activityname;
            $activitylink .= html_writer::end_tag( 'span' );
                
            $activitylink .= html_writer::start_tag('div', array('class' => 'activity-title'));
                $activitylink.= html_writer::tag('span', $instancename . $altname, array('class' => 'instancename'));
            $activitylink .= html_writer::end_tag('div');

            $activitylink .= html_writer::start_tag( 'div', array( 'class' => 'activity-link' ) );
           
            
            $activitylink .= html_writer::end_tag( 'div' );
            

            $linkclasses .= ' clearfix';
        } else {
            $activitylink = html_writer::empty_tag('img', array('src' => $mod->get_icon_url(),
                            'class' => 'iconlarge activityicon', 'alt' => ' ', 'role' => 'presentation'));
            $activitylink.= html_writer::tag('span', $instancename . $altname, array('class' => 'instancename'));
        }
        
        
                
        if ($mod->uservisible) {
            $output .= html_writer::link($url, $activitylink, array('class' => $linkclasses, 'onclick' => $onclick));
        } else {
            // We may be displaying this just in order to show information
            // about visibility, without the actual link ($mod->is_visible_on_course_page()).
            $output .= html_writer::tag('div', $activitylink, array('class' => $textclasses));
        }
        return $output;
    }



    /* ============================================================================ */
    /* Activity Tiles */
    /* ============================================================================ */

    


    


    
}
