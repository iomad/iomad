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
 * Payment subsystem callback implementation for block_iomad_commerce.
 *
 * @package    block_iomad_commerce
 * @category   payment
 * @copyright  2020 Shamim Rezaie <shamim@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_commerce\payment;

use block_iomad_commerce\helper;
use block_iomad_commerce\processor;
use context_system;
use core_payment\local\entities\payable;
use local_iomad\{company, emailtemplate, iomad};
use moodle_url;

/**
 * Payment subsystem callback implementation for block_iomad_commerce.
 *
 * @copyright  2020 Shamim Rezaie <shamim@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_provider implements \core_payment\local\callback\service_provider {

    /**
     * Callback function that returns the enrolment cost and the accountid
     * for the course that $instanceid enrolment instance belongs to.
     *
     * @param string $paymentarea Payment area
     * @param int $instanceid The enrolment instance id
     * @return \core_payment\local\entities\payable
     */
    public static function get_payable(string $paymentarea, int $instanceid): payable {
        global $CFG;

        $companyid = iomad::get_my_companyid(context_system::instance());
        $company = new company($companyid);
        if ($paymentaccount = $company->get_payment_account()) {
            $basket = helper::get_basket_by_id($instanceid);
            return new payable($basket->total, $basket->currency, $paymentaccount);
        }
    }

    /**
     * Callback function that returns the URL of the page the user should be redirected to in the case of a successful payment.
     *
     * @param string $paymentarea Payment area
     * @param int $instanceid The enrolment instance id
     * @return moodle_url
     */
    public static function get_success_url(string $paymentarea, int $instanceid): moodle_url {
        global $CFG, $DB;

        // Send them back to the dashboard.
        return new moodle_url($CFG->wwwroot . '/my');
    }

    /**
     * Callback function that delivers what the user paid for to them.
     *
     * @param string $paymentarea
     * @param int $instanceid The enrolment instance id
     * @param int $paymentid payment id as inserted into the 'payments' table, if needed for reference
     * @param int $userid The userid the order is going to deliver to
     * @return bool Whether successful or not
     */
    public static function deliver_order(string $paymentarea, int $instanceid, int $paymentid, int $userid): bool {
        global $DB;

        processor::trigger_oncheckout($instanceid);

        if ($invoice = $DB->get_record('block_iomad_commerce_invoices', ['id' => $instanceid])) {
            $invoice->paymentid = $paymentid;
            processor::trigger_onordercomplete($invoice);
        }

        return true;
    }
}
