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
 * Factory class for creating page wrapper instances.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcustompage\factories;

use local_iomadcustompage\local\models\page as page_persistent;
use local_iomadcustompage\local\iomadcustompage\page;

/**
 * Factory for creating page wrapper objects.
 *
 * Provides a convenient way to instantiate page wrapper objects
 * from page IDs.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_factory {
    /**
     * Create a page wrapper instance from a page ID.
     *
     * @param int $pageid The ID of the page to load.
     * @return page The page wrapper instance.
     */
    public static function create(int $pageid): page {
        $pagepersistent = new page_persistent($pageid);
        return new page($pagepersistent);
    }
}
