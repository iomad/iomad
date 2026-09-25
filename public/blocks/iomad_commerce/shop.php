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
 * IOMAD eCommerce
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_commerce\helper;
use block_iomad_commerce\output\shop;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');

helper::require_commerce_enabled();

$sort = optional_param('sort', 'name', PARAM_ALPHA);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', get_config('local_iomad', 'max_list_courses'), PARAM_INT);

// Setup the navbar.
// Set the name for the page.
$linktext = get_string('shop_title', 'block_iomad_commerce');
// Set the url.
$linkurl = new moodle_url('/blocks/iomad_commerce/shop.php');

require_login();

$systemcontext = context_system::instance();

// Set the companyid.
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Set up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);

// Save the SESSION options.
if (array_key_exists('tag', $_GET)) {
    $shoptags = helper::get_shop_tags();
    if (in_array( $_GET['tag'], $shoptags )) {
        $SESSION->shoptag = optional_param('tag', '', PARAM_NOTAGS);
    } else {
        unset($SESSION->shoptag);
    }
}
if (array_key_exists('q', $_GET)) {
    $searchkey = optional_param('q', '', PARAM_NOTAGS);
    if ($searchkey) {
        $SESSION->shopsearch = $searchkey;
    } else {
        unset($SESSION->shopsearch);
    }
}

// Add the control buttons.
$buttons = helper::get_page_buttons('shop');
$PAGE->set_button($buttons);

$baseurl = new moodle_url('/blocks/iomad_commerce/shop.php', ['sort' => $sort,
                                                              'dir' => $dir,
                                                              'perpage' => $perpage]);
$returnurl = $baseurl;

// Set up the renderers.
$renderable = new shop($companyid, $companycontext, $page, $perpage, $baseurl);
$renderer = $PAGE->get_renderer('block_iomad_commerce');

// Display the page header.
echo $OUTPUT->header();

// Render the shop output.
echo $renderer->render($renderable);

// Display the footer.
echo $OUTPUT->footer();
