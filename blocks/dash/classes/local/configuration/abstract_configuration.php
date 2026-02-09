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
 * Common methods for configuration objects.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\configuration;

use block_dash\local\data_source\data_source_interface;

/**
 * Common methods for configuration objects.
 *
 * @package block_dash
 */
abstract class abstract_configuration implements configuration_interface {

    /**
     * @var \context
     */
    private $context;

    /**
     * @var data_source_interface
     */
    private $datasource;

    /**
     * abstract_configuration constructor.
     *
     * @param \context $context
     * @param data_source_interface|null $datasource
     */
    protected function __construct(\context $context, data_source_interface $datasource = null) {
        $this->context = $context;
        $this->datasource = $datasource;
    }

    /**
     * Get context.
     *
     * @return \context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get data source.
     *
     * @return data_source_interface
     */
    public function get_data_source() {
        return $this->datasource;
    }

    /**
     * Check if block is ready to display content.
     *
     * @return bool
     */
    public function is_fully_configured() {
        return !empty($this->datasource);
    }
}
