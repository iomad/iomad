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
 * Class user_field_filter.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\filter;
/**
 * Class user_field_filter.
 *
 * @package block_dash
 */
class user_field_filter extends select_filter {

    /**
     * User field.
     *
     * @var string
     */
    private $userfield;

    /**
     * User filter constructor.
     *
     * @param string $name
     * @param string $select
     * @param string $userfield
     * @param string $label
     */
    public function __construct($name, $select, $userfield, $label = '') {
        $this->userfield = $userfield;

        parent::__construct($name, $select, $label);
    }

    /**
     * Initialize the filter. It must be initialized before values are extracted or SQL generated.
     * If overridden call parent.
     */
    public function init() {
        global $DB;

        $userfield = $this->userfield;

        $data = $DB->get_records_sql_menu("SELECT DISTINCT $userfield, $userfield AS value
                                           FROM {user} where $userfield <> ''");

        $this->add_options($data);

        parent::init();
    }
}
