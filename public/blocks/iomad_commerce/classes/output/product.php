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

use block_iomad_commerce\helper;
use local_iomad\iomad;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

class product implements renderable, templatable {

    /** @var object The company context. */
    public $companycontext;

    /** @var bool Is there an error with the amount? */
    public $invalidamount;

    /** @var object The product. */
    public $item;

    /** @var int Is the license form empty? */
    public $licenseformempty;

    /** @var string Product name or not found message. */
    public $pagetitle;

    /**
     * Constructor.
     *
     * @param object $companycontext the company context
     * @param object $item the product
     * @param string $pagetitle product name or no product found
     * @param bool $invalidamount form amount error indicator
     * @param bool $licenseformempty is the form empty?
     */
    public function __construct($companycontext, $item, $pagetitle, $invalidamount, $licenseformempty) {
        $this->companycontext = $companycontext;
        $this->invalidamount = $invalidamount;
        $this->item = $item;
        $this->licenseformempty = $licenseformempty;
        $this->pagetitle = $pagetitle;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $DB;

        // Set up some defaults.
        $template = (object)[];
        $strextra = "";
        $strbuynow = "";
        $strmoreinfo = "";
        $pricetable = [];
        if ($this->item) {
            $mustlogin = false;
            $strbuynow = get_string('buynow', 'block_iomad_commerce');
            if (!isloggedin() || isguestuser()) {
                $mustlogin = true;
                $strbuynow = get_string('login', 'moodle');
                $strextra = get_string('product_login', 'block_iomad_commerce');
            }
            $strmoreinfo = get_string('moreinfo', 'block_iomad_commerce');

            if ($mustlogin) {
                $wantsurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/buynow.php', ['itemid' => $this->item->id]);
                $buynowurl = new moodle_url($CFG->wwwroot . "/login/index.php", ['wantsurl' => $wantsurl->out()]);
            } else if (($this->item->allow_single_purchase || $this->item->allow_license_blocks) &&
                (iomad::has_capability('block/iomad_commerce:buyitnow', $this->companycontext) ||
                iomad::has_capability('block/iomad_commerce:buyinbulk', $this->companycontext))) {

                if ($this->item->allow_single_purchase &&
                    iomad::has_capability('block/iomad_commerce:buyitnow', $this->companycontext)) {
                    $buynowurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/buynow.php', ['itemid' => $this->item->id]);
                    $pricetable[] = [
                        'type' => get_string('single_purchase', 'block_iomad_commerce'),
                        'currency' => $this->item->single_purchase_currency,
                        'price' => number_format($this->item->single_purchase_price, 2),
                        'buyurl' => $buynowurl->out(false),
                        'extrainfo' => $strextra,
                    ];
                }

                if ($this->item->allow_license_blocks) {
                    $priceblocks = $DB->get_records(
                        'block_iomad_commerce_product_blockprices',
                        ['itemid' => $this->item->id],
                        'price_bracket_start'
                    );
                    if (count($priceblocks) > 0 &&
                        iomad::has_capability('block/iomad_commerce:buyinbulk', $this->companycontext)) {
                        foreach ($priceblocks as $priceblock) {
                            $pricetable[] = [
                                'type' => get_string('licenseblock_n', 'block_iomad_commerce', $priceblock->price_bracket_start),
                                'currency' => $priceblock->currency,
                                'price' => number_format($priceblock->price, 2),
                            ];
                        }

                        // Do we have an error message?
                        $msg = $this->licenseformempty ? get_string('licenseformempty', 'block_iomad_commerce') : '';

                        // Create url for the form.
                        $licenseformurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/buynow.php', ['itemid' => $this->item->id]);

                        // Create the template for the mustache file.
                        $template = (object)[
                            'form_url' => $licenseformurl->out(),
                            'item_id' => $this->item->id,
                            'msg_txt' => $msg,
                            'howmany_txt' => get_string('howmanylicences', 'block_iomad_commerce'),
                            'buynow_txt' => get_string('buynow', 'block_iomad_commerce'),
                            'noerror_style' => 'display: none;',
                        ];
                        if (isset($this->invalidamount) && $this->invalidamount == true) {
                            $template->error_class = 'text-danger';
                            unset($template->noerror_style);
                            $template->error_txt = get_string('error_singlepurchaseunavailable', 'block_iomad_commerce');
                        }
                    }
                }
            }
        }

        // Set up the JSON output.
        return [
            'item' => $this->item,
            'pagetitle' => $this->pagetitle,
            'buynow' => $strbuynow,
            'moreinfo' => $strmoreinfo,
            'formtemplate' => $template,
            'pricetable' => ['prices' => $pricetable],
        ];
    }
}
