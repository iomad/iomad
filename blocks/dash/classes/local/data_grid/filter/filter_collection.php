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
class filter_collection implements filter_collection_interface {

    /**
     * @var filter_interface[] Every filter that belongs to this collection.
     */
    private $filters = [];

    /**
     * @var string
     */
    private $uniqueidentifier;

    /**
     * @var \context
     */
    private $context;

    /**
     * Set the current page layout for filters.
     *
     * @var string $layout
     */
    public $layout;

    /**
     * Filter collection constructor.
     *
     * @param string $uniqueidentifier
     * @param \context $context
     */
    public function __construct($uniqueidentifier, \context $context) {
        $this->uniqueidentifier = $uniqueidentifier;
        $this->context = $context;
    }

    /**
     * Initialize all filters.
     */
    public function init() {
        foreach ($this->get_filters() as $filter) {
            $filter->init();
        }
    }

    /**
     * Get filter collection unique identifier.
     *
     * @return string
     */
    public function get_unique_identifier() {
        return $this->uniqueidentifier;
    }

    /**
     * Add filter to colelction.
     *
     * @param filter_interface $filter
     */
    public function add_filter(filter_interface $filter) {
        $filter->set_context($this->context);
        $this->filters[] = $filter;
    }

    /**
     * Remove filter from collection. Careful doing this.
     *
     * @param filter_interface $filter
     * @return bool
     */
    public function remove_filter(filter_interface $filter) {
        foreach ($this->filters as $key => $searchfilter) {
            if ($filter->get_name() == $searchfilter->get_name()) {
                unset($this->filters[$key]);
                return true;
            }
        }

        return false;
    }

    /**
     * Check if collection has any filters added.
     *
     * @return bool
     */
    public function has_filters() {
        return count($this->filters) > 0;
    }

    /**
     * Get all filters.
     *
     * @return filter_interface[]
     */
    public function get_filters() {
        return $this->filters;
    }

    /**
     * Check if a filter exists in this collection.
     *
     * @param string $name
     * @return bool
     */
    public function has_filter($name) {
        return !empty($this->get_filter($name));
    }

    /**
     * Get a filter by field name.
     *
     * @param string $name
     * @return filter_interface|null
     */
    public function get_filter($name) {
        foreach ($this->get_filters() as $filter) {
            if ($filter->get_name() == $name) {
                return $filter;
            }
        }

        return null;
    }

    /**
     * Set a filter value.
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function apply_filter($name, $value) {
        // Don't apply empty values.
        if ($value === "") {
            return false;
        }
        foreach ($this->filters as $filter) {
            if ($filter->get_name() == $name) {
                $filter->set_raw_value($value);
                return true;
            }
        }

        return false;
    }

    /**
     * Get filters with user submitted values.
     *
     * @return filter_interface[]
     */
    public function get_applied_filters() {
        $filters = [];

        foreach ($this->filters as $filter) {
            if ($filter->is_applied()) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * Get filters with user submitted values, along with filters that have default
     * values.
     *
     * @return filter_interface[]
     */
    public function get_filters_with_values() {
        $filters = [];
        foreach ($this->get_filters() as $filter) {
            if ($filter->has_raw_value() || $filter->has_default_raw_value()) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * Check if filter collection contains any required filters.
     *
     * @return bool
     */
    public function has_required_filters() {
        foreach ($this->get_filters() as $filter) {
            if ($filter->is_required()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all filters that are required for this grid.
     *
     * @return filter[]
     */
    public function get_required_filters() {
        $filters = [];

        foreach ($this->get_filters() as $filter) {
            if ($filter->is_required()) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * Get SQL query and parameters.
     *
     * @return array
     * @throws \Exception
     */
    public function get_sql_and_params() {
        $params = [];
        $havingsql = [];
        $wheresql = [];
        foreach ($this->get_filters_with_values() as $filter) {
            list($filtersql, $filterparams) = $filter->get_sql_and_params();
            // Ignore filters with no values.
            if (empty($filterparams)) {
                continue;
            }
            switch ($filter->get_clause_type()) {
                case filter_interface::CLAUSE_TYPE_WHERE:
                    $wheresql[] = $filtersql;
                    break;
                case filter_interface::CLAUSE_TYPE_HAVING:
                    $havingsql[] = $filtersql;
                    break;
            }
            $params = array_merge($params, $filterparams);
        }

        if (empty($havingsql)) {
            $havingsql = ['1=1'];
        }
        if (empty($wheresql)) {
            $wheresql = ['1=1'];
        }

        return [[implode(' AND ', $wheresql), implode(' AND ', $havingsql)], $params];
    }

    /**
     * Create form for filters.
     *
     * @param string $elementnameprefix
     * @param string $layout
     * @throws \Exception
     * @return string|null
     */
    public function create_form_elements($elementnameprefix = '', $layout='') {
        if (!$this->has_filters()) {
            return null;
        }

        $html = '';
        // Set the current block instnace layout if available.
        $this->layout = $layout;

        foreach ($this->get_filters() as $filter) {
            $html .= $filter->create_form_element($this, $elementnameprefix);
        }

        return $html;
    }

    /**
     * Create a cache object store.
     *
     * @return \cache_session
     */
    private function create_cache() {
        return \cache::make_from_params(\cache_store::MODE_SESSION, 'block_dash', 'filter_cache');
    }

    /**
     * Cache filter data.
     *
     * @param \stdClass $user User to cache filter preferences for.
     */
    public function cache(\stdClass $user) {
        $filterdata = [];

        foreach ($this->filters as $filter) {
            $filterdata[$filter->get_name()] = $filter->get_raw_value();
        }

        $identifier = sprintf('%s-%s', $user->id, $this->get_unique_identifier());

        $this->create_cache()->set($identifier, $filterdata);
    }

    /**
     * Get cached filter data.
     *
     * @param \stdClass $user
     * @return array|false|mixed
     * @throws \coding_exception
     */
    public function get_cache(\stdClass $user) {
        $cache = $this->create_cache();

        $identifer = sprintf('%s-%s', $user->id, $this->get_unique_identifier());

        if (!$cache->has($identifer)) {
            return [];
        }
        return $cache->get($identifer);
    }

    /**
     * Delete filter cache.
     *
     * @param \stdClass $user
     */
    public function delete_cache(\stdClass $user) {
        $cache = $this->create_cache();

        $identifier = sprintf('%s-%s', $user->id, $this->get_unique_identifier());

        $cache->delete($identifier);
    }

    /**
     * Take a Moodle form and add any settings for the filters beloning to this collection.
     *
     * @param moodleform $form
     * @param MoodleQuickForm $mform
     * @param string $type Which type of filters to include.
     * @param string $fieldnameformat
     */
    public function build_settings_form(
        moodleform $form,
        MoodleQuickForm $mform,
        string $type = 'filter',
        $fieldnameformat = 'filters[%s]'): void {

        foreach ($this->get_filters() as $filter) {
            if ($type == 'filter') {
                if ($filter instanceof condition) {
                    continue;
                }
            } else if ($type == 'condition') {
                if (!$filter instanceof condition) {
                    continue;
                }
            }

            $filter->build_settings_form_fields($form, $mform, $fieldnameformat);
        }
    }
}
