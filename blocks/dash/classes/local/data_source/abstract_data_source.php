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
 * Class abstract_data_source.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_source;

use block_dash\local\dash_framework\query_builder\builder;
use block_dash\local\dash_framework\structure\field_interface;
use block_dash\local\dash_framework\structure\table;
use block_dash\local\data_grid\data\data_collection;
use block_dash\local\data_grid\field\attribute\identifier_attribute;
use block_dash\local\data_grid\data\data_collection_interface;
use block_dash\local\data_grid\filter\filter_collection_interface;
use block_dash\local\paginator;
use block_dash\local\data_source\form\preferences_form;
use block_dash\local\layout\grid_layout;
use block_dash\local\layout\layout_factory;
use block_dash\local\layout\layout_interface;
use coding_exception;

/**
 * Class abstract_data_source.
 *
 * @package block_dash
 */
abstract class abstract_data_source implements data_source_interface, \templatable {

    /**
     * @var \context
     */
    private $context;

    /**
     * @var data_collection_interface
     */
    private $data;

    /**
     * @var filter_collection_interface
     */
    private $filtercollection;

    /**
     * @var array
     */
    private $preferences = [];

    /**
     * @var layout_interface
     */
    private $layout;

    /**
     * @var field_interface[]
     */
    private $fields;

    /**
     * @var field_interface[]
     */
    private $sortedfields;

    /**
     * @var \block_base|null
     */
    private $blockinstance = null;

    /**
     * @var builder
     */
    private $query;

    /**
     * @var paginator
     */
    protected $paginator;

    /**
     * @var table[]
     */
    private $tables = [];

    /**
     * Constructor.
     *
     * @param \context $context
     */
    public function __construct(\context $context) {
        $this->context = $context;
    }

    /**
     * Get human readable name of data source.
     *
     * @return string
     */
    public function get_name() {
        return self::get_name_from_class(get_class($this));
    }

    /**
     * Get human readable name of data source.
     *
     * @param string $fullclassname
     * @param bool $help Returns the help.
     * @return string
     * @throws coding_exception
     */
    public static function get_name_from_class($fullclassname, $help=false) {
        $component = explode('\\', $fullclassname)[0];
        $class = array_reverse(explode('\\', $fullclassname))[0];

        $stringidentifier = "datasource:$class";
        $stringcomponent = $component;

        $stringmanager = get_string_manager();
        if ($stringmanager->string_exists($stringidentifier, $stringcomponent)) {
            $name = get_string($stringidentifier, $stringcomponent);
            $helpid = ['name' => $stringidentifier, 'component' => $stringcomponent];
        } else if ($stringmanager->string_exists($stringidentifier, 'block_dash')) {
            $name = get_string($stringidentifier, 'block_dash');
            $helpid = ['name' => $stringidentifier, 'component' => 'block_dash'];
        } else {
            $name = '[[' . $stringidentifier . ']]';
            $helpid = [];
        }

        if ($help && !empty($helpid)) {
            return ($stringmanager->string_exists($helpid['name'].'_help', $helpid['component'])) ? $helpid : [];
        }

        return ($help) ? $helpid : $name;
    }

    /**
     * Add table to this data source. If the table is used in a join in the main query.
     *
     * @param table $table
     */
    public function add_table(table $table): void {
        $this->tables[$table->get_alias()] = $table;
    }

    /**
     * Get tables that are in this data source's main query.
     *
     * @return array
     */
    public function get_tables(): array {
        return $this->tables;
    }

    /**
     * Get table pagination class.
     * @return paginator
     */
    public function get_paginator(): paginator {
        if ($this->get_layout()->supports_pagination()) {
            $perpage = (int) $this->get_preferences('perpage');
        }
        $perpage = isset($perpage) && !empty($perpage) ? $perpage : \block_dash\local\paginator::PER_PAGE_DEFAULT;

        if ($this->paginator == null) {
            $this->paginator = new paginator(function () {
                $count = $this->get_query()->count($this->count_by_uniqueid());
                if ($maxlimit = $this->get_max_limit()) {
                    return $maxlimit < $count ? $maxlimit : $count;
                }
                return $count;
            }, 0, $perpage);
        }

        return $this->paginator;
    }

    /**
     * Get fully built query for execution.
     *
     * @return builder
     */
    final public function get_query(): builder {
        if (is_null($this->query)) {
            $this->query = $this->get_query_template();

            if (count($this->get_available_fields()) == 0) {
                throw new \moodle_exception('Cannot build empty query in data source.');
            }

            if ($this->get_filter_collection() && $this->get_filter_collection()->has_filters()) {
                list ($filtersql, $filterparams) = $this->get_filter_collection()->get_sql_and_params();
                $this->query->where_raw($filtersql[0], $filterparams);
            }

            $fields = $this->get_available_fields();

            foreach ($fields as $field) {
                if (is_null($field->get_select())) {
                    continue;
                }

                $this->query->select($field->get_select(), $field->get_alias());
            }

            foreach ($this->get_available_fields() as $field) {
                if ($field->get_sort()) {
                    $this->query->orderby($field->get_select(), strtoupper($field->get_sort_direction()));
                }
            }

            // If there are multiple identifiers in the data source, construct a unique column.
            // This is to prevent warnings when multiple rows have the same value in the first column.
            $identifierselects = [];
            foreach ($this->get_available_fields() as $field) {
                if ($field->has_attribute(identifier_attribute::class)) {
                    $identifierselects[] = $field->get_select();
                }
            }
            global $DB;
            $concat = $DB->sql_concat_join("'-'", $identifierselects);
            if (count($identifierselects) > 1) {
                $this->query->select($concat, 'unique_id');
            }

            if ($this->get_layout()->supports_pagination()) {
                $perpage = $this->get_per_page();

                // Shorten per page if pagination will exceed max limit.
                if ($maxlimit = $this->get_max_limit()) {
                    if ($this->get_paginator()->get_limit_from() + $perpage > $maxlimit) {
                        $offset = $this->get_paginator()->get_limit_from() + $perpage - $maxlimit;
                        $perpage = $perpage - $offset;
                    }
                }

                $this->query
                    ->limitfrom($this->get_paginator()->get_limit_from())
                    ->limitnum($perpage);
            } else {
                $this->query->limitfrom(0);
                if ($maxlimit = $this->get_max_limit()) {
                    $this->query->limitnum($maxlimit);
                }
            }

            if ($sorting = $this->get_sorting()) {
                foreach ($sorting as $field => $direction) {
                    // Configured field is removed then remove the order.
                    if (is_null($this->get_field($field))) {
                        continue;
                    }
                    $this->query->orderby($this->get_field($field)->get_sort_select(), $direction);
                }
            }
        }

        return $this->query;
    }

    /**
     * Get filter collection for data grid. Build if necessary.
     *
     * @return filter_collection_interface
     */
    final public function get_filter_collection() {
        if (is_null($this->filtercollection)) {
            $this->filtercollection = $this->build_filter_collection();
            $this->filtercollection->init();

            if ($this->get_preferences('filters')) {
                foreach ($this->get_preferences('filters') as $filtername => $filterpreferences) {
                    if (is_array($filterpreferences) || is_object($filterpreferences)) {
                        if ($this->filtercollection->has_filter($filtername)) {
                            $this->filtercollection->get_filter($filtername)->set_preferences($filterpreferences);
                        }
                    }
                }
            }
        }

        return $this->filtercollection;
    }

    /**
     * Modify objects before data is retrieved.
     */
    public function before_data() {
        if ($this->get_layout()->supports_field_visibility()) {
            foreach ($this->get_available_fields() as $availablefield) {
                $availablefield->set_visibility(field_interface::VISIBILITY_HIDDEN);
            }
            if ($this->preferences && isset($this->preferences['available_fields'])) {
                foreach ($this->preferences['available_fields'] as $fieldname => $preferences) {
                    if (isset($preferences['visible'])) {
                        if ($field = $this->get_field($fieldname)) {
                            $field->set_visibility($preferences['visible']);
                        }
                    }
                }
            }
        }

        if ($this->preferences && isset($this->preferences['filters'])) {
            $enabledfilters = [];
            foreach ($this->preferences['filters'] as $filtername => $preference) {
                if (isset($preference['enabled']) && $preference['enabled']) {
                    $enabledfilters[] = $filtername;
                }
            }
            // No preferences set yet, remove all filters.
            foreach ($this->get_filter_collection()->get_filters() as $filter) {
                if (!in_array($filter->get_name(), $enabledfilters)) {
                    $this->get_filter_collection()->remove_filter($filter);
                }
            }
        } else {
            // No preferences set yet, remove all filters.
            foreach ($this->get_filter_collection()->get_filters() as $filter) {
                $this->get_filter_collection()->remove_filter($filter);
            }
        }

        $this->get_layout()->before_data();
    }

    /**
     * Get data collection.
     *
     * @return data_collection_interface
     * @throws \moodle_exception
     */
    final public function get_data() {
        if (is_null($this->data)) {
            // If the block has no preferences do not query any data.
            if (empty($this->get_all_preferences())) {
                return block_dash_get_data_collection();
            }

            $this->before_data();

            if (!$strategy = $this->get_layout()->get_data_strategy()) {
                throw new coding_exception('Not fully configured.');
            }

            if ($this->is_widget()) {
                $this->data = $this->get_widget_data();
            } else {
                $records = $this->get_query()->query();
                $this->data = $strategy->convert_records_to_data_collection($records, $this->get_sorted_fields());
                if ($modifieddata = $this->after_data($this->data)) {
                    $this->data = $modifieddata;
                }
            }
        }
        return $this->data;
    }

    /**
     * Modify objects after data is retrieved.
     *
     * @param data_collection_interface $datacollection
     */
    public function after_data(data_collection_interface $datacollection) {
        return $this->get_layout()->after_data($datacollection);
    }

    /**
     * Explicitly set layout.
     *
     * @param layout_interface $layout
     */
    public function set_layout(layout_interface $layout) {
        $this->layout = $layout;
    }

    /**
     * Get layout.
     *
     * @return layout_interface
     */
    public function get_layout() {
        if (is_null($this->layout)) {
            if ($layout = $this->get_preferences('layout')) {
                $this->layout = layout_factory::build_layout($layout, $this);
            }

            // If still null default to grid layout.
            if (is_null($this->layout)) {
                $this->layout = new grid_layout($this);
            }
        }

        return $this->layout;
    }

    /**
     * Get context.
     *
     * @return \context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get template variables.
     *
     * @param \renderer_base $output
     * @return array|\renderer_base|\stdClass|string
     * @throws coding_exception
     */
    final public function export_for_template(\renderer_base $output) {
        $data = $this->get_layout()->export_for_template($output);
        $data['datasource'] = $this;
        return $data;
    }

    /**
     * Add form fields to the block edit form. IMPORTANT: Prefix field names with config_ otherwise the values will
     * not be saved.
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     * @throws coding_exception
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform) {
        if ($form->get_tab() == preferences_form::TAB_GENERAL) {
            $mform->addElement('static', 'data_source_name', get_string('datasource', 'block_dash'), $this->get_name());

            $mform->addElement('select', 'config_preferences[layout]', get_string('layout', 'block_dash'),
                layout_factory::get_layout_form_options());
            $mform->setType('config_preferences[layout]', PARAM_TEXT);
        }

        if ($layout = $this->get_layout()) {
            $layout->build_preferences_form($form, $mform);
        }

        if ($form->get_tab() == preferences_form::TAB_FIELDS) {
            $mform->addElement('html', '<hr>');

            $sortablefields = [];
            foreach ($this->get_available_fields() as $field) {
                if ($field->get_option('supports_sorting') !== false) {
                    $sortablefields[$field->get_alias()] = $field->get_table()->get_title() . ': ' . $field->get_title();
                }
            }

            $mform->addElement('select', 'config_preferences[default_sort]', get_string('defaultsortfield', 'block_dash'),
                $sortablefields);
            $mform->setType('config_preferences[default_sort]', PARAM_TEXT);
            $mform->addHelpButton('config_preferences[default_sort]', 'defaultsortfield', 'block_dash');

            $mform->addElement('select', 'config_preferences[default_sort_direction]',
                get_string('defaultsortdirection', 'block_dash'), [ 'asc' => 'ASC', 'desc' => 'DESC']
            );
            $mform->setType('config_preferences[default_sort_direction]', PARAM_TEXT);

            $mform->addElement('text', 'config_preferences[maxlimit]', get_string('maxlimit', 'block_dash'));
            $mform->setType('config_preferences[maxlimit]', PARAM_INT);
            $mform->addHelpButton('config_preferences[maxlimit]', 'maxlimit', 'block_dash');

            $mform->addElement('text', 'config_preferences[perpage]', get_string('perpage', 'block_dash'));
            $mform->setType('config_preferences[perpage]', PARAM_INT);
            $mform->addHelpButton('config_preferences[perpage]', 'perpage', 'block_dash');
        }
    }

    // Region Preferences.

    /**
     * Get a specific preference.
     *
     * @param string $name
     * @return mixed|array
     */
    final public function get_preferences($name) {
        if ($this->preferences && isset($this->preferences[$name])) {
            return $this->preferences[$name];
        }
        return null;
    }

    /**
     * Get all preferences associated with the data source.
     *
     * @return array
     */
    final public function get_all_preferences() {
        return $this->preferences;
    }

    /**
     * Set preferences on this data source.
     *
     * @param array $preferences
     */
    final public function set_preferences(array $preferences) {
        $this->preferences = $preferences;
    }

    // Endregion.

    /**
     * Get count query template.
     *
     * @return string
     */
    public function get_count_query_template() {
        return $this->get_query_template();
    }

    /**
     * Get group by fields.
     *
     * @return string
     */
    public function get_groupby() {
        return false;
    }

    /**
     * Get available field definitions.
     *
     * @return field_interface[]
     */
    final public function get_available_fields() {
        if (is_null($this->fields)) {
            // Get all available field definitions based on tables.
            $this->fields = [];
            foreach ($this->get_tables() as $table) {
                foreach ($table->get_fields() as $field) {
                    $this->fields[$field->get_alias()] = $field;
                }
            }
        }

        return $this->fields;
    }

    /**
     * Check if report has a certain field
     *
     * @param string $alias Field alias.
     * @return bool
     */
    public function has_field(string $alias): bool {
        return isset($this->get_available_fields()[$alias]);
    }

    /**
     * Get field by name. Returns null if not found.
     *
     * @param string $alias Field alias.
     * @return ?field_interface
     */
    public function get_field(string $alias): ?field_interface {
        // Fields are keyed by name.
        if ($this->has_field($alias)) {
            return $this->get_available_fields()[$alias];
        }

        return null;
    }

    /**
     * Get sorted field definitions based on preferences.
     *
     * @return field_interface[]
     */
    public function get_sorted_fields() {
        if (is_null($this->sortedfields)) {
            $fields = $this->get_available_fields();;

            if ($this->get_layout()->supports_field_visibility()) {

                $sortedfields = [];

                // First add the identifiers, in order, so they always come first in the query.
                foreach ($fields as $key => $field) {
                    if ($field->has_attribute(identifier_attribute::class)) {
                        $sortedfields[] = $field;
                        unset($fields[$key]);
                    }
                }

                if ($availablefields = $this->get_preferences('available_fields')) {
                    foreach ($availablefields as $fieldalias => $availablefield) {
                        foreach ($fields as $key => $field) {
                            if ($field->get_alias() == $fieldalias) {
                                $sortedfields[] = $field;
                                unset($fields[$key]);
                                break;
                            }
                        }
                    }

                    foreach ($fields as $field) {
                        $sortedfields[] = $field;
                    }

                    $fields = $sortedfields;
                }

            }

            $this->sortedfields = array_values($fields);
        }

        return $this->sortedfields;
    }

    /**
     * Get sorting.
     *
     * @throws coding_exception
     */
    public function get_sorting() {
        global $USER;

        if ($this->get_layout()->supports_sorting() && $this->get_block_instance()) {
            $cache = \cache::make_from_params(\cache_store::MODE_SESSION, 'block_dash', 'sort');

            if ($cache->has($USER->id . '_' . $this->get_block_instance()->instance->id)) {
                return $cache->get($USER->id . '_' . $this->get_block_instance()->instance->id);
            }
        }

        if ($defaultsort = $this->get_preferences('default_sort')) {
            return [$defaultsort => $this->get_preferences('default_sort_direction') ?? 'asc'];
        }

        return [];
    }

    /**
     * Get maximum number of records to query.
     *
     * @return ?int
     */
    public function get_max_limit() {
        return $this->get_preferences('maxlimit');
    }

    /**
     * Get per page number for pagination.
     *
     * @return ?int
     */
    public function get_per_page() {
        if ($perpage = $this->get_preferences('perpage')) {
            return $perpage;
        }
        return $this->get_paginator()->get_per_page();
    }

    /**
     * Set block instance.
     *
     * @param \block_base $blockinstance
     */
    public function set_block_instance(\block_base $blockinstance) {
        $this->blockinstance = $blockinstance;
    }

    /**
     * Get block instance.
     *
     * @return null|\block_base
     */
    public function get_block_instance() {
        return $this->blockinstance;
    }

    /**
     * Update the block fetched data before render.
     *
     * @param array $data
     * @return void
     */
    public function update_data_before_render(&$data) {
        return null;
    }

    /**
     * Set the data source supports debug.
     *
     * @return bool;
     */
    public function supports_debug() {
        return true;
    }

    /**
     * Type of the data source.
     *
     * @return boolean
     */
    public function is_widget() {
        return false;
    }

    /**
     * Count a data record by uniqueid.
     *
     * @return boolean
     */
    public function count_by_uniqueid() {
        return false;
    }

}
