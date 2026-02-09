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
 * Configuration helps with building block instance content.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\configuration;

use block_dash\local\data_source\data_source_interface;
/**
 * Configuration helps with building block instance content.
 *
 * @package block_dash
 */
interface configuration_interface {

    /**
     * Get context.
     *
     * @return \context
     */
    public function get_context();

    /**
     * Get data source.
     *
     * @return data_source_interface
     */
    public function get_data_source();

    /**
     * Check if block is ready to display content.
     *
     * @return bool
     */
    public function is_fully_configured();

    /**
     * Create new configuration instance
     *
     * @param \block_base $blockinstance
     * @return configuration_interface
     */
    public static function create_from_instance(\block_base $blockinstance);
}
