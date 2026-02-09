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
 * Responsible for creating data sources on request.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_source;

use block_dash\local\data_custom\abstract_custom_type;

/**
 * Responsible for creating data sources on request.
 *
 * @package block_dash
 */
class data_source_factory implements data_source_factory_interface {

    /**
     * Cache registered data sources so they are only retrieved once.
     *
     * @var array
     */
    private static $datasourceregistry;

    /**
     * Register and return data registry.
     *
     * @return array
     */
    protected static function get_data_source_registry() {
        if (is_null(self::$datasourceregistry)) {

            self::$datasourceregistry = [];
            if ($pluginsfunction = get_plugins_with_function('register_data_sources')) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $pluginfunction) {
                        foreach ($pluginfunction() as $datasourceinfo) {
                            self::$datasourceregistry[$datasourceinfo['identifier']] = $datasourceinfo;
                        }
                    }
                }
            }
            $crd = \core_component::get_component_classes_in_namespace(null, 'local\\block_dash');
            foreach ($crd as $fullclassname => $classpath) {
                if (is_subclass_of($fullclassname, abstract_data_source::class)) {
                    self::$datasourceregistry[$fullclassname] = [
                        'identifier' => $fullclassname,
                        'name' => abstract_data_source::get_name_from_class($fullclassname),
                        'help' => abstract_data_source::get_name_from_class($fullclassname, true),
                    ];
                }
            }

            if ($pluginsfunction = get_plugins_with_function('register_widget')) {
                foreach ($pluginsfunction as $plugintype => $plugins) {
                    foreach ($plugins as $pluginfunction) {
                        foreach ($pluginfunction() as $callback) {
                            self::$datasourceregistry[$callback['identifier']] = $callback + ['type' => 'widget'];
                        }
                    }
                }
            }

            // Attach the custom type of feature. For now its content.
            $crd = \core_component::get_component_classes_in_namespace(null, 'local\\block_dash');
            foreach ($crd as $fullclassname => $classpath) {
                if (is_subclass_of($fullclassname, abstract_custom_type::class)) {
                    self::$datasourceregistry[$fullclassname] = [
                        'identifier' => $fullclassname,
                        'name' => abstract_data_source::get_name_from_class($fullclassname),
                        'help' => abstract_data_source::get_name_from_class($fullclassname, true),
                        'type' => 'custom',
                    ];
                }
            }
        }

        return self::$datasourceregistry;
    }

    /**
     * Check if data source identifier exists.
     *
     * @param string $identifier
     * @return bool
     */
    public static function exists($identifier) {
        return isset(self::get_data_source_registry()[$identifier]);
    }

    /**
     * Get data source info.
     *
     * @param string $identifier
     * @return array|null
     */
    public static function get_data_source_info($identifier) {
        if (self::exists($identifier)) {
            return self::get_data_source_registry()[$identifier];
        }

        return null;
    }

    /**
     * Build data source.
     *
     * @param string $identifier
     * @param \context $context
     * @return data_source_interface
     */
    public static function build_data_source($identifier, \context $context) {
        if (!self::exists($identifier)) {
            return null;
        }

        $datasourceinfo = self::get_data_source_info($identifier);

        if (isset($datasourceinfo['factory']) && $datasourceinfo['factory'] != self::class) {
            return $datasourceinfo['factory']::build_data_source($identifier, $context);
        }

        if (!class_exists($identifier)) {
            return null;
        }

        return new $identifier($context);
    }

    /**
     * Get options array for select form fields.
     *
     * @param string $type
     * @return array
     */
    public static function get_data_source_form_options($type='') {
        $options = [];
        foreach (self::get_data_source_registry() as $identifier => $datasourceinfo) {
            if ($type) {
                if (isset($datasourceinfo['type']) && $datasourceinfo['type'] == $type) {
                    $options[$identifier] = [
                        'name' => $datasourceinfo['name'],
                        'help' => isset($datasourceinfo['help']) ? $datasourceinfo['help'] : '',
                    ];
                }
            } else {
                if (!isset($datasourceinfo['type'])) {
                    $options[$identifier] = [
                        'name' => $datasourceinfo['name'],
                        'help' => isset($datasourceinfo['help']) ? $datasourceinfo['help'] : '',
                    ];
                }
            }
        }

        return $options;
    }

}
