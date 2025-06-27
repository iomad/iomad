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
 *  index.php description here.
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

use local_iomadcustompage\output\renderer;
use local_iomadcustompage\permission;
use core_reportbuilder\system_report_factory;
use local_iomadcustompage\reportbuilder\local\systemreports\pages_list;

require_once(__DIR__ . '/../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

$systemcontext = context_system::instance();
if (has_capability('moodle/site:configview', $systemcontext)) {
    admin_externalpage_setup(
        'manageiomadcustompages',
        null,
        [],
        new moodle_url('/local/iomadcustompage/index.php'),
        ['pagelayout' => 'admin', 'nosearch' => true]
    );
} else {
    $PAGE->set_pagelayout('standard');
    $PAGE->set_url('/local/iomadcustompage/index.php');
}

$PAGE->set_secondary_navigation(false);
$PAGE->set_heading('');

$PAGE->requires->js_call_amd('local_iomadcustompage/pages_list', 'init');

$context = $systemcontext;
$companyid = iomad::get_my_companyid($systemcontext);
if ($companyid > 0) {
    $context = \core\context\company::instance($companyid);
}
$PAGE->set_context($systemcontext);

echo $OUTPUT->header();
echo html_writer::start_div('d-flex justify-content-between mb-2');

echo $OUTPUT->heading(get_string('iomadcustompages', 'local_iomadcustompage'));

if (permission::can_create_page()) {
    /** @var renderer $renderer */
    $renderer = $PAGE->get_renderer('local_iomadcustompage');
    echo $renderer->render_new_page_button();
}

echo html_writer::end_div();

$report = system_report_factory::create(pages_list::class, $context);
echo html_writer::start_div('mt-5');
echo $report->output();
echo html_writer::end_div();

echo $OUTPUT->footer();
