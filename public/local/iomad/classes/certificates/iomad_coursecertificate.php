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
 * IOMAD certificate handler class for certificatebeautiful
 *
 * @package   local_iomad
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\certificates;

use mod_coursecertificate\helper;
use tool_certificate\template;

/**
 * IOMAD certificate class - used generate certificate PDF files.
 *
 * @package   local_iomad
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iomad_coursecertificate {

    /**
     * Get the database record for the certificate
     *
     * @param int $id
     * @return object
     */
    public static function get_certrecord($id) {
        global $DB;

        return $DB->get_record('coursecertificate', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Get the users issues certificate record for the course
     *
     * @param object $course
     * @param object $user
     * @param object $certificate
     * @param object $cm
     * @return object
     */
    public static function get_certissue($course, $user, $certificate, $cm) {
        global $DB;

        // Does the user already have one?
        $issues = $DB->get_records('tool_certificate_issues', ['userid' => $user->id, 'courseid' => $course->id]);
        if ($issues) {
            $issue = reset($issues);
        } else {
            $issue = (object) helper::get_issue_data($course, $user);
            $issue->userid = $user->id;
            $issue->id = $DB->insert_record('tool_certificate_issues', $issue);
        }

        return $issue;
    }

    /**
     * Create a new certificate using certificate module template
     * @param object $certificate certificate instance
     * @param object $user completing user
     * @param object $cm course module (in completing course)
     * @param object $course completing course
     * @param object $certissue certificate issue instance
     * @return string pdf content
     */
    public static function create_certificate($certificate, $user, $cm, $course, $certissue) {

        $template = template::instance($certificate->template);
        return $template->generate_pdf(false, $certissue, true);
    }
}
