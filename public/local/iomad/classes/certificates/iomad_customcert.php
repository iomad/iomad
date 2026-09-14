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
 * IOMAD certificate class - used generate certificate PDF files.
 *
 * @package   local_iomad
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\certificates;

use mod_customcert\{certificate, template};

/**
 * IOMAD certificate handler class for iomadcertificate
 *
 * @package   local_iomad
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iomad_customcert {

    /**
     * Get the database record for the certificate
     *
     * @param int $id
     * @return object
     */
    public static function get_certrecord($id) {
        global $DB;

        return $DB->get_record('customcert', ['id' => $id], '*', MUST_EXIST);
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

        // Does the user already have a certificate?
        $issues = $DB->get_records('customcert_issues', ['userid' => $user->id, 'customcertid' => $certificate->id]);
        if ($issues) {
            return reset($issues);
        } else {
            // Issue a new certificate.
            certificate::issue_certificate($certificate->id, $user->id);
            return $DB->get_record('customcert_issues', ['userid' => $user->id, 'customcertid' => $certificate->id]);
        }
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
        global $DB;

        // Get the certificate template.
        $templaterec = $DB->get_record('customcert_templates', ['id' => $certificate->templateid], '*', MUST_EXIST);
        $template = new template($templaterec);

        // Generate the PDF output.
        return $template->generate_pdf(false, $certissue->userid, true);
    }

    /**
     * Hacky function to change the course completion information on the fly to
     * what we want it to be as the certificate module doesn't allow for the
     * date to be passed as part of the generate_pdf function. Instead it gets
     * its own date.
     *
     * @param object $trackinfo
     * @return object
     */
    public static function set_completion($trackinfo) {
        global $DB;

        // Is there a current completion record?
        if ($currentrecord = $DB->get_record(
            'course_completions',
            [
                'userid' => $trackinfo->userid,
                'course' => $trackinfo->courseid,
            ])) {
            // Update it to match our wanted date.
            $DB->set_field(
                'course_completions',
                'timecompleted',
                $trackinfo->timecompleted,
                ['id' => $currentrecord->id]);
        } else {
            // Create a temporary record.
            $currentrecord = (object) [
                'userid' => $trackinfo->userid,
                'course' => $trackinfo->courseid,
                'timeenrolled' => $trackinfo->timeenrolled,
                'timestarted' => $trackinfo->timestarted,
                'timecompleted' => $trackinfo->timecompleted,
            ];

            $currentrecord->id = $DB->insert_record('course_completions', $currentrecord);
            $currentrecord->deleteme = true;
        }

        return $currentrecord;
    }

    /**
     * Undo the hacky set_completion function.
     *
     * @param object $currentrecord
     * @return void
     */
    public static function reset_completion($currentrecord) {
        global $DB;

        // Did we create a temporary record?
        if (!empty($currentrecord->deleteme)) {
            $DB->delete_records('course_completions', ['id' => $currentrecord->id]);
        } else {
            // Put it back.
            $DB->set_field(
                'course_completions',
                'timecompleted',
                $currentrecord->timecompleted,
                ['id' => $currentrecord->id]);
        }
    }
}
