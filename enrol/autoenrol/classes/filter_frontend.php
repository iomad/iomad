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
 * Class with filter frontend (editing form) functionality.
 *
 * @package enrol_autoenrol
 * @copyright 2021 Roberto Pinna
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_autoenrol;

/**
 * Class with filter frontend (editing form) functionality.
 *
 * @package enrol_autoenrol
 * @copyright 2021 Roberto Pinna
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_frontend extends \core_availability\frontend {
    /**
     * Includes JavaScript for the main system and all plugins.
     *
     * @param \stdClass $course Course object
     * @param \cm_info $cm Course-module currently being edited (null if none)
     * @param \section_info $section Section currently being edited (null if none)
     */
    public static function include_all_javascript($course, \cm_info $cm = null,
            \section_info $section = null) {
        global $PAGE;

        // Prepare array of required YUI modules. It is bad for performance to
        // make multiple yui_module calls, so we group all the plugin modules
        // into a single call (the main init function will call init for each
        // plugin).
        $modules = ['moodle-core_availability-form', 'base', 'node', 'panel', 'moodle-core-notification-dialogue', 'json'];

        // Work out JS to include for all components.
        $pluginmanager = \core_plugin_manager::instance();
        $enabled = $pluginmanager->get_enabled_plugins('availability');
        $authorized = [];
        $availabilityplugins = get_config('enrol_autoenrol', 'availabilityplugins');
        if ($availabilityplugins !== false) {
            $authorized = explode(',', $availabilityplugins);
        } else {
            $authorized = ['profile', 'grouping'];
        }
        $componentparams = new \stdClass();
        foreach ($enabled as $plugin => $info) {
            if (in_array($plugin, $authorized)) {
                // Create plugin front-end object.
                $class = '\availability_' . $plugin . '\frontend';
                $frontend = new $class();

                // Add to array of required YUI modules.
                $component = $frontend->get_component();
                $modules[] = 'moodle-' . $component . '-form';

                // Get parameters for this plugin.
                $componentparams->{$plugin} = [$component,
                        $frontend->allow_add($course, $cm, $section),
                        $frontend->get_javascript_init_params($course, $cm, $section),
                ];

                // Include strings for this plugin.
                $identifiers = $frontend->get_javascript_strings();
                $identifiers[] = 'title';
                $identifiers[] = 'description';
                $PAGE->requires->strings_for_js($identifiers, $component);
            }
        }

        // Include all JS (in one call). The init function runs on DOM ready.
        $PAGE->requires->yui_module($modules,
                'M.core_availability.form.init', [$componentparams], null, true);

        // Include main strings.
        $PAGE->requires->strings_for_js(['none', 'cancel', 'delete', 'choosedots'],
                'moodle');
        $PAGE->requires->strings_for_js(['addrestriction', 'invalid',
                'listheader_sign_before', 'listheader_sign_pos',
                'listheader_sign_neg', 'listheader_single',
                'listheader_multi_after', 'listheader_multi_before',
                'listheader_multi_or', 'listheader_multi_and',
                'unknowncondition', 'hide_verb', 'hidden_individual',
                'show_verb', 'shown_individual', 'hidden_all', 'shown_all',
                'condition_group', 'condition_group_info', 'and', 'or',
                'label_multi', 'label_sign', 'setheading', 'itemheading',
                'missingplugin',
        ], 'availability');
    }
}
