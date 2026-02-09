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
 * Thrown when a where condition does not have any values.
 *
 * @package    block_dash
 * @copyright  2020 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\dash_framework\query_builder\exception;

use moodle_exception;

/**
 * Thrown when a where condition does not have any values.
 *
 * @package block_dash
 */
class invalid_operator_exception extends moodle_exception {

    /**
     * Constructor
     * @param string $link The url where the user will be prompted to continue.
     * If no url is provided the user will be directed to the site index page.
     * @param mixed $a Extra words and phrases that might be required in the error string
     * @param string|null $debuginfo optional debugging information
     */
    public function __construct($link = '', $a = null, $debuginfo = null) {
        parent::__construct('invalidoperator', 'block_dash', $link, $a, $debuginfo);
    }
}
