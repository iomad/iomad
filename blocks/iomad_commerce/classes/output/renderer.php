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
 * IOMAD commerce block renderer
 * @package   block_iomad_commerce
 * @copyright 2026 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_commerce\output;

use plugin_renderer_base;
use block_iomad_commerce\output\{basket, product, shop};

/**
 * IOMAD commerce block renderer
 *
 * @package   block_iomad_commerce
 * @copyright 2026 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Return the basket content for the block basket page.
     *
     * @param basket $basket The basket renderable
     * @return string HTML string
     */
    public function render_basket(basket $basket) {
        return $this->render_from_template('block_iomad_commerce/basket', $basket->export_for_template($this));
    }

    /**
     * Return the product content for the block item page.
     *
     * @param product $product The shop renderable
     * @return string HTML string
     */
    public function render_product(product $product) {
        return $this->render_from_template('block_iomad_commerce/product', $product->export_for_template($this));
    }

    /**
     * Return the shop content for the block shop page.
     *
     * @param shop $shop The shop renderable
     * @return string HTML string
     */
    public function render_shop(shop $shop) {
        return $this->render_from_template('block_iomad_commerce/shop', $shop->export_for_template($this));
    }
}
