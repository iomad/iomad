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
            'companyid' => new external_value(PARAM_INT, 'Parameter 1'),
            'page' => new external_value(PARAM_INT, 'Parameter 1'),
            'perpage' => new external_value(PARAM_INT, 'Parameter 1'),
            'baseurl' => new external_value(PARAM_INT, 'Parameter 1'),
        ]);
    }

    /**
     * Implementation of web service block_iomad_commerce_get_products
     *
     * @param mixed $companyid
     * @param mixed $page
     * @param mixed $perpage
     * @param mixed $param1
     */
    public static function execute($companyid, $page, $perpage, $baseurl) {
        // Parameter validation.
        [
            'companyid' => $companyid,
            'page' => $page,
            'perpage' => $perpage,
            'baseurl' => $param1,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'companyid' => $companyid,
                'page' => $page,
                'perpage' => $perpage,
                'baseurl' => $baseurl,
            ]
        );

        // From web services we don't call require_login(), but rather validate_context.
        $companycontext = context_company::instance($companyid);
        self::validate_context($companycontext);

        // Get the list of products.
        $items = array_values(helper::get_shop_products($companyid, $companycontext));
        $itemcount = count($items);

        // Set up the rest.
        $pagingbar = $output->paging_bar($itemcount, $page, $perpage, $baseurl);

        // Process all of the shop items.
        foreach ($items as $id => $item) {
            $available = ($item->allow_single_purchase ||
                        $item->allow_license_blocks) &&
                        (iomad::has_capability('block/iomad_commerce:buyitnow', $companycontext) ||
                         iomad::has_capability('block/iomad_commerce:buyinbulk', $companycontext));
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


        return [
            'itemcount' => $itemcount,
            'pagingbar' => $pagingbar,
            'items' => $items,
        ];
    }

    /**
     * Describe the return structure for block_iomad_commerce_get_products
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([]);
    }
}
