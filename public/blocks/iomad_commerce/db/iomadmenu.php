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
 * Block IOMAD eCommerce
 *
 * @package   block_iomad_commerce
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the IOMAD menu items provided by this plugin.
 *
 * @return array
 */
function block_iomad_commerce_menu() {

    return [
        'ShopSettings_list' => [
            'category' => 'ECommerceAdmin',
            'tab' => 6,
            'name' => get_string('courses', 'block_iomad_commerce'),
            'url' => '/blocks/iomad_commerce/courselist.php',
            'cap' => 'block/iomad_commerce:admin_view',
            'icondefault' => 'courses',
            'style' => 'ecomm',
            'icon' => 'fa-file-text',
            'iconsmall' => 'fa-money',
        ],
        'Orders' => [
            'category' => 'ECommerceAdmin',
            'tab' => 6,
            'name' => get_string('orders', 'block_iomad_commerce'),
            'url' => '/blocks/iomad_commerce/orderlist.php',
            'cap' => 'block/iomad_commerce:admin_view',
            'icondefault' => 'orders',
            'style' => 'ecomm',
            'icon' => 'fa-truck',
            'iconsmall' => 'fa-eye',
        ],
    ];
}
