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
namespace block_iomad_commerce\tables;

// Add required dependancies
use \table_sql;
use \moodle_url;
use \iomad;
use \context_system;

// Ensure that it is loaded in Moodle else die
defined('MOODLE_INTERNAL') || die();

// Require the table library
require_once($CFG->libdir.'/tablelib.php');

// Define the class manage_tags_table
class manage_tags_table extends table_sql {

    protected $assignableitems;
    protected $companyid;

    /**
     * Constructor
     * @param string $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session
     */
    public function __construct($uniqueid) {
        global $DB;
        // Store the unique id
        $this->uniqueid = $uniqueid;
        // Defined the requests
        $this->request = [
            TABLE_VAR_SORT   => 'tsort',
            TABLE_VAR_HIDE   => 'thide',
            TABLE_VAR_SHOW   => 'tshow',
            TABLE_VAR_IFIRST => 'tifirst',
            TABLE_VAR_ILAST  => 'tilast',
            TABLE_VAR_PAGE   => 'page',
            TABLE_VAR_RESET  => 'treset',
            TABLE_VAR_DIR    => 'tdir',
        ];
        // Get the company id and stored it
        $this->companyid = iomad::get_my_companyid(context_system::instance());
        $this->assignableitems = $DB->get_records('course_shopsettings', ['companyid' => $this->companyid], 'name ASC', 'id, name');
    }

    /**
     * Generate the display of the tags name
     * @param object $row is the data for the current record
     * @return string HTML content to go inside the td
     */
    public function col_tag($row) {
        global $USER, $OUTPUT;
        $return = '';
        // Check if editing is enabled
        if (!empty($USER->editing)) {
            $return = new \block_iomad_commerce\output\tag_name_editable($this->companyid, $row);
            $return = $OUTPUT->render_from_template('core/inplace_editable', $return->export_for_template($OUTPUT));
        } else {
            // If editing is disabled then just output the name as text
            $return = $row->tag;
        }
        return $return;
    }

    /**
     * Generate the display of the shop items that use the current tag
     * @param object $row is the data for the current record
     * @return string HTML content to go inside the td
     */
    public function col_itemsusedby($row) {
        global $USER, $DB, $OUTPUT;
        // Define the return variable
        $return = '';
        // Define the $itemsusedby as a empty array
        $itemsusedby = [];
        // Get the relevant records from the database and check if any exist
        if ($records = $DB->get_records_sql('SELECT id as id, name as name FROM {course_shopsettings} 
                                             WHERE id in (SELECT itemid FROM {course_shoptag} WHERE shoptagid = :shoptagid)
                                             AND companyid = :companyid ORDER BY name ASC',
                                             ['shoptagid' => $row->id, 'companyid' => $this->companyid])){
            // Create a array of IDs
            $itemsusedby = array_map(fn($r) => $r->id, $records);
        }
        // Check if editing is enabled
        if (!empty($USER->editing)) {
            // Create a array which displays as id => name
            $assignableitems = array_column($DB->get_records('course_shopsettings', ['companyid' => $this->companyid]), 'name', 'id');
            // Create the editable
            $return = new \block_iomad_commerce\output\course_shoptag_editable($this->companyid,
                                                    $row->id,
                                                    $itemsusedby,
                                                    $assignableitems,
                                                    $row->tag);
            // Return the editable output
            $return = $OUTPUT->render_from_template('core/inplace_editable', $return->export_for_template($OUTPUT));
        } else {
            // Editing is disabled so loop through the records and add them as a string to the return variable
            $return = implode(', ', array_map(fn($r) => format_string($r->name), $records));
        }
        // Return the $return variable
        return $return;
    }

    /**
     * Generate the display of the actions
     * @param object $row is the data for the current record
     * @return string HTML content to go inside the td
     */
    public function col_actions($row) {
        global $CFG, $USER;
        // Create the delete button
        $deletebutton = '';
        if (!empty($USER->editing)) {
            // Editing is turned on so display the delete hyperlink
            $deleteurl = new moodle_url("$CFG->wwwroot/blocks/iomad_commerce/manage_tags.php",
                                        ['delete' => $row->id,
                                        'sesskey' => sesskey()]);
            $deletebutton = "<a href=$deleteurl>".get_string('delete')."</a>";
        }
        return $deletebutton;
    }
}