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
 * Page details renderable for template output.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcustompage\output;

use local_iomadcustompage\local\models\page;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Page details renderable class.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_details implements renderable, templatable {
    /** @var page The page persistent model. */
    private page $page;

    /**
     * Constructor.
     *
     * @param page $page The page persistent model.
     */
    public function __construct(page $page) {
        $this->page = $page;
    }

    /**
     * Export data for template.
     *
     * @param renderer_base $output The renderer.
     * @return stdClass Data for template.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();
        $data->name = $this->page->get_formatted_name();
        $data->title = $this->page->get_formatted_title();
        return $data;
    }
}
