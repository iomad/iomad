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
 *  lib.php description here.
 *
 * @package local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

define('CONTEXT_CUSTOMPAGE', 75);

use core\exception\moodle_exception;
use core\output\inplace_editable;
use local_iomadcustompage\form\audience;
use local_iomadcustompage\local\models\page as page_persistent;
use local_iomadcustompage\output\audience_heading_editable;
use local_iomadcustompage\output\page_name_editable;
use local_iomadcustompage\output\page_title_editable;
use local_iomadcustompage\local\helpers\audience as audience_helper;

/**
 * Update the editable item and return its updated state.
 *
 * @param string $itemtype The type of the item being edited (e.g., 'pagename', 'pagetitle', 'audienceheading').
 * @param int $itemid The ID of the item being edited.
 * @param string $newvalue The new value to assign to the editable item.
 * @return inplace_editable|null The updated inplace_editable instance, or null if the itemtype is not recognized.
 * @throws moodle_exception
 */
function local_iomadcustompage_inplace_editable(string $itemtype, int $itemid, string $newvalue): ?inplace_editable {
    require_sesskey();
    switch ($itemtype) {
        case 'pagename':
            return page_name_editable::update($itemid, $newvalue);
        case 'pagetitle':
            return page_title_editable::update($itemid, $newvalue);
        case 'audienceheading':
            return audience_heading_editable::update($itemid, $newvalue);
    }
    return null;
}

/**
 * Return the audience form fragment
 *
 * @param array $params
 * @return string
 * @throws moodle_exception
 */
function local_iomadcustompage_output_fragment_audience_form(array $params): string {
    global $PAGE;

    $audienceform = new audience(null, null, 'post', '', [], true, [
    'pageid' => $params['pageid'],
    'classname' => $params['classname'],
    ]);
    $audienceform->set_data_for_dynamic_submission();

    $context = [
    'instanceid' => 0,
    'heading' => $params['title'],
    'headingeditable' => $params['title'],
    'form' => $audienceform->render(),
    'canedit' => true,
    'candelete' => true,
    'showormessage' => $params['showormessage'],
    ];

    $renderer = $PAGE->get_renderer('local_iomadcustompage');
    return $renderer->render_from_template('local_iomadcustompage/local/audience/form', $context);
}

/**
 * Extend the global navigation with custom pages for the current user.
 *
 * @param global_navigation $nav The global navigation instance.
 * @return void
 * @throws coding_exception
 * @throws moodle_exception
 */
function local_iomadcustompage_extend_navigation(global_navigation $nav) {
    global $PAGE, $CFG, $USER;

    // First, we need to find the pages based on user id.
    // If guest-login is enabled, then we will also check for guest user otherwise only for logged-in user.

    $CFG->dbunmodifiedcustommenuitems = $CFG->custommenuitems;

    if (isloggedin()) {
        $userid = (int)$USER->id;
    } else if ($CFG->guestloginbutton) {
        $guest = guest_user();
        $userid = (int)$guest->id;
    }
    if (!isset($userid)) {
        return;
    }
    $pages = audience_helper::user_pages_list($userid);

    foreach ($pages as $pageid) {
        $iomadcustompage = page_persistent::get_record(['id' => (int)$pageid]);
        $pagename = $iomadcustompage->get_formatted_name();
        $pagetitle = $iomadcustompage->get_formatted_title();
        if (!$pagetitle) {
            $pagetitle = $pagename;
        }

        $CFG->custommenuitems .= "\n" . "$pagetitle|/local/iomadcustompage/view.php?id=$pageid\n";

        if ($PAGE->context->contextlevel == CONTEXT_CUSTOMPAGE) {
            if ($pageid == $PAGE->context->instanceid) {
                // Add page node to homepage node.
                $frontpagenode = $nav->find('home', null);

                if (!$frontpagenode) {
                    $frontpagenode = $nav->add(
                        get_string('home'),
                        new moodle_url('/index.php'),
                        navigation_node::TYPE_ROOTNODE,
                        null
                    );
                    $frontpagenode->force_open();
                }
                $iomadcustompagenode = $frontpagenode->add(
                    $iomadcustompage->get_formatted_name(),
                    new moodle_url('/local/iomadcustompage/view.php', ['id' => $iomadcustompage->get('id')])
                );
                $iomadcustompagenode->make_active();
            }
        } else {
            // Add page node to homepage node.
            $frontpagenode = $PAGE->navigation->find('home', null);

            if (!$frontpagenode) {
                $frontpagenode = $PAGE->navigation->add(
                    get_string('home'),
                    new moodle_url('/index.php'),
                    navigation_node::TYPE_ROOTNODE,
                    null
                );
                $frontpagenode->force_open();
            }

            $frontpagenode->add(
                $pagename,
                new moodle_url('/local/iomadcustompage/view.php', ['id' => $pageid])
            );
        }
    }
}

/**
 * Configure custom context classes for the local_iomadcustompage plugin using after_config hook.
 *
 * @return void
 */
function local_iomadcustompage_after_config() {
    global $CFG;
    $customcontextclasses = [
        CONTEXT_CUSTOMPAGE => 'local_iomadcustompage\\custom_context\\context_iomadcustompage',
    ];

    if (isset($CFG->custom_context_classes)) {
        $CFG->custom_context_classes = $CFG->custom_context_classes + $customcontextclasses;
    } else {
        $CFG->custom_context_classes = $customcontextclasses;
    }
}
