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
 * Custom behat step definitions.
 *
 * @package    block_dash
 * @copyright  2022 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
Behat\Mink\Exception\DriverException as DriverException,
Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Custom behat step definitions.
 */
class behat_block_dash extends behat_base {

    /**
     * Turns block editing mode on.
     *
     * @Given I turn dash block editing mode on
     */
    public function i_turn_dash_block_editing_mode_on() {
        global $CFG;

        if ($CFG->branch >= "400") {
            $this->execute('behat_forms::i_set_the_field_to', [get_string('editmode'), 1]);
            if (!$this->running_javascript()) {
                $this->execute('behat_general::i_click_on', [
                    get_string('setmode', 'core'),
                    'button',
                ]);
            }
        } else {
            $this->execute('behat_general::i_click_on', ['Blocks editing on', 'button']);
        }
    }

    /**
     * I follow badge recipients
     * @Given I follow badge recipients
     */
    public function i_follow_badge_recipients() {
        global $CFG;

        if ($CFG->branch >= "400") {
            $this->execute('behat_forms::i_select_from_the_singleselect', ["Recipients (0)", "jump"]);
        } else {
            $this->execute('behat_general::i_click_on', ["Recipients (0)", "link"]);
        }
    }

    /**
     * I follow dashboard
     * @Given I follow dashboard
     */
    public function i_follow_dashboard() {
        global $CFG;

        if ($CFG->branch >= "400") {
            $this->execute('behat_general::i_click_on', ["Dashboard", 'link']);
        } else {
            $this->execute('behat_navigation::i_follow_in_the_user_menu', ["Dashboard"]);
        }
    }

    /**
     * Creates a datasource for dash block.
     *
     * @Given I create dash :arg1 datasource
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $datasource
     */
    public function i_create_dash_datasource($datasource) {
        global $CFG;

        $this->execute('behat_navigation::i_navigate_to_in_site_administration',
            ['Appearance > Default Dashboard page']);
        $this->execute('behat_block_dash::i_turn_dash_block_editing_mode_on', []);
        $this->execute('behat_blocks::i_add_the_block', ["Dash"]);
        $this->execute('behat_general::i_click_on_in_the', [$datasource, 'text', 'New Dash', 'block']);
    }

    /**
     * Clicks on preference of the dash for specified block. Page must be in editing mode.
     *
     * Argument block_name may be either the name of the block or CSS class of the block.
     *
     * @Given /^I open the "(?P<block_name_string>(?:[^"]|\\")*)" block preference$/
     * @param string $blockname
     */
    public function i_open_the_dash_block($blockname) {
        // Note that since $blockname may be either block name or CSS class, we can not use the exact label of "Configure" link.
        $this->execute("behat_blocks::i_open_the_blocks_action_menu", $this->escape($blockname));

        $this->execute('behat_general::i_click_on_in_the',
            ["Preference", "link", $this->escape($blockname), "block"]
        );
    }

    /**
     * Check that the focus mode enable.
     *
     * @Given /^I check dash css "(?P<value>(?:[^"]|\\")*)" "(?P<selector>(?:[^"]|\\")*)" "(?P<type>(?:[^"]|\\")*)"$/
     * @param string $value
     * @param string $selector
     * @param string $type
     * @throws ExpectationException
     */
    public function i_check_dash_css($value, $selector, $type): void {
        $stylejs = "
            return (
                window.getComputedStyle(document.querySelector('$selector')).getPropertyValue('$type')
            )
        ";
        if (strpos($this->evaluate_script($stylejs), $value) === false) {
            throw new ExpectationException("Doesn't working correct style", $this->getSession());
        }
    }
}
