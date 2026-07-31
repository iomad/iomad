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
 * View a custom page.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

use local_iomadcustompage\custom_context\context_iomadcustompage;
use local_iomadcustompage\manager;
use local_iomadcustompage\permission;
use local_iomadcustompage\event\iomadcustompage_viewed;

require_once(__DIR__ . '/../../config.php');

$pageid = required_param('id', PARAM_INT);

// Validate page ID.
if ($pageid <= 0) {
    throw new moodle_exception('invalidpageid', 'local_iomadcustompage');
}

try {
    $context = context_iomadcustompage::instance($pageid);
    $page = manager::get_page_from_id($pageid);
} catch (dml_missing_record_exception $e) {
    throw new moodle_exception('pagenotfound', 'local_iomadcustompage');
} catch (Exception $e) {
    debugging('Error loading page: ' . $e->getMessage(), DEBUG_DEVELOPER);
    throw new moodle_exception('errorloadingpage', 'local_iomadcustompage');
}

require_login(null, true);
permission::require_can_view_page($page);
$PAGE->set_context($context);

// Log page view event.
try {
    $pageobject = $page->to_record();
    $event = iomadcustompage_viewed::create_from_object($pageobject, $context);
    $event->trigger();
} catch (Exception $e) {
    debugging('Error logging page view: ' . $e->getMessage(), DEBUG_DEVELOPER);
}

$pageurl = new moodle_url('/local/iomadcustompage/view.php', ['id' => $pageid]);
$pagetitle = $page->get_formatted_title() ?: $page->get_formatted_name();
$iscontainer = $page->is_container();

// Are we using a custom page as the dashboard?
if ($DB->record_exists(
    'local_iomad_company_pages',
    ['pageid' => $pageid,
     'type' => 'dashboard',
    ]
    )) {
    $isdashboard = true;
    $pageurl = new moodle_url('/my/index.php');
    $title = get_string('myhome');
    $pagelayout = 'mydashboard';
    $PAGE->set_heading($title);
} else {
    $pageurl = new moodle_url('/local/iomadcustompage/view.php', ['id' => $pageid]);
    $title = $page->get('title');
    $pagelayout = 'report';
    $isdashboard = false;
}

// Container pages cannot be edited.
if ($iscontainer && isset($USER->editing)) {
    $USER->editing = 0;
}

$PAGE->set_subpage((string)$pageid);
$PAGE->set_pagelayout($pagelayout);
$PAGE->set_pagetype('local-iomadcustompage-view');

// Load theme block regions first to ensure standard regions (like the right drawer) remain the default.
$PAGE->blocks->get_regions();

// Only non-container pages should show blocks.
if (!$iscontainer && !$isdashboard) {
    $PAGE->blocks->add_region('content');
    $PAGE->blocks->add_region('side-pre');
} else if ($isdashboard) {
    $PAGE->blocks->add_region('content');
}

$PAGE->set_title($pagetitle);
$PAGE->set_heading($page->get_formatted_name());
$PAGE->set_url($pageurl);

// Setup breadcrumb navigation.
try {
    manager::setup_page_breadcrumb($page->get_breadcrumb());
} catch (Exception $e) {
    debugging('Error setting up breadcrumb: ' . $e->getMessage(), DEBUG_DEVELOPER);
}

$renderer = $PAGE->get_renderer('local_iomadcustompage');
$showeditorheader = $PAGE->user_is_editing() &&
                   permission::can_edit_page($page) &&
                   !$iscontainer;

// IOMAD - log this page view.
block_iomad_company_admin\event\dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

echo $OUTPUT->header();

// Only content pages can have blocks added to them.
if (!$iscontainer) {
    echo $OUTPUT->addblockbutton('content');
}

// Show editor header for non-container pages.
if ($showeditorheader) {
    try {
        echo $renderer->render_fullpage_editor_header($page);
    } catch (Exception $e) {
        debugging('Error rendering editor header: ' . $e->getMessage(), DEBUG_DEVELOPER);
        echo $OUTPUT->notification(get_string('erroreditorheader', 'local_iomadcustompage'), 'error');
    }
}

// Render page content blocks.
if (!$iscontainer || $isdashboard) {
    echo $OUTPUT->custom_block_region('content');
}

echo $OUTPUT->footer();
