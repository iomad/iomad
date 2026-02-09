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

namespace block_dash\local\widget;

use block_dash\local\data_source\abstract_data_source;
use block_dash\local\data_source\data_source_interface;
use block_dash\local\data_source\form\preferences_form;
use block_dash\local\dash_framework\query_builder\builder;
use block_dash\local\data_grid\filter\filter_collection;
use block_dash\local\layout\layout_interface;
use renderer_base;
use block_dash\local\paginator;

/**
 * Widgets extend class for new widgets.
 */
abstract class abstract_widget extends abstract_data_source implements data_source_interface, widget_interface, \templatable {

    /**
     * List of data to generate widget template content.
     *
     * @var array
     */
    public $data = [];

    /**
     * Check the datasource is widget.
     *
     * @var bool
     */
    public $iswidget = true;



    /**
     * Constructor.
     *
     * @param \context $context
     */
    public function __construct(\context $context) {
        parent::__construct($context);
        $this->set_widget_layout();
        $this->set_widget_preference();
    }

    /**
     * Set widget preferences
     *
     * @return void
     */
    public function set_widget_preference() {
        $preferences = $this->widget_preferences();
        $this->set_preferences($preferences);
    }

    /**
     * Set the widget layout class.
     *
     * @return void
     */
    public function set_widget_layout() {
        $layout = $this->layout();
        $this->set_layout($layout);
    }

    /**
     * Fetch the widget data if supports the query method.
     *
     * @return array
     */
    public function get_widget_data() {
        global $PAGE;
        $querydata = ($this->supports_query()) ? $this->get_query_template()->query() : [];
        $this->data = $querydata;
        $this->build_widget();
        return $this->data;
    }

    /**
     * Build widget data from child widget classes..
     *
     * @return array
     */
    public function build_widget() {
        return $this->data;
    }

    /**
     * Check the widget contains any data to render.
     *
     * @return bool
     */
    public function is_empty() {
        $this->build_widget();
        return (empty($this->data)) ? true : false;
    }

    /**
     * Prefence form for widget. We make the fields disable other than the general.
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     * @return void
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform) {
        if ($form->get_tab() == preferences_form::TAB_GENERAL) {
            parent::build_preferences_form($form, $mform);
            $element = $mform->getElement('config_preferences[layout]');
            if ($element) {
                $mform->removeElement('config_preferences[layout]');
            }
        } else {
            $mform->addElement('html', get_string('fieldalert', 'block_dash'), 'fieldalert');
        }
    }

    /**
     * Empty builder to make sure supports the datasource method.
     *
     * @return builder
     */
    public function get_query_template(): builder {
        global $USER;
        $builder = new builder();
        return $builder;
    }

    /**
     * Filter conditions are added to badges preference report.
     *
     * @return filter_collection
     */
    public function build_filter_collection() {
        $filtercollection = new filter_collection(get_class($this), $this->get_context());
        return $filtercollection;
    }

    /**
     * Set the data source supports debug.
     * @return bool;
     */
    public function supports_debug() {
        return false;
    }
    /**
     * Widget supports builder method. If new widget is not supports then widget should return as false.
     *
     * @return void
     */
    public function supports_query() {
        return false;
    }

    /**
     * Confirm the groups datasource is widget.
     *
     * @return bool
     */
    public function is_widget() {
        return true;
    }

    /**
     * Is the widget needs to load the js when it the content updated using JS.
     *
     * @return bool
     */
    public function supports_currentscript() {
        return false;
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
     * Get widget count based on the data. Define the steps to check the count of records.
     *
     * @return int
     */
    public function widget_data_count() {
        return 0;
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

        if (!isset($this->paginator) || $this->paginator == null) {
            $this->paginator = new paginator(function () {
                return $this->widget_data_count();
            }, 0, $perpage);
        }
        return $this->paginator;
    }
}
