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
 * Block IOMAD eCommerce
 *
 * @package   block_iomad_commerce
 * @copyright 2026 e-Learn Design
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_commerce\output;

use block_iomad_commerce\event\product_updated;
use block_iomad_commerce\event\tag_name_updated;
use context_system;
use core\output\inplace_editable;
use core_external;
use local_iomad\iomad;
use local_iomad\custom_context\context_company;
use renderer_base;

/**
 * Block IOMAD eCommerce product name editable class
 *
 * @package   block_iomad_commerce
 * @copyright 2026 e-Learn Design
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class product_name_editable extends inplace_editable {

    /**
     * Constructor function
     * @param int $companyid to identify the specific company
     * @param object $product the data for the product
     */
    public function __construct($companyid, $product) {

        $mycompanyid = iomad::get_my_companyid(context_system::instance(), false);
        if ($companyid > 0) {
            // Check the user has the correct permissions.
            $capability = iomad::has_capability(
                'block/iomad_commerce:edit_course',
                context_company::instance($companyid)
            );
        } else {
            // Its a default product.
            $capability = iomad::has_capability(
                'block/iomad_commerce:manage_default',
                context_company::instance($mycompanyid)
            );
        }

        // Invent an itemid.
        $itemid = $companyid . ':' . $product->id;

        // Convert the tag to json.
        $value = json_encode($product->name);

        // Pass parameters to the parent class.
        parent::__construct('block_iomad_commerce', 'product_name', $itemid, $capability, $value, $value);
    }

    /**
     * Export the data so it can be used as the context for a mustache template
     *
     * @param renderer_base $OUTPUT
     * @return array
     */
    public function export_for_template(renderer_base $OUTPUT) {
        // Decode the JSON.
        $currentvalue = json_decode($this->value);

        // Set variables to match the current value.
        $this->value = $currentvalue;
        $this->displayvalue = $currentvalue;

        // Return the $OUTPUT to the parent class and then return the result.
        return parent::export_for_template($OUTPUT);
    }

    /**
     * Updates the database to match the users submitted input
     *
     * @param int $id to identify the record in the shoptag table
     * @param mixed $newvalue a json string containing the value set by the user
     * @return self
     */
    public static function update($itemid, $newvalue) {
        global $DB, $CFG, $USER;

        require_once($CFG->libdir . '/external/externallib.php');

        [$companyid, $productid] = explode(':', $itemid, 2);

        // Clean the parameters passed.
        $productid = clean_param($productid, PARAM_INT);
        $newvalue = clean_param($newvalue, PARAM_NOTAGS);

        // Get the current company id for the user.
        $mycompanyid = iomad::get_my_companyid(context_system::instance(), false);

        // Define the context.
        if ($companyid > 0) {
            // Check the user has the correct permissions.
            $capability = 'block/iomad_commerce:edit_course';
            $context = context_company::instance($companyid);
        } else {
            // Its a default product.
            $capability = 'block/iomad_commerce:manage_default';
            $context = context_company::instance($mycompanyid);
        }

        // Check if the user has permissions to access this.
        core_external::validate_context($context);

        // Check the user has the correct capability.
        iomad::require_capability($capability, $context);

        // Check the record to be updated exists in the shoptag table and is within the users current company.
        $product = $DB->get_record(
            'block_iomad_commerce_products',
            ['id' => $productid, 'companyid' => $companyid],
            '*',
            MUST_EXIST);

        $product->name = $newvalue;

        // Update the shop tag record.
        $DB->update_record('block_iomad_commerce_products', $product);

        // Create a event and trigger it.
        $event = product_updated::create([
            'context' => $context,
            'objectid' => $productid,
            'userid' => $USER->id,
        ]);
        $event->trigger();

        // Define variables to be passed back to the class.
        return new self($companyid, $product);
    }
}
