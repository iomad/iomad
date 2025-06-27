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
 * Edit page for managing custom pages, including content, details, audience and access settings.
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

use core\output\dynamic_tabs;
use local_iomadcustompage\manager;
use local_iomadcustompage\permission;
use local_iomadcustompage\output\dynamictabs\access;
use local_iomadcustompage\output\dynamictabs\audience;
use local_iomadcustompage\output\dynamictabs\content;
use local_iomadcustompage\output\dynamictabs\details;

require_once(__DIR__ . '/../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

$pageid = required_param('id', PARAM_INT);

$page = manager::get_page_from_id($pageid);
permission::require_can_edit_page($page);

admin_externalpage_setup('manageiomadcustompages',
        null,
        ['id' => $pageid],
        new moodle_url('/local/iomadcustompage/edit.php'),
        ['pagelayout' => 'admin', 'nosearch' => true]);

$PAGE->set_context($page->get_context());
$PAGE->navbar->add($page->get_formatted_name(), $PAGE->url);
$PAGE->set_secondary_navigation(false);
$PAGE->set_heading($page->get_formatted_name());

/** @var \local_iomadcustompage\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_iomadcustompage');

$pagename = $page->get_formatted_name();
$PAGE->set_title($pagename);

echo $OUTPUT->header();

// Add dynamic tabs.
$tabdata = ['pageid' => $pageid];
$tabs = [
  new content($tabdata),
  new details($tabdata),
  new audience($tabdata),
  new access($tabdata),
];

echo $OUTPUT->render_from_template(
    'core/dynamic_tabs',
    (new dynamic_tabs($tabs))->export_for_template($OUTPUT)
);

echo $OUTPUT->footer();
