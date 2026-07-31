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

use local_iomadcustompage\local\models\page;

/**
 * Uninstall steps for local_iomadcustompage.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute iomadcustompage uninstall.
 *
 * @return bool
 */
function xmldb_local_iomadcustompage_uninstall(): bool {

    $iomadcustompages = page::get_records();

    foreach ($iomadcustompages as $iomadcustompage) {
        $iomadcustompage->delete();
    }

    return true;
}
