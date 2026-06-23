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
        return $template->generate_pdf(false, $certissue->id, true);
    }
}
