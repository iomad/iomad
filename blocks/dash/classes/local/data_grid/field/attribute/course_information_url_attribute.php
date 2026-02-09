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
 * Transform the data into course information.
 *
 * @package    block_dash
 * @copyright  2024 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\field\attribute;

use block_dash\local\data_grid\field\attribute\abstract_field_attribute;
use moodle_url;

/**
 * Transforms data to formatted course information url.
 *
 * @package block_dash
 */
class course_information_url_attribute extends abstract_field_attribute {

    /**
     * Transforms the given data by generating a URL based on the course context.
     *
     * Retrieves the dashboard record associated with the course context ID.
     * If found, constructs a URL to the dashboard page; otherwise, constructs
     * a URL to the user index page.
     *
     * @param \stdClass $data Course data used to determine the context.
     * @param \stdClass $record The entire database record row.
     * @return moodle_url The constructed URL based on the course context.
     * @throws \moodle_exception If an error occurs during URL construction.
     */
    public function transform_data($data, \stdClass $record) {
        global $DB;

        $context = \context_course::instance($data);
        $url = '';

        if ($dashboard = $DB->get_record('dashaddon_dashboard_dash', ['contextid' => $context->id], '*', IGNORE_MULTIPLE)) {
            $url = new moodle_url('/local/dash/addon/dashboard/dashboard.php', ['id' => $dashboard->id]);
        } else {
            $url = new moodle_url('/enrol/index.php', ['id' => $data]);
        }
        return $url;
    }
}
