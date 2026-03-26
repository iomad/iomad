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
 * External functions and service declaration for IOMAD eCommerce
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/external/description}
 *
 * @package    block_iomad_commerce
 * @category   webservice
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'block_iomad_commerce_delete_product' => [
        'classname' => block_iomad_commerce\external\delete_product::class,
        'description' => 'Delete product from the shop',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/iomad_commerce:delete_course',
    ],

    'block_iomad_commerce_import_product' => [
        'classname' => block_iomad_commerce\external\import_product::class,
        'description' => 'Import product to company from catalogue',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/iomad_commerce:manage_default',
    ],

    'block_iomad_commerce_export_product' => [
        'classname' => block_iomad_commerce\external\export_product::class,
        'description' => 'Export product from tenant to catalogue',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/iomad_commerce:manage_default',
    ],

    'block_iomad_commerce_showhide_product' => [
        'classname' => block_iomad_commerce\external\showhide_product::class,
        'description' => 'Show or hide a product in the shop',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/iomad_commerce:hide_course',
    ],

    'block_iomad_commerce_delete_shoptag' => [
        'classname' => block_iomad_commerce\external\delete_shoptag::class,
        'description' => 'Delete shoptag',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/iomad_commerce:manage_tags',
    ],
];

$services = [
];
