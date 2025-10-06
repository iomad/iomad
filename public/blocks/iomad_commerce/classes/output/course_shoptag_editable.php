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

// Add the namespace
namespace block_iomad_commerce\output;

use \context_system;
use \iomad;
use \stdClass;
use \core_external;

// Ensure that it is loaded in Moodle else die
defined('MOODLE_INTERNAL') || die();

// Define the class course_shoptag_editable
class course_shoptag_editable extends \core\output\inplace_editable {

    /** @var array $assignableitems */
    private $assignableitems;

    /**
     * Constructor
     * @param int $companyid to identify the specific company
     * @param int $shoptagid to identify the record in the shoptag table
     * @param object $itemsusedby contains records for each shop item that already uses the tag
     * @param array $assignableitems an array of all the shop items which can have the shop tag assigned to them 
     */
    public function __construct($companyid, $shoptagid, $itemsusedby, $assignableitems, $name) {
        // Check the user has the correct permissions
        $capability = iomad::has_capability('block/iomad_commerce:manage_tags', \core\context\company::instance($companyid));
        // Define variables used in other functions
        $this->assignableitems = $assignableitems;
        $this->edithint = get_string('xshopitems', 'block_iomad_commerce', $name);
        $this->editlabel = get_string('xshopitems', 'block_iomad_commerce', $name);
        // Convert the array to json
        $value = json_encode(array_values($itemsusedby));
        // Pass paramters to the parent class
        parent::__construct('block_iomad_commerce', 'course_shoptag', $shoptagid, $capability, $value, $value);
        // Set the type of inplace_editable
        $this->set_type_autocomplete($this->assignableitems, ['multiple' => true]);
    }

    /**
     * Export the data so it can be used as the content for a mustache template
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $OUTPUT) {
        // Decode the ID's from a json string
        $ids = json_decode($this->value);
        // Create a empty array to store the names
        $names = [];
        // Loop through the ID's
        foreach($ids as $id) {
            // If the id exists in the array then return the name
            if (array_key_exists($id, $this->assignableitems)) {
                $names[] = $this->assignableitems[$id];
            }
        }
        // Convert the array to a string which lists each value in the array
        $this->displayvalue = implode(', ', $names);
        // Return the output
        return parent::export_for_template($OUTPUT);
    }

    /**
     * Updates the database to match the users submitted input
     * @param int $id to identify the record in the shoptag table
     * @param mixed $newvalue a json string containing the values set by the user
     * @return \self
     */
    public static function update($itemid, $newvalue) {
        global $DB, $CFG, $USER;
        
        require_once($CFG->libdir . '/external/externallib.php');
        // Clean the parameters passed
        $shoptagid = clean_param($itemid, PARAM_INT);
        // Decode the json string. Loop through the array and add it to a new array as well as clean each value in the array
        $itemid = array_map(fn($v) => clean_param($v, PARAM_INT), json_decode($newvalue));

        // Define the company id
        $companyid = iomad::get_my_companyid($context, true);

        // Define the context
        $context = \core\context\company::instance($companyid);
        // Check if the user has permissions to access this
        core_external::validate_context($context);
        // Check the user has the correct capability
        iomad::require_capability('block/iomad_commerce:manage_tags', $context);

        // Prevent SQL injection
        $sql = '';
        $params = [];
        if(!empty($itemid)){
            list($sql, $params) = $DB->get_in_or_equal($itemid, SQL_PARAMS_NAMED, 'itemid');
            $sql = "AND NOT itemid $sql";
        }
        $params['shoptagid'] = $shoptagid;
        // Delete records which where the shop tag has been removed from the item
        if ($records = $DB->get_records_sql("SELECT id FROM {course_shoptag} 
                                             WHERE shoptagid = :shoptagid 
                                             $sql",
                                             $params)) {
            foreach($records as $record) {
                // Delete the record
                $DB->delete_records('course_shoptag', ['id' =>$record->id]);
                // Create a event and trigger it
                $event = \block_iomad_commerce\event\course_shoptag_deleted::create(['context' => $context,
                                                                               'objectid' => $record->id,
                                                                               'userid' => $USER->id]);
                $event->trigger();
            }
        }

        // Create records which don't exist for the shop items which have been assigned the shop tag
        foreach($itemid as $i) {
            if (!$DB->record_exists('course_shoptag', ['shoptagid' => $shoptagid, 'itemid' => $i])) {
                // Create a object for the new record
                $record = new stdClass();
                $record->shoptagid = $shoptagid;
                $record->itemid = $i;
                // Create the record and return the id
                $newid = $DB->insert_record('course_shoptag', $record, true);
                // Create a event with data for the 'other' parameter and then trigger the event
                $eventother = ['companyid' => $companyid, 'itemid' => $i];
                $event = \block_iomad_commerce\event\course_shoptag_created::create(['context' => $context,
                                                                'objectid' => $newid,
                                                                'userid' => $USER->id,
                                                                'other' => $eventother]);
                $event->trigger();
            }
        }

        // Define variables to be passed back to the class
        $assignableitems = array_column($DB->get_records('course_shopsettings', ['companyid' => $companyid]), 'name', 'id');
        $name = $DB->get_record('shoptag',['id' => $shoptagid])->name;
        return new self($companyid, $shoptagid, $itemid, $assignableitems, $name);
    }
}
