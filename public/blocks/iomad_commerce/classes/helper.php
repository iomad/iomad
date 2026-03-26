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

namespace block_iomad_commerce;

use block_iomad_commerce\event\product_created;
use block_iomad_commerce\event\product_updated;
use moodle_url;
use html_writer;
use html_table;
use local_iomad\{company, company_user, iomad};
use context_system;
use core\notification;
use Exception;
use local_iomad\custom_context\context_company;

/**
 * Block IOMAD eCommerce helper class
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /** Used when the invoice hasn't moved beyond the user's basket */
    const INVOICESTATUS_BASKET = 'b';

    /** Used when payment for the invoice has been initiated but not completed */
    const INVOICESTATUS_UNPAID = 'u';

    /** Used when payment for the invoice is complete */
    const INVOICESTATUS_PAID = 'p';

    /**
     * Do we need this?
     *
     * @return void
     */
    public static function require_commerce_enabled() {
        return;
    }

    /**
     * Get the lowest price
     *
     * @param object $blockprice
     * @return void
     */
    public static function get_lowest_price_text($blockprice) {
        global $CFG, $DB;

        if (empty($blockprice->single_purchase_currency)) {
            if (!empty($CFG->commerce_admin_currency)) {
                $currency = $CFG->commerce_admin_currency;
            } else {
                $currency = 'GBP';
            }
        } else {
            $currency = $blockprice->single_purchase_currency;
        }
        $prices = [];
        if ($blockprice->allow_single_purchase) {
            if ($blockprice->single_purchase_price) {
                $prices[] = $blockprice->single_purchase_price;
            }
        }
        if ($blockprice->allow_license_blocks) {
            if ($blockprices = $DB->get_records_sql("SELECT * FROM {course_shopblockprice}
                                                    WHERE itemid = :itemid
                                                    AND price_bracket_start <= 2",
                                                    ['itemid' => $blockprice->id])) {
                foreach ($blockprices as $blockprice) {
                    $prices[] = $blockprice->price;
                }
            }
        }

        $lowestprice = number_format(min($prices), 2);

        if ($lowestprice) {
            $price = get_string('pricefrom', 'block_iomad_commerce', "<b>" . $currency . ' ' . $lowestprice. "</b>");
        } else {
            $price = '';
        }

        return $price;
    }

    /**
     * Get the license block
     *
     * @param int $itemid
     * @param int $nlicenses
     * @return array
     */
    public static function get_license_block($itemid, $nlicenses) {
        global $DB;

        $record = $DB->get_records_sql("SELECT *
                                        FROM {course_shopblockprice}
                                        WHERE itemid = :itemid
                                        AND price_bracket_start <= :nlicenses
                                        ORDER BY price_bracket_start DESC",
                                       ['nlicenses' => $nlicenses,
                                        'itemid' => $itemid],
                                        0, 1);
        return array_shift($record);
    }

    /**
     * Get the user's basket ID
     *
     * @return int
     */
    public static function get_basket_id() {
        if ($basket = self::get_basket('id')) {
            return $basket->id;
        }
        return 0;
    }

    /**
     * Calculate the user's basket total.
     *
     * @param integer $basketid
     * @return int
     */
    public static function get_basket_total($basketid = 0) {
        global $DB, $SESSION;

        if (empty($basketid)) {
            $basketid = $SESSION->basketid;
        }

        if ($basket = $DB->get_record_sql("SELECT
                                            i.id,
                                            sum(quantity*license_allocation*price) AS total
                                           FROM
                                            {block_iomad_commerce_invoices} i
                                            INNER JOIN {block_iomad_commerce_invoice_items} ii ON ii.invoiceid = i.id
                                           WHERE
                                            i.status = :status
                                            AND
                                            i.id = :basketid
                                           GROUP BY
                                            i.id",
                                           ['basketid' => $basketid,
                                            'status' => self::INVOICESTATUS_BASKET])) {
            return $basket->total;
        }

        return 0;
    }

    /**
     * Get the user's basket information given a basket id
     *
     * @param integer $basketid
     * @param string $status
     * @return object
     */
    public static function get_basket_by_id($basketid = 0, $status = self::INVOICESTATUS_BASKET) {
        global $DB, $SESSION;

        if (empty($basketid)) {
            $basketid = $SESSION->basketid;
        }

        if ($basket = $DB->get_record_sql("SELECT
                                            i.id,
                                            sum(quantity*license_allocation*price) AS total
                                           FROM
                                            {block_iomad_commerce_invoices} i
                                            INNER JOIN {block_iomad_commerce_invoice_items} ii ON ii.invoiceid = i.id
                                           WHERE
                                            i.status = :status
                                            AND
                                            i.id = :basketid
                                           GROUP BY
                                            i.id",
                                          ['basketid' => $basketid,
                                           'status' => $status])) {

            $currency = $DB->get_record_sql("SELECT DISTINCT ii.currency
                                             FROM {block_iomad_commerce_invoices} i
                                             INNER JOIN {block_iomad_commerce_invoice_items} ii ON ii.invoiceid = i.id
                                             WHERE
                                             i.status = :status
                                             AND
                                             i.id = :basketid",
                                            ['basketid' => $basketid,
                                             'status' => $status]);
            $basket->currency = $currency->currency;
            return $basket;
        }

        return false;
    }

    /**
     * Get the user's current basket
     *
     * @param string $fields
     * @return object
     */
    public static function get_basket($fields = '*') {
        global $SESSION, $DB;

        if (!empty($SESSION->basketid)) {
            return $DB->get_record('block_iomad_commerce_invoices', ['id' => $SESSION->basketid], $fields);
        }

        return false;
    }

    /**
     * Try and add the extra details to the invoice from the user object
     *
     * @param object $invoice
     * @return void
     */
    public static function enrich_invoice($invoice) {
        global $USER, $DB;

        $additionalitems = [
            'id',
            'firstname',
            'lastname',
            'department',
            'address',
            'city',
            'state',
            'country',
        ];

        foreach ($additionalitems as $key) {
            if ($key != 'id') {
                $invoice->$key = $USER->$key;
            } else {
                $invoice->userid = $USER->id;
            }
        }
        $DB->update_record('block_iomad_commerce_invoices', $invoice);
    }

    /**
     * Get the invoice from the database
     *
     * @param int $invoiceid
     * @param string $fields
     * @return onject
     */
    public static function get_invoice($invoiceid, $fields = '*') {
        global $DB;
        return $DB->get_record('block_iomad_commerce_invoices', ['id' => $invoiceid], $fields);
    }

    /**
     * Get the invoice from the database from the reference
     *
     * @param string $invoicereference
     * @param string $fields
     * @return object
     */
    public static function get_invoice_by_reference($invoicereference, $fields = '*') {
        global $DB;
        return $DB->get_record('block_iomad_commerce_invoices', ['reference' => $invoicereference], $fields);
    }

    /**
     * Get the user's basket information
     *
     * @return string
     */
    public static function get_basket_info() {
        global $SESSION, $DB;

        if (!empty($SESSION->basketid)) {
            $nitems = $DB->count_records_sql("SELECT COUNT(*)
                                              FROM {block_iomad_commerce_invoice_items} ii
                                              INNER JOIN {course} c ON ii.invoiceableitemid = c.id
                                              WHERE EXISTS (
                                                  SELECT id
                                                  FROM {block_iomad_commerce_invoices} i
                                                  WHERE i.id = :basketid
                                                  AND i.status = :status
                                                  AND i.id = ii.invoiceid
                                              )",
                                           ['basketid' => $SESSION->basketid,
                                            'status' => self::INVOICESTATUS_BASKET]);
        } else {
            return html_writer::tag('p', get_string('emptybasket', 'block_iomad_commerce'));
        }

        if ($nitems) {
            $strkey = ($nitems == 1) ? 'basket_1item' : 'basket_nitems';
            $url = new moodle_url('/blocks/iomad_commerce/basket.php');
            $return = html_writer::start_tag('p');
            $return .= html_writer::tag('a', get_string($strkey, 'block_iomad_commerce', $nitems), ['href' => $url]);
            $return .= html_writer::end_tag('p');
        } else {
            return html_writer::tag('p', get_string('emptybasket', 'block_iomad_commerce'));
        }
    }

    /**
     * Output the basket info
     *
     * @return void
     */
    public static function show_basket_info() {
        echo self::get_basket_info();
    }

    /**
     * Get the basket menu link
     *
     * @return string
     */
    public static function get_basket_menu_link() {
        global $SESSION, $DB;

        if (!empty($SESSION->basketid)) {
            $nitems = $DB->count_records_sql("SELECT COUNT(*)
                                              FROM {block_iomad_commerce_invoice_items} ii
                                              INNER JOIN {course} c ON ii.invoiceableitemid = c.id
                                              WHERE EXISTS (
                                                  SELECT id
                                                  FROM {block_iomad_commerce_invoices} i
                                                  WHERE i.id = :basketid
                                                  AND i.status = :status
                                                  AND i.id = ii.invoiceid
                                              )",
                                             ['basketid' => $SESSION->basketid,
                                             'status' => self::INVOICESTATUS_BASKET]);
        } else {
            return '-' . get_string('emptybasket', 'block_iomad_commerce') . "|#\n\r";
        }

        // Does the basket contain anything?
        if ($nitems) {
            $strkey = ($nitems == 1) ? 'basket_1item' : 'basket_nitems';
            $url = new moodle_url('/blocks/iomad_commerce/basket.php');
            return '-' . get_string($strkey, 'block_iomad_commerce', $nitems) . '|' . $url->out() . "\n\r";
        } else {
            return '-' . get_string('emptybasket', 'block_iomad_commerce') . "|#\n\r";
        }
    }

    /**
     * Get the shop menu link
     *
     * @param array $companyrec
     * @return string
     */
    public static function get_shop_menu_link($companyrec) {
        global $DB, $CFG, $USER;

        $shoplink = "";
        $companycontext = context_company::instance($companyrec->id);
        if (iomad::has_capability('block/iomad_commerce:buyitnow', $companycontext) ||
            iomad::has_capability('block/iomad_commerce:buyinbulk', $companycontext)) {
            if (!empty($CFG->commerce_enable_external)) {
                // Get and store a one time token.
                $token = company_user::generate_token();
                $configname = "commerce_externalshop_url_" . $companyrec->id;
                if (empty($CFG->$configname)) {
                    $configname = "commerce_externalshop_url";
                }
                $link = new moodle_url($CFG->$configname . '/wp-content/plugins/wooiomad/land.php',
                                       ['username' => $USER->username,
                                        'token' => $token]);
                $shoplink = "" . get_string('gotoshop', 'block_iomad_commerce') . '|' . $link->out() . "\n\r";
            } else {
                if ($DB->get_records('block_iomad_commerce_products', ['companyid' => $companyrec->id, 'enabled' => 1])) {
                    $shoplink = "" . get_string('buycourses', 'block_iomad_commerce') . "|#\n\r";
                    $shoplink .= "-" . get_string('gotoshop', 'block_iomad_commerce') . "|/blocks/iomad_commerce/shop.php\n\r";
                    $shoplink .= self::get_basket_menu_link();
                }
            }
        }

        return $shoplink;
    }

    /**
     * Check if a payment provider is enabled
     *
     * @param string $providername
     * @return void
     */
    public static function payment_provider_enabled($providername) {
        global $CFG;

        $penabled = $providername . "_enabled";
        if (!empty($CFG->$penabled)) {
            return ($CFG->$penabled);
        } else {
            return false;
        }
    }

    /**
     * Get the list of enabled payment providers.
     *
     * @return array
     */
    public static function get_enabled_payment_providers() {
        $result = [];
        foreach (self::get_payment_providers() as $p) {
            if (self::payment_provider_enabled($p)) {
                $result[] = $p;
            }
        }
        return $result;
    }

    /**
     * Get the list of enabled payment provider instances
     *
     * @return array
     */
    public static function get_enabled_payment_providers_instances() {
        $ppnames = self::get_enabled_payment_providers();
        $result = [];
        foreach ($ppnames as $ppname) {
            $result[] = self::get_payment_provider_instance($ppname);
        }
        return $result;
    }

    /**
     * Get a payment provider instance checkout path by name
     *
     * @param string $providername
     * @return string
     */
    public static function get_payment_provider_instance($providername) {
        $path = dirname(__FILE__) . '/checkout/' . $providername . '/' . $providername . '.php';
        require_once($path);
        return new $providername;
    }

    /**
     * Check if there are multiple currencies used in the invoice
     *
     * @param int $invoiceid
     * @return bool
     */
    public static function check_multiple_currencies($invoiceid) {
        global $DB;

        $currencycount = $DB->count_records_sql('SELECT count(DISTINCT currency)
                                                 FROM {block_iomad_commerce_invoice_items}
                                                 WHERE invoiceid = :invoiceid',
                                                ['invoiceid' => $invoiceid]);

        if ($currencycount > 1) {
            return true;
        }

        return false;
    }

    /**
     * Get the payment provider display name
     *
     * @param string $providername
     * @return string
     */
    public static function get_payment_provider_displayname($providername) {
        return get_string('pp_' . $providername . '_name', 'block_iomad_commerce');
    }

    /**
     * Get the HTML code for a basket
     *
     * @param int $includeremove
     * @return void
     */
    public static function get_basket_html($includeremove = 0) {
        if ($basketid = self::get_basket_id()) {
            return self::get_invoice_html($basketid, $includeremove);
        }
    }

    /**
     * Get the invoice html output
     *
     * @param int $invoiceid
     * @param integer $includeremove
     * @param integer $links
     * @param integer $showprocessed
     * @return void
     */
    public static function get_invoice_html($invoiceid, $includeremove = 0, $links = 1, $showprocessed = 0) {
        global $DB, $CFG;

        $result = '';
        $multiplecurrency = false;
        $currentcurrency = '';

        if ($basketitems = $DB->get_records_sql("SELECT ii.*, css.name
                                                 FROM {block_iomad_commerce_invoice_items} ii
                                                 INNER JOIN {block_iomad_commerce_products} css ON ii.invoiceableitemid = css.id
                                                 WHERE ii.invoiceid = :invoiceid
                                                 ORDER BY ii.id",
                                                ['invoiceid' => $invoiceid])) {

            $table = new html_table();
            $table->head = [get_string('course'),
                            "",
                            get_string('unitprice', 'block_iomad_commerce'),
                            get_string('amount', 'block_iomad_commerce'),
                            ];
            if ($includeremove) {
                $table->head[] = "";
            }
            if ($showprocessed) {
                $table->head[] = get_string('process', 'block_iomad_commerce');
            }
            $table->align = ["left", "center", "right", "right", "right"];
            $table->width = "600px";

            $total = 0;
            $count = 0;
            if (!empty($CFG->commerce_admin_currency)) {
                $currency = get_string($CFG->commerce_admin_currency, 'core_currencies');
            } else {
                $currency = get_string('GBP', 'core_currencies');
            }
            foreach ($basketitems as $item) {
                $rowtotal = $item->price * $item->license_allocation;

                if ($item->invoiceableitemtype == 'singlepurchase') {
                    $unitprice = '';
                } else {
                    $unitprice = $item->currency . number_format($item->price, 2);
                }

                if (!empty($currentcurrency) && $item->currency != $currentcurrency) {
                    $multiplecurrency = true;
                } else {
                    $currentcurrency = $item->currency;
                }

                // Set some variables for the row.
                $itemurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/item.php',
                                          ['itemid' => $item->invoiceableitemid]);
                $itemlink = html_writer::tag('a', $item->name, ['href' => $itemurl]);
                $itemtype = 'type_quantity_' . ($item->license_allocation > 1 ? 'n' : '1') . '_' . $item->invoiceableitemtype;
                $row = [
                    ($links ? $itemlink : $item->name),
                    get_string($itemtype, 'block_iomad_commerce', $item->license_allocation),
                    $unitprice,
                    $item->currency . ' ' . number_format($rowtotal, 2),
                ];

                // Do we also add in the remove links?
                if ($includeremove) {
                    $removeurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/basket.php', ['remove' => $item->id]);
                    $removerow = html_writer::start_tag('a', ['href' => $removeurl]);
                    $removerow .= html_writer::tag('i', '', ['class' => 'icon fa fa-trash fa-fw',
                                                             'title' => get_string('remove'),
                                                             'role' => 'img',
                                                             'aria-label' => get_string('remove')]);
                    $removerow .= html_writer::end_tag('a');
                    $row[] = $removerow;
                }
                if ($showprocessed) {
                    if ($item->processed) {
                        $row[] = get_string('processed', 'block_iomad_commerce');
                    } else {
                        $row[] = "<input type='checkbox' name='process_" . ($count++) . "' value='" . $item->id . "' />";
                    }
                }

                $table->data[] = $row;

                $currency = $item->currency;
                $total += $rowtotal;
            }

            if (!$multiplecurrency) {
                $totalrow = [
                    '<b>' . get_string('total', 'block_iomad_commerce') . '</b>',
                    '',
                    '',
                    '<b>' . $currency . ' ' . number_format($total, 2) . '</b>',
                ];
            } else {
                $totalrow = ['', '', '', ''];
            }
            if ($includeremove) {
                $totalrow[] = '';
            }
            if ($showprocessed) {
                $totalrow[] = '';
            }
            $table->data[] = $totalrow;

            if (!empty($table)) {
                $result .= html_writer::table($table);
            }
        }
        if ($multiplecurrency) {
            notification::error(get_string('multiplecurrencies', 'block_iomad_commerce'));
        }

        return $result;
    }

    /**
     * Get the invoice summary
     *
     * @param integer $invoiceid
     * @param integer $includeremove
     * @param integer $links
     * @param integer $showprocessed
     * @return string
     */
    public static function get_invoice_summary($invoiceid, $includeremove = 0, $links = 1, $showprocessed = 0) {
        global $DB, $USER, $CFG;

        $result = '';
        $multiplecurrency = false;
        $currentcurrency = '';

        if ($basketitems = $DB->get_records_sql("SELECT ii.*, css.name
                                                 FROM {block_iomad_commerce_invoice_items} ii
                                                 INNER JOIN {block_iomad_commerce_products} css ON ii.invoiceableitemid = css.id
                                                 WHERE ii.invoiceid = :invoiceid
                                                 ORDER BY ii.id",
                                                ['invoiceid' => $invoiceid])) {

            foreach ($basketitems as $item) {
                $rowtotal = $item->price * $item->license_allocation;

                if ($item->invoiceableitemtype == 'singlepurchase') {
                    $unitprice = '';
                } else {
                    $unitprice = $item->currency . number_format($item->price, 2);
                }

                if (!empty($currentcurrency) && $item->currency != $currentcurrency) {
                    $multiplecurrency = true;
                } else {
                    $currentcurrency = $item->currency;
                }

                $row = $item->name . ": " .
                    get_string('type_quantity_' . ($item->license_allocation > 1 ? 'n' : '1') .
                    '_' . $item->invoiceableitemtype, 'block_iomad_commerce', $item->license_allocation) . " @ " .
                    $unitprice . ' = ' .
                    $item->currency .number_format($rowtotal, 2);

                $result .= $row;
            }
        }

        return $result;
    }

    /**
     * Get the error table html
     *
     * @param string $msg
     * @param array $data
     * @return void
     */
    public static function get_error_table($msg, $data) {
        $html = "<p class='error'>$msg</p>";

        if ($data) {
            $table = new html_table();
            $table->head = [get_string('error'),
                            "",
                           ];
            $table->align = ["left", "left"];

            $table->data = $data;

            $html .= html_writer::table($table);
        }
        return $html;
    }

    /**
     * Get all shop tags which are assigned to a specific company
     *
     * @param boolean $all
     * @return array
     */
    public static function get_shop_tags($all = false) {
        global $DB;

        // If all is set to true, filter all shop tags which aren't being used by a shop item.
        $filter = '';
        if (!$all) {
            $filter = 'AND EXISTS (SELECT cst.id FROM {block_iomad_commerce_product_shoptags} cst
                                    INNER JOIN {block_iomad_commerce_products} css ON css.id = cst.itemid
                                    WHERE cst.shoptagid = st.id AND css.enabled = 1)';
        }

        // Get all relevant records from the database and then create a array of values to return.
        if ($shoptags = $DB->get_records_sql('SELECT st.tag as tag FROM {block_iomad_commerce_shoptags} st
                                              WHERE st.companyid = :companyid '.$filter.'
                                              ORDER BY st.tag',
                                              ['companyid' => iomad::get_my_companyid(context_system::instance(), true)])) {
            // Return an array of shop tags.
            return array_map(fn($r) => $r->tag, $shoptags);
        }

        // Return a empty array when there are no records retrieved from the database.
        return [];
    }

    /**
     * Get all tags which the current shop item is using
     *
     * @param integer $itemid
     * @return void
     */
    public static function get_course_tags($itemid, $companyid) {
        global $DB;

        // Did we get a companyid?
        if (empty($companyid)) {
            $companyid = iomad::get_my_companyid(context_system::instance(), true);
        }

        // Get all records for the shop item id.
        if ($shoptags = $DB->get_records_sql(
            "SELECT st.tag as tag
               FROM {block_iomad_commerce_product_shoptags} cst
               JOIN {block_iomad_commerce_shoptags} st
                 ON cst.shoptagid = st.id
              WHERE cst.itemid = :itemid
                AND st.companyid = :companyid
           ORDER BY st.tag",
            [
                'itemid' => $itemid,
                'companyid' => $companyid,
            ])) {
            // Return the shop tags as a list.
            return implode(', ', array_map(fn($r) => $r->tag, $shoptags));
        }

        // Return a empty string when there are no shop tags for the current shop item.
        return '';
    }

    /**
     * Create a random invoice reference
     *
     * @return void
     */
    public static function random_invoice_reference() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $refstr = '';
        for ($i = 0; $i < 6; $i++) {
            $refstr .= $chars[rand(0, strlen($chars) - 1 )];
        }
        return $refstr;
    }

    /**
     * Save a the invoice reference to the database
     *
     * @param [type] $invoiceid
     * @return void
     */
    public static function set_new_invoice_reference($invoiceid) {
        global $DB;
        try {
            return $DB->set_field(
                'block_iomad_commerce_invoices',
                'reference',
                self::random_invoice_reference(),
                ['id' => $invoiceid]
            );
        } catch (Exception $e) {
            // Assume the issue we have is a unique index issue.
            return false;
        }
    }

    /**
     * Create the invoice reference
     *
     * @param int $invoiceid
     * @return void
     */
    public static function create_invoice_reference($invoiceid) {

        $invariant = 1000;
        if (!self::get_invoice($invoiceid, 'reference')->reference) {
            while ($invariant-- && !self::set_new_invoice_reference($invoiceid));
        }
    }

    /**
     * Check if the IOMAD eCommerce block is good to use
     *
     * @return boolean
     */
    public static function is_commerce_configured() {
        global $CFG;

        // Confirm commerce admin has been defined.
        if (!$CFG->commerce_enable_external &&
             (!$CFG->commerce_admin_firstname ||
              !$CFG->commerce_admin_lastname ||
              !$CFG->commerce_admin_email ||
              !$CFG->commerce_admin_paymentaccount)) {
            return false;
        }
        // If we are using the external shop then also need the URL.
        if ($CFG->commerce_enable_external &&
            !$CFG->commerce_externalshop_url) {
            return false;
        }

        return true;
    }

    /**
     * Import a product to a company
     *
     * @param int $itemid
     * @param int $companyid
     * @return void
     */
    public static function import_item_to_company($itemid, $companyid) {
        global $DB;

        if (!empty($companyid)) {
            $checkcourses = true;
            $company = new company($companyid);
            $companycourses = $company->get_menu_courses(true, false);
        } else {
            $checkcourses = false;
        }

        if ($shopitem = $DB->get_record('block_iomad_commerce_products', ['id' => $itemid])) {
            unset($shopitem->id);
            $shopitem->companyid = $companyid;
            if ($newitemid = $DB->insert_record('block_iomad_commerce_products', $shopitem)) {
                if ($courses = $DB->get_records('block_iomad_commerce_product_courses', ['itemid' => $itemid])) {
                    foreach ($courses as $course) {

                        // Only bring in courses which the company can see.
                        if (!$checkcourses || !empty($companycourses[$course->courseid])) {
                            unset($course->id);
                            $course->itemid = $newitemid;
                            $DB->insert_record('block_iomad_commerce_product_courses', $course);
                        }
                    }
                }
                if ($blockprices = $DB->get_records('block_iomad_commerce_product_blockprices', ['itemid' => $itemid])) {
                    foreach ($blockprices as $blockid => $blockprice) {
                        unset($blockprice->id);
                        $blockprice->itemid = $newitemid;
                        $DB->insert_record('block_iomad_commerce_product_blockprices', $blockprice);
                    }
                }
                if ($shoptags = $DB->get_records('block_iomad_commerce_product_shoptags', ['itemid' => $itemid])) {
                    foreach ($shoptags as $shoptagid => $shoptag) {
                        unset($shoptag->id);
                        $shoptag->itemid = $newitemid;
                        $DB->insert_record('block_iomad_commerce_product_shoptags', $shoptag);
                    }
                }

                return true;
            } else {

                return false;
            }
        }
    }

    /**
     * Populate an ecommerce product with the rest of it's data.
     *
     * @param object $product
     * @return void
     */
    public static function populate_product(&$product) {
        global $CFG, $DB;

        if (empty($product->id)) {
            // We are adding a new product.
            $product->itemcourses = [];
            $product->block_start = [];
            $product->itempaths = [];
            $product->default = $product->companyid;
            if (!empty($CFG->commerce_admin_currency)) {
                $product->currency = $CFG->commerce_admin_currency;
            } else {
                $product->currency = 'GBP';
            }
        } else {
            // Deal with courses.
            $courses = $DB->get_records(
                'block_iomad_commerce_product_courses',
                ['itemid' => $product->id],
                '',
                'courseid'
            );
            $product->itemcourses = array_keys($courses);

            // Get the learning paths that are already assigned to the shop item.
            $paths = $DB->get_records(
                'block_iomad_commerce_product_learningpaths',
                ['itemid' => $product->id],
                '',
                'pathid'
            );

            // Create the array to store the learning paths.
            $product->itempaths = array_keys($paths);

            // Get the tags that are being used by the current shop item.
            $product->tags = self::get_course_tags($product->id, $product->companyid);

            // Get any price bandings.
            $product->block_start = [];
            $pricebands = $DB->get_records(
                'block_iomad_commerce_product_blockprices',
                ['itemid' => $product->id],
                'price_bracket_start'
            );
            foreach ($pricebands as $priceband) {
                $product->item_block_start[] = $priceband->price_bracket_start;
                $product->item_block_price[] = $priceband->price;
            }
            $product->short_summary_editor = ['text' => $product->short_description];
            $product->summary_editor = ['text' => $product->long_description];
            $product->default = $product->companyid;
            $product->currency = $product->single_purchase_currency;
        }
    }

    /**
     * Update or create a new product
     *
     * @param object $product
     * @return bool
     */
    public static function update_product($product) {
        global $DB;

        // Set the context.
        $companycontext = context_company::instance($product->mycompanyid);

        try {
            $transaction = $DB->start_delegated_transaction();

            // Create or update the product.
            if (empty($product->id)) {
                $product->single_purchase_currency = $product->currency;
                $product->id = $DB->insert_record('block_iomad_commerce_products', $product);
                $event = product_created::create([
                    'context' => $companycontext,
                    'userid' => $product->userid,
                    'objectid' => $product->id,
                ]);
            } else {
                $product->single_purchase_currency = $product->currency;
                $DB->update_record('block_iomad_commerce_products', $product);
                $event = product_updated::create([
                    'context' => $companycontext,
                    'userid' => $product->userid,
                    'objectid' => $product->id,
                ]);
            }

            // Deal with the License price blocks.
            $DB->delete_records(
                'block_iomad_commerce_product_blockprices',
                ['itemid' => $product->id]
            );

            // Add the new ones in.
            foreach ($product->item_block_start as $blockid => $itemblock) {
                if (!empty($itemblock)) {
                    $priceblock = (object) [];
                    $priceblock->itemid = $product->id;
                    $priceblock->currency = $product->currency;
                    $priceblock->price_bracket_start = $itemblock;
                    $priceblock->price = $product->item_block_price[$blockid];
                    $priceblock->validlength = $product->single_purchase_validlength;
                    $priceblock->shelflife = $product->single_purchase_shelflife;

                    $DB->insert_record('block_iomad_commerce_product_blockprices', $priceblock, false, false);
                }
            }

            // Delete associated courses.
            $DB->delete_records('block_iomad_commerce_product_courses', ['itemid' => $product->id]);

            // Add any new ones in.
            foreach ($product->itemcourses as $itemcourse) {
                $DB->insert_record(
                    'block_iomad_commerce_product_courses',
                    ['itemid' => $product->id, 'courseid' => $itemcourse]
                );
            }

            // Delete associated learning paths.
            $DB->delete_records('block_iomad_commerce_product_learningpaths', ['itemid' => $product->id]);

            // Add any new ones in.
            foreach ($product->itempaths as $itempath) {
                $DB->insert_record(
                    'block_iomad_commerce_product_learningpaths',
                    ['itemid' => $product->id, 'pathid' => $itempath]
                );
            }

            // Delete course_shoptag records.
            $DB->delete_records('block_iomad_commerce_product_shoptags', ['itemid' => $product->id]);

            // Add any new ones in.
            $tags = preg_split('/\s*,\s*/', $product->tags);
            $newcourseshoptagrecord = (object)[];
            $newcourseshoptagrecord->itemid = $product->id;
            foreach ($tags as $tag) {
                // Check if the tag exists for the company and if it doesn't then
                // create a new record for the tag for the current company.
                if ($tag == '') {
                    $st = $DB->get_record('block_iomad_commerce_shoptags', ['tag' => $tag]);
                } else if (!$st = $DB->get_record(
                    'block_iomad_commerce_shoptags',
                    ['tag' => $tag, 'companyid' => $product->companyid])) {
                    $st = (object)[];
                    $st->tag = $tag;
                    $st->companyid = $product->companyid;
                    $st->id = $DB->insert_record('block_iomad_commerce_shoptags', $st, true);
                }

                if (isset($st)) {
                    // Create a new record in course_shoptag for the tag being used for the shop item.
                    $newcourseshoptagrecord->shoptagid = $st->id;
                    $DB->insert_record('block_iomad_commerce_product_shoptags', $newcourseshoptagrecord);
                }
            }

            // Everything OK then...
            $transaction->allow_commit();

            // Fire the event.
            $event->trigger();

            return true;
        } catch (Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }
}
