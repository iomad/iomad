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
 * Responsible for building field definitions and retrieving them as needed.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\field;

use block_dash\local\dash_framework\structure\field_interface;
use block_dash\local\data_grid\field\attribute\field_attribute_interface;

/**
 * Responsible for building field definitions and retrieving them as needed.
 *
 * @package block_dash
 */
class field_definition_factory implements field_definition_factory_interface {

    /**
     * Cache registered field definitions so they are only retrieved once.
     *
     * @var array
     */
    private static $fielddefintionregistry;

    /**
     * @var field_definition_interface[]
     */
    private static $fielddefinitions;

    /**
     * Register and return data registry.
     *
     * @return array
     */
    protected static function get_field_definition_registry() {
        if (is_null(self::$fielddefintionregistry)) {
            self::$fielddefintionregistry = [];
            if ($pluginsfunction = get_plugins_with_function('register_field_definitions')) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $pluginfunction) {
                        foreach ($pluginfunction() as $fielddefinition) {
                            self::$fielddefintionregistry[$fielddefinition['name']] = $fielddefinition;
                        }
                    }
                }
            }
        }

        return self::$fielddefintionregistry;
    }

    /**
     * Returns all registered field definitions.
     *
     * @return field_definition_interface[]
     * @throws \coding_exception
     */
    public static function get_all_field_definitions() {
        if (is_null(self::$fielddefinitions)) {
            self::$fielddefinitions = [];
            foreach (self::get_field_definition_registry() as $info) {
                if (!isset($info['name'])) {
                    throw new \coding_exception('Standard SQL fields need a name defined.');
                }
                self::$fielddefinitions[$info['name']] = self::build_field_definition($info['name'], $info);
            }
        }

        return self::$fielddefinitions;
    }

    /**
     * Check if field definition exists.
     *
     * @param string $name
     * @return bool
     */
    public static function exists($name) {
        return isset(self::get_field_definition_registry()[$name]);
    }

    /**
     * Get field definition info.
     *
     * @param string $name
     * @return array|null
     */
    public static function get_field_definition_info($name) {
        if (self::exists($name)) {
            return self::get_field_definition_registry()[$name];
        }

        return null;
    }

    /**
     * Build field definition.
     *
     * @param string $name
     * @param array $info
     * @return field_definition_interface
     * @throws \coding_exception
     */
    public static function build_field_definition($name, array $info) {
        global $CFG;

        if (!self::exists($name)) {
            return null;
        }

        $fielddefinitioninfo = self::get_field_definition_info($name);

        if (isset($fielddefinitioninfo['factory']) && $fielddefinitioninfo['factory'] != self::class) {
            return $fielddefinitioninfo['factory']::build_field_definition($name, $info);
        }

        // Check for db driver specific select statements.
        if (isset($fielddefinitioninfo['select_' . $CFG->dbtype])) {
            $select = $fielddefinitioninfo['select_' . $CFG->dbtype];
        } else {
            // Otherwise default to agnostic select (not db specific).
            if (!isset($fielddefinitioninfo['select'])) {
                throw new \coding_exception('Standard SQL fields need a select defined: ' . $name);
            }
            $select = $fielddefinitioninfo['select'];
        }

        if (!isset($fielddefinitioninfo['title'])) {
            throw new \coding_exception('Standard SQL fields need a title defined: ' . $name);
        }

        $newfielddefinition = new sql_field_definition(
            $select,
            $fielddefinitioninfo['name'],
            $fielddefinitioninfo['title'],
            isset($fielddefinitioninfo['visibility']) ? $fielddefinitioninfo['visibility'] :
                field_definition_interface::VISIBILITY_VISIBLE,
            isset($fielddefinitioninfo['options']) ? $fielddefinitioninfo['options'] : []);

        if (isset($fielddefinitioninfo['tables'])) {
            $newfielddefinition->set_option('tables', $fielddefinitioninfo['tables']);
        }

        // Support adding attributes from configuration array.
        if (isset($fielddefinitioninfo['attributes'])) {
            foreach ($fielddefinitioninfo['attributes'] as $attribute) {
                /** @var field_attribute_interface $newattribute */
                $newattribute = new $attribute['type']();
                if (isset($attribute['options'])) {
                    $newattribute->set_options($attribute['options']);
                }
                $newfielddefinition->add_attribute($newattribute);
            }
        }

        return $newfielddefinition;
    }

    /**
     * Get field definitions by names. Maintain order.
     *
     * @param string[] $names Field definition names to retrieve.
     * @return field_definition_interface[]
     * @throws \coding_exception
     */
    public static function get_field_definitions(array $names) {
        $fielddefinitions = [];
        $all = self::get_all_field_definitions();

        foreach ($all as $fielddefinition) {
            if (in_array($fielddefinition->get_name(), $names)) {
                $fielddefinitions[array_search($fielddefinition->get_name(), $names)] = clone $fielddefinition;
            }
        }

        ksort($fielddefinitions);

        return $fielddefinitions;
    }

    /**
     * Get field definition by name.
     *
     * @param string $name Field definition name to retrieve.
     * @return field_definition_interface
     * @throws \coding_exception
     */
    public static function get_field_definition($name) {
        foreach (self::get_all_field_definitions() as $fielddefinition) {
            if ($fielddefinition->get_name() == $name) {
                return clone $fielddefinition;
            }
        }

        return null;
    }

    /**
     * Get options for form select field.
     *
     * @param field_interface[] $fields
     * @return array
     * @throws \coding_exception
     */
    public static function get_field_definition_options($fields) {
        $options = [];
        foreach ($fields as $field) {

            $title = $field->get_table()->get_table_name() . ': ' . $field->get_title();

            $options[$field->get_alias()] = $title;
        }

        return $options;
    }
}
