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
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../iomad_company_admin/lib.php');

\block_iomad_commerce\helper::require_commerce_enabled();

$itemid         = required_param('itemid', PARAM_INT);
$licenseformempty = optional_param('licenseformempty', 0, PARAM_INT);
$invalidamount = optional_param('invalidamount', 0, PARAM_BOOL);

require_login();

$systemcontext = context_system::instance();

// Set the companyid
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = \core\context\company::instance($companyid);
$company = new company($companyid);

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('course_shop_title', 'block_iomad_commerce');
// Set the url.
$linkurl = new moodle_url('/blocks/iomad_commerce/shop.php');

// Page stuff.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');
$PAGE->set_title($linktext);
$PAGE->navbar->add($linktext, $linkurl);
$PAGE->requires->js_call_amd('block_iomad_commerce/item_license_amount_form', 'init');

if ($item = $DB->get_record('course_shopsettings', ['id' => $itemid, 'enabled' => 1, 'companyid' => $companyid])) {
    $PAGE->navbar->add($item->name);
}

echo $OUTPUT->header();

flush();

\block_iomad_commerce\helper::show_basket_info();

if ($item) {
    $mustlogin = false;
    $strextra = "";
    $strbuynow = get_string('buynow', 'block_iomad_commerce');
    if (!isloggedin() || isguestuser()) {
        $mustlogin = true;
        $strbuynow = get_string('login', 'moodle');
        $strextra = get_string('product_login', 'block_iomad_commerce');
    }
    $strmoreinfo = get_string('moreinfo', 'block_iomad_commerce');

    echo '<h3>' . format_string($item->name) . "</h3>";

    if (isset($item->long_description)) {
        echo $item->long_description;
    } else {
        echo $item->summary;
    }

    if ($mustlogin) {
        $buynowurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/buynow.php', ['itemid' => $item->id]);
        $buynowurl = new moodle_url($CFG->wwwroot . "/login/index.php", ['wantsurl' => $buynowurl->out()]);
        echo "<a href='" . $buynowurl->out() . "' class='btn btn-primary'>" . $strbuynow . "<a>&nbsp $strextra<br>";
    } else if (($item->allow_single_purchase || $item->allow_license_blocks) &&
        (iomad::has_capability('block/iomad_commerce:buyitnow', $companycontext) || iomad::has_capability('block/iomad_commerce:buyinbulk', $companycontext))) {
        $table = new html_table();
        $table->head = array (get_string('priceoptions', 'block_iomad_commerce'), "", "");
        $table->align = array ("left", "center", "center");
        $table->width = "600px";


        if ($item->allow_single_purchase && iomad::has_capability('block/iomad_commerce:buyitnow', $companycontext)) {
            $buynowurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/buynow.php', ['itemid' => $item->id]);
            $table->data[] = [get_string('single_purchase', 'block_iomad_commerce'),
                              $item->single_purchase_currency . number_format($item->single_purchase_price, 2),
                              "<a href='" . $buynowurl->out() . "' class='btn btn-primary'>" .
                                   $strbuynow .
                                   "<a>&nbsp$strextra"];
        }

        $form = '';

        if ($item->allow_license_blocks) {
            $priceblocks = $DB->get_records('course_shopblockprice', ['itemid' => $item->id], 'price_bracket_start');

            if (count($priceblocks)) {
                if (iomad::has_capability('block/iomad_commerce:buyinbulk', $companycontext)) {
                    foreach ($priceblocks as $priceblock) {
                        $table->data[] = array(get_string('licenseblock_n', 'block_iomad_commerce',
                                                           $priceblock->price_bracket_start),
                                                $priceblock->currency . ' ' . number_format($priceblock->price, 2),
                                                '');
                    }

                    $msg = '';
                    if ($licenseformempty) {
                        $msg = "<p class='error'>" . get_string('licenseformempty', 'block_iomad_commerce') . "</p>";
                    }

                    // Create url for the form
                    $licenseformurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_commerce/buynow.php', ['itemid' => $itemid]);
                    // Create the tempalte for the mustache file
                    $template = (object)[
                        'form_url' => $licenseformurl->out(),
                        'item_id' => $itemid,
                        'msg_txt' => $msg,
                        'howmany_txt' => 'How many licenses?',
                        'buynow_txt' => get_string('buynow', 'block_iomad_commerce'),
                        'noerror_style' => 'display: none;'
                    ];
                    if(isset($invalidamount) && $invalidamount == true){
                        $template->error_class = 'text-danger';
                        unset($template->noerror_style);
                        $template->error_txt = get_string('error_singlepurchaseunavailable', 'block_iomad_commerce');
                    }
                    // Save the mustache file output in the variable $form
                    $form = $OUTPUT->render_from_template("block_iomad_commerce/item_license_amount_form", $template);
                }
            }
        }

        if (!empty($table)) {
            echo "<a name='buynow'></a>";
            echo html_writer::table($table);
            echo $form;
            echo html_writer::tag('a', get_string('back'), ['class' => 'btn btn-secondary', 'href' => $linkurl]);
        }
    }

} else {
    echo "<p>" . get_string('courseunavailable', 'block_iomad_commerce') . "</p>";
}

echo $OUTPUT->footer();