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
 * A layout contains information on how to display data.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\layout;

use block_dash\local\data_grid\data\data_collection_interface;
use block_dash\local\data_grid\data\strategy\data_strategy_interface;
use block_dash\local\data_source\data_source_interface;
/**
 * A layout contains information on how to display data.
 *
 * @package block_dash
 */
interface layout_interface {

    /**
     * Get data source for this layout.
     *
     * @return data_source_interface
     */
    public function get_data_source();

    /**
     * Get mustache template name.
     *
     * @return string
     */
    public function get_mustache_template_name();

    /**
     * If the data source fields can be hidden or shown conditionally.
     *
     * @return bool
     */
    public function supports_field_visibility();

    /**
     * If the data source should display filters (does not affect conditions).
     *
     * @return bool
     */
    public function supports_filtering();

    /**
     * If the data source should display pagination links.
     *
     * @return bool
     */
    public function supports_pagination();

    /**
     * If the layout supports field sorting.
     *
     * @return mixed
     */
    public function supports_sorting();

    /**
     * Get data strategy.
     *
     * @return data_strategy_interface
     */
    public function get_data_strategy();

    /**
     * Modify objects before data is retrieved in the data source. This allows the layout to make decisions on the
     * data source and data grid.
     */
    public function before_data();

    /**
     * Modify objects after data is retrieved in the data source. This allows the layout to make decisions on the
     * data source and data grid.
     *
     * @param data_collection_interface $datacollection
     */
    public function after_data(data_collection_interface $datacollection);

    /**
     * Add form elements to the preferences form when a user is configuring a block.
     *
     * This extends the form built by the data source. When a user chooses a layout, specific form elements may be
     * displayed after a quick refresh of the form.
     *
     * Be sure to call parent::build_preferences_form() if you override this method.
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     * @throws \coding_exception
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform);

    /**
     * Allows layout to modified preferences values before exporting to mustache template.
     *
     * @param array $preferences
     * @return array
     */
    public function process_preferences(array $preferences);

    /**
     * Get data for layout mustache template.
     *
     * @param \renderer_base $output
     * @return array|\stdClass
     * @throws \coding_exception
     */
    public function export_for_template(\renderer_base $output);
}
