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
 * A widget contains information on how to display data.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\widget;

/**
 * A widget contains information on how to display data.
 *
 * @package block_dash
 */
interface widget_interface {

    /**
     * Confirm the loaded data source is widget.
     *
     * @return boolean
     */
    public function is_widget();

    /**
     * Define the widget will supports the default query builder method.
     *
     * @return bool
     */
    public function supports_query();

    /**
     * Is the widget needs to load the js when it the content updated using JS.
     *
     * @return bool
     */
    public function supports_currentscript();

    /**
     * Get tables if the widget uses query method
     *
     * @return void
     */
    public function get_tables();

    /**
     * Layout class that the widget will used.
     *
     * @return void
     */
    public function layout();

    /**
     * List of widget preferences loadded by default.
     *
     * @return \stdclass
     */
    public function widget_preferences();

    /**
     * Build the widget data to render the widget.
     *
     * @return array
     */
    public function build_widget();

    /**
     * Fetch the data used to generate the widget.
     *
     * @return array
     */
    public function get_widget_data();
}

