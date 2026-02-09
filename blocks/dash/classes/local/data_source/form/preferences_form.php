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
 * Form for editing block preferences.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_source\form;

use block_dash\local\configuration\configuration;

defined('MOODLE_INTERNAL') || die('No direct access');

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing block preferences.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preferences_form extends \moodleform {

    /** @var string General tab id. */
    const TAB_GENERAL = 'tabgeneral';

    /** @var string Preference modal fields tab. */
    const TAB_FIELDS = 'tabfields';

    /** @var string Preference modal Filters tab. */
    const TAB_FILTERS = 'tabfilters';

    /** @var string Preference modal Conditions tab. */
    const TAB_CONDITIONS = 'tabconditions';

    /** @var array List of tabs used in preference modal. */
    const TABS = [
        self::TAB_GENERAL,
        self::TAB_FIELDS,
        self::TAB_FILTERS,
        self::TAB_CONDITIONS,
    ];

    /**
     * Define form fields.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function definition() {
        $block = $this->_customdata['block'];

        if (!isset($this->_customdata['tab'])) {
            $this->_customdata['tab'] = self::TABS[0];
        }

        $configuration = configuration::create_from_instance($block);
        if ($configuration->is_fully_configured()) {
            $configuration->get_data_source()->build_preferences_form($this, $this->_form);
        }

        $mform = $this->_form;

        if (empty($mform->_elements)) {
            $mform->addElement('html', '<p class="text-muted">' . get_string('nothingtodisplay') . '</p>');
        }

        $mform->addElement('html', '<hr>');

        // When two elements we need a group.
        $buttonarray = [];
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = &$mform->createElement('button', 'cancelbutton', get_string('cancel'), ['data-action' => 'cancel']);
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Get current tab of preferences form.
     */
    public function get_tab(): string {
        return $this->_customdata['tab'];
    }
}
