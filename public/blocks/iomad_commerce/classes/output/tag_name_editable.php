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
class tag_name_editable extends \core\output\inplace_editable {
    
    /**
     * Constructor
     * @param int $companyid to identify the specific company
     * @param stdClass $shoptag the data for the tag
     */
    public function __construct($companyid, $shoptag) {
        // Check the user has the correct permissions
        $capability = iomad::has_capability('block/iomad_commerce:manage_tags', \core\context\company::instance($companyid));
        // Define variables used in other functions
        $this->edithint = get_string('xshoptag', 'block_iomad_commerce', $shoptag->tag);
        $this->editlabel = get_string('xshoptag', 'block_iomad_commerce', $shoptag->tag);
        // Convert the tag to json
        $value = json_encode($shoptag->tag);
        // Pass parameters to the parent class
        parent::__construct('block_iomad_commerce', 'tag_name', $shoptag->id, $capability, $value, $value);
    }

    /**
     * Export the data so it can be used as the context for a mustache template
     * @param \renderer_base $OUTPUT
     * @return array
     */
    public function export_for_template(\renderer_base $OUTPUT) {
        // Decode the JSON
        $currentvalue = json_decode($this->value);
        // Set variables to match the current value
        $this->value = $currentvalue;
        $this->displayvalue = $currentvalue;
        // Return the $OUTPUT to the parent class and then return the result
        return parent::export_for_template($OUTPUT);
    }

    /**
     * Updates the database to match the users submitted input
     * @param int $id to identify the record in the shoptag table
     * @param mixed $newvalue a json string containing the value set by the user
     * @return \self
     */
    public static function update($itemid, $newvalue) {
        global $DB, $CFG, $USER;

        require_once($CFG->libdir . '/external/externallib.php');

        // Clean the parameters passed
        $itemid = clean_param($itemid, PARAM_INT);
        $newvalue = clean_param($newvalue, PARAM_NOTAGS);

        // Get the current company id for the user
        $companyid = iomad::get_my_companyid($context, true);

        // Define the context
        $context = \core\context\company::instance($companyid);
        // Check if the user has permissions to access this
        core_external::validate_context($context);
        // Check the user has the correct capability
        iomad::require_capability('block/iomad_commerce:manage_tags', $context);

        // Check the record to be updated exists in the shoptag table and is within the users current company
        if (!$DB->record_exists('shoptag', ['id' => $itemid, 'companyid' => $companyid])) {
            throw new moodle_exception('shoptag record does not exists for the id provided', 'error');
        }

        // Create a class for the shop tag record to be updated
        $record = new stdClass();
        $record->id = $itemid;
        $record->tag = $newvalue;
        // Update the shop tag record
        $DB->update_record('shoptag', $record);
        // Create a event and trigger it
        $event = \block_iomad_commerce\event\tag_name_updated::create(['context' => $context,
                                                                        'objectid' => $itemid,
                                                                        'userid' => $USER->id]);
        $event->trigger();

        // Define variables to be passed back to the class
        return new self($companyid, $record);
    }
}
