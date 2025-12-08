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
 * License enrolment external functions.
 *
 * @package   enrol_license
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * License enrolment external functions.
 *
 * @package   enrol_license
 * @copyright  2011 E-Learn Design Ltd. http://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_license_external extends external_api {

    /**
     * Returns description of get_instance_info() parameters.
     *
     * @return external_function_parameters
     */
    public static function get_instance_info_parameters() {
        return new external_function_parameters(
                ['instanceid' => new external_value(PARAM_INT, 'instance id of license enrolment plugin.')]
            );
    }

    /**
     * Return license-enrolment instance information.
     *
     * @param int $instanceid instance id oflicenseenrolment plugin.
     * @return array instance information.
     * @throws moodle_exception
     */
    public static function get_instance_info($instanceid) {
        global $DB, $CFG;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(self::get_instance_info_parameters(), ['instanceid' => $instanceid]);

        // Retrievelicenseenrolment plugin.
        $enrolplugin = enrol_get_plugin('license');
        if (empty($enrolplugin)) {
            throw new moodle_exception('invaliddata', 'error');
        }

        self::validate_context(context_system::instance());

        $enrolinstance = $DB->get_record('enrol', ['id' => $params['instanceid']], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $enrolinstance->courseid], '*', MUST_EXIST);
        if (!core_course_category::can_view_course_info($course) && !can_access_course($course)) {
            throw new moodle_exception('coursehidden');
        }

        $instanceinfo = (array) $enrolplugin->get_enrol_info($enrolinstance);
        if (isset($instanceinfo['requiredparam']->enrolpassword)) {
            $instanceinfo['enrolpassword'] = $instanceinfo['requiredparam']->enrolpassword;
        }
        unset($instanceinfo->requiredparam);

        return $instanceinfo;
    }

    /**
     * Returns description of get_instance_info() result value.
     *
     * @return external_description
     */
    public static function get_instance_info_returns() {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'id of course enrolment instance'),
                'courseid' => new external_value(PARAM_INT, 'id of course'),
                'type' => new external_value(PARAM_PLUGIN, 'type of enrolment plugin'),
                'name' => new external_value(PARAM_RAW, 'name of enrolment plugin'),
                'status' => new external_value(PARAM_RAW, 'status of enrolment plugin'),
                'enrolpassword' => new external_value(PARAM_RAW, 'password required for enrolment', VALUE_OPTIONAL),
            ]
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function enrol_user_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Id of the course'),
                'password' => new external_value(PARAM_RAW, 'Enrolment key', VALUE_DEFAULT, ''),
                'instanceid' => new external_value(PARAM_INT, 'Instance id oflicenseenrolment plugin.', VALUE_DEFAULT, 0),
            ]
        );
    }

    /**
     * Enrol the current user in the given course using a license.
     *
     * @param int $courseid id of course
     * @param string $password enrolment key
     * @param int $instanceid instance id oflicenseenrolment plugin
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function enrol_user($courseid, $password = '', $instanceid = 0) {
        global $CFG, $DB, $USER;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(self::enrol_user_parameters(),
                                            [
                                                'courseid' => $courseid,
                                                'password' => $password,
                                                'instanceid' => $instanceid,
                                            ]);

        $warnings = [];

        $course = get_course($params['courseid']);
        $context = context_course::instance($course->id);
        self::validate_context(context_system::instance());

        if (!core_course_category::can_view_course_info($course)) {
            throw new moodle_exception('coursehidden');
        }

        // Retrieve thelicenseenrolment plugin.
        $enrol = enrol_get_plugin('license');
        if (empty($enrol)) {
            throw new moodle_exception('canntenrol', 'enrol_license');
        }

        // We can expect multiple license-enrolment instances.
        $instances = [];
        $enrolinstances = enrol_get_instances($course->id, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "license") {
                // Instance specified.
                if (!empty($params['instanceid'])) {
                    if ($courseenrolinstance->id == $params['instanceid']) {
                        $instances[] = $courseenrolinstance;
                        break;
                    }
                } else {
                    $instances[] = $courseenrolinstance;
                }

            }
        }
        if (empty($instances)) {
            throw new moodle_exception('canntenrol', 'enrol_license');
        }

        // Try to enrol the user in the instance/s.
        $enrolled = false;
        foreach ($instances as $instance) {
            $enrolstatus = $enrol->can_license_enrol($instance);
            if ($enrolstatus === true) {
                if ($instance->password && $params['password'] !== $instance->password) {

                    // Check if we are using group enrolment keys.
                    if ($instance->customint1) {
                        require_once($CFG->dirroot . "/enrol/license/locallib.php");

                        if (!enrol_license_check_group_enrolment_key($course->id, $params['password'])) {
                            $warnings[] = [
                                'item' => 'instance',
                                'itemid' => $instance->id,
                                'warningcode' => '2',
                                'message' => get_string('passwordinvalid', 'enrol_license'),
                            ];
                            continue;
                        }
                    } else {
                        if ($enrol->get_config('showhint')) {
                            $hint = core_text::substr($instance->password, 0, 1);
                            $warnings[] = [
                                'item' => 'instance',
                                'itemid' => $instance->id,
                                'warningcode' => '3',
                                'message' => s(get_string('passwordinvalidhint', 'enrol_license', $hint)),
                            ];
                            continue;
                        } else {
                            $warnings[] = [
                                'item' => 'instance',
                                'itemid' => $instance->id,
                                'warningcode' => '4',
                                'message' => get_string('passwordinvalid', 'enrol_license'),
                            ];
                            continue;
                        }
                    }
                }

                // Get the license information.
                $sql = "SELECT cl.*, clu.id AS userlicenseid
                        FROM {companylicense} cl
                        JOIN {companylicense_users} clu ON (cl.id = clu.licenseid)
                        WHERE clu.userid = :userid
                        AND clu.isusing = 0
                        AND clu.licensecourseid = :courseid";
                if (!$license = $DB->get_record_sql($sql, ['userid' => $USER->id,
                                                           'courseid' => $course->id])) {
                    // Set the companyid.
                    $companyid = iomad::get_my_companyid(context_system::instance(), false);

                    $blanketsql = "SELECT cl.* FROM {companylicense} cl
                                   JOIN {companylicense_courses} clc ON (cl.id = clc.licenseid)
                                   WHERE clc.courseid = :courseid
                                   AND cl.companyid =:companyid
                                   AND cl.startdate < :startdate
                                   AND cl.expirydate > :expirydate
                                   AND cl.type = 4
                                   AND cl.used < cl.allocation";
                    $license = $DB->get_record_sql($blanketsql, [
                                                                'courseid' => $instance->courseid,
                                                                'companyid' => $companyid,
                                                                'startdate' => time(),
                                                                'expirydate' => time(),
                                                            ]);
                }

                if (empty($license)) {
                    throw new moodle_exception('canntenrol', 'enrol_license');
                }

                // If we are a blanket license we need to allocate the license at this time.
                if ($license->type == 4) {
                    $issuedate = time();
                    $userlicense = (object) [
                                                'licenseid' => $license->id,
                                                'userid' => $USER->id,
                                                'licensecourseid' => $instance->courseid,
                                                'issuedate' => $issuedate,
                                                'isusing' => 1,
                                                'type' => $license->type,
                                            ];
                    $userlicense->id = $DB->insert_record('companylicense_users', $userlicense);

                    // Create an event.
                    $eventother = [
                                    'licenseid' => $license->id,
                                    'issuedate' => $issuedate,
                                    'duedate' => $issuedate,
                                    'noemail' => true,
                                  ];
                    $event = block_iomad_company_admin\event\user_license_assigned::create([
                                                                                              'context' => $context,
                                                                                              'objectid' => $instance->courseid,
                                                                                              'courseid' => $instance->courseid,
                                                                                              'userid' => $USER->id,
                                                                                              'other' => $eventother,
                                                                                            ]);
                    $event->trigger();
                }

                // Get the userlicense record.
                if (empty($userlicense)) {
                    $userlicense = $DB->get_record('companylicense_users', ['id' => $license->userlicenseid]);
                }

                // Do the enrolment.
                $data = ['enrolpassword' => $params['password']];
                $data['license'] = $license;
                $data['userlicense'] = $userlicense;
                $enrol->enrol_license($instance, (object) $data);
                $enrolled = true;
                break;
            } else {
                $warnings[] = [
                    'item' => 'instance',
                    'itemid' => $instance->id,
                    'warningcode' => '1',
                    'message' => $enrolstatus,
                ];
            }
        }

        $result = [];
        $result['status'] = $enrolled;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function enrol_user_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL, 'status: true if the user is enrolled, false otherwise'),
                'warnings' => new external_warnings(),
            ]
        );
    }
}
