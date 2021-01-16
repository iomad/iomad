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
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\plugininfo;

use moodle_url, part_of_admin_tree, admin_settingpage;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for activity modules
 */
class mod extends base {
    /**
     * Finds all enabled plugins, the result may include missing plugins.
     * @return array|null of enabled plugins $pluginname=>$pluginname, null means unknown
     */
    public static function get_enabled_plugins() {
        global $DB;
        return $DB->get_records_menu('modules', array('visible'=>1), 'name ASC', 'name, name AS val');
    }

    /**
     * Magic method getter, redirects to read only values.
     *
     * For module plugins we pretend the object has 'visible' property for compatibility
     * with plugins developed for Moodle version below 2.4
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        if ($name === 'visible') {
            debugging('This is now an instance of plugininfo_mod, please use $module->is_enabled() instead of $module->visible', DEBUG_DEVELOPER);
            return ($this->is_enabled() !== false);
        }
        return parent::__get($name);
    }

    public function init_display_name() {
        if (get_string_manager()->string_exists('pluginname', $this->component)) {
            $this->displayname = get_string('pluginname', $this->component);
        } else {
            $this->displayname = get_string('modulename', $this->component);
        }
    }

    public function get_settings_section_name() {
        return 'modsetting' . $this->name;
    }

    public function load_settings(part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE; // In case settings.php wants to refer to them.
        $ADMIN = $adminroot; // May be used in settings.php.
        $plugininfo = $this; // Also can be used inside settings.php.
        $module = $this;     // Also can be used inside settings.php.

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig or !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $section = $this->get_settings_section_name();

        $settings = new admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);
        include($this->full_path('settings.php')); // This may also set $settings to null.

        if ($settings) {
            $ADMIN->add($parentnodename, $settings);
        }
    }

    /**
     * Allow all activity modules but Forum to be uninstalled.
     *
     * This exception for the Forum has been hard-coded in Moodle since ages,
     * we may want to re-think it one day.
     */
    public function is_uninstall_allowed() {
        if ($this->name === 'forum') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Return URL used for management of plugins of this type.
     * @return moodle_url
     */
    public static function get_manage_url() {
        return new moodle_url('/admin/modules.php');
    }

    /**
     * Return warning with number of activities and number of affected courses.
     *
     * @return string
     */
    public function get_uninstall_extra_warning() {
        global $DB;

        if (!$module = $DB->get_record('modules', array('name'=>$this->name))) {
            return '';
        }

        if (!$count = $DB->count_records('course_modules', array('module'=>$module->id))) {
            return '';
        }

        $sql = "SELECT COUNT('x')
                  FROM (
                    SELECT course
                      FROM {course_modules}
                     WHERE module = :mid
                  GROUP BY course
                  ) c";
        $courses = $DB->count_records_sql($sql, array('mid'=>$module->id));

        return '<p>'.get_string('uninstallextraconfirmmod', 'core_plugin', array('instances'=>$count, 'courses'=>$courses)).'</p>';
    }

    /**
     * Pre-uninstall hook.
     *
     * This is intended for disabling of plugin, some DB table purging, etc.
     *
     * NOTE: to be called from uninstall_plugin() only.
     * @private
     */
    public function uninstall_cleanup() {
        global $DB, $CFG;

        if (!$module = $DB->get_record('modules', array('name' => $this->name))) {
            parent::uninstall_cleanup();
            return;
        }

        // Delete all the relevant instances from all course sections.
        if ($coursemods = $DB->get_records('course_modules', array('module' => $module->id))) {
            foreach ($coursemods as $coursemod) {
                // Do not verify results, there is not much we can do anyway.
                delete_mod_from_section($coursemod->id, $coursemod->section);
            }
        }

        // Increment course.cacherev for courses that used this module.
        // This will force cache rebuilding on the next request.
        increment_revision_number('course', 'cacherev',
            "id IN (SELECT DISTINCT course
                      FROM {course_modules}
                     WHERE module=?)",
            array($module->id));

        // Delete all the course module records.
        $DB->delete_records('course_modules', array('module' => $module->id));

        // Delete module contexts.
        if ($coursemods) {
            foreach ($coursemods as $coursemod) {
                \context_helper::delete_instance(CONTEXT_MODULE, $coursemod->id);
            }
        }

        // Delete the module entry itself.
        $DB->delete_records('modules', array('name' => $module->name));

        // Cleanup the gradebook.
        require_once($CFG->libdir.'/gradelib.php');
        grade_uninstalled_module($module->name);

        // Do not look for legacy $module->name . '_uninstall any more,
        // they should have migrated to db/uninstall.php by now.

        parent::uninstall_cleanup();
    }
}
