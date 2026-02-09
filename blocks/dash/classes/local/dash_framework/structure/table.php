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
 * Class abstract_data_source.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\dash_framework\structure;
/**
 * Class abstract_data_source.
 *
 * @package block_dash
 */
abstract class table implements table_interface {

    /**
     * @var string
     */
    private $tablename;

    /**
     * @var string
     */
    private $alias;

    /**
     * Build a new table.
     * @param string $tablename
     * @param string $alias
     */
    public function __construct(string $tablename, string $alias) {
        $this->tablename = $tablename;
        $this->alias = $alias;
    }

    /**
     * Get name of table without prefix.
     *
     * @return string
     */
    public function get_table_name(): string {
        return $this->tablename;
    }

    /**
     * Get unique table alias.
     *
     * @return string
     */
    public function get_alias(): string {
        return $this->alias;
    }
}
