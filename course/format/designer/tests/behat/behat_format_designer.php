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
 * Behat Designer course format steps definitions.
 *
 * @package    format_designer
 * @category   test
 * @copyright  2020 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
Behat\Mink\Exception\DriverException as DriverException,
Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Designer course format steps definitions.
 *
 * @package    format_designer
 * @category   test
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_format_designer extends behat_base {

    /**
     * Go to editing section layout for specified section number and layout type.
     * You need to be in the course page and on editing mode.
     *
     * @Given /^I edit the section "(?P<section_number>\d+)" to layout "(?P<layout_name_string>(?:[^"]|\\")*)"$/
     * @param int $sectionnumber
     * @param string $layouttype
     */
    public function i_edit_the_section_layout($sectionnumber, $layouttype) {
        // If javascript is on, link is inside a menu.
        global $CFG;
        // Lower 4.3.
        if ($CFG->version < 2023092300) {
            if ($this->running_javascript()) {
                $this->i_open_section_layout_edit_menu($sectionnumber);
            }

            // We need to know the course format as the text strings depends on them.
            if (get_string_manager()->string_exists($layouttype, 'format_designer')) {
                $strlayout = get_string($layouttype, 'format_designer');
            } else {
                $strlayout = get_string('link', 'format_designer');
            }
            $xpath = $this->execute("behat_course::section_exists", $sectionnumber);
            $xpath .= "/descendant::div[contains(@id, 'section-designer-action')]/descendant::
                div[contains(@class, 'dropdown-menu')]";
            // Click on layout link.
            $this->execute('behat_general::i_click_on_in_the',
            [$strlayout, "link", $this->escape($xpath), "xpath_element"]
            );
        } else {
            $this->execute('behat_course::i_open_section_edit_menu', [$sectionnumber]);
            $actionmenu = "Section Layout > ". get_string($layouttype, 'format_designer');
            $this->execute('behat_action_menu::i_choose_in_the_open_action_menu', [$actionmenu]);
            $this->execute('behat_general::reload', []);
        }
    }

    /**
     * Check that heroactivity position.
     *
     * @Given /^I check heroactivity position "(?P<name>(?:[^"]|\\")*)" "(?P<pos>(?:[^"]|\\")*)"$/
     * @param string $name
     * @param string $pos
     * @throws ExpectationException
     */
    public function i_check_heroactivity_position($name, $pos): void {
        $script = "
            return (function() {
                var val = document.querySelector('.secondary-navigation .moremenu ul li:nth-child($pos) a.nav-link').innerHTML;
                return val.trim();
            })();
        ";
        $config = $this->evaluate_script($script);
        if (strpos($config, $name) === false) {
            throw new ExpectationException("Doesn't working correct $config", $this->getSession());
        }
    }

    /**
     * Check that heroactivity position.
     *
     * @Given /^I check heroactivity not in the position "(?P<name>(?:[^"]|\\")*)" "(?P<pos>(?:[^"]|\\")*)"$/
     * @param string $name
     * @param string $pos
     * @throws ExpectationException
     */
    public function i_check_heroactivity_not_in_the_position($name, $pos): void {
        $script = "
            return (function() {
                var val = document.querySelector('.secondary-navigation .moremenu ul li:nth-child($pos) a.nav-link').innerHTML;
                return val.trim();
            })();
        ";
        $config = $this->evaluate_script($script);
        if (strpos($config, $name) !== false) {
            throw new ExpectationException("Doesn't working correct $config", $this->getSession());
        }
    }

    /**
     * I set the completion.
     *
     * @Given /^I set the designer manual completion$/
     */
    public function i_set_the_manual_completion() {
        global $CFG;
        if ($CFG->version < 2023092300) {
            $this->execute("behat_forms::i_set_the_field_to", ['completion', 1]);
        } else {
            $this->execute("behat_forms::i_set_the_field_to", ['Students must manually mark the activity as done', 1]);
        }
    }

    /**
     * I set the completion expected.
     *
     * @Given /^I set the designer completion expected "(?P<value>(?:[^"]|\\")*)"$/
     * @param string $value
     */
    public function i_set_completion_expected($value) {
        global $CFG;
        if ($CFG->version < 2023092300) {
            $this->execute("behat_forms::i_set_the_field_to", ['Expect completed on', $value]);
        } else {
            $this->execute("behat_forms::i_set_the_field_to", ['Set reminder in Timeline', $value]);
        }
    }

    /**
     * Opens a section edit menu if it is not already opened.
     *
     * @Given /^I open section layout "(?P<section_number>\d+)" edit menu$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $sectionnumber
     */
    public function i_open_section_layout_edit_menu($sectionnumber) {
        if (!$this->running_javascript()) {
            throw new DriverException('Section layout edit menu not available when Javascript is disabled');
        }

        // Wait for section to be available, before clicking on the menu.
        $this->execute("behat_course::i_wait_until_section_is_available", $sectionnumber);

        // If it is already opened we do nothing.
        $xpath = "//li[@id='section-" . $sectionnumber . "']";
        $xpath .= "/descendant::div[contains(@id, 'section-designer-action')]/descendant::
        button[contains(@data-toggle, 'dropdown')]";
        $exception = new ExpectationException('Section "' . $sectionnumber . '" was not found', $this->getSession());
        $menu = $this->find('xpath', $xpath, $exception);
        $menu->click();
        $this->execute("behat_course::i_wait_until_section_is_available", $sectionnumber);
    }

    /**
     * Check the section layout.
     *
     * @Given /^I check the section "(?P<section_number>\d+)" to layout "(?P<layout_name_string>(?:[^"]|\\")*)"$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param int $sectionnumber
     * @param string $layouttype
     */
    public function i_check_the_section_layout($sectionnumber, $layouttype) {
        $layoutclass = "$layouttype-layout";
        $xpath = "//li[@id='section-" . $sectionnumber . "']";
        $xpath .= "/descendant::ul[contains(@class, 'designer-section-content') and contains(@class, '".$layoutclass."')]";
        $exception = new ExpectationException('Section "' . $sectionnumber . '" was not change the layout "'
        . $layouttype . '"', $this->getSession());
        $this->find('xpath', $xpath, $exception);
    }

    /**
     * Check the activity completion element for designer format.
     *
     * @Given /^I check the activity "(?P<activiyt_idendifier>(?:[^"]|\\")*)" to element "(?P<element_string>(?:[^"]|\\")*)"$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $activityidendifier
     * @param string $element
     */
    public function i_check_the_activity_element($activityidendifier, $element) {
        $xpath = $this->get_activity_idendifier_slug($activityidendifier);
        $xpath .= $element;
        $exception = new ExpectationException('Module "'. $activityidendifier.'" was does not correct completion ',
            $this->getSession());
        $this->find('xpath', $xpath, $exception);
    }

    /**
     * Check the activity completion element for designer format.
     *
     * @Given /^I click on activity "(?P<activiyt_idendifier>(?:[^"]|\\")*)"$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $activityidendifier
     */
    public function i_click_on_activity($activityidendifier) {
        $xpath = $this->get_activity_idendifier_slug($activityidendifier);
        $xpath .= "/descendant::div[contains(@class, 'activityinstance')]/descendant::span[contains(@class, 'instancename')]";
        $exception = new ExpectationException('Click for "' . $activityidendifier . '" was not found', $this->getSession());
        $menu = $this->find('xpath', $xpath, $exception);
        $menu->click();
    }

    /**
     * Click the section header.
     *
     * @Given /^I click on section header "(?P<section_number>\d+)"$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param int $sectionnum
     */
    public function i_click_on_section_header($sectionnum) {
        $xpath = "//li[@id='section-" . $sectionnum . "']";
        $xpath .= "/descendant::div[contains(@class, 'section-header-content')]";
        $exception = new ExpectationException('Click for section"'. $sectionnum .'" header was not found', $this->getSession());
        $menu = $this->find('xpath', $xpath, $exception);
        $menu->click();
    }

    /**
     * Check the section is expanded.
     * @Given /^I click on section expanded "(?P<section_number>\d+)"$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param int $sectionnumber
     */
    public function i_check_section_expanded($sectionnumber) {
        $xpath = "//li[@id='section-" . $sectionnumber . "']";
        $xpath .= "/descendant::div[contains(@class, 'section-header-content') and contains(@data-toggle, 'collapse')]";
        $exception = "";
        $this->find('xpath', $xpath, $exception);
    }

    /**
     * Check the section is collapsed.
     * @Given /^I click on section collapsed "(?P<section_number>\d+)"$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param int $sectionnumber
     */
    public function i_check_section_collapsed($sectionnumber) {
        $xpath = "//li[@id='section-" . $sectionnumber . "']";
        $xpath .= "/descendant::div[contains(@class, 'section-header-content')
        and contains(@class, 'collapse') and contains(@data-toggle, 'collapse')]";
        $exception = "";
        $this->find('xpath', $xpath, $exception);
    }

    /**
     * Check the activity completion info for designer format.
     *
     * @Given /^I should see designerinfo "(?P<acti_id>(?:[^"]|\\")*)" "(?P<com_info>(?:[^"]|\\")*)" "(?P<Dur_info>(?:[^"]|\\")*)"$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $activityidendifier
     * @param string $completioninfo
     * @param string $duration
     */
    public function i_should_see_activity_completioninfo($activityidendifier, $completioninfo, $duration) {
        $xpath = $this->get_activity_idendifier_slug($activityidendifier);
        $durationinfo = behat_context_helper::escape($duration);
        $completioninfo .= str_replace("'", "", $durationinfo);
        $xpath .= "/descendant::div[contains(@class, 'completion-info')]/descendant::
        span[contains(., '".$completioninfo."')]";
        $exception = new ExpectationException('Completion info for "' . $activityidendifier . '" was not found',
            $this->getSession());
        $this->find('xpath', $xpath, $exception);
    }

    /**
     * Manual completion for designer format.
     *
     * @Given /^I toggle assignment manual completion designer "(?P<acti_id>(?:[^"]|\\")*)" "(?P<acti_type>(?:[^"]|\\")*)"$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $activityname
     * @param string $activityidendifier
     */
    public function i_toggle_assignment_manual_completion_designer($activityname, $activityidendifier) {
        global $CFG;
            // Moodle-3.11 and above.
        $this->i_click_on_activity($activityidendifier);
        $this->execute("behat_completion::toggle_the_manual_completion_state", [$activityname]);
        $this->execute("behat_completion::manual_completion_button_displayed_as", [$activityname, "Done"]);
    }

    /**
     * Get activity xpath selector
     *
     * @param string $activityidendifier
     * @return string activity selector xpath
     */
    public function get_activity_idendifier_slug($activityidendifier) {
        $cm = $this->get_course_module_for_identifier($activityidendifier);
        if (!$cm) {
            throw new Exception('The specified activity with idnumber "' . $activityidendifier . '" does not exist');
        }
        $moduleid = "module-" . $cm->id;
        $xpath = "//li[@id='".$moduleid."']";
        $exception = new ExpectationException('Activity idendifier "' . $activityidendifier . '" was not found',
            $this->getSession());
        $this->find('xpath', $xpath, $exception);
        return $xpath;
    }

    /**
     * Turns block editing mode on.
     * @Given I switch block editing mode on
     * @Given I turn block editing mode on
     */
    public function i_turn_block_editing_mode_on() {
        global $CFG;
        $this->execute('behat_forms::i_set_the_field_to', [get_string('editmode'), 1]);
        if (!$this->running_javascript()) {
            $this->execute('behat_general::i_click_on', [
                get_string('setmode', 'core'),
                'button',
            ]);
        }
    }

    /**
     * Turns block editing mode on.
     * @Given I check the designer section general section
     */
    public function i_check_the_designer_section_general_section() {
        global $CFG;
        $this->execute('behat_forms::the_field_matches_value', ["Section name" , ""]);
        $this->execute('behat_general::assert_page_contains_text', ["General"]);
    }

}
