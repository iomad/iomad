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
 * @package   block_iomad_commerce
 * @copyright 2025 e-Learn Design
 * @author    Robert Tyrone Cullen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include Moodle configuration file
require_once(dirname(__FILE__) . '/../../config.php');
// Include IOMAD company admin library 
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');

// Check if commerce is enabled 
\block_iomad_commerce\helper::require_commerce_enabled();

// Define optional GET parameters
$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   // Md5 confirmation hash.

// Get the context
$systemcontext = context_system::instance();

// Set the companyid
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = \core\context\company::instance($companyid);
$company = new company($companyid);

// Require the user to be logged in
require_login();

// Ensure that the user has the correct capability
iomad::require_capability('block/iomad_commerce:manage_tags', $companycontext);

// Define the component string
$component = 'block_iomad_commerce';

// Define the title for the page
$title = get_string('managetags', $component);

// Define the base url for the page
$baseurl = new moodle_url('/blocks/iomad_commerce/manage_tags.php');

// Variable to store whether the user has deleted a tag, it is used to not log a event for the page being viewed if a user has just deleted a tag
$tagdeleted = false;

// Set paramters for the page using the PAGE variable
$PAGE->set_context($companycontext);
$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Delete a tag dependant on the value of the delete parameter passed after 
if ($delete && confirm_sesskey()) {
    // Check the user has the correct capability to delete a shop tag
    if (!iomad::has_capability('block/iomad_commerce:manage_tags', $companycontext)) {
        throw new moodle_exception('nopermissions', 'error', '', 'delete a tag');
    }
    // Check that the record exists
    if (!$DB->record_exists('shoptag',['id' => $delete])) {
        throw new moodle_exception('notag', 'error');
    }
    if ($confirm != md5($delete)) {
        // If the confirm md5 hash does not match then prompt the user to confirm the deletion of the tag
        // Output the header
        echo $OUTPUT->header();
        // Get the shop tag name and output it to the heading
        echo $OUTPUT->heading(get_string('deleteshoptag', $component, $DB->get_record('shoptag', ['id' => $delete], 'tag')->tag));
        // Get all shop items using the current tag and create a list
        $shopitems = $DB->get_records_sql('SELECT id as id, name as name FROM {course_shopsettings} 
                                           WHERE id in (SELECT itemid FROM {course_shoptag} WHERE shoptagid = :shoptagid)
                                           AND companyid = :companyid ORDER BY name ASC',
                                           ['shoptagid' => $delete, 'companyid' => $companyid]);
        // Set the paramters for the URL
        $optionyes = ['delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey()];
        // Define the $string variable dependant whether there are shop items using the shop tag or not
        $string = (!empty($shopitems)) ? 
                                        get_string('deleteshoptagcheckused', $component, implode(', ', array_map(fn($r) => $r->name, $shopitems))) : 
                                        get_string('deleteshoptagcheck', $component);
        // Define and output the URL
        echo $OUTPUT->confirm($string, new moodle_url('manage_tags.php', $optionyes), 'manage_tags.php');
        // Output the footer
        echo $OUTPUT->footer();
        // die to not proceed further with the rest of the code
        die;
    } else {
        // Create data for the other parameter of the event
        $eventother = ['tag' => $DB->get_record('shoptag', ['id' => $delete], 'tag')->tag];
        // Delete the tag
        $DB->delete_records('course_shoptag', ['shoptagid' => $delete]);
        $DB->delete_records('shoptag', ['id' => $delete]);
        // Create the event and then trigger it
        $event = \block_iomad_commerce\event\shoptag_deleted::create(['context' => $companycontext,
                                                                      'objectid' => $delete,
                                                                      'other' => $eventother]);
        $event->trigger();
        $tagdeleted = true;
    }
}

// Output the header
echo $OUTPUT->header();

// Check if there are any tags for the current company
if ($tags = $DB->record_exists('shoptag', ['companyid' => $companyid])) {
    // Define SQL for the table
    $selectsql = "id, tag";
    $fromsql = "{shoptag}";
    $wheresql = "companyid = :companyid";
    $sqlparams = ["companyid" => $companyid];

    // Create and display the table
    $table = new \block_iomad_commerce\tables\manage_tags_table('block_iomad_commerce');
    $table->set_sql($selectsql, $fromsql, $wheresql, $sqlparams);
    $table->define_baseurl($baseurl);
    $table->define_columns(['tag', 'itemsusedby', 'actions']);
    $table->define_headers([get_string('name'), get_string('itemsusedby', $component), get_string('actions')]);
    $table->no_sorting('itemsusedby');
    $table->no_sorting('actions');
    $table->out(10, false);

} else {
    // No records returned so output a message to state there are no tags available
    echo "<p>".get_string('notagsexist', $component)."</p>";
}

// Add a cancel button which returns the user to the ecommerce page dashboard
echo $OUTPUT->single_button(new moodle_url("$CFG->wwwroot/blocks/iomad_commerce/courselist.php"), get_string('cancel'));

if(!$tagdeleted){
// Create a event and trigger it
    $eventother = ['companyid' => $companyid];
    $event = \block_iomad_commerce\event\manage_tags_viewed::create(['context' => $companycontext,
                                                                    'objectid' => $delete,
                                                                    'other' => $eventother]);
    $event->trigger();
}

// Output the footer
echo $OUTPUT->footer();
