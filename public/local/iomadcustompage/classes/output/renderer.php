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
 * Renderer for local_iomadcustompage plugin.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_iomadcustompage\output;

use html_writer;
use moodle_url;
use plugin_renderer_base;
use local_iomadcustompage\local\models\page;

/**
 * Renderer class for local_iomadcustompage plugin.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Renders the New Page button.
     *
     * @return string The HTML for the new page button.
     */
    public function render_new_page_button(): string {
        return html_writer::tag('button', get_string('newpage', 'local_iomadcustompage'), [
            'class' => 'btn btn-primary my-auto',
            'data-action' => 'page-create',
        ]);
    }

    /**
     * Renders full page editor header.
     *
     * @param page $page The page model to render header for.
     * @return string The rendered HTML.
     */
    public function render_fullpage_editor_header(page $page): string {
        $pagename = $page->get_formatted_name();

        $editdetailsbutton = html_writer::tag('button', get_string('editpagedetails', 'local_iomadcustompage'), [
            'class' => 'btn btn-outline-secondary mr-2',
            'data-action' => 'page-edit',
            'data-page-id' => $page->get('id'),
        ]);

        $closebutton = html_writer::link(
            new moodle_url('/local/iomadcustompage/edit.php', ['id' => $page->get('id')]),
            get_string('closebuttontitle'),
            [
                'class' => 'btn btn-secondary',
                'title' => get_string('closebuttontitle', 'moodle', $pagename),
                'role' => 'button',
            ]
        );

        $context = [
            'title' => $pagename,
            'closebutton' => $closebutton,
            'output' => $this->output,
        ];

        return $this->render_from_template('local_iomadcustompage/editor_navbar', $context);
    }

    /**
     * Renders page details.
     *
     * @param page_details $pagedetails The page details renderable.
     * @return string The rendered HTML.
     */
    public function render_page_details(page_details $pagedetails): string {
        $data = $pagedetails->export_for_template($this);
        return $this->render_from_template('local_iomadcustompage/page_details', $data);
    }

    /**
     * Renders page contents.
     *
     * @param page_contents $pagecontents The page contents renderable.
     * @return string The rendered HTML.
     */
    public function render_page_contents(page_contents $pagecontents): string {
        $data = $pagecontents->export_for_template($this);
        return $this->render_from_template('local_iomadcustompage/page_contents', $data);
    }
}
