<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
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
 * Handles verifying the code for a certificate.
 *
 * @package   mod_customcert
 * @copyright 2017 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreLine
require_once('../../config.php');

$contextid = optional_param('contextid', context_system::instance()->id, PARAM_INT);
$code = optional_param('code', '', PARAM_ALPHANUM); // The code for the certificate we are verifying.

$context = context::instance_by_id($contextid);

// Set up the page.
$pageurl = new moodle_url('/mod/customcert/verify_certificate.php', array('contextid' => $contextid));

// Ok, a certificate was specified.
if ($context->contextlevel != CONTEXT_SYSTEM) {
    $cm = get_coursemodule_from_id('customcert', $context->instanceid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $customcert = $DB->get_record('customcert', array('id' => $cm->instance), '*', MUST_EXIST);

    // Check if we are allowing anyone to verify, if so, no need to check login, or permissions.
    if (!$customcert->verifyany) {
        // Need to be logged in.
        require_login($course, false, $cm);
        // Ok, now check the user has the ability to verify certificates.
        require_capability('mod/customcert:verifycertificate', $context);
    } else {
        $PAGE->set_cm($cm, $course);
    }

    $checkallofsite = false;
} else {
    // If the 'verifyallcertificates' is not set and the user does not have the capability 'mod/customcert:verifyallcertificates'
    // then show them a message letting them know they can not proceed.
    $verifyallcertificates = get_config('customcert', 'verifyallcertificates');
    $canverifyallcertificates = has_capability('mod/customcert:verifyallcertificates', $context);
    if (!$verifyallcertificates && !$canverifyallcertificates) {
        $strheading = get_string('verifycertificate', 'customcert');
        $PAGE->navbar->add($strheading);
        $PAGE->set_context(context_system::instance());
        $PAGE->set_title($strheading);
        $PAGE->set_url($pageurl);
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strheading);
        echo $OUTPUT->notification(get_string('cannotverifyallcertificates', 'customcert'));
        echo $OUTPUT->footer();
        exit();
    }

    $checkallofsite = true;
}

if ($code) {
    $pageurl->param('code', $code);
}

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('verifycertificate', 'customcert'));

// The form we are using to verify these codes.
$form = new \mod_customcert\verify_certificate_form($pageurl);

if ($form->get_data()) {
    $result = new stdClass();
    $result->issues = array();

    // Ok, now check if the code is valid.
    $userfields = get_all_user_name_fields(true, 'u');
    $sql = "SELECT ci.id, u.id as userid, $userfields, co.id as courseid,
                   co.fullname as coursefullname, c.name as certificatename, c.verifyany
              FROM {customcert} c
              JOIN {customcert_issues} ci
                ON c.id = ci.customcertid
              JOIN {course} co
                ON c.course = co.id
              JOIN {user} u
                ON ci.userid = u.id
             WHERE ci.code = :code";

    if ($checkallofsite) {
        // Only people with the capability to verify all the certificates can verify any.
        if (!$canverifyallcertificates) {
            $sql .= " AND c.verifyany = 1";
        }
        $params = ['code' => $code];
    } else {
        $sql .= " AND c.id = :customcertid";
        $params = ['code' => $code, 'customcertid' => $customcert->id];
    }

    $sql .= " AND u.deleted = 0";

    // It is possible (though unlikely) that there is the same code for issued certificates.
    if ($issues = $DB->get_records_sql($sql, $params)) {
        $result->success = true;
        $result->issues = $issues;
    } else {
        // Can't find it, let's say it's not verified.
        $result->success = false;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('verifycertificate', 'customcert'));
echo $form->display();
if (isset($result)) {
    $renderer = $PAGE->get_renderer('mod_customcert');
    $result = new \mod_customcert\output\verify_certificate_results($result);
    echo $renderer->render($result);
}
echo $OUTPUT->footer();
