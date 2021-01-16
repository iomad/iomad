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
 * Defines classes used for plugin info.
 *
 * @package    core
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\plugininfo;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for document converter plugins
 *
 * @package    core
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fileconverter extends base {

    /**
     * Should there be a way to uninstall the plugin via the administration UI.
     *
     * Uninstallation is allowed for fileconverter plugins.
     *
     * @return bool
     */
    public function is_uninstall_allowed() {
        return true;
    }

    /**
     * Get the name for the settings section.
     *
     * @return string
     */
    public function get_settings_section_name() {
        return 'fileconverter' . $this->name;
    }

    /**
     * Load the global settings for a particular availability plugin (if there are any)
     *
     * @param \part_of_admin_tree $adminroot
     * @param string $parentnodename
     * @param bool $hassiteconfig
     */
    public function load_settings(\part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE; // In case settings.php wants to refer to them.
        $ADMIN = $adminroot; // May be used in settings.php.
        $plugininfo = $this; // Also can be used inside settings.php.

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig) {
            return;
        }

        $section = $this->get_settings_section_name();

        $settings = null;
        if (file_exists($this->full_path('settings.php'))) {
            $settings = new \admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);
            include($this->full_path('settings.php')); // This may also set $settings to null.
        }
        if ($settings) {
            $ADMIN->add($parentnodename, $settings);
        }
    }

    /**
     * Return URL used for management of plugins of this type.
     * @return \moodle_url
     */
    public static function get_manage_url() {
        return new \moodle_url('/admin/settings.php', array('section' => 'managefileconverterplugins'));
    }

    /**
     * Finds all enabled plugins, the result may include missing plugins.
     *
     * @return array|null of enabled plugins $pluginname=>$pluginname, null means unknown
     */
    public static function get_enabled_plugins() {
        global $CFG;

        $order = (!empty($CFG->converter_plugins_sortorder)) ? explode(',', $CFG->converter_plugins_sortorder) : [];
        if ($order) {
            $plugins = \core_plugin_manager::instance()->get_installed_plugins('fileconverter');
            $order = array_intersect($order, array_keys($plugins));
        }

        return array_combine($order, $order);
    }

    /**
     * Sets the current plugin as enabled or disabled
     * When enabling tries to guess the sortorder based on default rank returned by the plugin.
     * @param bool $newstate
     */
    public function set_enabled($newstate = true) {
        $enabled = self::get_enabled_plugins();
        if (array_key_exists($this->name, $enabled) == $newstate) {
            // Nothing to do.
            return;
        }
        if ($newstate) {
            // Enable converter plugin.
            $plugins = \core_plugin_manager::instance()->get_plugins_of_type('fileconverter');
            if (!array_key_exists($this->name, $plugins)) {
                // Can not be enabled.
                return;
            }
            $enabled[$this->name] = $this->name;
            self::set_enabled_plugins($enabled);
        } else {
            // Disable converter plugin.
            unset($enabled[$this->name]);
            self::set_enabled_plugins($enabled);
        }
    }

    /**
     * Set the list of enabled converter players in the specified sort order
     * To be used when changing settings or in unit tests
     * @param string|array $list list of plugin names without frankenstyle prefix - comma-separated string or an array
     */
    public static function set_enabled_plugins($list) {
        if (empty($list)) {
            $list = [];
        } else if (!is_array($list)) {
            $list = explode(',', $list);
        }
        if ($list) {
            $plugins = \core_plugin_manager::instance()->get_installed_plugins('fileconverter');
            $list = array_intersect($list, array_keys($plugins));
        }
        set_config('converter_plugins_sortorder', join(',', $list));
        \core_plugin_manager::reset_caches();
    }

    /**
     * Returns a string describing the formats this engine can converter from / to.
     *
     * @return string
     */
    public function get_supported_conversions() {
        $classname = self::get_classname($this->name);
        if (class_exists($classname)) {
            $object = new $classname();
            return $object->get_supported_conversions();
        }
        return '';
    }

    /**
     * Return the class name for the plugin.
     *
     * @param   string $plugin
     * @return  string
     */
    public static function get_classname($plugin) {
        return "\\fileconverter_{$plugin}\\converter";
    }
}
