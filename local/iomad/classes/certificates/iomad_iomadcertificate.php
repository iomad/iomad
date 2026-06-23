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

/**
 * IOMAD certificate handler class for iomadcertificate
 *
 * @package   local_iomad
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iomad_iomadcertificate {

    /**
     * Get the database record for the certificate
     *
     * @param int $id
     * @return object
     */
    public static function get_certrecord($id) {
        global $DB;

        return $DB->get_record('iomadcertificate', ['id' => $id], '*', MUST_EXIST);
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
    public static function get_certissue($course, $user, $iomadcertificate, $cm) {
         global $CFG;

        // Load the required libraries.
        require_once($CFG->dirroot . '/mod/iomadcertificate/locallib.php');

        return iomadcertificate_get_issue($course, $user, $iomadcertificate, $cm);
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
        global $CFG;

        // Load the required libraries.
        require_once($CFG->libdir . '/pdflib.php');
        require_once($CFG->dirroot . '/mod/iomadcertificate/lib.php');
        require_once($CFG->dirroot . '/mod/iomadcertificate/locallib.php');

        // Some name changes (as used in cert template).
        $certuser = $user;
        $certificatename = 'iomadcertificatetype';;
        $$certificatename = $certificate;
        $certrecord = $certissue;

        // Load certificate template (magically creates $pdf variable. Grrrrrr).
        // Assumes a whole bunch of stuff exists without being explicitly required (double grrrrr).
        $typefield = 'iomadcertificatetype';
        require($CFG->dirroot . "/mod/iomadcertificate/type/{$certificate->$typefield}/certificate.php");

        // Create the certificate content. 'S' means return as string.
        return $pdf->Output('', 'S');
    }
}
