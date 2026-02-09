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
 * Common functions.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_dash\local\data_source\categories_data_source;
use block_dash\local\data_source\form\preferences_form;
use block_dash\local\layout\grid_layout;
use block_dash\local\layout\accordion_layout;
use block_dash\local\layout\one_stat_layout;
use block_dash\local\data_source\users_data_source;

use block_dash\local\widget\mylearning\mylearning_widget;
use block_dash\local\widget\groups\groups_widget;
use block_dash\local\widget\contacts\contacts_widget;
use block_dash\local\widget\skillprogress\skillprogress_widget;

define("BLOCK_DASH_FILTER_TABS_COUNT", 4);

/**
 * Register field definitions.
 *
 * @return array
 */
function block_dash_register_field_definitions() {
    global $CFG;

    if (PHPUNIT_TEST) {
        return require("$CFG->dirroot/blocks/dash/field_definitions_phpunit.php");
    }

    return require("$CFG->dirroot/blocks/dash/field_definitions.php");
}

/**
 * Register data sources.
 *
 * @return array
 * @throws coding_exception
 */
function block_dash_register_data_sources() {
    return [
        [
            'name' => get_string('users'),
            'help' => ['name' => 'users', 'component' => 'block_dash'],
            'identifier' => users_data_source::class,
        ],
    ];
}

/**
 * Register layouts.
 *
 * @return array
 * @throws coding_exception
 */
function block_dash_register_layouts() {
    return [
        [
            'name' => get_string('layoutgrid', 'block_dash'),
            'identifier' => grid_layout::class,
        ],
    ];
}

/**
 * Register widgets.
 *
 * @return array
 * @throws coding_exception
 */
function block_dash_register_widget() {

    return [
        [
            'name' => get_string('widget:mylearning', 'block_dash'),
            'identifier' => mylearning_widget::class,
            'help' => 'widget:mylearning',
        ],
        [
            'name' => get_string('widget:mycontacts', 'block_dash'),
            'identifier' => contacts_widget::class,
            'help' => 'widget:mycontacts',
        ],
        [
            'name' => get_string('widget:mygroups', 'block_dash'),
            'identifier' => groups_widget::class,
            'help' => 'widget:mygroups',
        ],

    ];
}

/**
 * Serve the new group form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function block_dash_output_fragment_block_preferences_form($args) {
    global $DB, $OUTPUT;

    $args = (object) $args;
    $context = $args->context;

    $blockinstance = $DB->get_record('block_instances', ['id' => $context->instanceid]);
    $block = block_instance($blockinstance->blockname, $blockinstance);

    if (!$args->tab) {
        $args->tab = preferences_form::TAB_GENERAL;
    }

    $form = new preferences_form(null, ['block' => $block, 'tab' => $args->tab], 'post', '', [
        'class' => 'dash-preferences-form',
        'data-double-submit-protection' => 'off',
    ]);

    require_capability('block/dash:addinstance', $context);

    if (isset($block->config->preferences)) {
        $data = block_dash_flatten_array($block->config->preferences, 'config_preferences');
        $form->set_data($data);
    }

    ob_start();
    $form->display();
    $formhtml = ob_get_contents();
    ob_end_clean();

    $tabs = [];
    foreach (preferences_form::TABS as $tab) {
        $tabs[] = [
            'label' => get_string($tab, 'block_dash'),
            'active' => $tab == $args->tab,
            'tabid' => $tab,
        ];
    }

    return $OUTPUT->render_from_template('block_dash/preferences_form', [
        'formhtml' => $formhtml,
        'tabs' => $tabs,
        'istotara' => block_dash_is_totara(),
    ]);
}

/**
 * File serving callback
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file was not found, just send the file otherwise and do not return anything
 */
function block_dash_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=[]) {

    if ($context->contextlevel != CONTEXT_BLOCK && $context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    require_login();

    if ($filearea == 'images' || $filearea == 'categoryimg') {

        $relativepath = implode('/', $args);

        $fullpath = "/$context->id/block_dash/$filearea/$relativepath";

        $fs = get_file_storage();
        $file = $fs->get_file_by_hash(sha1($fullpath));
        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, null, 0, $forcedownload, $options);
    }
}

/**
 * Flatten array to form field names.
 *
 * @param array $array
 * @param string $prefix
 * @return array
 */
function block_dash_flatten_array($array, $prefix = '') {
    $result = [];
    foreach ($array as $key => $value) {
        if (is_integer($key)) {
            // Don't flatten arrays with numeric indexes. Otherwise it won't be set on the Moodle form.
            $result[$prefix] = $array;
        } else if (is_array($value)) {
            $result = $result + block_dash_flatten_array($value, $prefix . '[' . $key . ']');
        } else {
            $result[$prefix . '[' . $key . ']'] = $value;
        }
    }
    return $result;
}

/**
 * Check if system is Totara.
 *
 * @return bool
 */
function block_dash_is_totara() {
    global $CFG;
    return file_exists("$CFG->dirroot/totara");
}

/**
 * Check if pro plugin is installed.
 *
 * @return bool
 */
function block_dash_has_pro() {
    global $CFG;
    return file_exists("$CFG->dirroot/local/dash");
}

/**
 * Check if dash output should be disabled.
 *
 * @return bool
 * @throws dml_exception
 */
function block_dash_is_disabled() {
    global $CFG;

    if (get_config('block_dash', 'disableall')) {
        return true;
    }

    if (isset($CFG->block_dash_disableall) && $CFG->block_dash_disableall) {
        return true;
    }

    return false;
}

/**
 * Fragment to load the widget methods.
 *
 * @param stdclass $args
 * @return string Returns the widget content.
 */
function block_dash_output_fragment_loadwidget($args) {
    global $DB;
    $args = (object) $args;
    $context = $args->context;

    $blockinstance = $DB->get_record('block_instances', ['id' => $context->instanceid]);
    $block = block_instance($blockinstance->blockname, $blockinstance);
    $datasource = block_dash\local\block_builder::create($block)->get_configuration()->get_data_source();

    if (isset($datasource->iswidget)) {
        $method = $args->method;
        $params = json_decode($args->args);
        if (isset($params->page)) {
            $datasource->get_paginator()->set_current_page($params->page);
        }
        return (method_exists($datasource, $method)) ? $datasource->$method($context, $params) : '';
    }
    return null;
}

/**
 * Load the table pagination via ajax. withou page refresh.
 *
 * @param stdclass $args
 * @return string
 */
function block_dash_output_fragment_loadtable($args) {
    global $DB;

    $args = (object) $args;
    $context = $args->context;

    $classstr = 'block_dash\table\\'.$args->handler;
    $table = new $classstr($args->uniqueid);
    $table->set_filterset(json_decode($args->filter));
    $table->set_sort_column($args->sort);
    $table->currentpage = isset($args->page) ? $args->page : 0;

    ob_start();
    echo html_writer::start_div('dash-widget-table');
    $table->out(10, true);
    echo html_writer::end_div();
    $tablehtml = ob_get_contents();
    ob_end_clean();

    return $tablehtml;

}

/**
 * Get list of all suggest users for contact list.
 */
function block_dash_get_suggest_users() {
    global $DB, $CFG;

    $users = $DB->get_records_sql("SELECT *
                                   FROM {user}
                                  WHERE confirmed = 1 AND deleted = 0 AND id <> ?", [$CFG->siteguest]);
    foreach ($users as $user) {
        $list[$user->id] = fullname($user);
    }
    return isset($list) ? $list : [];
}

/**
 * Get the current php version related data collection. Data collection implements PHP arrayAccess class.
 * PHP arrayaccess parameters are changed from 8.1.
 *
 * @return mixed
 */
function block_dash_get_data_collection() {
    return version_compare(phpversion(), '8.1', '<')
        ? new block_dash\local\data_grid\data\data_collection() : new \block_dash\local\data_grid\data\data_collection_new();
}

/**
 * Return the dash addon visible staus.
 *
 * @param int $id ID
 * @return bool
 */
function block_dash_visible_addons($id) {
    global $CFG;
    preg_match('/(dashaddon_[a-zA-Z_]+)/', $id, $matches);
    if ($matches) {
        $value = $matches[1];
        $parts = explode('\\', $value);
        if ($parts) {
            $addon = $parts[0];
            $addondependencies = $addon . "_extend_added_dependencies";
            if (get_config($addon, 'enabled')) {
                $addonplugin = explode("dashaddon_", $addon)[1];
                if (file_exists($CFG->dirroot. "/local/dash/addon/$addonplugin/lib.php")) {
                    require_once($CFG->dirroot. "/local/dash/addon/$addonplugin/lib.php");
                    if (function_exists($addondependencies) && !empty($addondependencies())) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }
    }
    return true;
}
