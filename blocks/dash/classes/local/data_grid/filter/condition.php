<?php
// This file is part of The Bootstrap Moodle theme
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
 * A condition is a filter that is always applied.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\filter;
/**
 * A condition is a filter that is always applied.
 *
 * @package block_dash
 */
class condition extends filter {

    /**
     * Check if a user value was set.
     *
     * @return bool
     */
    public function has_raw_value() {
        return true;
    }

    /**
     * Check if this filter was applied by the user.
     *
     * @return bool
     */
    public function is_applied() {
        return true;
    }

    /**
     * Override this method and call it after creating a form element.
     *
     * @param filter_collection_interface $filtercollection
     * @param string $elementnameprefix
     * @throws \Exception
     */
    public function create_form_element(filter_collection_interface $filtercollection, $elementnameprefix = '') {
        // Intentionally left blank.
    }

}
