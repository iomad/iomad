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
 * Handles loading a customcert template.
 *
 * @package    mod_customcert
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$tid = required_param('tid', PARAM_INT);
$ltid = required_param('ltid', PARAM_INT); // The template to load.
$confirm = optional_param('confirm', 0, PARAM_INT);

$template = $DB->get_record('customcert_templates', array('id' => $tid), '*', MUST_EXIST);
$template = new \mod_customcert\template($template);

$loadtemplate = $DB->get_record('customcert_templates', array('id' => $ltid), '*', MUST_EXIST);
$loadtemplate = new \mod_customcert\template($loadtemplate);

if ($cm = $template->get_cm()) {
    require_login($cm->course, false, $cm);
} else {
    require_login();
}
$template->require_manage();

// Check that they have confirmed they wish to load the template.
if ($confirm && confirm_sesskey()) {
    // First, remove all the existing elements and pages.
    $sql = "SELECT e.*
              FROM {customcert_elements} e
        INNER JOIN {customcert_pages} p
                ON e.pageid = p.id
             WHERE p.templateid = :templateid";
    if ($elements = $DB->get_records_sql($sql, array('templateid' => $template->get_id()))) {
        foreach ($elements as $element) {
            // Get an instance of the element class.
            if ($e = \mod_customcert\element_factory::get_element_instance($element)) {
                $e->delete();
            }
        }
    }

    // Delete the pages.
    $DB->delete_records('customcert_pages', array('templateid' => $template->get_id()));

    // Copy the items across.
    $loadtemplate->copy_to_template($template->get_id());

    // Redirect.
    $url = new moodle_url('/mod/customcert/edit.php', array('tid' => $tid));
    redirect($url);
}

// Create the link options.
$nourl = new moodle_url('/mod/customcert/edit.php', array('tid' => $tid));
$yesurl = new moodle_url('/mod/customcert/load_template.php', array('tid' => $tid,
                                                                    'ltid' => $ltid,
                                                                    'confirm' => 1,
                                                                    'sesskey' => sesskey()));

$pageurl = new moodle_url('/mod/customcert/load_template.php', array('tid' => $tid, 'ltid' => $ltid));
\mod_customcert\page_helper::page_setup($pageurl, $template->get_context(), get_string('loadtemplate', 'customcert'));

// Show a confirmation page.
echo $OUTPUT->header();
echo $OUTPUT->confirm(get_string('loadtemplatemsg', 'customcert'), $yesurl, $nourl);
echo $OUTPUT->footer();