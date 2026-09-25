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

class shop implements renderable, templatable {

    /** @var int The company ID. */
    public $companyid;

    /** @var object The company context. */
    public $companycontext;

    /** @var int The current page. */
    public $page;

    /** @var int How many to show on a page. */
    public $perpage;

    /** @var moodle_url the main shop URL. */
    public $baseurl;

    /**
     * Constructor.
     *
     * @param int $companyid Company ID
     * @param object $companycontext the company context
     * @param int $page the current page
     * @param int $perpage how many to show per page
     * @param moodle_url $baseurl the main shop URL
     */
    public function __construct($companyid, $companycontext, $page, $perpage, $baseurl) {
        $this->companyid = $companyid;
        $this->companycontext = $companycontext;
        $this->page = $page;
        $this->perpage = $perpage;
        $this->baseurl = $baseurl;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $SESSION;

        // Deal with the shop tags.
        $shoptags = helper::get_shop_tags();
        $tags = [];
        $hasselected = false;
        foreach ($shoptags as $shoptag) {
            $selected = false;
            if (isset($SESSION->shoptag) && $SESSION->shoptag == $shoptag) {
                $selected = true;
                $hasselected = true;
            }
            $tagurl = new moodle_url('', ['tag' => $shoptag]);
            $tags[] = [
                'tagname' => $shoptag,
                'tagurl' => $tagurl->out(false),
                'selected' => $selected,
            ];
        }

        // Deal with search text.
        $searchkey = '';
        if (isset($SESSION->shopsearch)) {
            $searchkey = $SESSION->shopsearch;
        }

        // Get the list of products.
        $items = array_values(helper::get_shop_products($this->companyid, $this->companycontext));
        $itemcount = count($items);

        // Set up the rest.
        $pagingbar = $output->paging_bar($itemcount, $this->page, $this->perpage, $this->baseurl);

        // Process all of the shop items.
        foreach ($items as $id => $item) {
            $available = ($item->allow_single_purchase ||
                        $item->allow_license_blocks) &&
                        (iomad::has_capability('block/iomad_commerce:buyitnow', $this->companycontext) ||
                        iomad::has_capability('block/iomad_commerce:buyinbulk', $this->companycontext));
            $items[$id]->price = helper::get_lowest_price_text($item);
            if ($available) {
                $items[$id]->buynowurl = new moodle_url($CFG->wwwroot . "/blocks/iomad_commerce/buynow.php", ['itemid' => $item->id]);
                $items[$id]->moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/iomad_commerce/item.php", ['itemid' => $item->id]);
            } else {
                if ($mustlogin && $item->allow_single_purchase) {
                    $wantsurl = new moodle_url($CFG->wwwroot . "/blocks/iomad_commerce/buynow.php", ['itemid' => $item->id]);
                    $items[$id]->buynowurl = new moodle_url($CFG->wwwroot . "/login/index.php", ['wantsurl' => $wantsurl->out()]);
                }
                $items[$id]->moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/iomad_commerce/item.php", ['itemid' => $item->id]);
            }
        }

        // Set up the JSON output.
        return [
            'itemcount' => $itemcount,
            'pagingbar' => $pagingbar,
            'searchkey' => $searchkey,
            'tags' => $tags,
            'hastags' => !empty($tags),
            'hasselected' => $hasselected,
            'hasresults' => (!empty($searchkey) || !empty($hasselected)),
            'items' => $items,
        ];
    }
}
