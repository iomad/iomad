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
 * Designer helper for course staff users option.
 *
 * @package   format_designer
 * @copyright 2021 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designer;

use stdClass;

/**
 * Course staff users helper class.
 */
class helper {

    /**
     * Instance for the helper class.
     *
     * @var \instance
     */
    public static $instance;

    /**
     * Course record.
     *
     * @var \stdclass
     */
    public $course;

    /**
     * Create instance of helper class.
     *
     * @param \stdClass $course
     * @return self
     */
    public static function create($course=null) {
        // Create this class instance.
        if (self::$instance == null) {
            self::$instance = new self();
        }
        // Self instance.
        self::$instance->set_course($course);

        return self::$instance;
    }

    /**
     * Set the course.
     *
     * @param stdclass $course
     */
    public function set_course($course) {
        $this->course = $course;
    }

    /**
     * Get the course header staffs.
     *
     * @param object $course
     * @return array data
     */
    public function get_course_staff_users($course) {
        global $PAGE, $DB, $USER;
        $staffs = [];
        $i = 1;

        // Course staff roles are not setup, exit here.
        if (!isset($course->coursestaff)) {
            return [];
        }

        // Course context.
        $coursecontext = \context_course::instance($course->id);
        // List of staff users ids for the course.
        $staffids = $this->get_staffs_users($course);
        // Staff users are not found.
        if (empty($staffids)) {
            return [];
        }
        foreach ($staffids as $userid) {
            $customfield = [];
            $user = \core_user::get_user($userid);
            profile_load_data($user);

            if (format_designer_has_pro() != 1) {
                $extrafields = profile_get_user_fields_with_data($userid);
                foreach ($extrafields as $formfield) {
                    if ($course->{$formfield->inputname}) {
                        $customfield[]['value'] = $formfield->data;
                    }
                }
            }
            $roles = get_user_roles($coursecontext, $userid, false);
            array_map(function($role) {
                $role->name = role_get_name($role);
                return $role;
            }, $roles);
            $roles = implode(", ", array_column($roles, 'name'));

            // Users data list.
            $list = clone $user;
            $list->userid = $userid;
            $list->email = $user->email;
            $list->fullname = fullname($user);
            // Contact and profile view url.
            $list->profileurl = new \moodle_url('/user/profile.php', ['id' => $userid]);
            $list->contacturl = new \moodle_url('/message/index.php', ['id' => $userid]);
            // User picture.
            $userpicture = new \user_picture($user);
            $userpicture->size = 1; // Size f1.
            $list->profileimageurl = $userpicture->get_url($PAGE)->out(false);

            $list->role = $roles; // List of roles, user assigned to.
            $list->showaddtocontacts = ($USER->id != $user->id) ? true : false;
            // Check this staff is in current user contact.
            $iscontact = \core_message\api::is_contact($USER->id, $user->id);
            $list->iscontact = $iscontact;
            $list->contacttitle = $iscontact ? get_string('removefromyourcontacts', 'message') :
                get_string('addtoyourcontacts', 'message');
            // List of custom fields.
            $list->customfield = $customfield;
            // Add active class to the first user for carousel.
            $list->active = ($i == 1) ? true : false;

            $staffs[] = $list; // Attach to staffs list.
            $i++;
        }
        return $staffs;
    }

    /**
     * Get course staff users.
     *
     * @param object $course
     * @return array userids
     */
    protected function get_staffs_users($course) {
        $staffids = [];
        $staffroleids = explode(",", $course->coursestaff);
        $enrolusers = enrol_get_course_users_roles($course->id);
        if (!empty($enrolusers)) {
            foreach ($enrolusers as $userid => $roles) {
                foreach ($staffroleids as $staffid) {
                    if (isset($roles[$staffid])) {
                        $staffids[] = $userid;
                    }
                }
            }
        }
        return array_unique($staffids);
    }
}
