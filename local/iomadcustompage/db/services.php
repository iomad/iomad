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
 *  Services definitions for local_iomadcustompage.
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
  'local_iomadcustompage_audiences_delete' => [
    'classname'   => 'local_iomadcustompage\external\audiences\delete',
    'description' => 'Delete audience of iomadcustompage',
    'type'        => 'write',
    'ajax'        => true,
  ],
  'local_iomadcustompage_page_delete' => [
    'classname'   => 'local_iomadcustompage\external\page\delete',
    'description' => 'Delete a custom page',
    'type'        => 'write',
    'ajax'        => true,
  ],
];
