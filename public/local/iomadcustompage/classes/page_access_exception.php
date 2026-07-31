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

declare(strict_types=1);

namespace local_iomadcustompage;

use moodle_exception;

/**
 * Exception thrown when a user cannot access a custom page
 *
 * @package     local_iomadcustompage
 * @copyright   2021 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_access_exception extends moodle_exception {
    /**
     * Constructor for the page access exception
     *
     * @param string $errorcode The language string identifier for the error message
     */
    public function __construct(string $errorcode = 'errorpageview') {
        parent::__construct($errorcode, 'local_iomadcustompage');
    }
}
