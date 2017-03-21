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
 * @package    Block IOMAD Company Admin
 * @copyright  2017 onwards E-Learn Design Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Define the web service funtions
$functions = array(
    'block_iomad_company_admin_create_company' => array(
        'classname' => 'block_iomad_company_admin_external',
        'methodname' => 'create_company',
        'classpath' => 'blocks/iomad_company_admin/externallib.php',
        'description' => 'Create new Iomad company',
        'type' => 'write',
    ),
);
