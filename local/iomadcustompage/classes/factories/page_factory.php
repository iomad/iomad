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
 *  page_factory.php description here.
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcustompage\factories;
use local_iomadcustompage\local\iomadcustompage\page;
use local_iomadcustompage\local\models\page as page_persistent;

/**
 * page factory.
 *
 * This factory creates the page object, which is responsible for accessing the data
 * from the database and performing the logic of the page.
 */
class page_factory {
    /**
     * create instance of a page
     * @param int $pageid
     * @return page
     */
    public static function create(int $pageid) {
        $pagepersistent = new page_persistent($pageid);
        return new page($pagepersistent);
    }
}
