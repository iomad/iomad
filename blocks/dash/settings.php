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
 * Global settings definition for block dash.
 * @package   block_dash
 * @copyright 2020 bdecent gmbh <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    require_once("$CFG->dirroot/blocks/dash/lib.php");

    // Default high scores.
    $settings->add(new admin_setting_configselect(
        'block_dash/bootstrap_version',
        get_string('bootstrapversion', 'block_dash'),
        get_string('bootstrapversion_desc', 'block_dash'),
        block_dash_is_totara() ? 3 : 4,
        [
            3 => 'Bootstrap 3.x',
            4 => 'Bootstrap 4.x',
        ]
    ));

    // Css classes.
    $settings->add(new admin_setting_configtext(
        'block_dash/cssclass',
        get_string('cssclass', 'block_dash'),
        get_string('cssclass_help', 'block_dash'),
        '',
        PARAM_TEXT
        ));

    $settings->add(new admin_setting_configselect(
        'block_dash/showheader',
        get_string('showheader', 'block_dash'),
        get_string('showheader_help', 'block_dash'),
        1,
        [
            0 => get_string('hidden', 'block_dash'),
            1 => get_string('visible'),
        ]
        ));

    $settings->add(new admin_setting_configselect(
        'block_dash/hide_when_empty',
        get_string('hidewhenempty', 'block_dash'),
        get_string('hidewhenempty_desc', 'block_dash'),
        0,
        [
            0 => get_string('no'),
            1 => get_string('yes'),
        ]
        ));

    $settings->add(new admin_setting_configcheckbox(
        'block_dash/disableall',
        get_string('disableall', 'block_dash'),
        get_string('disableall_help', 'block_dash'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_dash/exportdata',
        get_string('defaultexportdata', 'block_dash'),
        get_string('defaultexportdata_help', 'block_dash'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'block_dash/suggestinterests',
        get_string('suggestinterests', 'block_dash'),
        get_string('suggestinterests_desc', 'block_dash'), 0, PARAM_INT)
    );

    $settings->add(new admin_setting_configtext(
        'block_dash/suggestcohort',
        get_string('suggestcohort', 'block_dash'),
        get_string('suggestcohort_desc', 'block_dash'), 0, PARAM_INT)
    );

    $settings->add(new admin_setting_configtext(
        'block_dash/suggestgroups',
        get_string('suggestgroups', 'block_dash'),
        get_string('suggestgroups_desc', 'block_dash'), 0, PARAM_INT)
    );

    $users = block_dash_get_suggest_users();
    $settings->add(new admin_setting_configmultiselect(
        'block_dash/suggestusers',
        get_string('suggestusers', 'block_dash'),
        get_string('suggestusers_desc', 'block_dash'), [], $users)
    );

    if ($ADMIN->fulltree) {// Category images.

        $settings->add(new admin_setting_heading('block_dash_categoryimg', get_string('categoryimgheadingsub', 'block_dash'),
        format_text(get_string('categoryimgdesc', 'block_dash'), FORMAT_MARKDOWN)));

        $name = 'block_dash/categoryimgfallback';
        $title = get_string('categoryimgfallback', 'block_dash');
        $description = get_string('categoryimgfallbackdesc', 'block_dash');
        $default = 'categoryimg';
        $setting = new admin_setting_configstoredfile($name, $title, $description, $default, 0);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $settings->add($setting);

        $coursecats = core_course_category::make_categories_list();

        // Go through all categories and create the necessary settings.
        foreach ($coursecats as $key => $value) {
            // Category Icons for each category.
            $name = 'block_dash/categoryimg';
            $title = $value;
            $description = get_string('categoryimgcategory', 'block_dash', ['category' => $value]);
            $filearea = 'categoryimg';
            $setting = new admin_setting_configstoredfile($name . $key, $title, $description, $default, $key);
            $setting->set_updatedcallback('theme_reset_all_caches');
            $settings->add($setting);
        }
        unset($coursecats);
    }

    $PAGE->requires->js_amd_inline("
        require(['core/form-autocomplete'], function(module) {
            module.enhance('#id_s_block_dash_suggestusers');
        });
    ");

}
