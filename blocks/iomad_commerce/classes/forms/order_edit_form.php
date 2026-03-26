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
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace block_iomad_commerce\forms;

use block_iomad_commerce\helper;
use context;
use core\exception\moodle_exception;
use core\notification;
use core_form\dynamic_form;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;
use moodle_url;

/**
 * Block IOMAD eCommerce order edit form
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class order_edit_form extends dynamic_form {

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $orderid = $this->optional_param('orderid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        // Set up the form.
        $mform =& $this->_form;

        $strrequired = get_string('required');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'companyid');
        $mform->setType('companyid', PARAM_INT);

        $mform->addElement('header', 'header', get_string('order', 'block_iomad_commerce'));

        $mform->addElement('static', 'reference', get_string('reference', 'block_iomad_commerce'));

        // Set some defaults.
        $choices = [];
        $statuses = [
            helper::INVOICESTATUS_UNPAID,
            helper::INVOICESTATUS_PAID,
            ];

        // Set up the choice options for the select.
        foreach ($statuses as $status) {
            $choices[$status] = get_string('status_' . $status, 'block_iomad_commerce');
        }

        $mform->addElement('select', 'status', get_string('status'), $choices);
        $mform->addRule('status', $strrequired, 'required', null, 'client');
        $mform->disabledIf('status', 'id', 'ne', 0);

        $mform->addElement('header', 'header', get_string('purchaser_details', 'block_iomad_commerce'));

        $mform->addElement('static', 'firstname', get_string('firstname'));

        $mform->addElement('static', 'lastname', get_string('lastname'));
        $mform->addElement('static', 'company', get_string('company', 'block_iomad_company_admin'));
        $mform->addElement('static', 'address', get_string('address'));
        $mform->addElement('static', 'city', get_string('city'));
        $mform->addElement('static', 'postcode', get_string('postcode', 'block_iomad_commerce'));
        $mform->addElement('static', 'state', get_string('state', 'block_iomad_commerce'));
        $mform->addElement('static', 'country', get_string('selectacountry'));
        $mform->addElement('static', 'email', get_string('email'));
        $mform->addElement('static', 'phone1', get_string('phone'));

        $mform->addElement('header', 'header', get_string('basket', 'block_iomad_commerce'));

        $mform->addElement('html', '<p>' . get_string('process_help', 'block_iomad_commerce') . '</p>');
        $mform->addElement('html', helper::get_invoice_html($orderid, 0, 0, 0));

        $mform->addElement('header', 'header', get_string('paymentprocessing', 'block_iomad_commerce'));

        $mform->addElement('static', 'checkout_method', get_string('paymentprovider', 'block_iomad_commerce'));

        if (iomad::has_capability('block/iomad_company_admin:company_add', $companycontext)) {
            $mform->addElement('static', 'pp_account', get_string('paymentaccount', 'payment'));
        }
    }

    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {

        // Return stuff to the JS.
        return [
            'result' => true,
            'returnmessage' => '',
        ];
    }

    /**
     * Load in existing data as form defaults (not applicable).
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $orderid = $this->optional_param('orderid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        // Check we can do these things.
        iomad::require_capability('block/iomad_commerce:admin_view', $companycontext);

        // Check the record exists.
        $data = $DB->get_record(
            'block_iomad_commerce_invoices',
            ['id' => $orderid, 'companyid' => $companyid],
            '*',
            MUST_EXIST
        );

        // Send it.
        $this->set_data($data);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception.
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        global $CFG;

        $context = $this->get_context_for_dynamic_submission();
        if (!iomad::has_capability('block/iomad_commerce:admin_view', $context)) {
            $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/orderlist.php');
            throw new moodle_exception(
                'nopermissions',
                '',
                $returnurl->out(),
                get_string(
                    'block/iomad_commerce:admin_view',
                    'iomad_commerce'
                )
            );
        }
    }

    /**
     * Return form context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        return $companycontext;
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {

        return new moodle_url('/blocks/iomad_commerce/orderlist.php');
    }
}
