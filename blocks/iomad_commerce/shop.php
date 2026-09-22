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

use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');

block_iomad_commerce\helper::require_commerce_enabled();

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

$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->navbar->add($linktext);

$baseurl = new moodle_url('/blocks/iomad_commerce/shop.php', ['sort' => $sort,
                                                              'dir' => $dir,
                                                              'perpage' => $perpage]);
$returnurl = $baseurl;

// Display the page header.
echo $OUTPUT->header();

block_iomad_commerce\helper::show_basket_info();

// ...**********tag listing and filtering*****************.
if (array_key_exists('tag', $_GET)) {
    $shoptags = block_iomad_commerce\helper::get_shop_tags();
    if (in_array( $_GET['tag'], $shoptags )) {
        $SESSION->shoptag = optional_param('tag', '', PARAM_NOTAGS);
    } else {
        unset($SESSION->shoptag);
    }
}

$tagjoin = '';
$tagwhere = '';
$sqlparams = ['companyid' => $companyid];
$tagfilters = '';
if (isset($SESSION->shoptag) && $SESSION->shoptag != '') {
    $tagfilters = html_writer::tag('li', get_string('filtered_by_tag',
                                                    'block_iomad_commerce',
                                                    '<em>' . $SESSION->shoptag . '</em>' ));
    $tagjoin = 'INNER JOIN {block_iomad_commerce_product_shoptags} cst ON cst.itemid = css.id
                INNER JOIN {block_iomad_commerce_shoptags} st ON cst.shoptagid = st.id';
    $tagwhere = ' AND st.tag = :tag ';
    $sqlparams['tag'] = $SESSION->shoptag;
} else {
    $shoptags = block_iomad_commerce\helper::get_shop_tags();
    if (count($shoptags)) {
        echo get_string('filter_by_tag', 'block_iomad_commerce');
        foreach ($shoptags as $shoptag) {
            echo "<a href='?tag=$shoptag'>$shoptag</a> ";
        }
    }
}
echo html_writer::start_tag('ul', ['id' => 'filtering']);
echo $tagfilters;

// ...***********search*****************.
$searchkey = '';
if (array_key_exists('q', $_GET)) {
    $searchkey = optional_param('q', '', PARAM_NOTAGS);
    if ($searchkey) {
        $SESSION->shopsearch = $searchkey;
    } else {
        unset($SESSION->shopsearch);
    }
}

$searchwhere = '';
if (isset($SESSION->shopsearch)) {
    $searchkey = $SESSION->shopsearch;
    echo html_writer::tag('li', get_string('filtered_by_search', 'block_iomad_commerce', '<em>' . $searchkey . '</em>'));

    $searchwhere = ' AND
        (' . $DB->sql_like("c.fullname", ":searchkey1") . '
         OR
         ' . $DB->sql_like("c.shortname", ":searchkey2") . '
         OR
         ' . $DB->sql_like("c.summary", ":searchkey3") . '
         OR
         ' . $DB->sql_like("css.short_description", ":searchkey4") . '
         OR
         ' . $DB->sql_like("css.long_description", ":searchkey5") . '
         OR
         ' . $DB->sql_like("css.name", ":searchkey6") . '
         OR
         ' . $DB->sql_like("ilp.name", ":searchkey7") . '
        )
    ';
    for ($i = 1; $i < 8; $i++) {
        $sqlparams['searchkey' . $i] = '%' . $searchkey . '%';
    }
}

if (count($sqlparams) > 1) {
    echo '<a href="?tag=&q=">' . get_string('remove_filter', 'block_iomad_commerce') . '</a>';
}

echo html_writer::end_tag('ul');

echo html_writer::start_tag('div', ['class' => 'form-check-inline pb-3',
                                    'id' => 'shoptagsearch']);
echo html_writer::tag('span', get_string('search'));
echo html_writer::start_tag('form', ['method' => 'get']);
echo html_writer::empty_tag('input', ['type' => 'text',
                                      'name' => 'q',
                                      'class' => 'form-control offset-1',
                                      'valuesize' => '50',
                                      'maxlength' => '100',
                                      'value' => $searchkey]);
echo html_writer::end_tag('form');
echo html_writer::end_tag('div');

// ...***********create course list sql (includes filtering on tags)*****************.
$typewhere = "";
if (!iomad::has_capability('block/iomad_commerce:buyinbulk', $companycontext)) {
    $typewhere = " AND css.allow_single_purchase = 1 ";
}

$sql = "FROM {block_iomad_commerce_products} css
        LEFT JOIN {block_iomad_commerce_product_courses} csc ON (css.id = csc.itemid)
        LEFT JOIN {course} c ON (csc.courseid = c.id)
        LEFT JOIN {block_iomad_commerce_product_learningpaths} cssp ON (css.id = cssp.itemid)
        LEFT JOIN {block_iomad_learningpath} ilp ON (cssp.pathid = ilp.id)
        $tagjoin
        LEFT JOIN {block_iomad_commerce_product_blockprices} sbp ON (
            css.id = sbp.itemid
            AND sbp.id = (
                SELECT id FROM {block_iomad_commerce_product_blockprices}
                WHERE itemid = css.id
                ORDER BY price
                LIMIT 1
            )
        )
        WHERE css.enabled = 1
        AND css.companyid = :companyid
        AND (
            css.allow_single_purchase = 1 OR css.id = sbp.itemid
            AND sbp.id = (
                SELECT id FROM {block_iomad_commerce_product_blockprices}
                WHERE itemid = css.id ORDER BY price LIMIT 1 ))
        AND (
            ilp.id IS NULL
            OR (
                ilp.id IS NOT NULL
                AND ilp.id IN (
                    SELECT pathid
                    FROM {block_iomad_learningpath_courses}
                    WHERE pathid = ilp.id)))
        $tagwhere
        $searchwhere
        $typewhere
        GROUP BY css.id, sbp.id
        ORDER BY css.name";

// Get the number of Courses.
$items = $DB->get_records_sql("SELECT DISTINCT css.* $sql", $sqlparams);
$itemcount = count($items);

echo $OUTPUT->paging_bar($itemcount, $page, $perpage, $baseurl);

if ($itemcount) {
    $mustlogin = false;
    $strextra = "";
    $strbuynow = get_string('buynow', 'block_iomad_commerce');
    if (!isloggedin() || isguestuser()) {
        $mustlogin = true;
        $strbuynow = get_string('login', 'moodle');
        $strextra = get_string('product_login', 'block_iomad_commerce');
    }
    $strmoreinfo = get_string('moreinfo', 'block_iomad_commerce');

    // Set up the product table.
    $producttable = "";

    // Process all of the shop items.
    foreach ($items as $item) {
        $available = ($item->allow_single_purchase ||
                      $item->allow_license_blocks) &&
                     (iomad::has_capability('block/iomad_commerce:buyitnow', $companycontext) ||
                      iomad::has_capability('block/iomad_commerce:buyinbulk', $companycontext));
        $price = block_iomad_commerce\helper::get_lowest_price_text($item);
        if ($available) {
            if ($item->allow_single_purchase) {
                $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/iomad_commerce/buynow.php", ['itemid' => $item->id]);
            } else {
                $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/iomad_commerce/item.php", ['itemid' => $item->id]);
            }
            $buynowbutton = html_writer::tag('a', $strbuynow, ['href' => $buynowurl,
                                                               'class' => 'btn btn-primary']) .
                            "&nbsp" . $strextra;

            $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/iomad_commerce/item.php", ['itemid' => $item->id]);
            $moreinfobutton = html_writer::tag(
                'a',
                $strmoreinfo,
                [
                    'href' => $moreinfourl,
                    'class' => 'btn btn-secondary',
                ]
            );
        } else {
            if ($mustlogin) {
                $buynowurl = new moodle_url($CFG->wwwroot . "/blocks/iomad_commerce/item.php", ['itemid' => $item->id]);
                $buynowurl = new moodle_url($CFG->wwwroot . "/login/index.php", ['wantsurl' => $buynowurl->out()]);
                $buynowbutton = html_writer::tag(
                    'a',
                    $strbuynow,
                    [
                        'href' => $buynowurl,
                        'class' => 'btn btn-primary',
                    ]
                ) . "&nbsp" . $strextra;
            } else {
                $buynowbutton = "";
            }
            $moreinfourl = new moodle_url($CFG->wwwroot . "/blocks/iomad_commerce/item.php", ['itemid' => $item->id]);
            $moreinfobutton = $price . "&nbsp" .
            html_writer::tag(
                'a',
                $strmoreinfo,
                [
                    'href' => $moreinfourl,
                    'class' => 'btn btn-secondary',
                ]
            );
        }

        // Add this to the product table.
        $producttable .= html_writer::start_div('col d-flex pl-0 pr-1 mb-1');
        $producttable .= html_writer::start_div('card h-100 shadow-sm');
        $producttable .= html_writer::div(format_string($item->name), 'card-title h4 ps-2 pt-2');
        $producttable .= html_writer::start_div('card-body');
        $producttable .= html_writer::tag('p', $item->short_description, ['class' => 'card-text small']);
        $producttable .= html_writer::end_div();
        $producttable .= html_writer::tag('div', $price, ['class' => 'card-body']);
        $producttable .= html_writer::start_div('card-footer d-flex flex-wrap gap-2 text-end');
        $producttable .= html_writer::tag('span', $moreinfobutton . "&nbsp;" . $buynowbutton, ['class' => 'container']);
        $producttable .= html_writer::end_div();
        $producttable .= html_writer::end_div();
        $producttable .= html_writer::end_div();
    }

    if (!empty($producttable)) {
        echo html_writer::start_div('card-grid mx-0 row row-cols-1 row-cols-sm-2 row-cols-lg-3');
        echo $producttable;
        echo html_writer::end_div();
        echo $OUTPUT->paging_bar($itemcount, $page, $perpage, $baseurl);
    }

} else {
    echo html_writer::tag('p', get_string('nocoursesontheshop', 'block_iomad_commerce'));
}

echo $OUTPUT->footer();
