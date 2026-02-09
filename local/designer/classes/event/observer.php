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
 * Event observers supported by this plugin.
 *
 * @package    local_designer
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_designer\event;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot."/course/format/lib.php");
require_once($CFG->dirroot. "/local/designer/lib.php");

/**
 * Event observers supported by this plugin.
 *
 * @package    local_designer
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Create course completion request when the user is created.
     *
     * @param \core\event\course_completion_updated $event
     */
    public static function create_course_completion_updated(\core\event\course_completion_updated $event) {
        global $DB;
        $updategroupcoursetoprecourses = [];
        $data = $event->get_data();
        $course = course_get_format($data['courseid'])->get_course();
        if ($course->format == 'designer') {
            if ($course->prerequisitesautostudents) {
                local_designer_prerequisites_autoenrol($course);
            }

            // Here get the current course groups and assign the group courses update.
            $groups = local_designer_get_pregroups($course->id);
            if ($groups) {
                $existprecourses = local_designer_is_prerequisites_courses($course, false);
                foreach ($groups as $group) {
                    $groupcourses = local_designer_get_pregroup_courses($group);
                    $removegroupcourses = array_diff($groupcourses, $existprecourses);
                    if (!empty($removegroupcourses)) {
                        $removepregroupsql = '(pregroupid = :groupid AND courseid ';
                        list($removegroupcoursessql, $removegroupcoursesparam) = $DB->get_in_or_equal($removegroupcourses,
                            SQL_PARAMS_NAMED);
                        $removegroupcoursesparam['groupid'] = $group;
                        $DB->delete_records_select('local_designer_groupcourses', $removepregroupsql .
                            $removegroupcoursessql . ")", $removegroupcoursesparam);
                        $updategroupcourses = local_designer_get_pregroup_courses($group);
                        $DB->set_field('local_designer_pregroups', 'coursesorder',
                            implode(",", $updategroupcourses), ['id' => $group]);
                    }
                }
            }

            if (local_designer_is_prerequisites_courses($course)) {
                local_designer_clear_criteria_precourses($course);
                local_designer_clear_criteria_groupcourses($course);
                $precourses = local_designer_is_nongroup_assign_precourses($course);
                $precourses = array_flip($precourses);
                // Check prerequisitecourses insert or update.
                if (!empty($precourses)) {
                    $existrecord = $DB->get_record('course_format_options', ['courseid' => $course->id,
                        'name' => 'prerequisitecourses', 'format' => 'designer', ]);
                    if (!$existrecord) {
                        $record = new \stdClass();
                        $record->courseid = $course->id;
                        $record->format = 'designer';
                        $record->name = 'prerequisitecourses';
                        $record->value = implode(",", $precourses);
                        $DB->insert_record('course_format_options', $record);
                    } else {
                        $existprecourses = explode(",", $existrecord->value);
                        // Addeing the new course.
                        if ($addprecourses = array_diff($precourses, $existprecourses)) {
                            $existprecourses = array_merge($existprecourses, $addprecourses);
                        }

                        // Removeing the exist one.
                        if ($removeprecourses = array_diff( $existprecourses, $precourses)) {
                            $existprecourses = array_diff($existprecourses, $removeprecourses);
                        }
                        $existrecord->value = implode(",", $existprecourses);
                        $DB->update_record('course_format_options', $existrecord);
                    }
                } else {
                    // Remove the prerequisite course when empty of non grouping precourses.
                    local_designer_remove_exist_precourses($course);
                }
            } else {
                // Remove the prerequisite course when empty of precourses.
                local_designer_remove_exist_precourses($course);
            }
        }
    }

    /**
     * Create course updated request.
     *
     * @param \core\event\course_updated $event
     */
    public static function create_course_updated(\core\event\course_updated $event) {
        $eventdata = $event->get_data();
        $course = course_get_format($eventdata['courseid'])->get_course();
        if ($course->format == 'designer') {
            if (isset($course->prerequisitesautostudents) && $course->prerequisitesautostudents) {
                local_designer_prerequisites_autoenrol($course);
            }
        }
    }

    /**
     * Create role assigned request.
     *
     * @param \core\event\role_assigned $event
     */
    public static function create_role_assigned(\core\event\role_assigned $event) {
        $contextid = $event->contextid;
        $userid = $event->relateduserid;
        $context = \context::instance_by_id($contextid);
        if ($context->contextlevel == CONTEXT_COURSE) {
            $course = course_get_format($context->instanceid)->get_course();
            if ($course->format == 'designer') {
                if ($course->prerequisitesautostudents) {
                    $user = \core_user::get_user($userid);
                    local_designer_prerequisites_autoenrol($course, $user);
                }
            }
        }
    }

    /**
     * Create user enrollment deleted request created.
     *
     * @param \core\event\user_enrolment_deleted $event
     */
    public static function create_user_enrolment_deleted(\core\event\user_enrolment_deleted $event) {
        $course = course_get_format($event->courseid)->get_course();
        if ($course->format == 'designer') {
            if ($course->prerequisitesunenrolstudents) {
                $user = \core_user::get_user($event->relateduserid);
                local_designer_prerequisites_unenrol($course, $user);
            }
        }
    }

    /**
     * Create user enrollment updated request created.
     *
     * @param \core\event\user_enrolment_updated $event
     */
    public static function create_user_enrolment_updated(\core\event\user_enrolment_updated $event) {
        global $DB;
        $ue = $DB->get_record('user_enrolments', ['id' => $event->objectid]);
        $course = course_get_format($event->courseid)->get_course();
        if ($course->format == 'designer') {
            if ($course->prerequisitesunenrolstudents) {
                $user = \core_user::get_user($event->relateduserid);
                if ($ue->timeend < time()) {
                    local_designer_prerequisites_unenrol($course, $user);
                }
            }
        }
    }
}
