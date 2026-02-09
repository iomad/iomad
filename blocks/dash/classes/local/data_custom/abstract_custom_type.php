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
 * Widgets extend class for new widgets.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_custom;

use block_dash\local\data_source\abstract_data_source;
use block_dash\local\data_source\data_source_interface;
use block_dash\local\data_source\form\preferences_form;
use block_dash\local\dash_framework\query_builder\builder;
use block_dash\local\data_grid\filter\filter_collection;
use block_dash\local\layout\layout_interface;
use renderer_base;
use block_dash\local\paginator;
use block_dash\local\widget\abstract_widget;

/**
 * Widgets extend class for new widgets.
 */
abstract class abstract_custom_type extends abstract_widget implements \templatable {

    /**
     * List of data to generate widget template content.
     *
     * @var array
     */
    public $data = [];

    /**
     * Get the features config moodleform elements to display on the configuration.
     *
     * @param moodle_form $mform
     * @param datasource $source
     * @return void
     */
    abstract public static function get_features_config(&$mform, $source);

    /**
     * Template name to render the datasourse.
     *
     * @return string
     */
    abstract public function get_mustache_template_name(): string;

    /**
     * Verify the user has capability ti config the widget.
     *
     * @param context $context
     * @return bool
     */
    abstract public static function has_capbility($context): bool;

    /**
     * Layout class that the widget will used.
     *
     * @return void
     */
    public function layout() {
        return new custom_layout($this);
    }

}
