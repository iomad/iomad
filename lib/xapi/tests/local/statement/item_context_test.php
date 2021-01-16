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
 * This file contains unit test related to xAPI library.
 *
 * @package    core_xapi
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_xapi\local\statement;

use advanced_testcase;
use core_xapi\xapi_exception;

/**
 * Contains test cases for testing statement context class.
 *
 * @package    core_xapi
 * @since      Moodle 3.9
 * @copyright  2020 Ferran Recio
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_context_testcase extends advanced_testcase {

    /**
     * Test item creation.
     */
    public function test_create() {

        $data = $this->get_generic_data();
        $item = item_context::create_from_data($data);

        $this->assertEquals(json_encode($item), json_encode($data));
    }

    /**
     * Return a generic data to create a valid item.
     *
     * @return sdtClass the creation data
     */
    private function get_generic_data(): \stdClass {
        // For now context has no data validation so a generic data is enough.
        return (object) [
            'usageType' => '51a6f860-1997-11e3-8ffd-0800200c9a66',
        ];
    }
}
