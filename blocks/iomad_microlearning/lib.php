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
 * Callback implementations for IOMAD microlearning threads
 *
 * @package    block_iomad_microlearning
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Callback for inplace editable API.
 *
 * @param string $itemtype - Only user_roles is supported.
 * @param string $itemid - Courseid and userid separated by a :
 * @param string $newvalue - json encoded list of roleids.
 * @return \core\output\inplace_editable
 */
function block_iomad_microlearning_inplace_editable($itemtype, $itemid, $newvalue) {
    // Check if the item type has a corresponding editable and if so then return the
    // update method for that editable and pass the $itemid and $newvalue variables.
    if ($itemtype === 'nugget_name') {
        return block_iomad_microlearning\output\nugget_name_editable::update($itemid, $newvalue);
    }

    if ($itemtype === 'group_name') {
        return block_iomad_microlearning\output\group_name_editable::update($itemid, $newvalue);
    }

    if ($itemtype === 'thread_name') {
        return block_iomad_microlearning\output\thread_name_editable::update($itemid, $newvalue);
    }
}
