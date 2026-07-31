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
 * Library functions for local_iomadcustompage plugin.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

use core\output\inplace_editable;
use local_iomadcustompage\local\helpers\audience as audience_helper;
use local_iomadcustompage\local\models\page as page_persistent;
use local_iomadcustompage\local\services\navigation as navigation_service;
use local_iomadcustompage\local\services\audience_fragment as audience_fragment_service;
use local_iomadcustompage\output\audience_heading_editable;
use local_iomadcustompage\output\page_name_editable;
use local_iomadcustompage\output\page_title_editable;

defined('MOODLE_INTERNAL') || die();


if (!defined('CONTEXT_CUSTOMPAGE')) {
    define('CONTEXT_CUSTOMPAGE', 75);
}

/**
 * Plugin inplace editable implementation.
 *
 * @param string $itemtype The type of item being edited
 * @param int $itemid The ID of the item being edited
 * @param string $newvalue The new value for the item
 * @return inplace_editable|null The inplace editable object or null if not supported
 * @throws moodle_exception If session key is invalid
 * @throws coding_exception If invalid parameters are provided
 */
function local_iomadcustompage_inplace_editable(string $itemtype, int $itemid, string $newvalue): ?inplace_editable {
    require_sesskey();

    // Validate input parameters.
    if ($itemid <= 0) {
        throw new coding_exception('Invalid item ID provided');
    }

    if (empty($itemtype)) {
        throw new coding_exception('Item type cannot be empty');
    }

    switch ($itemtype) {
        case 'pagename':
            return page_name_editable::update($itemid, $newvalue);
        case 'pagetitle':
            return page_title_editable::update($itemid, $newvalue);
        case 'audienceheading':
            return audience_heading_editable::update($itemid, $newvalue);
        default:
            return null;
    }
}

/**
 * Return the audience form fragment.
 *
 * @param array $params Parameters for the fragment
 * @return string The rendered fragment
 */
function local_iomadcustompage_output_fragment_audience_form(array $params): string {
    return audience_fragment_service::render($params);
}

/**
 * Extend site navigation with custom pages.
 *
 * @param global_navigation $nav The global navigation object
 * @return void
 */
function local_iomadcustompage_extend_navigation(global_navigation $nav): void {
    global $PAGE, $CFG, $USER;

    // Skip for AJAX and CLI scripts.
    if (AJAX_SCRIPT || CLI_SCRIPT) {
        return;
    }

    // Preserve original custom menu items.
    $CFG->dbunmodifiedcustommenuitems = $CFG->custommenuitems ?? '';

    $userid = local_iomadcustompage_get_user_id();
    if (!$userid) {
        return;
    }

    try {
        $pages = audience_helper::user_pages_list($userid);
        if (empty($pages)) {
            return;
        }

        // Build hierarchical menu structure.
        $pagehierarchy = build_page_hierarchy($pages);

        // Add pages to menu in hierarchical order.
        add_bootstrap_hierarchical_menu_items($pagehierarchy);

        // Handle navigation breadcrumb for current page.
        local_iomadcustompage_handle_current_page_breadcrumb($nav);
    } catch (Exception $e) {
        debugging('Error extending navigation: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

/**
 * Get the current user ID, including guest users.
 *
 * @return int|null User ID or null if no valid user
 */
function local_iomadcustompage_get_user_id(): ?int {
    global $CFG, $USER;

    if (isloggedin()) {
        return (int)$USER->id;
    }

    if (!empty($CFG->guestloginbutton)) {
        $guest = guest_user();
        return (int)$guest->id;
    }

    return null;
}

/**
 * Handle breadcrumb navigation for current custom page.
 *
 * @param global_navigation $nav The global navigation object
 * @return void
 */
function local_iomadcustompage_handle_current_page_breadcrumb(global_navigation $nav): void {
    global $PAGE;

    if ($PAGE->context->contextlevel !== CONTEXT_CUSTOMPAGE) {
        return;
    }

    try {
        $currentpageid = $PAGE->context->instanceid;
        $currentpage = page_persistent::get_record(['id' => $currentpageid]);

        if ($currentpage) {
            $userid = local_iomadcustompage_get_user_id();
            if ($userid) {
                $pages = audience_helper::user_pages_list($userid);
                if (in_array($currentpageid, $pages)) {
                    add_page_navigation_breadcrumb($nav, $currentpage);
                }
            }
        }
    } catch (Exception $e) {
        debugging('Error handling page breadcrumb: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

/**
 * Build page hierarchy from flat list.
 *
 * @param array $pageids List of page IDs to build hierarchy from
 * @return array Hierarchical structure of pages
 * @throws coding_exception If navigation service fails
 */
function build_page_hierarchy(array $pageids): array {
    return navigation_service::build_page_hierarchy($pageids);
}

/**
 * Recursively build page tree.
 *
 * @param stdClass $page Current page to process
 * @param array $pagesbyparent Array of pages grouped by parent ID
 * @return array Node structure with current page and its children
 * @throws coding_exception If navigation service fails
 */
function build_page_tree_recursive(stdClass $page, array $pagesbyparent): array {
    return navigation_service::build_page_tree_recursive($page, $pagesbyparent);
}

/**
 * Add hierarchical menu items with proper Bootstrap formatting.
 *
 * @param array $hierarchy Hierarchical structure of pages
 * @param int $level Current nesting level
 * @return void
 * @throws coding_exception If navigation service fails
 */
function add_bootstrap_hierarchical_menu_items(array $hierarchy, int $level = 0): void {
    navigation_service::add_bootstrap_hierarchical_menu_items($hierarchy, $level);
}

/**
 * Add page navigation breadcrumb.
 *
 * @param global_navigation $nav Navigation object to add breadcrumbs to
 * @param page_persistent $currentpage Current page being viewed
 * @return void
 * @throws coding_exception If page hierarchy cannot be built
 * @throws moodle_exception If URL construction fails
 */
function add_page_navigation_breadcrumb(global_navigation $nav, page_persistent $currentpage): void {
    $frontpagenode = $nav->find('home', null);

    if (!$frontpagenode) {
        $frontpagenode = $nav->add(
            get_string('home'),
            new moodle_url('/'),
            navigation_node::TYPE_ROOTNODE,
            null,
            'home'
        );
        $frontpagenode->force_open();
    }

    // Get page hierarchy.
    $breadcrumb = [];
    $current = $currentpage;

    while ($current) {
        array_unshift($breadcrumb, $current);
        try {
            $parentid = $current->get('parent');
        } catch (coding_exception $e) {
            debugging('Error getting parent ID: ' . $e->getMessage(), DEBUG_DEVELOPER);
            break;
        }
        $current = $parentid ? page_persistent::get_record(['id' => $parentid]) : null;
    }

    // Add breadcrumb nodes.
    $parentnode = $frontpagenode;
    foreach ($breadcrumb as $breadcrumbpage) {
        $pageurl = new moodle_url('/local/iomadcustompage/view.php', ['id' => $breadcrumbpage->get('id')]);
        $pagename = $breadcrumbpage->get_formatted_name();

        $pagenode = $parentnode->add(
            $pagename,
            $pageurl,
            navigation_node::TYPE_CUSTOM,
            null,
            'iomadcustompage_' . $breadcrumbpage->get('id')
        );
        $pagenode->make_active();
        $parentnode = $pagenode;
    }
}

// Note: The legacy local_iomadcustompage_before_standard_top_of_body_html() callback has been
// removed. Menu restoration is now handled by hook_callbacks::before_standard_top_of_body_html_generation().
