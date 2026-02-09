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
 * Mylearning widget class contains the courses user enrolled and not completed.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\widget\mylearning;

use block_dash\local\widget\abstract_widget;
use block_dash\local\widget\mylearning\mylearning_layout;
use context_module;
use context_course;
use html_writer;
use cm_info;
use moodle_url;

/**
 * Mylearning widget class contains the courses user enrolled and not completed.
 */
class mylearning_widget extends abstract_widget {

    /**
     * Get the name of widget.
     *
     * @return void
     */
    public function get_name() {
        return get_string('widget:mylearning', 'block_dash');
    }

    /**
     * Check the widget support uses the query method to build the widget.
     *
     * @return bool
     */
    public function supports_query() {
        return false;
    }

    /**
     * Layout class widget will use to render the widget content.
     *
     * @return \abstract_layout
     */
    public function layout() {
        return new mylearning_layout($this);
    }

    /**
     * Pre defined preferences that widget uses.
     *
     * @return array
     */
    public function widget_preferences() {
        $preferences = [
            'datasource' => 'mylearning',
            'layout' => 'mylearning',
        ];
        return $preferences;
    }

    /**
     * Build widget data and send to layout thene the layout will render the widget.
     *
     * @return void
     */
    public function build_widget() {
        global $USER, $CFG, $DB;
        $userid = $USER->id;
        require_once($CFG->dirroot.'/lib/enrollib.php');
        require_once($CFG->dirroot.'/course/renderer.php');

        $completed = $DB->get_records_sql(
            'SELECT * FROM {course_completions} cc
            WHERE cc.userid = :userid AND cc.timecompleted > 0',
            ['userid' => $userid]
        );
        $completedcourses = array_column($completed, 'course');
        $basefields = [
            'id', 'category', 'sortorder', 'format',
            'shortname', 'fullname', 'idnumber', 'summary',
            'startdate', 'visible',
            'groupmode', 'groupmodeforce', 'cacherev',
        ];
        $courses = enrol_get_my_courses($basefields, null, 0, [], false, 0, $completedcourses);
        array_walk($courses, function($course) {
            $courseelement = (class_exists('\core_course_list_element'))
            ? new \core_course_list_element($course) : new \course_in_list($course);
            $summary = (new \coursecat_helper($course))->get_course_formatted_summary($courseelement);

            $category = (class_exists('\core_course_category'))
            ? \core_course_category::get($course->category) : \coursecat::get($course->category);
            $course->courseimage = $this->courseimage($course);
            $course->category = (isset($category->name) ? format_string($category->name) : '');
            $course->badges = $this->badges($course);
            $course->coursecontent = $this->coursecontent($course);
            $course->contacts = $this->contacts($course);
            $course->customfields = $this->customfields($course);
            $course->courseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
            $course->summary = shorten_text($summary, 200);
            $course->fullname = $courseelement->get_formatted_fullname();
        });
        $this->data = (!empty($courses)) ? ['courses' => array_values($courses)] : [];

        return $this->data;
    }

    /**
     * After records are relieved from database each field has a chance to transform the data.
     * Example: Convert unix timestamp into a human readable date format
     *
     * @param \stdClass $course
     * @return mixed
     * @throws \moodle_exception
     */
    public function courseimage($course) {
        global $DB, $CFG;

        require_once("$CFG->dirroot/course/lib.php");

        if ($course = $DB->get_record('course', ['id' => $course->id])) {
            if (block_dash_is_totara()) {
                $image = course_get_image($course->id)->out();
            } else {
                $image = \core_course\external\course_summary_exporter::get_course_image($course);
            }

            if ($image == '') {
                $courseimage = get_config('local_dash', 'courseimage');
                if ($courseimage != '') {
                    $image = moodle_url::make_pluginfile_url(
                        \context_system::instance()->id,
                        'local_dash',
                        'courseimage',
                        null,
                        null,
                        $courseimage
                    );
                }
            }
            return $image;
        }

        return false;
    }

    /**
     * Generate the Course contents like sections and activities.
     *
     * @param stdclass $record
     * @return array
     */
    public function coursecontent($record) {
        global $CFG;
        require_once($CFG->dirroot.'/course/externallib.php');
        $options = ['name' => 'excludecontents', 'value' => true];
        $contents = self::get_course_contents($record->id, [$options]);
        return $contents;
    }

    /**
     * List of badges available in the course.
     *
     * @param \stdclass $record
     * @return void
     */
    public function badges($record) {
        global $USER, $CFG;
        require_once($CFG->dirroot.'/lib/badgeslib.php');
        $badges = badges_get_badges(BADGE_TYPE_COURSE, $record->id);
        $userbadges = badges_get_user_badges($USER->id, $record->id);
        $userbadges = array_column($userbadges, 'id');
        $coursecontext = \context_course::instance($record->id);
        $images = array_map(function($badge) use ($coursecontext, $userbadges) {
            $collected = (in_array($badge->id, $userbadges)) ? 'collected' : '';
            return html_writer::tag('li', print_badge_image($badge, $coursecontext, 'f1'), ['class' => $collected]);
        }, $badges);
        $heading = html_writer::tag('h5', get_string('badgestitle', 'block_dash'));
        $content = html_writer::tag('ul', implode('', $images));

        if ($images) {
            return html_writer::tag('div', $heading.$content, ['class' => 'badge-block']);
        }
    }

    /**
     * Contacts staff users list with profile pic.
     *
     * @param stdclass $record
     * @return array
     */
    public function contacts($record) {
        global $DB;
        $courserecord = get_course($record->id);
        $course = (class_exists('\core_course_list_element'))
            ? new \core_course_list_element($courserecord) : new \course_in_list($courserecord);
        $contacts = $course->get_course_contacts();
        $data = array_map(function($user) {
            global $PAGE;
            // Set the user picture data.
            $user = \core_user::get_user($user['user']->id);
            $userpicture = new \user_picture($user);
            $userpicture->size = 0; // Size f2.

            $profileurl = new \moodle_url('/user/profile.php', ['id' => $user->id]);
            $link = html_writer::empty_tag('img', [
                'src' => $userpicture->get_url($PAGE)->out(false),
                'alt' => fullname($user),
                'role' => "presentation",
            ]);
            $link .= html_writer::tag('span', fullname($user));

            $html = html_writer::start_tag('li', ['class' => 'contact-user']);
            $html .= html_writer::link($profileurl, $link);
            $html .= html_writer::end_tag('li');

            return $html;
        }, $contacts);

        if ($data) {
            $heading = html_writer::tag('h5', get_string('coursestafftitle', 'block_dash'));
            $content = html_writer::tag('ul', implode('', $data));

            return html_writer::tag('div', $heading.$content, ['class' => 'course-staff-block']);
        }
    }

    /**
     * Get course custom fields with data.
     *
     * @param stdclass $record
     * @return void
     */
    public function customfields($record) {
        global $CFG;
        $courserecord = get_course($record->id);
        $output = [];
        if (class_exists('\core_course_list_element')) {
            $course = new \core_course_list_element($courserecord);
            if ($course->has_custom_fields()) {
                foreach ($course->get_custom_fields() as $field) {
                    $output[] = [
                        'fieldname' => format_string($field->get_field()->get('name')),
                        'value' => ($field->export_value()) ? $field->export_value() : '-',
                    ];
                }
                return $output;
            }
        } else {
            $fields = get_course_custom_fields($record->id);
            if ($fields) {
                foreach ($fields as $field) {
                    $type = $field->datatype;
                    if (file_exists($CFG->dirroot.'/totara/customfield/field/'.$type.'/field.class.php')) {
                        require_once($CFG->dirroot.'/totara/customfield/field/'.$type.'/field.class.php');
                        $classname = 'customfield_'.$type;
                        $output[] = [
                            'fieldname' => format_string($field->fullname),
                            'value' => $classname::display_item_data($field->data),
                        ];
                    }
                }
                return $output;
            }
        }
    }

    /**
     * Get current course module progress. count of completion enable modules and count of completed modules.
     *
     * @param stdclass $course
     * @param int $userid
     * @return array Modules progress
     */
    protected function activity_progress($course, $userid) {
        $completion = new \completion_info($course);
        // First, let's make sure completion is enabled.
        if (!$completion->is_enabled()) {
            return null;
        }
        $result = [];

        // Get the number of modules that support completion.
        $modules = $completion->get_activities();
        $completionactivities = $completion->get_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY);

        $count = count($completionactivities);
        if (!$count) {
            return null;
        }
        // Get the number of modules that have been completed.
        $completed = 0;
        foreach ($completionactivities as $activity) {
            $cmid = $activity->moduleinstance;

            if (isset($modules[$cmid])) {
                $data = $completion->get_data($modules[$cmid], true, $userid);
                $completed += $data->completionstate == COMPLETION_INCOMPLETE ? 0 : 1;
            }
        }
        $percent = ($completed / $count) * 100;

        return ['count' => $count, 'completed' => $completed, 'percent' => $percent] + $result;
    }

    /**
     * Get course contents. Modified version of course/externallib class method get_course_contents.
     *
     * @param int $courseid course id
     * @return array
     * @since Moodle 2.9 Options available
     * @since Moodle 2.2
     */
    public static function get_course_contents($courseid) {
        global $CFG, $DB, $USER, $PAGE;
        // Include library files.
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->libdir . '/externallib.php');

        $filters = [];
        // Retrieve the course.
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        if ($course->id != SITEID) {
            // Check course format exist.
            if (file_exists($CFG->dirroot . '/course/format/' . $course->format . '/lib.php')) {
                require_once($CFG->dirroot . '/course/format/' . $course->format . '/lib.php');
            }
        }

        // Now security checks.
        $context = context_course::instance($course->id);
        $canupdatecourse = true;

        // Create return value.
        $coursecontents = [];

        if ($canupdatecourse || $course->visible
                || has_capability('moodle/course:viewhiddencourses', $context)) {

            $modinfo = get_fast_modinfo($course);
            $sections = $modinfo->get_section_info_all();
            $courseformat = course_get_format($course);
            $coursenumsections = $courseformat->get_last_section_number();
            $stealthmodules = [];   // Array to keep all the modules available but not visible in a course section/topic.

            $completioninfo = new \completion_info($course);

            $modinfosections = $modinfo->get_sections();
            foreach ($sections as $key => $section) {
                // This becomes true when we are filtering and we found the value to filter with.
                $sectionfound = false;
                $sectionvalues = [];
                $sectionvalues['id'] = $section->id;
                $sectionvalues['name'] = get_section_name($course, $section);
                $sectionvalues['visible'] = $section->visible;

                $options = (object) ['noclean' => true];
                list($sectionvalues['summary'], $sectionvalues['summaryformat']) =
                        external_format_text($section->summary, $section->summaryformat,
                                $context->id, 'course', 'section', $section->id, $options);
                $sectionvalues['section'] = $section->section;
                $sectionvalues['hiddenbynumsections'] = $section->section > $coursenumsections ? 1 : 0;
                $sectionvalues['uservisible'] = $section->uservisible;
                if (!empty($section->availableinfo)) {
                    $sectionvalues['availabilityinfo'] = \core_availability\info::format_info($section->availableinfo, $course);
                }

                $sectioncontents = [];

                // For each module of the section.
                $sectionactivitycompleted = $sectionactivitycount = 0;
                if (!empty($modinfosections[$section->section])) {

                    foreach ($modinfosections[$section->section] as $cmid) {
                        $cm = $modinfo->cms[$cmid];
                        $cminfo = cm_info::create($cm);
                        // Stop here if the module is not visible to the user on the course main page:
                        // The user can't access the module and the user can't view the module on the course page.
                        if (!$cm->uservisible) {
                            continue;
                        }

                        // This becomes true when we are filtering and we found the value to filter with.
                        $modfound = false;
                        $module = [];
                        $modcontext = context_module::instance($cm->id);

                        $module['id'] = $cm->id;
                        $module['name'] = external_format_string($cm->name, $modcontext->id);
                        $module['instance'] = $cm->instance;
                        $module['contextid'] = $modcontext->id;
                        $module['modname'] = (string) $cm->modname;
                        $module['modplural'] = (string) $cm->modplural;
                        $module['modicon'] = $cm->get_icon_url()->out(false);
                        $module['indent'] = $cm->indent;
                        $module['onclick'] = $cm->onclick;
                        $module['afterlink'] = $cm->afterlink;
                        $module['customdata'] = json_encode($cm->customdata);
                        $module['completion'] = $cm->completion;
                        $module['noviewlink'] = plugin_supports('mod', $cm->modname, FEATURE_NO_VIEW_LINK, false);

                        // Check module completion.
                        $completion = $completioninfo->is_enabled($cm);
                        if ($completion != COMPLETION_DISABLED) {
                            if (class_exists('\core_completion\cm_completion_details')) {
                                $cmcompletion = \core_completion\cm_completion_details::get_instance($cm, $USER->id);
                                $exporter = new \core_completion\external\completion_info_exporter($course, $cm, $USER->id);
                                $renderer = $PAGE->get_renderer('core');
                                $modulecompletiondata = (array)$exporter->export($renderer);
                                $module['completiondata'] = $modulecompletiondata;
                            } else {
                                $data = $completioninfo->get_data($cm);
                                $module['completiondata']['state'] = $data->completionstate;
                            }
                            $sectionactivitycompleted += $module['completiondata']['state'] ? 1 : 0;
                            $sectionactivitycount += 1;
                        }

                        if (!empty($cm->showdescription) || $module['noviewlink']) {
                            // We want to use the external format. However from reading get_formatted_content(), $cm->content
                            // Format is always FORMAT_HTML.
                            $options = ['noclean' => true];
                            list($module['description'], $descriptionformat) = external_format_text($cm->content,
                                FORMAT_HTML, $modcontext->id, $cm->modname, 'intro', $cm->id, $options);
                        }
                        // Url of the module.
                        $url = $cm->url;
                        if ($url) {
                            $module['url'] = $url->out(false);
                        } else {
                            $module['url'] = (new \moodle_url('/mod/'.$cm->modname.'/view.php', ['id' => $cm->id]))->out(false);
                        }
                        $canviewhidden = has_capability('moodle/course:viewhiddenactivities',
                                            context_module::instance($cm->id));
                        // User that can view hidden module should know about the visibility.
                        $module['visible'] = $cm->visible;
                        $module['visibleoncoursepage'] = $cm->visibleoncoursepage;
                        $module['uservisible'] = $cm->uservisible;
                        if (!empty($cm->availableinfo)) {
                            $module['availabilityinfo'] = \core_availability\info::format_info($cm->availableinfo, $course);
                        }
                        // Availability date (also send to user who can see hidden module).
                        if ($CFG->enableavailability && ($canviewhidden || $canupdatecourse)) {
                            $module['availability'] = $cm->availability;
                        }

                        // Assign result to $sectioncontents, there is an exception,
                        // Stealth activities in non-visible sections for students go to a special section.
                        if (!empty($filters['includestealthmodules']) && !$section->uservisible && $cm->is_stealth()) {
                            $stealthmodules[] = $module;
                        } else {
                            $sectioncontents[] = $module;
                        }
                    }
                }
                $sectionvalues['activitycompleted'] = $sectionactivitycompleted;
                $sectionvalues['activitycount'] = $sectionactivitycount;
                if ($sectionactivitycount > 0 && ($sectionactivitycount - $sectionactivitycompleted) == 0) {
                    $sectionvalues['completed'] = 1;
                }
                $sectionvalues['modules'] = $sectioncontents;
                $sectionvalues['hidemodules'] = count($sectioncontents) > 0 ? false : true;
                // Assign result to $coursecontents.
                $coursecontents[$key] = $sectionvalues;
                // Break the loop if we are filtering.
                if ($sectionfound) {
                    break;
                }
            }
            // Now that we have iterated over all the sections and activities, check the visibility.
            // We didn't this before to be able to retrieve stealth activities.
            foreach ($coursecontents as $sectionnumber => $sectioncontents) {
                $section = $sections[$sectionnumber];
                // Show the section if the user is permitted to access it OR
                // if it's not available but there is some available info text which explains the reason & should display OR
                // the course is configured to show hidden sections name.
                $showsection = $section->uservisible ||
                    ($section->visible && !$section->available && !empty($section->availableinfo)) ||
                    (!$section->visible && empty($courseformat->get_course()->hiddensections));

                if (!$showsection) {
                    unset($coursecontents[$sectionnumber]);
                    continue;
                }
                // Remove section and modules information if the section is not visible for the user.
                if (!$section->uservisible) {
                    $coursecontents[$sectionnumber]['modules'] = [];
                    // Remove summary information if the section is completely hidden only,
                    // Even if the section is not user visible, the summary is always displayed among the availability information.
                    if (!$section->visible) {
                        $coursecontents[$sectionnumber]['summary'] = '';
                    }
                }
            }

            // Include stealth modules in special section (without any info).
            if (!empty($stealthmodules)) {
                $coursecontents[] = [
                    'id' => -1,
                    'name' => '',
                    'summary' => '',
                    'summaryformat' => FORMAT_MOODLE,
                    'modules' => $stealthmodules,
                ];
            }
        }
        return $coursecontents;
    }
}
