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
 * IOMAD mycourses block install function
 *
 * @package   block_iomad_mycourses
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This script is run after the dashboard has been installed.
 *
 * @return bool
 */
function xmldb_block_iomad_mycourses_install() {
    global $CFG, $DB;

    // Add some default blocks to the dashboard
    // yes, I know this isn't really what this is for!!
    $systemcontext = context_system::instance();
    $page = new moodle_page();
    $page->set_context( $systemcontext );

    if ($defaultmycoursespage = $DB->get_record(
        'my_pages',
        ['userid' => null, 'name' => '__courses', 'private' => 0])) {
        $mycoursesubpagepattern = $defaultmycoursespage->id;
    } else {
        $mycoursesubpagepattern = null;
    }

    $page->blocks->add_blocks($page->blocks->filter_nonexistent_blocks([
        'content' => [
            'iomad_mycourses',
        ]]),
        'my-index',
        $mycoursesubpagepattern
    );

    // Deal with amy migrations from original mycourses block.
    if (!empty($CFG->mycourses_showsummary)) {
        set_config('showsummary', $CFG->mycourses_showsummary, 'block_iomad_mycourses');
        unset_config('mycourses_showsummary');
    }
    if (!empty($CFG->mycourses_defaultview)) {
        set_config('defaultview', $CFG->mycourses_defaultview, 'block_iomad_mycourses');
        unset_config('mycourses_defaultview');
    }

    $pluginman = core_plugin_manager::instance();
    $plugin = 'block_mycourses';
    if ($pluginman->can_uninstall_plugin($plugin)) {
        mtrace('Uninstalling: ' . $plugin);
        $progress = new progress_trace_buffer(new text_progress_trace(), true);
        $pluginman->uninstall_plugin($plugin, $progress);
        $progress->finished();
        mtrace($progress->get_buffer());
    }

    return true;
}
