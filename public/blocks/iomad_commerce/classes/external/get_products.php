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

namespace block_iomad_commerce\external;

use block_iomad_commerce\helper;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;
use moodle_url;

/**
 * Implementation of web service block_iomad_commerce_get_products
 *
 * @package    block_iomad_commerce
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_products extends external_api {

    /**
     * Describes the parameters for block_iomad_commerce_get_products
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
            'page' => new external_value(PARAM_INT, 'Current page'),
            'perpage' => new external_value(PARAM_INT, 'Per page'),
            'passedshoptag' => new external_value(PARAM_TEXT, 'Search tag'),
            'passedshopsearch' => new external_value(PARAM_TEXT, 'Search text'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_commerce_get_products
     *
     * @param mixed $companyid
     * @param mixed $page
     * @param mixed $perpage
     * @param mixed $passedshoptag
     * @param mixed $passedshopsearch
     */
    public static function execute($companyid, $page, $perpage, $passedshoptag = '', $passedshopsearch = '') {
        global $CFG, $OUTPUT, $SESSION;

        // Parameter validation.
        [
            'companyid' => $companyid,
            'page' => $page,
            'perpage' => $perpage,
            'passedshoptag' => $passedshoptag,
            'passedshopsearch' => $passedshopsearch,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'companyid' => $companyid,
                'page' => $page,
                'perpage' => $perpage,
                'passedshoptag' => $passedshoptag,
                'passedshopsearch' => $passedshopsearch,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($companyid);
        self::validate_context($companycontext);

        // Set the URL.
        $baseurl = new moodle_url('/blocks/iomad_commerce/shop.php');

        // Reset the perpage.
        if (empty($perpage)) {
            $perpage = get_config('local_iomad', 'max_list_courses');
        }

        // Does the passed search terms match the current ones?
        $searchchange = false;
        $sessionsearch = empty($SESSION->shopsearch) ? '' : $SESSION->shopsearch;
        $sessiontag = empty($SESSION->shoptag) ? '' : $SESSION->shoptag;
        if ($sessionsearch != $passedshopsearch) {
            $searchchange = true;
            if (!empty($passedshopsearch)) {
                $SESSION->shopsearch = $passedshopsearch;
                $sessionsearch = $passedshopsearch;
            } else {
                unset($SESSION->shopsearch);
                $sessionsearch = '';
            }
        }
        if ($sessiontag != $passedshoptag) {
            $searchchange = true;
            if (!empty($passedshoptag)) {
                $SESSION->shoptag = $passedshoptag;
                $sessiontag = $passedshoptag;
            } else {
                unset($SESSION->shoptag);
                $sessiontag = '';
            }
        }

        // Reset the pagination if the search terms changed.
        if ($searchchange) {
            $page = 0;
            $perpage = get_config('local_iomad', 'max_list_courses');
        }

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
            $tags[] = [
                'tagname' => $shoptag,
                'selected' => $selected,
            ];
        }

        // Get the list of products.
        $items = array_values(helper::get_shop_products($companyid, $companycontext, $page, $perpage));
        $itemcount = helper::count_shop_products($companyid, $companycontext);

        // Set up the rest.
        $pagingbar = $OUTPUT->paging_bar($itemcount, $page, $perpage, $baseurl);

        // Process all of the shop items.
        foreach ($items as $id => $item) {
            $available = ($item->allow_single_purchase ||
                        $item->allow_license_blocks) &&
                        (iomad::has_capability('block/iomad_commerce:buyitnow', $companycontext) ||
                         iomad::has_capability('block/iomad_commerce:buyinbulk', $companycontext));
            $items[$id]->price = helper::get_lowest_price_text($item);
            if ($available) {
                $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/iomad_commerce/buynow.php", ['itemid' => $item->id]);
                $items[$id]->buynowurl = $buynowurl->out();
                $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/iomad_commerce/item.php", ['itemid' => $item->id]);
                $items[$id]->moreinfourl = $moreinfourl->out();
            } else {
                if ($mustlogin && $item->allow_single_purchase) {
                    $wantsurl = new moodle_url($CFG->wwwroot . "/blocks/iomad_commerce/buynow.php", ['itemid' => $item->id]);
                    $buynowurl = new moodle_url($CFG->wwwroot . "/login/index.php", ['wantsurl' => $wantsurl->out()]);
                    $items[$id]->buynowurl = $buynowurl->out();
                }
                $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/iomad_commerce/item.php", ['itemid' => $item->id]);
                $items[$id]->moreinfourl = $moreinfourl->out();
            }
        }


        return [
            'itemcount' => $itemcount,
            'pagingbar' => $pagingbar,
            'items' => $items,
            'searchkey' => $sessionsearch,
            'searchtag' => $sessiontag,
            'companyid' => $companyid,
            'perpage' => $perpage,
            'page' => $page,
            'tags' => $tags,
            'hastags' => !empty($tags),
            'hasselected' => $hasselected,
            'hasresults' => (!empty($searchkey) || !empty($hasselected)),
        ];
    }

    /**
     * Describe the return structure for block_iomad_commerce_get_products
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'itemcount' => new external_value(PARAM_INT, 'Total values'),
            'pagingbar' => new external_value(PARAM_RAW, 'Paging bar HTML'),
            'items' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Product ID'),
                    'companyid' => new external_value(PARAM_INT, 'Company ID'),
                    'name' => new external_value(PARAM_TEXT, 'Product name'),
                    'short_description' => new external_value(PARAM_RAW, 'Product short description'),
                    'long_description' => new external_value(PARAM_RAW, 'Product long description'),
                    'allow_single_purchase' => new external_value(PARAM_BOOL, 'Can it be bought as a single'),
                    'allow_license_blocks' => new external_value(PARAM_BOOL, 'Can it be bought with price banding'),
                    'enabled' => new external_value(PARAM_BOOL, 'Is it available'),
                    'single_purchase_currency' => new external_value(PARAM_TEXT, 'Currency'),
                    'single_purchase_price' => new external_value(PARAM_FLOAT, 'Product price'),
                    'single_purchase_validlength' => new external_value(PARAM_INT, 'License valid length'),
                    'single_purchase_shelflife' => new external_value(PARAM_INT, 'License shelf life'),
                    'type' => new external_value(PARAM_INT, 'License type'),
                    'program' => new external_value(PARAM_BOOL, 'Is it a program license'),
                    'instant' => new external_value(PARAM_BOOL, 'Is it instant access'),
                    'cutofftime' => new external_value(PARAM_INT, 'Enrolment cutoff time'),
                    'clearonexpire' => new external_value(PARAM_BOOL, 'Clear enrolments on expire'),
                    'buynowurl' => new external_value(PARAM_URL, 'Buy now URL'),
                    'moreinfourl' => new external_value(PARAM_URL, 'Product info URL'),
                ]),
            ),
            'searchkey' => new external_value(PARAM_TEXT, 'Search Text'),
            'searchtag' => new external_value(PARAM_TEXT, 'Filtered tag'),
            'companyid' => new external_value(PARAM_INT, 'Company ID'),
            'perpage' => new external_value(PARAM_INT, 'Products per page'),
            'page' => new external_value(PARAM_INT, 'Current page'),
            'tags' => new external_multiple_structure(
                new external_single_structure([
                    'tagname' => new external_value(PARAM_TEXT, 'Tag name'),
                    'selected' => new external_value(PARAM_BOOL, 'Is it selected'),
                ]),
            ),
            'hastags' => new external_value(PARAM_BOOL, 'Do we have any tags'),
            'hasselected' => new external_value(PARAM_BOOL, 'Do we have a selected tag'),
        ]);
    }
}
