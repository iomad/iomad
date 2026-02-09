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
 * Field definitions.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require_login();

use block_dash\local\block_builder;

$download = optional_param('download', 'csv', PARAM_TEXT);
$instanceid = required_param('block_instance_id', PARAM_INT);
$filterformdata = optional_param('filter_form_data', '', PARAM_TEXT);
$currentpage = optional_param('page', 0, PARAM_INT);
$sortfield = optional_param('sort_field', '', PARAM_TEXT);
$sortdir = optional_param('sort_direction', '', PARAM_TEXT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/blocks/dash/download.php', ['block_instance_id' => $instanceid]);

$renderer = $PAGE->get_renderer('block_dash');
$binstance = $DB->get_record('block_instances', ['id' => $instanceid]);
$block = block_instance($binstance->blockname, $binstance);
if ($sortfield) {
    $block->set_sort($sortfield, $sortdir);
}
$bbdownload = block_builder::create($block);
if (!$bbdownload->get_configuration()->get_data_source()->get_preferences('exportdata') ) {
    return false;
}
foreach (json_decode($filterformdata, true) as $filter) {
    $bbdownload->get_configuration()
        ->get_data_source()
        ->get_filter_collection()
        ->apply_filter($filter['name'], $filter['value']);
}
$bbdownload->get_configuration()->get_data_source()->get_paginator()->set_current_page($currentpage);
$bbdownloadsource = $bbdownload->get_configuration()->get_data_source();
$file = $bbdownload->get_configuration()->get_data_source()->get_name();
$filename = $file . "_" . get_string('strdatasource', 'block_dash');
if ($download == "xls") {
    require_once("$CFG->libdir/excellib.class.php");
    // Calculate file name.
    // Creating a workbook.
    $workbook = new \MoodleExcelWorkbook("-");
    // Send HTTP headers.
    $filename .= "_" . time();
    $workbook->send($filename);
    // Creating the first worksheet.
    $myxls = $workbook->add_worksheet('dash');
    // Print names of all the fields.
    $i = 0;
    foreach ($bbdownloadsource->export_for_template($renderer)['data']->first_child()['data'] as $col) {
        if ($col->is_visible()) {
            $myxls->write_string(0, $i++, $col->get_label());
        }
    }
    $rowdata = $bbdownloadsource->export_for_template($renderer)['data']['rows'];
    if ($rowdata) {
        // Generate the data for the body of the spreadsheet.
        $j = 1;
        foreach ($rowdata as $row) {
            $fields = [];
            $k = 0;
            foreach ($row['data'] as $data) {
                if ($data->is_visible()) {
                    $myxls->write_string($j, $k++, trim(strip_tags(format_text($data->get_value(), true))));
                }
            }
            $j++;
        }
    }
    // Close the workbook.
    $workbook->close();
} else if ($download == 'csv') {
    require_once("$CFG->libdir/csvlib.class.php");
    $csvexport = new \csv_export_writer("-");
    $csvexport->set_filename($filename);
    $headers = [];
    foreach ($bbdownloadsource->export_for_template($renderer)['data']->first_child()['data'] as $col) {
        if ($col->is_visible()) {
            $headers[] = $col->get_label();
        }
    }
    $csvexport->add_data($headers);
    $rowdata = $bbdownloadsource->export_for_template($renderer)['data']['rows'];
    if ($rowdata) {
        foreach ($rowdata as $row) {
            $cols = [];
            foreach ($row['data'] as $data) {
                if ($data->is_visible()) {
                    $cols[] = trim(strip_tags(format_text($data->get_value(), true)));
                }
            }
            $csvexport->add_data($cols);
        }
    }
    $csvexport->download_file();
}


