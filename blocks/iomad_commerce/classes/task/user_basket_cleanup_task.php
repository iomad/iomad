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
 * IOMAD eCommerce abandonded basket clean up task
 *
 * @package   block_iomad_commerce
 * @copyright 2026 E-Learn Design Ltd https://www.e-learndesign.co.uk
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_commerce\task;

use core\task\scheduled_task;

/**
 * IOMAD eCommerce abandonded basket clean up task
 *
 * @package   block_iomad_commerce
 * @copyright 2026 E-Learn Design Ltd https://www.e-learndesign.co.uk
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_basket_cleanup_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('user_basket_cleanup_task', 'block_iomad_commerce');
    }

    /**
     * Run IOMAD eCommerce user_basket_cleanup_task.
     */
    public function execute() {
        global $DB, $CFG;

        // Set some defaults.
        $runtime = time();

        mtrace("Running IOMAD eCommerce user basket cleanup task at ".date('d M Y h:i:s', $runtime));

        // Get all of the baskets which haven't been updated in 7 days and are unprocessed.
        $baskets = $DB->get_records_select('block_iomad_commerce_invoices',
                                           "status = :status AND date < :timestamp",
                                           ['status' => 'b',
                                            'timestamp' => $runtime - 7 * 24 * 60 * 60]);
        // Delete the baskets and contents.
        if (!empty($baskets)) {
            mtrace("cleaning up " . count($baskets) . " abandonded baskets.");
            foreach ($baskets as $basket) {
                // Remove all of the basket items.
                $DB->delete_records('block_iomad_commerce_invoice_items', ['invoiceid' => $basket->id]);

                // Remove the basket.
                $DB->delete_records('block_iomad_commerce_invoices', ['id' => $basket->id]);
            }
        }

        mtrace("IOMAD eCommerce user basket cleanup task completed at " . date('d M Y h:i:s', time()));
    }
}
