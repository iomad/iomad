<?php
// This file is part of The Bootstrap Moodle theme
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
 * Container for a collection of filters.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\filter;

use moodleform;
use MoodleQuickForm;
/**
 * Container for a collection of filters.
 *
 * @package block_dash
 */
interface filter_collection_interface {

    /**
     * Initialize all filters.
     */
    public function init();

    /**
     * Get unique identifier for this filter collection.
     *
     * @return string
     */
    public function get_unique_identifier();

    /**
     * Add filter to collection.
     *
     * @param filter_interface $filter
     */
    public function add_filter(filter_interface $filter);

    /**
     * Remove filter from collection. Careful doing this.
     *
     * @param filter_interface $filter
     * @return bool
     */
    public function remove_filter(filter_interface $filter);

    /**
     * Check if collection has any filters added.
     *
     * @return bool
     */
    public function has_filters();

    /**
     * Get all filters.
     *
     * @return filter_interface[]
     */
    public function get_filters();

    /**
     * Check if a filter exists in this collection.
     *
     * @param string $fieldname
     * @return bool
     */
    public function has_filter($fieldname);

    /**
     * Get a filter by field name.
     *
     * @param string $fieldname
     * @return filter_interface|null
     */
    public function get_filter($fieldname);

    /**
     * Set a filter value.
     *
     * @param string $fieldname
     * @param mixed $value
     * @return bool
     */
    public function apply_filter($fieldname, $value);
    /**
     * Get filters with user submitted values.
     *
     * @return filter_interface[]
     */
    public function get_applied_filters();

    /**
     * Get filters with user submitted values, along with filters that have default
     * values.
     *
     * @return array
     */
    public function get_filters_with_values();

    /**
     * Check if filter collection contains any required filters.
     *
     * @return bool
     */
    public function has_required_filters();

    /**
     * Get all filters that are required for this grid.
     *
     * @return filter[]
     */
    public function get_required_filters();

    /**
     * Get SQL query and parameters.
     *
     * @return array
     */
    public function get_sql_and_params();

    /**
     * Create form for filters.
     *
     * @param string $elementnameprefix
     * @throws \Exception
     */
    public function create_form_elements($elementnameprefix = '');

    /**
     * Cache filter data.
     *
     * @param \stdClass $user User to cache filter preferences for.
     */
    public function cache(\stdClass $user);

    /**
     * Get cached filter data.
     *
     * @param \stdClass $user
     * @return array|false|mixed
     * @throws \coding_exception
     */
    public function get_cache(\stdClass $user);

    /**
     * Delete filter cache.
     *
     * @param \stdClass $user
     */
    public function delete_cache(\stdClass $user);

    /**
     * Take a Moodle form and add any settings for the filters beloning to this collection.
     *
     * @param moodleform $form
     * @param MoodleQuickForm $mform
     * @param string $type
     * @param string $fieldnameformat
     */
    public function build_settings_form(
        moodleform $form,
        MoodleQuickForm $mform,
        string $type = 'filter',
        $fieldnameformat = 'filters[%s]'): void;
}
