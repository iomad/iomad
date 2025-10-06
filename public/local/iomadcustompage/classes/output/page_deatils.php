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
 *  page_deatils.php description here.
 *
 * @package
 * @copyright  local_iomadcustompage
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcustompage\output;

use coding_exception;
use local_iomadcustompage\local\models\page;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * page_deatils class
 */
class page_deatils implements renderable, templatable {
    /**
     * @var page
     */
    private $page_persistent;

    /**
     * constructor
     * @param page $pagepersistent
     */
    public function __construct(page $pagepersistent) {
        $this->page_persistent = $pagepersistent;
    }
    /**
     * export for template
     *
     * @param renderer_base $output
     * @return stdClass
     * @throws coding_exception
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->name = $this->page_persistent->get_formatted_name();
        $data->title = $this->page_persistent->get_formatted_title();
        return $data;
    }
}
