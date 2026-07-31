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
 * Main management page for custom pages listing.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

use local_iomadcustompage\permission;
use core_reportbuilder\system_report_factory;
use local_iomadcustompage\reportbuilder\local\systemreports\pages_list;
use local_iomad\iomad;
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

// IOMAD.
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

// IOMAD.
$context = $systemcontext;
$companyid = iomad::get_my_companyid($systemcontext);
if ($companyid > 0) {
    $context = context_company::instance($companyid);
}
$PAGE->set_context($systemcontext);

// Log this page view.
block_iomad_company_admin\event\dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

echo $OUTPUT->header();

// Header section with title and create button.
echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');
echo $OUTPUT->heading(get_string('iomadcustompages', 'local_iomadcustompage'), 2, 'mb-0');

if (permission::can_create_page()) {
    $renderer = $PAGE->get_renderer('local_iomadcustompage');
    echo $renderer->render_new_page_button();
}
echo html_writer::end_div();

// Pages list report.
try {
    $report = system_report_factory::create(pages_list::class, $context);
    echo html_writer::div($report->output(), 'pages-list-container mt-4');
} catch (Exception $e) {
    debugging('Error loading pages list: ' . $e->getMessage(), DEBUG_DEVELOPER);
    echo $OUTPUT->notification(get_string('error'), 'error');
}

echo $OUTPUT->footer();
