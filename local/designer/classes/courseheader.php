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
 * File contains course header definition of designer pro.
 *
 * @package   local_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_designer;

use renderable;
use renderer_base;
use templatable;
use stdClass;
use local_designer\info;

/**
 * Designer course header template data.
 */
class courseheader implements renderable, templatable {

    /**
     * Course record with format options.
     *
     * @var stdClass
     */
    protected $course;

    /**
     * Designer course format instance.
     *
     * @var format_designer
     */
    protected $format;

    /**
     * Local designer info class instance.
     *
     * @var local_designer\info
     */
    protected $info;

    /**
     * User record.
     *
     * @var \stdClass
     */
    protected $user;

    /**
     * Contructor for course header render template.
     *
     * @param \format_designer $format
     * @param int $userid user id.
     */
    public function __construct(\format_designer $format, $userid=null) {
        global $USER;

        $this->course = $format->get_course();
        $this->format = $format;
        $this->info = info::create();
        $this->user = $userid ? \core_user::get_user($userid) : $USER;
    }

    /**
     * Create a instance of designer info class.
     *
     * @param object $format course format.
     * @return \local_designer\courseheader
     */
    public static function create($format) {
        return new self($format);
    }

    /**
     * Get the header instance only, course header is available for this page.
     *
     * @param \format_designer $format
     * @return self
     */
    public static function get_header_instance($format) {
        $self = new self($format);
        $instance = $self->is_available() ? $self : null;
        return $instance;
    }

    /**
     * Verify the course header is available for this page.
     *
     * @return bool
     */
    protected function is_available() {
        global $PAGE;

        // Course header disabled.
        if (!$this->course->courseheadertype) {
            return false;
        }

        // Hide on module pages.
        if (stripos($PAGE->pagetype, 'mod-') === 0) {
            return false;
        }

        return true;
    }

    /**
     * Get the header type clases form the course.
     *
     * @param bool $body Class for adding to body.
     * @return string $class course header type classes.
     */
    public function get_header_type_class($body=false) {
        global $PAGE;

        $course = $this->course;
        if (!$this->is_available()) {
            return '';
        }
        if ($course->courseheadertype == DESIGNER_COURSE_TYPE_HERO ) {
            $class = 'course-header-type-hero';
            $class .= $body ? ' designer-course-header-type' : '';
        } else if ($course->courseheadertype == DESIGNER_COURSE_TYPE_CONTENT) {
            $class = 'course-header-type-content';
            $class .= $body ? ' designer-course-header-type' : '';
        } else {
            $class = 'course-header-type-default';
        }
        return $class;
    }

    /**
     * Return the Course progress data.
     *
     * @return array $data course progress data
     */
    public function get_courseprogress() {
        $courseprogress = \format_designer\output\renderer::criteria_progress($this->course, $this->user->id);
        $progress = isset($courseprogress['percent']) ? $courseprogress['percent'] : 0;
        $coursprogress = ($progress) != null ? round($progress) : 0;
        $courseprogresscomp = ($progress == 100) ? true : false;
        $data = [
            'courseprogress' => $coursprogress,
            'courseprogresscomp' => $courseprogresscomp,
        ];
        return $data;
    }

    /**
     * Return the section progress type data.
     *
     * @param int $sectionprogress section progress value
     * @param bool $sectionprogresscomp section progress completion
     * @param string $backgroundcolor
     * @return array $templatecontext.
     */
    public function section_progress_type($sectionprogress, $sectionprogresscomp, $backgroundcolor) {
        // Section progress type.
        $templatecontext['progresspro'] = format_designer_has_pro();
        $templatecontext['progress'] = $this->get_progress_data('sectionprogresstype', $sectionprogress, 0,
            $sectionprogresscomp, 'section', $backgroundcolor);
        return $templatecontext;
    }

    /**
     * Return the progress donut degree values.
     *
     * @param int $progress progres value.
     * @return array $dountdata Donut degree
     */
    public function get_donut_degree($progress) {
        // Make the progress as circle.
        $donutdata = [];
        $deg1 = '';
        $deg2 = '';
        if (!empty($progress)) {
            $deg = (int) (($progress / 100 ) * 360);
            $deg1 = $deg;
            $deg2 = 0;
            if ($deg > 180) {
                $deg1 = '180';
                $deg2 = (int) $deg - $deg1;
            }
        }
        $donutdata = [
            'deg1' => $deg1,
            'deg2' => $deg2,
        ];
        return $donutdata;
    }

    /**
     * Return the progress data.
     *
     * @param string $setting config setting name
     * @param int $progress  progress value
     * @param int $courseprogresscompletion course progres completion value
     * @param int $sectionprogresscompletion section progress completion value.
     * @param string $type
     * @param string $bgcolor
     * @return string
     */
    public function get_progress_data($setting, $progress, $courseprogresscompletion = 0,
        $sectionprogresscompletion = 0, $type = 'course', $bgcolor = '') {
        global $OUTPUT;
        $course = $this->course;
        $progressdata = [];
        $degdata = $this->get_donut_degree($progress);
        $progresstype = $course->{$setting};
        $progresstextcolor = '';

        if ($bgcolor) {
            $progresstextcolor = \local_designer\options::getnotificationtextcolor($bgcolor);
        }

        $progressdata = [
            'progressbar' => isset($progresstype) && $progresstype == DESIGNER_PROGERSS_TYPE_BAR ? true : false,
            'progressdonut' => isset($progresstype) && $progresstype == DESIGNER_PROGERSS_TYPE_DONUT ? true : false,
            'progress' => $progress,
            'donutshowprogress' => (($progress == 100) && $course->completioncheckmark) ?
                \html_writer::tag('i', '', ['class' => 'fa fa-check']) : $progress . "%",
            'courseprogresscompletion' => (!empty($courseprogresscompletion)) ? true : false,
            'sectionprogresscompletion' => (!empty($sectionprogresscompletion)) ? true : false,
            'deg1' => $degdata['deg1'],
            'deg2' => $degdata['deg2'],
            'issection' => $type == 'section' ? true : false,
            'progresstextcolor' => $progresstextcolor,
        ];

        return $OUTPUT->render_from_template('local_designer/progress', $progressdata);
    }

    /**
     * Get the course header staffs.
     *
     * @return array $users users
     */
    public function get_staff_users() {
        $course = $this->course;
        $users = \format_designer\helper::create($course)->get_course_staff_users($course);
        $userfields = $course->userfields ?? [];
        return $users;
    }

    /**
     * Data for course header.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE, $USER, $CFG, $DB;

        if (!$this->course->courseheadertype) {
            return [];
        }

        // Flag for the current page is course main page or not.
        $iscoursemainpage = ($PAGE->pagetype == 'course-view-designer' && !optional_param('section', null, PARAM_INT));

        // General data initilaize.
        $template = [];
        $style = '';
        // Course format and context.
        $course = $this->course;
        $context = \context_course::instance($this->course->id);
        // Courseoptions instance.
        $courseoptions = courseoptions::create($course);

        // Header type classes.
        $headerclass[] = $this->get_header_type_class();

        $courseprogresstype = $course->courseprogresstype;

        // List of users based on the selected course staff role.
        $staffusers = $this->get_staff_users();
        // Include the selected user fields data to user object.
        if ($course->userfields) {
            if (!is_array($course->userfields)) {
                $course->userfields = explode(",", $course->userfields);

            }
            array_walk($staffusers, function(&$user, $key, $course) {
                $user->customfield = $customfield = []; // Remove all custom fields from list.
                $extrafields = profile_get_user_fields_with_data($user->userid);
                foreach ($extrafields as $formfield) {
                    if (in_array($formfield->inputname, $course->userfields)) {
                        $user->{$formfield->inputname} = $formfield->data ? $formfield->display_data() : '';
                    }
                }
                foreach ($course->userfields as $field) {
                    if (isset($user->$field) && !empty($user->$field)) {
                        $value = $user->$field;
                        // Conver the values time format.
                        info::create()->convert_valuetime_format($value, $field);
                        // Create custom field to display.
                        $customfield[]['value'] = $value;
                    }
                }
                $user->customfield = $customfield; // Attach the customfield value to the user object.
            }, $course);
        }
        // Prepare staff users list.
        $coursestaffusers = ($staffusers) ? [
            'status' => count($staffusers) ? true : false,
            'staffusers' => $staffusers,
            'currentuser' => $USER->id,
            'ismessaging' => $CFG->messaging,
        ] : false;
        // Common course header data for template.
        $template = [
            'courseheader' => true,
            'iscoursemainpage' => $iscoursemainpage,
            'isexpand' => $iscoursemainpage,
            'title' => $course->fullname,
            'coursestaffusers' => $coursestaffusers,
            'courseprogresstypestatus' => (!empty($courseprogresstype)) ? true : false,
        ];

        if (format_designer_has_pro()) {
            list($indicatorstatus, $indicatorclass) = \format_designer\output\renderer::get_course_completion_indicator($course);
            $template += [
                'indicatorstatus' => $indicatorstatus,
                'indicatorclass' => $indicatorclass,
                'showindicatorstatus' => isset($course->completionindicator) &&
                ($course->completionindicator == DESIGNER_CMPIND_BELOWPROGRESS) ? true : false,
            ];
        }

        // Current user progress for this course and completion status.
        $context = \context_course::instance($this->course->id);
        if (is_enrolled($context, $this->user->id)) {
            $courseprogress = $this->get_courseprogress();
            $coursprogress = $courseprogress['courseprogress'];
            $courseprogresscomp = $courseprogress['courseprogresscomp'];
            $template['progress'] = $this->get_progress_data('courseprogresstype', $coursprogress, $courseprogresscomp);
        }

        // Course summary options.
        if ($course->summary && $course->courseheadersummary != courseoptions::COURSEHEADER_SUMMARYDISABLED) {
            // Fetch the course summary. summary is rewrited and format are changed.
            $courseelement = new \core_course_list_element($course);
            $coursesummary = (new \coursecat_helper())->get_course_formatted_summary($courseelement);
            // Trim the summary content be global length.
            if ($course->courseheadersummary == courseoptions::COURSEHEADER_SUMMARYTRIMMED) {
                $length = get_config('local_designer', 'summarylength') ?: 300; // Use length configure, or use 300 as default.
                $coursesummary = shorten_text($coursesummary, $length);
            }
            $template['summary'] = $coursesummary;
        }

        // Genareate the additional content for course header.
        if ($course->additionalcontent) {
            // Additional content text and format. get_course method update the file to draftfile, we can't use the draftfile.
            $additionalcontent = $DB->get_field('course_format_options', 'value', ['courseid' => $course->id,
                        'name' => 'additionalcontent', 'format' => 'designer', ]);
            $additionalcontentformat = $DB->get_field('course_format_options', 'value', ['courseid' => $course->id,
                            'name' => 'additionalcontentformat', 'format' => 'designer', ]);
            // Rewirte the addtional content file urls.
            $additionalcontent = file_rewrite_pluginfile_urls($additionalcontent, 'pluginfile.php',
                $context->id, 'local_designer', 'additionalcontent', 0);
            // Include to the template data.
            $template['additionalcontent'] = format_text($additionalcontent, $additionalcontentformat);
        }
        if ($course->courseheadertextcolor) {
            $style .= "color: $course->courseheadertextcolor" . ";";
        }

        $height = (!empty($course->courseheaderheight)) ? $course->courseheaderheight : 0;
        $template['minheight'] = $height ? "min-height: $height" . "px;" : '';

        if ($course->courseheaderbgcolor) {
            $style .= "background-color: $course->courseheaderbgcolor" . ";";
        }
        $headerbgimage = $courseoptions->get_coursebg_images('courseheaderbgimage');
        if ($headerbgimage) {
            $style .= "background-image: url('" . $headerbgimage . "'); background-size: cover;
            background-repeat: no-repeat; background-position: center;" . ";";
            $headerclass[] = 'course-header-bgimage';
        }

        $template['style'] = $style;

        // Course header is full screen. attach the corresponded classes to course header.
        if ($course->courseheaderfullscreen) {
            $headerclass[] = 'course-header-full-screen';
            $headerclass[] = $iscoursemainpage ? 'fullheight' : '';
            $template['fullscreen'] = true;
        }

        // Include the course fields list of data to template.
        $coursefields = $courseoptions->get_coursefields_data();
        $template['coursefieldsavailable'] = count($coursefields);
        $template['coursefields'] = $coursefields;
        // Attach the designer free default time managmenet fields.
        $contentrender = new \format_designer\output\renderer($PAGE, 'general');
        $contentrender->modinfo = $this->format->get_modinfo(); // Set the modinfo to fetch due activities.
        $template['headermetadata'] = $contentrender->course_header_metadata_details($course, true); // Get the time data.
        $courseinfoelements = (!empty($coursefields) || !empty($additionalcontent) || !empty($staffusers) ||
        (!empty($course->activityprogress) || (!(empty($course->timemanagement))))) ? true : false;
        $template['courseinfoelements'] = $courseinfoelements;

        $toggleicon = ($PAGE->pagetype == 'course-view-designer' && !optional_param('section', null, PARAM_INT)) ?
        '<i class="fa fa-fw fa-angle-double-up"></i>' : '<i class="fa fa-fw fa-angle-double-down"></i>';

        $button = \html_writer::link('javascript:void(0);', $toggleicon, [
            'id' => 'course-info-toggle-btn', "role" => "button", "aria-expanded" => "false", "aria-controls" => "courseinfoBlock",
        ]);
        if ($courseinfoelements) {
            // Add the collapse and expand course header icon to page header.
            $PAGE->add_header_action($button);
        }
        // Atttach the classes for the course header.
        $template['headerclass'] = implode(' ', $headerclass);
        return $template;
    }

}
