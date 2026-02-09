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
 * This layout displays data in a grid of cards.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\layout;

use block_dash\local\data_source\form\preferences_form;
/**
 * A layout contains information on how to display data.
 * @see abstract_layout for creating new layouts.
 *
 * This layout displays data in a grid of cards.
 *
 * @package block_dash
 */
class grid_layout extends abstract_layout {

    /**
     * Get mustache template name.
     *
     * @return string
     */
    public function get_mustache_template_name() {
        return 'block_dash/layout_grid';
    }

    /**
     * If the layout supports options.
     */
    public function supports_download() {
        return true;
    }

    /**
     * If the template should display pagination links.
     *
     * @return bool
     */
    public function supports_pagination() {
        return true;
    }

    /**
     * If the template fields can be hidden or shown conditionally.
     *
     * @return bool
     */
    public function supports_field_visibility() {
        return true;
    }

    /**
     * If the template should display filters (does not affect conditions).
     *
     * @return bool
     */
    public function supports_filtering() {
        return true;
    }

    /**
     * If the layout supports field sorting.
     *
     * @return mixed
     */
    public function supports_sorting() {
        return true;
    }

    /**
     * Add form elements to the preferences form when a user is configuring a block.
     *
     * This extends the form built by the data source. When a user chooses a layout, specific form elements may be
     * displayed after a quick refresh of the form.
     *
     * Be sure to call parent::build_preferences_form() if you override this method.
     *
     * @param \moodleform $form
     * @param \MoodleQuickForm $mform
     * @throws \coding_exception
     */
    public function build_preferences_form(\moodleform $form, \MoodleQuickForm $mform) {

        if ($form->get_tab() == preferences_form::TAB_FIELDS) {

            // Hide the table.
            $mform->addElement('advcheckbox', 'config_preferences[hidetable]', get_string('hidetable', 'block_dash'));
            $mform->setType('config_preferences[hidetable]', PARAM_BOOL);
            $mform->addHelpButton('config_preferences[hidetable]', 'hidetable', 'block_dash');
            $mform->setDefault('config_preferences[hidetable]', false);

            // Export the data.
            $mform->addElement('advcheckbox', 'config_preferences[exportdata]', get_string('enabledownload', 'block_dash'));
            $mform->setType('config_preferences[exportdata]', PARAM_BOOL);
            $mform->addHelpButton('config_preferences[exportdata]', 'enabledownload', 'block_dash');
            $mform->setDefault('config_preferences[exportdata]', get_config('block_dash', 'exportdata'));
        }
        parent::build_preferences_form($form, $mform);

    }
}
