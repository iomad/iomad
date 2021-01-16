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
 * Steps definitions to open and close action menus.
 *
 * @package    core
 * @category   test
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException as ExpectationException;
use Behat\Mink\Exception\DriverException as DriverException;

/**
 * Steps definitions to open and close action menus.
 *
 * @package    core
 * @category   test
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_action_menu extends behat_base {

    /**
     * Open the action menu in
     *
     * @Given /^I open the action menu in "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $element
     * @param string $selector
     * @return void
     */
    public function i_open_the_action_menu_in($element, $selectortype) {
        if (!$this->running_javascript()) {
            // Action menus automatically expand in a visible list of actions when Javascript is disabled.
            return;
        }
        // Gets the node based on the requested selector type and locator.
        $node = $this->get_node_in_container("css_element", "[role=menuitem][aria-haspopup=true]", $selectortype, $element);

        // Check if it is not already opened.
        $menunode = $this->find('css', '[aria-labelledby='.$node->getAttribute('id').']');
        if ($menunode->getAttribute('aria-hidden') === 'false') {
            return;
        }

        $this->ensure_node_is_visible($node);
        $node->click();
    }

    /**
     * When an action menu is open, follow one of the items in it.
     *
     * @Given /^I choose "(?P<link_string>(?:[^"]|\\")*)" in the open action menu$/
     * @param string $linkstring
     * @return void
     */
    public function i_choose_in_the_open_action_menu($linkstring) {
        if (!$this->running_javascript()) {
            throw new DriverException('Action menu steps are not available with Javascript disabled');
        }
        // Gets the node based on the requested selector type and locator.
        $node = $this->get_node_in_container("link",
            $linkstring,
            "css_element",
            ".moodle-actionmenu [role=menu][aria-hidden=false]");
        $this->ensure_node_is_visible($node);
        $node->click();
    }
}
