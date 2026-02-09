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
 * Display query info to admin for better debugging and troubleshooting.
 *
 * @package    block_dash
 * @copyright  2020 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\output;

use renderer_base;
use stdClass;
/**
 * Display query info to admin for better debugging and troubleshooting.
 *
 * @package block_dash
 */
final class query_debug implements \renderable, \templatable {

    /**
     * @var string SQL compiled raw query.
     */
    private $query;

    /**
     * @var array parameters used in query.
     */
    private $params;

    /**
     * Constructor.
     *
     * @param string $query Full SQL query to display to user for debug purposes.
     * @param array $params Any parameters that will be used in query.
     */
    public function __construct(string $query, array $params) {
        $this->query = $query;
        $this->params = $params;
    }

    /**
     * Check if user can view query debug.
     *
     * @param int $userid
     * @return bool
     */
    public function can_view(int $userid): bool {
        return is_siteadmin($userid);
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'query' => $this->query,
            'params' => json_encode($this->params, true),
            'uniqueid' => uniqid(),
        ];
    }
}
