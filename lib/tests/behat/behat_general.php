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
 * General use steps definitions.
 *
 * @package   core
 * @category  test
 * @copyright 2012 David Monllaó
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Facebook\WebDriver\Exception\NoSuchAlertException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\WebDriverAlert;
use Facebook\WebDriver\WebDriverExpectedCondition;

/**
 * Cross component steps definitions.
 *
 * Basic web application definitions from MinkExtension and
 * BehatchExtension. Definitions modified according to our needs
 * when necessary and including only the ones we need to avoid
 * overlapping and confusion.
 *
 * @package   core
 * @category  test
 * @copyright 2012 David Monllaó
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_general extends behat_base {

    /**
     * @var string used by {@link switch_to_window()} and
     * {@link switch_to_the_main_window()} to work-around a Chrome browser issue.
     */
    const MAIN_WINDOW_NAME = '__moodle_behat_main_window_name';

    /**
     * @var string when we want to check whether or not a new page has loaded,
     * we first write this unique string into the page. Then later, by checking
     * whether it is still there, we can tell if a new page has been loaded.
     */
    const PAGE_LOAD_DETECTION_STRING = 'new_page_not_loaded_since_behat_started_watching';

    /**
     * @var $pageloaddetectionrunning boolean Used to ensure that page load detection was started before a page reload
     * was checked for.
     */
    private $pageloaddetectionrunning = false;

    /**
     * Opens Moodle homepage.
     *
     * @Given /^I am on homepage$/
     */
    public function i_am_on_homepage() {
        $this->execute('behat_general::i_visit', ['/']);
    }

    /**
     * Opens Moodle site homepage.
     *
     * @Given /^I am on site homepage$/
     */
    public function i_am_on_site_homepage() {
        $this->execute('behat_general::i_visit', ['/?redirect=0']);
    }

    /**
     * Opens course index page.
     *
     * @Given /^I am on course index$/
     */
    public function i_am_on_course_index() {
        $this->execute('behat_general::i_visit', ['/course/index.php']);
    }

    /**
     * Reloads the current page.
     *
     * @Given /^I reload the page$/
     */
    public function reload() {
        $this->getSession()->reload();
    }

    /**
     * Follows the page redirection. Use this step after any action that shows a message and waits for a redirection
     *
     * @Given /^I wait to be redirected$/
     */
    public function i_wait_to_be_redirected() {

        // Xpath and processes based on core_renderer::redirect_message(), core_renderer::$metarefreshtag and
        // moodle_page::$periodicrefreshdelay possible values.
        if (!$metarefresh = $this->getSession()->getPage()->find('xpath', "//head/descendant::meta[@http-equiv='refresh']")) {
            // We don't fail the scenario if no redirection with message is found to avoid race condition false failures.
            return true;
        }

        // Wrapped in try & catch in case the redirection has already been executed.
        try {
            $content = $metarefresh->getAttribute('content');
        } catch (NoSuchElementException $e) {
            return true;
        } catch (StaleElementReferenceException $e) {
            return true;
        }

        // Getting the refresh time and the url if present.
        if (strstr($content, 'url') != false) {

            list($waittime, $url) = explode(';', $content);

            // Cleaning the URL value.
            $url = trim(substr($url, strpos($url, 'http')));

        } else {
            // Just wait then.
            $waittime = $content;
        }


        // Wait until the URL change is executed.
        if ($this->running_javascript()) {
            $this->getSession()->wait($waittime * 1000);

        } else if (!empty($url)) {
            // We redirect directly as we can not wait for an automatic redirection.
            $this->getSession()->getDriver()->getClient()->request('GET', $url);

        } else {
            // Reload the page if no URL was provided.
            $this->getSession()->getDriver()->reload();
        }
    }

    /**
     * Switches to the specified iframe.
     *
     * @Given /^I switch to "(?P<iframe_name_string>(?:[^"]|\\")*)" iframe$/
     * @Given /^I switch to "(?P<iframe_name_string>(?:[^"]|\\")*)" class iframe$/
     * @param string $name The name of the iframe
     */
    public function switch_to_iframe($name) {
        // We spin to give time to the iframe to be loaded.
        // Using extended timeout as we don't know about which
        // kind of iframe will be loaded.
        $this->spin(
            function($context) use ($name){
                $iframe = $context->find('iframe', $name);
                if ($iframe->hasAttribute('name')) {
                    $iframename = $iframe->getAttribute('name');
                } else {
                    if (!$this->running_javascript()) {
                        throw new \coding_exception('iframe must have a name attribute to use the switchTo command.');
                    }
                    $iframename = uniqid();
                    $this->execute_js_on_node($iframe, "{{ELEMENT}}.name = '{$iframename}';");
                }
                $context->getSession()->switchToIFrame($iframename);

                // If no exception we are done.
                return true;
            },
            behat_base::get_extended_timeout()
        );
    }

    /**
     * Switches to the main Moodle frame.
     *
     * @Given /^I switch to the main frame$/
     */
    public function switch_to_the_main_frame() {
        $this->getSession()->switchToIFrame();
    }

    /**
     * Switches to the specified window. Useful when interacting with popup windows.
     *
     * @Given /^I switch to "(?P<window_name_string>(?:[^"]|\\")*)" (window|tab)$/
     * @param string $windowname
     */
    public function switch_to_window($windowname) {
        if ($windowname === self::MAIN_WINDOW_NAME) {
            // When switching to the main window normalise the window name to null.
            // This is normalised further in the Mink driver to the root window ID.
            $windowname = null;
        }

        $this->getSession()->switchToWindow($windowname);
    }

    /**
     * Switches to a second window.
     *
     * @Given /^I switch to a second window$/
     * @throws DriverException If there aren't exactly 2 windows open.
     */
    public function switch_to_second_window() {
        $names = $this->getSession()->getWindowNames();

        if (count($names) !== 2) {
            throw new DriverException('Expected to see 2 windows open, found ' . count($names));
        }

        $this->getSession()->switchToWindow($names[1]);
    }

    /**
     * Switches to the main Moodle window. Useful when you finish interacting with popup windows.
     *
     * @Given /^I switch to the main (window|tab)$/
     */
    public function switch_to_the_main_window() {
        $this->switch_to_window(self::MAIN_WINDOW_NAME);
    }

    /**
     * Closes all extra windows opened during the navigation.
     *
     * This assumes all popups are opened by the main tab and you will now get back.
     *
     * @Given /^I close all opened windows$/
     * @throws DriverException If there aren't exactly 1 tabs open when finish or no javascript running
     */
    public function i_close_all_opened_windows() {
        if (!$this->running_javascript()) {
            throw new DriverException('Closing windows steps require javascript');
        }
        $names = $this->getSession()->getWindowNames();
        for ($index = 1; $index < count($names); $index ++) {
            $this->getSession()->switchToWindow($names[$index]);
            $this->execute_script("window.open('', '_self').close();");
        }
        $names = $this->getSession()->getWindowNames();
        if (count($names) !== 1) {
            throw new DriverException('Expected to see 1 tabs open, not ' . count($names));
        }
        $this->getSession()->switchToWindow($names[0]);
    }

    /**
     * Wait for an alert to be displayed.
     *
     * @return WebDriverAlert
     */
    public function wait_for_alert(): WebDriverAlert {
        $webdriver = $this->getSession()->getDriver()->getWebdriver();
        $webdriver->wait()->until(WebDriverExpectedCondition::alertIsPresent());

        return $webdriver->switchTo()->alert();
    }

    /**
     * Accepts the currently displayed alert dialog. This step does not work in all the browsers, consider it experimental.
     * @Given /^I accept the currently displayed dialog$/
     */
    public function accept_currently_displayed_alert_dialog() {
        $alert = $this->wait_for_alert();
        $alert->accept();
    }

    /**
     * Dismisses the currently displayed alert dialog. This step does not work in all the browsers, consider it experimental.
     * @Given /^I dismiss the currently displayed dialog$/
     */
    public function dismiss_currently_displayed_alert_dialog() {
        $alert = $this->wait_for_alert();
        $alert->dismiss();
    }

    /**
     * Clicks link with specified id|title|alt|text.
     *
     * @When /^I follow "(?P<link_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $link
     */
    public function click_link($link) {
        $linknode = $this->find_link($link);
        $linknode->click();
    }

    /**
     * Waits X seconds. Required after an action that requires data from an AJAX request.
     *
     * @Then /^I wait "(?P<seconds_number>\d+)" seconds$/
     * @param int $seconds
     */
    public function i_wait_seconds($seconds) {
        if ($this->running_javascript()) {
            $this->getSession()->wait($seconds * 1000);
        } else {
            sleep($seconds);
        }
    }

    /**
     * Waits until the page is completely loaded. This step is auto-executed after every step.
     *
     * @Given /^I wait until the page is ready$/
     */
    public function wait_until_the_page_is_ready() {

        // No need to wait if not running JS.
        if (!$this->running_javascript()) {
            return;
        }

        $this->getSession()->wait(self::get_timeout() * 1000, self::PAGE_READY_JS);
    }

    /**
     * Waits until the provided element selector exists in the DOM
     *
     * Using the protected method as this method will be usually
     * called by other methods which are not returning a set of
     * steps and performs the actions directly, so it would not
     * be executed if it returns another step.

     * @Given /^I wait until "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" exists$/
     * @param string $element
     * @param string $selector
     * @return void
     */
    public function wait_until_exists($element, $selectortype) {
        $this->ensure_element_exists($element, $selectortype);
    }

    /**
     * Waits until the provided element does not exist in the DOM
     *
     * Using the protected method as this method will be usually
     * called by other methods which are not returning a set of
     * steps and performs the actions directly, so it would not
     * be executed if it returns another step.

     * @Given /^I wait until "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" does not exist$/
     * @param string $element
     * @param string $selector
     * @return void
     */
    public function wait_until_does_not_exists($element, $selectortype) {
        $this->ensure_element_does_not_exist($element, $selectortype);
    }

    /**
     * Generic mouse over action. Mouse over a element of the specified type.
     *
     * @When /^I hover "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     */
    public function i_hover($element, $selectortype) {
        // Gets the node based on the requested selector type and locator.
        $node = $this->get_selected_node($selectortype, $element);
        $this->execute_js_on_node($node, '{{ELEMENT}}.scrollIntoView();');
        $node->mouseOver();
    }

    /**
     * Generic mouse over action. Mouse over a element of the specified type.
     *
     * @When I hover over the :element :selectortype in the :containerelement :containerselectortype
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @param string $containerelement Element we look for
     * @param string $containerselectortype The type of what we look for
     */
    public function i_hover_in_the(string $element, $selectortype, string $containerelement, $containerselectortype): void {
        // Gets the node based on the requested selector type and locator.
        $node = $this->get_node_in_container($selectortype, $element, $containerselectortype, $containerelement);
        $this->execute_js_on_node($node, '{{ELEMENT}}.scrollIntoView();');
        $node->mouseOver();
    }

    /**
     * Generic click action. Click on the element of the specified type.
     *
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     */
    public function i_click_on($element, $selectortype) {
        // Gets the node based on the requested selector type and locator.
        $this->get_selected_node($selectortype, $element)->click();
    }

    /**
     * Sets the focus and takes away the focus from an element, generating blur JS event.
     *
     * @When /^I take focus off "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     */
    public function i_take_focus_off_field($element, $selectortype) {
        if (!$this->running_javascript()) {
            throw new ExpectationException('Can\'t take focus off from "' . $element . '" in non-js mode', $this->getSession());
        }
        // Gets the node based on the requested selector type and locator.
        $node = $this->get_selected_node($selectortype, $element);
        $this->ensure_node_is_visible($node);

        // Ensure element is focused before taking it off.
        $node->focus();
        $node->blur();
    }

    /**
     * Clicks the specified element and confirms the expected dialogue.
     *
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" confirming the dialogue$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     */
    public function i_click_on_confirming_the_dialogue($element, $selectortype) {
        $this->i_click_on($element, $selectortype);
        $this->execute('behat_general::accept_currently_displayed_alert_dialog', []);
        $this->wait_until_the_page_is_ready();
    }

    /**
     * Clicks the specified element and dismissing the expected dialogue.
     *
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" dismissing the dialogue$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     */
    public function i_click_on_dismissing_the_dialogue($element, $selectortype) {
        $this->i_click_on($element, $selectortype);
        $this->execute('behat_general::dismiss_currently_displayed_alert_dialog', []);
        $this->wait_until_the_page_is_ready();
    }

    /**
     * Click on the element of the specified type which is located inside the second element.
     *
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)"$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @param string $nodeelement Element we look in
     * @param string $nodeselectortype The type of selector where we look in
     */
    public function i_click_on_in_the($element, $selectortype, $nodeelement, $nodeselectortype) {
        $node = $this->get_node_in_container($selectortype, $element, $nodeselectortype, $nodeelement);
        $node->click();
    }

    /**
     * Click on the element with some modifier key pressed (alt, shift, meta or control).
     *
     * It is important to note that not all HTML elements are compatible with this step because
     * the webdriver limitations. For example, alt click on checkboxes with a visible label will
     * produce a normal checkbox click without the modifier.
     *
     * @When I :modifier click on :element :selectortype in the :nodeelement :nodeselectortype
     * @param string $modifier the extra modifier to press (for example, alt+shift or shift)
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @param string $nodeelement Element we look in
     * @param string $nodeselectortype The type of selector where we look in
     */
    public function i_key_click_on_in_the($modifier, $element, $selectortype, $nodeelement, $nodeselectortype) {
        behat_base::require_javascript_in_session($this->getSession());

        $key = null;
        switch (strtoupper(trim($modifier))) {
            case '':
                break;
            case 'SHIFT':
                $key = behat_keys::SHIFT;
                break;
            case 'CTRL':
                $key = behat_keys::CONTROL;
                break;
            case 'ALT':
                $key = behat_keys::ALT;
                break;
            case 'META':
                $key = behat_keys::META;
                break;
            default:
                throw new \coding_exception("Unknown modifier key '$modifier'}");
        }

        $node = $this->get_node_in_container($selectortype, $element, $nodeselectortype, $nodeelement);

        // KeyUP and KeyDown require the element to be displayed in the current window.
        $this->execute_js_on_node($node, '{{ELEMENT}}.scrollIntoView();');
        $node->keyDown($key);
        $node->click();
        // Any click action can move the scroll. Ensure the element is still displayed.
        $this->execute_js_on_node($node, '{{ELEMENT}}.scrollIntoView();');
        $node->keyUp($key);
    }

    /**
     * Drags and drops the specified element to the specified container. This step does not work in all the browsers, consider it experimental.
     *
     * The steps definitions calling this step as part of them should
     * manage the wait times by themselves as the times and when the
     * waits should be done depends on what is being dragged & dropper.
     *
     * @Given /^I drag "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector1_string>(?:[^"]|\\")*)" and I drop it in "(?P<container_element_string>(?:[^"]|\\")*)" "(?P<selector2_string>(?:[^"]|\\")*)"$/
     * @param string $element
     * @param string $selectortype
     * @param string $containerelement
     * @param string $containerselectortype
     */
    public function i_drag_and_i_drop_it_in($source, $sourcetype, $target, $targettype) {
        if (!$this->running_javascript()) {
            throw new DriverException('Drag and drop steps require javascript');
        }

        $source = $this->find($sourcetype, $source);
        $target = $this->find($targettype, $target);

        if (!$source->isVisible()) {
            throw new ExpectationException("'{$source}' '{$sourcetype}' is not visible", $this->getSession());
        }
        if (!$target->isVisible()) {
            throw new ExpectationException("'{$target}' '{$targettype}' is not visible", $this->getSession());
        }

        $this->getSession()->getDriver()->dragTo($source->getXpath(), $target->getXpath());
    }

    /**
     * Checks, that the specified element is visible. Only available in tests using Javascript.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" should be visible$/
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @throws DriverException
     * @param string $element
     * @param string $selectortype
     * @return void
     */
    public function should_be_visible($element, $selectortype) {

        if (!$this->running_javascript()) {
            throw new DriverException('Visible checks are disabled in scenarios without Javascript support');
        }

        $node = $this->get_selected_node($selectortype, $element);
        if (!$node->isVisible()) {
            throw new ExpectationException('"' . $element . '" "' . $selectortype . '" is not visible', $this->getSession());
        }
    }

    /**
     * Checks, that the existing element is not visible. Only available in tests using Javascript.
     *
     * As a "not" method, it's performance could not be good, but in this
     * case the performance is good because the element must exist,
     * otherwise there would be a ElementNotFoundException, also here we are
     * not spinning until the element is visible.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" should not be visible$/
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @param string $element
     * @param string $selectortype
     * @return void
     */
    public function should_not_be_visible($element, $selectortype) {

        try {
            $this->should_be_visible($element, $selectortype);
        } catch (ExpectationException $e) {
            // All as expected.
            return;
        }
        throw new ExpectationException('"' . $element . '" "' . $selectortype . '" is visible', $this->getSession());
    }

    /**
     * Checks, that the specified element is visible inside the specified container. Only available in tests using Javascript.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)" should be visible$/
     * @throws ElementNotFoundException
     * @throws DriverException
     * @throws ExpectationException
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @param string $nodeelement Element we look in
     * @param string $nodeselectortype The type of selector where we look in
     */
    public function in_the_should_be_visible($element, $selectortype, $nodeelement, $nodeselectortype) {

        if (!$this->running_javascript()) {
            throw new DriverException('Visible checks are disabled in scenarios without Javascript support');
        }

        $node = $this->get_node_in_container($selectortype, $element, $nodeselectortype, $nodeelement);
        if (!$node->isVisible()) {
            throw new ExpectationException(
                '"' . $element . '" "' . $selectortype . '" in the "' . $nodeelement . '" "' . $nodeselectortype . '" is not visible',
                $this->getSession()
            );
        }
    }

    /**
     * Checks, that the existing element is not visible inside the existing container. Only available in tests using Javascript.
     *
     * As a "not" method, it's performance could not be good, but in this
     * case the performance is good because the element must exist,
     * otherwise there would be a ElementNotFoundException, also here we are
     * not spinning until the element is visible.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)" should not be visible$/
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @param string $nodeelement Element we look in
     * @param string $nodeselectortype The type of selector where we look in
     */
    public function in_the_should_not_be_visible($element, $selectortype, $nodeelement, $nodeselectortype) {

        try {
            $this->in_the_should_be_visible($element, $selectortype, $nodeelement, $nodeselectortype);
        } catch (ExpectationException $e) {
            // All as expected.
            return;
        }
        throw new ExpectationException(
            '"' . $element . '" "' . $selectortype . '" in the "' . $nodeelement . '" "' . $nodeselectortype . '" is visible',
            $this->getSession()
        );
    }

    /**
     * Checks, that page contains specified text. It also checks if the text is visible when running Javascript tests.
     *
     * @Then /^I should see "(?P<text_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $text
     */
    public function assert_page_contains_text($text) {

        // Looking for all the matching nodes without any other descendant matching the
        // same xpath (we are using contains(., ....).
        $xpathliteral = behat_context_helper::escape($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
            "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        try {
            $nodes = $this->find_all('xpath', $xpath);
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException('"' . $text . '" text was not found in the page', $this->getSession());
        }

        // If we are not running javascript we have enough with the
        // element existing as we can't check if it is visible.
        if (!$this->running_javascript()) {
            return;
        }

        // We spin as we don't have enough checking that the element is there, we
        // should also ensure that the element is visible. Using microsleep as this
        // is a repeated step and global performance is important.
        $this->spin(
            function($context, $args) {

                foreach ($args['nodes'] as $node) {
                    if ($node->isVisible()) {
                        return true;
                    }
                }

                // If non of the nodes is visible we loop again.
                throw new ExpectationException('"' . $args['text'] . '" text was found but was not visible', $context->getSession());
            },
            array('nodes' => $nodes, 'text' => $text),
            false,
            false,
            true
        );

    }

    /**
     * Checks, that page doesn't contain specified text. When running Javascript tests it also considers that texts may be hidden.
     *
     * @Then /^I should not see "(?P<text_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $text
     */
    public function assert_page_not_contains_text($text) {

        // Looking for all the matching nodes without any other descendant matching the
        // same xpath (we are using contains(., ....).
        $xpathliteral = behat_context_helper::escape($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
            "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        // We should wait a while to ensure that the page is not still loading elements.
        // Waiting less than self::get_timeout() as we already waited for the DOM to be ready and
        // all JS to be executed.
        try {
            $nodes = $this->find_all('xpath', $xpath, false, false, self::get_reduced_timeout());
        } catch (ElementNotFoundException $e) {
            // All ok.
            return;
        }

        // If we are not running javascript we have enough with the
        // element existing as we can't check if it is hidden.
        if (!$this->running_javascript()) {
            throw new ExpectationException('"' . $text . '" text was found in the page', $this->getSession());
        }

        // If the element is there we should be sure that it is not visible.
        $this->spin(
            function($context, $args) {

                foreach ($args['nodes'] as $node) {
                    // If element is removed from dom, then just exit.
                    try {
                        // If element is visible then throw exception, so we keep spinning.
                        if ($node->isVisible()) {
                            throw new ExpectationException('"' . $args['text'] . '" text was found in the page',
                                $context->getSession());
                        }
                    } catch (NoSuchElementException $e) {
                        // Do nothing just return, as element is no more on page.
                        return true;
                    } catch (ElementNotFoundException $e) {
                        // Do nothing just return, as element is no more on page.
                        return true;
                    }
                }

                // If non of the found nodes is visible we consider that the text is not visible.
                return true;
            },
            array('nodes' => $nodes, 'text' => $text),
            behat_base::get_reduced_timeout(),
            false,
            true
        );
    }

    /**
     * Checks, that the specified element contains the specified text. When running Javascript tests it also considers that texts may be hidden.
     *
     * @Then /^I should see "(?P<text_string>(?:[^"]|\\")*)" in the "(?P<element_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)"$/
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @param string $text
     * @param string $element Element we look in.
     * @param string $selectortype The type of element where we are looking in.
     */
    public function assert_element_contains_text($text, $element, $selectortype) {

        // Getting the container where the text should be found.
        $container = $this->get_selected_node($selectortype, $element);

        // Looking for all the matching nodes without any other descendant matching the
        // same xpath (we are using contains(., ....).
        $xpathliteral = behat_context_helper::escape($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
            "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        // Wait until it finds the text inside the container, otherwise custom exception.
        try {
            $nodes = $this->find_all('xpath', $xpath, false, $container);
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException('"' . $text . '" text was not found in the "' . $element . '" element', $this->getSession());
        }

        // If we are not running javascript we have enough with the
        // element existing as we can't check if it is visible.
        if (!$this->running_javascript()) {
            return;
        }

        // We also check the element visibility when running JS tests. Using microsleep as this
        // is a repeated step and global performance is important.
        $this->spin(
            function($context, $args) {

                foreach ($args['nodes'] as $node) {
                    if ($node->isVisible()) {
                        return true;
                    }
                }

                throw new ExpectationException('"' . $args['text'] . '" text was found in the "' . $args['element'] . '" element but was not visible', $context->getSession());
            },
            array('nodes' => $nodes, 'text' => $text, 'element' => $element),
            false,
            false,
            true
        );
    }

    /**
     * Checks, that the specified element does not contain the specified text. When running Javascript tests it also considers that texts may be hidden.
     *
     * @Then /^I should not see "(?P<text_string>(?:[^"]|\\")*)" in the "(?P<element_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)"$/
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @param string $text
     * @param string $element Element we look in.
     * @param string $selectortype The type of element where we are looking in.
     */
    public function assert_element_not_contains_text($text, $element, $selectortype) {

        // Getting the container where the text should be found.
        $container = $this->get_selected_node($selectortype, $element);

        // Looking for all the matching nodes without any other descendant matching the
        // same xpath (we are using contains(., ....).
        $xpathliteral = behat_context_helper::escape($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
            "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        // We should wait a while to ensure that the page is not still loading elements.
        // Giving preference to the reliability of the results rather than to the performance.
        try {
            $nodes = $this->find_all('xpath', $xpath, false, $container, self::get_reduced_timeout());
        } catch (ElementNotFoundException $e) {
            // All ok.
            return;
        }

        // If we are not running javascript we have enough with the
        // element not being found as we can't check if it is visible.
        if (!$this->running_javascript()) {
            throw new ExpectationException('"' . $text . '" text was found in the "' . $element . '" element', $this->getSession());
        }

        // We need to ensure all the found nodes are hidden.
        $this->spin(
            function($context, $args) {

                foreach ($args['nodes'] as $node) {
                    if ($node->isVisible()) {
                        throw new ExpectationException('"' . $args['text'] . '" text was found in the "' . $args['element'] . '" element', $context->getSession());
                    }
                }

                // If all the found nodes are hidden we are happy.
                return true;
            },
            array('nodes' => $nodes, 'text' => $text, 'element' => $element),
            behat_base::get_reduced_timeout(),
            false,
            true
        );
    }

    /**
     * Checks, that the first specified element appears before the second one.
     *
     * @Then :preelement :preselectortype should appear before :postelement :postselectortype
     * @Then :preelement :preselectortype should appear before :postelement :postselectortype in the :containerelement :containerselectortype
     * @throws ExpectationException
     * @param string $preelement The locator of the preceding element
     * @param string $preselectortype The selector type of the preceding element
     * @param string $postelement The locator of the latest element
     * @param string $postselectortype The selector type of the latest element
     * @param string $containerelement
     * @param string $containerselectortype
     */
    public function should_appear_before(
        string $preelement,
        string $preselectortype,
        string $postelement,
        string $postselectortype,
        ?string $containerelement = null,
        ?string $containerselectortype = null
    ) {
        $msg = "'{$preelement}' '{$preselectortype}' does not appear before '{$postelement}' '{$postselectortype}'";
        $this->check_element_order(
            $containerelement,
            $containerselectortype,
            $preelement,
            $preselectortype,
            $postelement,
            $postselectortype,
            $msg
        );
    }

    /**
     * Checks, that the first specified element appears after the second one.
     *
     * @Then :postelement :postselectortype should appear after :preelement :preselectortype
     * @Then :postelement :postselectortype should appear after :preelement :preselectortype in the :containerelement :containerselectortype
     * @throws ExpectationException
     * @param string $postelement The locator of the latest element
     * @param string $postselectortype The selector type of the latest element
     * @param string $preelement The locator of the preceding element
     * @param string $preselectortype The selector type of the preceding element
     * @param string $containerelement
     * @param string $containerselectortype
     */
    public function should_appear_after(
        string $postelement,
        string $postselectortype,
        string $preelement,
        string $preselectortype,
        ?string $containerelement = null,
        ?string $containerselectortype = null
    ) {
        $msg = "'{$postelement}' '{$postselectortype}' does not appear after '{$preelement}' '{$preselectortype}'";
        $this->check_element_order(
            $containerelement,
            $containerselectortype,
            $preelement,
            $preselectortype,
            $postelement,
            $postselectortype,
            $msg
        );
    }

    /**
     * Shared code to check whether an element is before or after another one.
     *
     * @param string $containerelement
     * @param string $containerselectortype
     * @param string $preelement The locator of the preceding element
     * @param string $preselectortype The locator of the preceding element
     * @param string $postelement The locator of the following element
     * @param string $postselectortype The selector type of the following element
     * @param string $msg Message to output if this fails
     */
    protected function check_element_order(
        ?string $containerelement,
        ?string $containerselectortype,
        string $preelement,
        string $preselectortype,
        string $postelement,
        string $postselectortype,
        string $msg
    ) {
        $containernode = false;
        if ($containerselectortype && $containerelement) {
            // Get the container node.
            $containernode = $this->get_selected_node($containerselectortype, $containerelement);
            $msg .= " in the '{$containerelement}' '{$containerselectortype}'";
        }

        list($preselector, $prelocator) = $this->transform_selector($preselectortype, $preelement);
        list($postselector, $postlocator) = $this->transform_selector($postselectortype, $postelement);

        $newlines = [
            "\r\n",
            "\r",
            "\n",
        ];
        $prexpath = str_replace($newlines, ' ', $this->find($preselector, $prelocator, false, $containernode)->getXpath());
        $postxpath = str_replace($newlines, ' ', $this->find($postselector, $postlocator, false, $containernode)->getXpath());

        if ($this->running_javascript()) {
            // The xpath to do this was running really slowly on certain Chrome versions so we are using
            // this DOM method instead.
            $js = <<<EOF
(function() {
    var a = document.evaluate("{$prexpath}", document, null, XPathResult.ANY_TYPE, null).iterateNext();
    var b = document.evaluate("{$postxpath}", document, null, XPathResult.ANY_TYPE, null).iterateNext();
    return a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING;
})()
EOF;
            $ok = $this->evaluate_script($js);
        } else {

            // Using following xpath axe to find it.
            $xpath = "{$prexpath}/following::*[contains(., {$postxpath})]";
            $ok = $this->getSession()->getDriver()->find($xpath);
        }

        if (!$ok) {
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * Checks, that element of specified type is disabled.
     *
     * @Then /^the "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should be disabled$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param string $element Element we look in
     * @param string $selectortype The type of element where we are looking in.
     */
    public function the_element_should_be_disabled($element, $selectortype) {
        $this->the_attribute_of_should_be_set("disabled", $element, $selectortype, false);
    }

    /**
     * Checks, that element of specified type is enabled.
     *
     * @Then /^the "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should be enabled$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param string $element Element we look on
     * @param string $selectortype The type of where we look
     */
    public function the_element_should_be_enabled($element, $selectortype) {
        $this->the_attribute_of_should_be_set("disabled", $element, $selectortype, true);
    }

    /**
     * Checks the provided element and selector type are readonly on the current page.
     *
     * @Then /^the "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should be readonly$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param string $element Element we look in
     * @param string $selectortype The type of element where we are looking in.
     */
    public function the_element_should_be_readonly($element, $selectortype) {
        $this->the_attribute_of_should_be_set("readonly", $element, $selectortype, false);
    }

    /**
     * Checks the provided element and selector type are not readonly on the current page.
     *
     * @Then /^the "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should not be readonly$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param string $element Element we look in
     * @param string $selectortype The type of element where we are looking in.
     */
    public function the_element_should_not_be_readonly($element, $selectortype) {
        $this->the_attribute_of_should_be_set("readonly", $element, $selectortype, true);
    }

    /**
     * Checks the provided element and selector type exists in the current page.
     *
     * This step is for advanced users, use it if you don't find anything else suitable for what you need.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should exist$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     */
    public function should_exist($element, $selectortype) {
        // Will throw an ElementNotFoundException if it does not exist.
        $this->find($selectortype, $element);
    }

    /**
     * Checks that the provided element and selector type not exists in the current page.
     *
     * This step is for advanced users, use it if you don't find anything else suitable for what you need.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should not exist$/
     * @throws ExpectationException
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     */
    public function should_not_exist($element, $selectortype) {
        // Will throw an ElementNotFoundException if it does not exist, but, actually it should not exist, so we try &
        // catch it.
        try {
            // The exception does not really matter as we will catch it and will never "explode".
            $exception = new ElementNotFoundException($this->getSession(), $selectortype, null, $element);

            // Using the spin method as we want a reduced timeout but there is no need for a 0.1 seconds interval
            // because in the optimistic case we will timeout.
            // If all goes good it will throw an ElementNotFoundExceptionn that we will catch.
            $this->find($selectortype, $element, $exception, false, behat_base::get_reduced_timeout());
        } catch (ElementNotFoundException $e) {
            // We expect the element to not be found.
            return;
        }

        // The element was found and should not have been. Throw an exception.
        throw new ExpectationException("The '{$element}' '{$selectortype}' exists in the current page", $this->getSession());
    }

    /**
     * Ensure that edit mode is (not) available on the current page.
     *
     * @Then edit mode should be available on the current page
     * @Then edit mode should :not be available on the current page
     * @param bool $not
     */
    public function edit_mode_should_be_available(bool $not = false): void {
        $isavailable = $this->is_edit_mode_available();
        $shouldbeavailable = empty($not);

        if ($isavailable && !$shouldbeavailable) {
            throw new ExpectationException("Edit mode is available and should not be", $this->getSession());
        } else if ($shouldbeavailable && !$isavailable) {
            throw new ExpectationException("Edit mode is not available and should be", $this->getSession());
        }
    }

    /**
     * Check whether edit mode is available on the current page.
     *
     * @return bool
     */
    public function is_edit_mode_available(): bool {
        // If the course is already in editing mode then it will have the class 'editing' on the body.
        // This is a 'cheap' way of telling if the course is in editing mode and therefore if edit mode is available.
        $body = $this->find('css', 'body');
        if ($body->hasClass('editing')) {
            return true;
        }

        try {
            $this->find('field', get_string('editmode'), false, false, 0);
            return true;
        } catch (ElementNotFoundException $e) {
            return false;
        }
    }

    /**
     * This step triggers cron like a user would do going to admin/cron.php.
     *
     * @Given /^I trigger cron$/
     */
    public function i_trigger_cron() {
        $this->execute('behat_general::i_visit', ['/admin/cron.php']);
    }

    /**
     * Runs a scheduled task immediately, given full class name.
     *
     * This is faster and more reliable than running cron (running cron won't
     * work more than once in the same test, for instance). However it is
     * a little less 'realistic'.
     *
     * While the task is running, we suppress mtrace output because it makes
     * the Behat result look ugly.
     *
     * Note: Most of the code relating to running a task is based on
     * admin/cli/scheduled_task.php.
     *
     * @Given /^I run the scheduled task "(?P<task_name>[^"]+)"$/
     * @param string $taskname Name of task e.g. 'mod_whatever\task\do_something'
     */
    public function i_run_the_scheduled_task($taskname) {
        $task = \core\task\manager::get_scheduled_task($taskname);
        if (!$task) {
            throw new DriverException('The "' . $taskname . '" scheduled task does not exist');
        }

        // Do setup for cron task.
        raise_memory_limit(MEMORY_EXTRA);
        \core\cron::setup_user();

        // Get lock.
        $cronlockfactory = \core\lock\lock_config::get_lock_factory('cron');
        if (!$cronlock = $cronlockfactory->get_lock('core_cron', 10)) {
            throw new DriverException('Unable to obtain core_cron lock for scheduled task');
        }
        if (!$lock = $cronlockfactory->get_lock('\\' . get_class($task), 10)) {
            $cronlock->release();
            throw new DriverException('Unable to obtain task lock for scheduled task');
        }
        $task->set_lock($lock);
        if (!$task->is_blocking()) {
            $cronlock->release();
        } else {
            $task->set_cron_lock($cronlock);
        }

        try {
            // Prepare the renderer.
            \core\cron::prepare_core_renderer();

            // Discard task output as not appropriate for Behat output!
            ob_start();
            $task->execute();
            ob_end_clean();

            // Restore the previous renderer.
            \core\cron::prepare_core_renderer(true);

            // Mark task complete.
            \core\task\manager::scheduled_task_complete($task);
        } catch (Exception $e) {
            // Restore the previous renderer.
            \core\cron::prepare_core_renderer(true);

            // Mark task failed and throw exception.
            \core\task\manager::scheduled_task_failed($task);

            throw new DriverException('The "' . $taskname . '" scheduled task failed', 0, $e);
        }
    }

    /**
     * Runs all ad-hoc tasks in the queue.
     *
     * This is faster and more reliable than running cron (running cron won't
     * work more than once in the same test, for instance). However it is
     * a little less 'realistic'.
     *
     * While the task is running, we suppress mtrace output because it makes
     * the Behat result look ugly.
     *
     * @Given /^I run all adhoc tasks$/
     * @throws DriverException
     */
    public function i_run_all_adhoc_tasks() {
        global $DB;

        // Do setup for cron task.
        \core\cron::setup_user();

        // Discard task output as not appropriate for Behat output!
        ob_start();

        // Run all tasks which have a scheduled runtime of before now.
        $timenow = time();

        while (!\core\task\manager::static_caches_cleared_since($timenow) &&
                $task = \core\task\manager::get_next_adhoc_task($timenow)) {
            // Clean the output buffer between tasks.
            ob_clean();

            // Run the task.
            \core\cron::run_inner_adhoc_task($task);

            // Check whether the task record still exists.
            // If a task was successful it will be removed.
            // If it failed then it will still exist.
            if ($DB->record_exists('task_adhoc', ['id' => $task->get_id()])) {
                // End ouptut buffering and flush the current buffer.
                // This should be from just the current task.
                ob_end_flush();

                throw new DriverException('An adhoc task failed', 0);
            }
        }
        ob_end_clean();
    }

    /**
     * Checks that an element and selector type exists in another element and selector type on the current page.
     *
     * This step is for advanced users, use it if you don't find anything else suitable for what you need.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should exist in the "(?P<element2_string>(?:[^"]|\\")*)" "(?P<selector2_string>[^"]*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     * @param NodeElement|string $containerelement The locator of the container selector
     * @param string $containerselectortype The container selector type
     */
    public function should_exist_in_the($element, $selectortype, $containerelement, $containerselectortype) {
        // Will throw an ElementNotFoundException if it does not exist.
        $this->get_node_in_container($selectortype, $element, $containerselectortype, $containerelement);
    }

    /**
     * Checks that an element and selector type does not exist in another element and selector type on the current page.
     *
     * This step is for advanced users, use it if you don't find anything else suitable for what you need.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should not exist in the "(?P<element2_string>(?:[^"]|\\")*)" "(?P<selector2_string>[^"]*)"$/
     * @throws ExpectationException
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     * @param NodeElement|string $containerelement The locator of the container selector
     * @param string $containerselectortype The container selector type
     */
    public function should_not_exist_in_the($element, $selectortype, $containerelement, $containerselectortype) {
        // Get the container node.
        $containernode = $this->find($containerselectortype, $containerelement);

        // Will throw an ElementNotFoundException if it does not exist, but, actually it should not exist, so we try &
        // catch it.
        try {
            // Looks for the requested node inside the container node.
            $this->find($selectortype, $element, false, $containernode, behat_base::get_reduced_timeout());
        } catch (ElementNotFoundException $e) {
            // We expect the element to not be found.
            return;
        }

        // The element was found and should not have been. Throw an exception.
        $elementdescription = $this->get_selector_description($selectortype, $element);
        $containerdescription = $this->get_selector_description($containerselectortype, $containerelement);
        throw new ExpectationException(
            "The {$elementdescription} exists in the {$containerdescription}",
            $this->getSession()
        );
    }

    /**
     * Change browser window size
     *
     * Allowed sizes:
     * - mobile: 425x750
     * - tablet: 768x1024
     * - small: 1024x768
     * - medium: 1366x768
     * - large: 2560x1600
     * - custom: widthxheight
     *
     * Example: I change window size to "small" or I change window size to "1024x768"
     * or I change viewport size to "800x600". The viewport option is useful to guarantee that the
     * browser window has same viewport size even when you run Behat on multiple operating systems.
     *
     * @throws ExpectationException
     * @Then /^I change (window|viewport) size to "(mobile|tablet|small|medium|large|\d+x\d+)"$/
     * @Then /^I change the (window|viewport) size to "(mobile|tablet|small|medium|large|\d+x\d+)"$/
     * @param string $windowsize size of the window (mobile|tablet|small|medium|large|wxh).
     */
    public function i_change_window_size_to($windowviewport, $windowsize) {
        $this->resize_window($windowsize, $windowviewport === 'viewport');
    }

    /**
     * Checks whether there the specified attribute is set or not.
     *
     * @Then the :attribute attribute of :element :selectortype should be set
     * @Then the :attribute attribute of :element :selectortype should :not be set
     *
     * @throws ExpectationException
     * @param string $attribute Name of attribute
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     * @param string $not
     */
    public function the_attribute_of_should_be_set($attribute, $element, $selectortype, $not = null) {
        // Get the container node (exception if it doesn't exist).
        $containernode = $this->get_selected_node($selectortype, $element);
        $hasattribute = $containernode->hasAttribute($attribute);

        if ($not && $hasattribute) {
            $value = $containernode->getAttribute($attribute);
            // Should not be set but is.
            throw new ExpectationException(
                "The attribute \"{$attribute}\" should not be set but has a value of '{$value}'",
                $this->getSession()
            );
        } else if (!$not && !$hasattribute) {
            // Should be set but is not.
            throw new ExpectationException(
                "The attribute \"{$attribute}\" should be set but is not",
                $this->getSession()
            );
        }
    }

    /**
     * Checks whether there is an attribute on the given element that contains the specified text.
     *
     * @Then /^the "(?P<attribute_string>[^"]*)" attribute of "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should contain "(?P<text_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $attribute Name of attribute
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     * @param string $text Expected substring
     */
    public function the_attribute_of_should_contain($attribute, $element, $selectortype, $text) {
        // Get the container node (exception if it doesn't exist).
        $containernode = $this->get_selected_node($selectortype, $element);
        $value = $containernode->getAttribute($attribute);
        if ($value == null) {
            throw new ExpectationException('The attribute "' . $attribute. '" does not exist',
                    $this->getSession());
        } else if (strpos($value, $text) === false) {
            throw new ExpectationException('The attribute "' . $attribute .
                    '" does not contain "' . $text . '" (actual value: "' . $value . '")',
                    $this->getSession());
        }
    }

    /**
     * Checks that the attribute on the given element does not contain the specified text.
     *
     * @Then /^the "(?P<attribute_string>[^"]*)" attribute of "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should not contain "(?P<text_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $attribute Name of attribute
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     * @param string $text Expected substring
     */
    public function the_attribute_of_should_not_contain($attribute, $element, $selectortype, $text) {
        // Get the container node (exception if it doesn't exist).
        $containernode = $this->get_selected_node($selectortype, $element);
        $value = $containernode->getAttribute($attribute);
        if ($value == null) {
            throw new ExpectationException('The attribute "' . $attribute. '" does not exist',
                    $this->getSession());
        } else if (strpos($value, $text) !== false) {
            throw new ExpectationException('The attribute "' . $attribute .
                    '" contains "' . $text . '" (value: "' . $value . '")',
                    $this->getSession());
        }
    }

    /**
     * Checks the provided value exists in specific row/column of table.
     *
     * @Then /^"(?P<row_string>[^"]*)" row "(?P<column_string>[^"]*)" column of "(?P<table_string>[^"]*)" table should contain "(?P<value_string>[^"]*)"$/
     * @throws ElementNotFoundException
     * @param string $row row text which will be looked in.
     * @param string $column column text to search (or numeric value for the column position)
     * @param string $table table id/class/caption
     * @param string $value text to check.
     */
    public function row_column_of_table_should_contain($row, $column, $table, $value) {
        $tablenode = $this->get_selected_node('table', $table);
        $tablexpath = $tablenode->getXpath();

        $rowliteral = behat_context_helper::escape($row);
        $valueliteral = behat_context_helper::escape($value);

        $columnpositionxpath = $this->get_table_column_xpath($table, $column);

        // Check if value exists in specific row/column.
        // Get row xpath.
        // Some drivers make XPath relative to the current context, so use descendant.
        $rowxpath = $tablexpath . "/tbody/tr[descendant::*[@class='rowtitle'][normalize-space(.)=" . $rowliteral . "] | " . "
            descendant::th[normalize-space(.)=" . $rowliteral . "] | descendant::td[normalize-space(.)=" . $rowliteral . "]]";

        $columnvaluexpath = $rowxpath . $columnpositionxpath . "[contains(normalize-space(.)," . $valueliteral . ")]";

        // Looks for the requested node inside the container node.
        $coumnnode = $this->getSession()->getDriver()->find($columnvaluexpath);
        if (empty($coumnnode)) {
            $locatorexceptionmsg = $value . '" in "' . $row . '" row with column "' . $column;
            throw new ElementNotFoundException($this->getSession(), "\n$columnvaluexpath\n\n".'Column value', null, $locatorexceptionmsg);
        }
    }

    /**
     * Checks the provided value should not exist in specific row/column of table.
     *
     * @Then /^"(?P<row_string>[^"]*)" row "(?P<column_string>[^"]*)" column of "(?P<table_string>[^"]*)" table should not contain "(?P<value_string>[^"]*)"$/
     * @throws ElementNotFoundException
     * @param string $row row text which will be looked in.
     * @param string $column column text to search
     * @param string $table table id/class/caption
     * @param string $value text to check.
     */
    public function row_column_of_table_should_not_contain($row, $column, $table, $value) {
        try {
            $this->row_column_of_table_should_contain($row, $column, $table, $value);
        } catch (ElementNotFoundException $e) {
            // Table row/column doesn't contain this value. Nothing to do.
            return;
        }
        // Throw exception if found.
        throw new ExpectationException(
            '"' . $column . '" with value "' . $value . '" is present in "' . $row . '"  row for table "' . $table . '"',
            $this->getSession()
        );
    }

    /**
     * Get xpath for a row child that corresponds to the specified column header
     *
     * @param string $table table identifier that can be used with 'table' node selector (i.e. table title or CSS class)
     * @param string $column either text in the column header or the column number, such as -1-, -2-, etc
     *      When matching the column header it has to be either exact match of the whole header or an exact
     *      match of a text inside a link in the header.
     *      For example, to match "<a>First name</a> / <a>Last name</a>" you need to specify either "First name" or "Last name"
     * @return string
     */
    protected function get_table_column_xpath(string $table, string $column): string {
        $tablenode = $this->get_selected_node('table', $table);
        $tablexpath = $tablenode->getXpath();
        $columnliteral = behat_context_helper::escape($column);
        if (preg_match('/^-?(\d+)-?$/', $column, $columnasnumber)) {
            // Column indicated as a number, just use it as position of the column.
            $columnpositionxpath = "/child::*[position() = {$columnasnumber[1]}]";
        } else {
            // Header can be in thead or tbody (first row), following xpath should work.
            $theadheaderxpath = "thead/tr[1]/th[(normalize-space(.)={$columnliteral} or a[normalize-space(text())=" .
                    $columnliteral . "] or div[normalize-space(text())={$columnliteral}])]";
            $tbodyheaderxpath = "tbody/tr[1]/td[(normalize-space(.)={$columnliteral} or a[normalize-space(text())=" .
                    $columnliteral . "] or div[normalize-space(text())={$columnliteral}])]";

            // Check if column exists.
            $columnheaderxpath = "{$tablexpath}[{$theadheaderxpath} | {$tbodyheaderxpath}]";
            $columnheader = $this->getSession()->getDriver()->find($columnheaderxpath);
            if (empty($columnheader)) {
                if (strpos($column, '/') !== false) {
                    // We are not able to match headers consisting of several links, such as "First name / Last name".
                    // Instead we can match "First name" or "Last name" or "-1-" (column number).
                    throw new Exception("Column matching locator \"$column\" not found. ".
                        "If the column header contains multiple links, specify only one of the link texts. ".
                        "Otherwise, use the column number as the locator");
                }
                $columnexceptionmsg = $column . '" in table "' . $table . '"';
                throw new ElementNotFoundException($this->getSession(), "\n$columnheaderxpath\n\n".'Column',
                    null, $columnexceptionmsg);
            }
            // Following conditions were considered before finding column count.
            // 1. Table header can be in thead/tr/th or tbody/tr/td[1].
            // 2. First column can have th (Gradebook -> user report), so having lenient sibling check.
            $columnpositionxpath = "/child::*[position() = count({$tablexpath}/{$theadheaderxpath}" .
                "/preceding-sibling::*) + 1]";
        }
        return $columnpositionxpath;
    }

    /**
     * Find a table row where each of the specified columns matches and throw exception if not found
     *
     * @param string $table table locator
     * @param array $cells key is the column locator (name or index such as '-1-') and value is the text contents of the table cell
     */
    protected function ensure_table_row_exists(string $table, array $cells): void {
        $tablenode = $this->get_selected_node('table', $table);
        $tablexpath = $tablenode->getXpath();

        $columnconditions = [];
        foreach ($cells as $columnname => $value) {
            $valueliteral = behat_context_helper::escape($value);
            $columnpositionxpath = $this->get_table_column_xpath($table, $columnname);
            $columnconditions[] = '.' . $columnpositionxpath . "[contains(normalize-space(.)," . $valueliteral . ")]";
        }
        $rowxpath = $tablexpath . "/tbody/tr[" . join(' and ', $columnconditions) . ']';

        $rownode = $this->getSession()->getDriver()->find($rowxpath);
        if (empty($rownode)) {
            $rowlocator = array_map(fn($k) => "{$k} => {$cells[$k]}", array_keys($cells));
            throw new ElementNotFoundException($this->getSession(), "\n$rowxpath\n\n".'Table row', null, join(', ', $rowlocator));
        }
    }

    /**
     * Find a table row where each of the specified columns matches and throw exception if found
     *
     * @param string $table table locator
     * @param array $cells key is the column locator (name or index such as '-1-') and value is the text contents of the table cell
     */
    protected function ensure_table_row_does_not_exist(string $table, array $cells): void {
        try {
            $this->ensure_table_row_exists($table, $cells);
            // Throw exception if found.
        } catch (ElementNotFoundException $e) {
            // Table row/column doesn't contain this value. Nothing to do.
            return;
        }
        $rowlocator = array_map(fn($k) => "{$k} => {$cells[$k]}", array_keys($cells));
        throw new ExpectationException('Table row "' . join(', ', $rowlocator) .
            '" is present in the table "' . $table . '"', $this->getSession()
        );
    }

    /**
     * Checks that the provided value exist in table.
     *
     * First row may contain column headers or numeric indexes of the columns
     * (syntax -1- is also considered to be column index). Column indexes are
     * useful in case of multirow headers and/or presence of cells with colspan.
     *
     * @Then /^the following should exist in the "(?P<table_string>[^"]*)" table:$/
     * @throws ExpectationException
     * @param string $table name of table
     * @param TableNode $data table with first row as header and following values
     *        | Header 1 | Header 2 | Header 3 |
     *        | Value 1 | Value 2 | Value 3|
     */
    public function following_should_exist_in_the_table($table, TableNode $data) {
        $datahash = $data->getHash();
        if ($datahash && count($data->getRow(0)) != count($datahash[0])) {
            // Check that the number of columns in the hash is the same as the number of the columns in the first row.
            throw new coding_exception('Table contains duplicate column headers');
        }

        foreach ($datahash as $row) {
            $this->ensure_table_row_exists($table, $row);
        }
    }

    /**
     * Checks that the provided values do not exist in a table.
     *
     * If there are more than two columns, we check that NEITHER of the columns 2..n match
     * in the row where the first column matches
     *
     * @Then /^the following should not exist in the "(?P<table_string>[^"]*)" table:$/
     * @throws ExpectationException
     * @param string $table name of table
     * @param TableNode $data table with first row as header and following values
     *        | Header 1 | Header 2 | Header 3 |
     *        | Value 1 | Value 2 | Value 3|
     */
    public function following_should_not_exist_in_the_table($table, TableNode $data) {
        $datahash = $data->getHash();
        if ($datahash && count($data->getRow(0)) != count($datahash[0])) {
            // Check that the number of columns in the hash is the same as the number of the columns in the first row.
            throw new coding_exception('Table contains duplicate column headers');
        }

        foreach ($datahash as $value) {
            if (count($value) > 2) {
                // When there are more than two columns, what we really want to check is that for the rows
                // where the first column matches, NEITHER of the other columns match.
                $columns = array_keys($value);
                for ($i = 1; $i < count($columns); $i++) {
                    $this->ensure_table_row_does_not_exist($table, [
                        $columns[0] => $value[$columns[0]],
                        $columns[$i] => $value[$columns[$i]],
                    ]);
                }
            } else {
                $this->ensure_table_row_does_not_exist($table, $value);
            }
        }
    }

    /**
     * Given the text of a link, download the linked file and return the contents.
     *
     * This is a helper method used by {@link following_should_download_bytes()}
     * and {@link following_should_download_between_and_bytes()}
     *
     * @param string $link the text of the link.
     * @return string the content of the downloaded file.
     */
    public function download_file_from_link($link) {
        // Find the link.
        $linknode = $this->find_link($link);
        $this->ensure_node_is_visible($linknode);

        // Get the href and check it.
        $url = $linknode->getAttribute('href');
        if (!$url) {
            throw new ExpectationException('Download link does not have href attribute',
                    $this->getSession());
        }
        if (!preg_match('~^https?://~', $url)) {
            throw new ExpectationException('Download link not an absolute URL: ' . $url,
                    $this->getSession());
        }

        // Download the URL and check the size.
        $session = $this->getSession()->getCookie('MoodleSession');
        return download_file_content($url, array('Cookie' => 'MoodleSession=' . $session));
    }

    /**
     * Downloads the file from a link on the page and checks the size.
     *
     * Only works if the link has an href attribute. Javascript downloads are
     * not supported. Currently, the href must be an absolute URL.
     *
     * @Then /^following "(?P<link_string>[^"]*)" should download "(?P<expected_bytes>\d+)" bytes$/
     * @throws ExpectationException
     * @param string $link the text of the link.
     * @param number $expectedsize the expected file size in bytes.
     */
    public function following_should_download_bytes($link, $expectedsize) {
        $exception = new ExpectationException('Error while downloading data from ' . $link, $this->getSession());

        // It will stop spinning once file is downloaded or time out.
        $result = $this->spin(
            function($context, $args) {
                $link = $args['link'];
                return $this->download_file_from_link($link);
            },
            array('link' => $link),
            behat_base::get_extended_timeout(),
            $exception
        );

        // Check download size.
        $actualsize = (int)strlen($result);
        if ($actualsize !== (int)$expectedsize) {
            throw new ExpectationException('Downloaded data was ' . $actualsize .
                    ' bytes, expecting ' . $expectedsize, $this->getSession());
        }
    }

    /**
     * Downloads the file from a link on the page and checks the size is in a given range.
     *
     * Only works if the link has an href attribute. Javascript downloads are
     * not supported. Currently, the href must be an absolute URL.
     *
     * The range includes the endpoints. That is, a 10 byte file in considered to
     * be between "5" and "10" bytes, and between "10" and "20" bytes.
     *
     * @Then /^following "(?P<link_string>[^"]*)" should download between "(?P<min_bytes>\d+)" and "(?P<max_bytes>\d+)" bytes$/
     * @throws ExpectationException
     * @param string $link the text of the link.
     * @param number $minexpectedsize the minimum expected file size in bytes.
     * @param number $maxexpectedsize the maximum expected file size in bytes.
     */
    public function following_should_download_between_and_bytes($link, $minexpectedsize, $maxexpectedsize) {
        // If the minimum is greater than the maximum then swap the values.
        if ((int)$minexpectedsize > (int)$maxexpectedsize) {
            list($minexpectedsize, $maxexpectedsize) = array($maxexpectedsize, $minexpectedsize);
        }

        $exception = new ExpectationException('Error while downloading data from ' . $link, $this->getSession());

        // It will stop spinning once file is downloaded or time out.
        $result = $this->spin(
            function($context, $args) {
                $link = $args['link'];

                return $this->download_file_from_link($link);
            },
            array('link' => $link),
            behat_base::get_extended_timeout(),
            $exception
        );

        // Check download size.
        $actualsize = (int)strlen($result);
        if ($actualsize < $minexpectedsize || $actualsize > $maxexpectedsize) {
            throw new ExpectationException('Downloaded data was ' . $actualsize .
                    ' bytes, expecting between ' . $minexpectedsize . ' and ' .
                    $maxexpectedsize, $this->getSession());
        }
    }

    /**
     * Checks that the image on the page is the same as one of the fixture files
     *
     * @Then /^the image at "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should be identical to "(?P<filepath_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $element The locator of the image
     * @param string $selectortype The selector type
     * @param string $filepath path to the fixture file
     */
    public function the_image_at_should_be_identical_to($element, $selectortype, $filepath) {
        global $CFG;

        // Get the container node (exception if it doesn't exist).
        $containernode = $this->get_selected_node($selectortype, $element);
        $url = $containernode->getAttribute('src');
        if ($url == null) {
            throw new ExpectationException('Element does not have src attribute',
                $this->getSession());
        }
        $session = $this->getSession()->getCookie('MoodleSession');
        $content = download_file_content($url, array('Cookie' => 'MoodleSession=' . $session));

        // Get the content of the fixture file.
        // Replace 'admin/' if it is in start of path with $CFG->admin .
        if (substr($filepath, 0, 6) === 'admin/') {
            $filepath = $CFG->admin . DIRECTORY_SEPARATOR . substr($filepath, 6);
        }
        $filepath = str_replace('/', DIRECTORY_SEPARATOR, $filepath);
        $filepath = $CFG->dirroot . DIRECTORY_SEPARATOR . $filepath;
        if (!is_readable($filepath)) {
            throw new ExpectationException('The file to compare to does not exist.', $this->getSession());
        }
        $expectedcontent = file_get_contents($filepath);

        if ($content !== $expectedcontent) {
            throw new ExpectationException('Image is not identical to the fixture. Received ' .
            strlen($content) . ' bytes and expected ' . strlen($expectedcontent) . ' bytes', $this->getSession());
        }
    }

    /**
     * Prepare to detect whether or not a new page has loaded (or the same page reloaded) some time in the future.
     *
     * @Given /^I start watching to see if a new page loads$/
     */
    public function i_start_watching_to_see_if_a_new_page_loads() {
        if (!$this->running_javascript()) {
            throw new DriverException('Page load detection requires JavaScript.');
        }

        $session = $this->getSession();

        if ($this->pageloaddetectionrunning || $session->getPage()->find('xpath', $this->get_page_load_xpath())) {
            // If we find this node at this point we are already watching for a reload and the behat steps
            // are out of order. We will treat this as an error - really it needs to be fixed as it indicates a problem.
            throw new ExpectationException(
                'Page load expectation error: page reloads are already been watched for.', $session);
        }

        $this->pageloaddetectionrunning = true;

        $this->execute_script(
            'var span = document.createElement("span");
            span.setAttribute("data-rel", "' . self::PAGE_LOAD_DETECTION_STRING . '");
            span.setAttribute("style", "display: none;");
            document.body.appendChild(span);'
        );
    }

    /**
     * Verify that a new page has loaded (or the same page has reloaded) since the
     * last "I start watching to see if a new page loads" step.
     *
     * @Given /^a new page should have loaded since I started watching$/
     */
    public function a_new_page_should_have_loaded_since_i_started_watching() {
        $session = $this->getSession();

        // Make sure page load tracking was started.
        if (!$this->pageloaddetectionrunning) {
            throw new ExpectationException(
                'Page load expectation error: page load tracking was not started.', $session);
        }

        // As the node is inserted by code above it is either there or not, and we do not need spin and it is safe
        // to use the native API here which is great as exception handling (the alternative is slow).
        if ($session->getPage()->find('xpath', $this->get_page_load_xpath())) {
            // We don't want to find this node, if we do we have an error.
            throw new ExpectationException(
                'Page load expectation error: a new page has not been loaded when it should have been.', $session);
        }

        // Cancel the tracking of pageloaddetectionrunning.
        $this->pageloaddetectionrunning = false;
    }

    /**
     * Verify that a new page has not loaded (or the same page has reloaded) since the
     * last "I start watching to see if a new page loads" step.
     *
     * @Given /^a new page should not have loaded since I started watching$/
     */
    public function a_new_page_should_not_have_loaded_since_i_started_watching() {
        $session = $this->getSession();

        // Make sure page load tracking was started.
        if (!$this->pageloaddetectionrunning) {
            throw new ExpectationException(
                'Page load expectation error: page load tracking was not started.', $session);
        }

        // We use our API here as we can use the exception handling provided by it.
        $this->find(
            'xpath',
            $this->get_page_load_xpath(),
            new ExpectationException(
                'Page load expectation error: A new page has been loaded when it should not have been.',
                $this->getSession()
            )
        );
    }

    /**
     * Helper used by {@link a_new_page_should_have_loaded_since_i_started_watching}
     * and {@link a_new_page_should_not_have_loaded_since_i_started_watching}
     * @return string xpath expression.
     */
    protected function get_page_load_xpath() {
        return "//span[@data-rel = '" . self::PAGE_LOAD_DETECTION_STRING . "']";
    }

    /**
     * Wait unit user press Enter/Return key. Useful when debugging a scenario.
     *
     * @Then /^(?:|I )pause(?:| scenario execution)$/
     */
    public function i_pause_scenario_execution() {
        $message = "<colour:lightYellow>Paused. Press <colour:lightRed>Enter/Return<colour:lightYellow> to continue.";
        behat_util::pause($this->getSession(), $message);
    }

    /**
     * Presses a given button in the browser.
     * NOTE: Phantomjs and browserkit driver reloads page while navigating back and forward.
     *
     * @Then /^I press the "(back|forward|reload)" button in the browser$/
     * @param string $button the button to press.
     * @throws ExpectationException
     */
    public function i_press_in_the_browser($button) {
        $session = $this->getSession();

        if ($button == 'back') {
            $session->back();
        } else if ($button == 'forward') {
            $session->forward();
        } else if ($button == 'reload') {
            $session->reload();
        } else {
            throw new ExpectationException('Unknown browser button.', $session);
        }
    }

    /**
     * Send key presses to the browser without first changing focusing, or applying the key presses to a specific
     * element.
     *
     * Example usage of this step:
     *     When I type "Penguin"
     *
     * @When    I type :keys
     * @param   string $keys The key, or list of keys, to type
     */
    public function i_type(string $keys): void {
        // Certain keys, such as the newline character, must be converted to the appropriate character code.
        // Without this, keys will behave differently depending on the browser.
        $keylist = array_map(function($key): string {
            switch ($key) {
                case "\n":
                    return behat_keys::ENTER;
                default:
                    return $key;
            }
        }, str_split($keys));
        behat_base::type_keys($this->getSession(), $keylist);
    }

    /**
     * Press a named or character key with an optional set of modifiers.
     *
     * Supported named keys are:
     * - up
     * - down
     * - left
     * - right
     * - pageup|page_up
     * - pagedown|page_down
     * - home
     * - end
     * - insert
     * - delete
     * - backspace
     * - escape
     * - enter
     * - tab
     *
     * You can also use a single character for the key name e.g. 'Ctrl C'.
     *
     * Supported moderators are:
     * - shift
     * - ctrl
     * - alt
     * - meta
     *
     * Example usage of this new step:
     *     When I press the up key
     *     When I press the space key
     *     When I press the shift tab key
     *
     * Multiple moderator keys can be combined using the '+' operator, for example:
     *     When I press the ctrl+shift enter key
     *     When I press the ctrl + shift enter key
     *
     * @When    /^I press the (?P<modifiers_string>.* )?(?P<key_string>.*) key$/
     * @param   string $modifiers A list of keyboard modifiers, separated by the `+` character
     * @param   string $key The name of the key to press
     */
    public function i_press_named_key(string $modifiers, string $key): void {
        behat_base::require_javascript_in_session($this->getSession());

        $keys = [];

        foreach (explode('+', $modifiers) as $modifier) {
            switch (strtoupper(trim($modifier))) {
                case '':
                    break;
                case 'SHIFT':
                    $keys[] = behat_keys::SHIFT;
                    break;
                case 'CTRL':
                    $keys[] = behat_keys::CONTROL;
                    break;
                case 'ALT':
                    $keys[] = behat_keys::ALT;
                    break;
                case 'META':
                    $keys[] = behat_keys::META;
                    break;
                default:
                    throw new \coding_exception("Unknown modifier key '$modifier'}");
            }
        }

        $modifier = trim($key);
        switch (strtoupper($key)) {
            case 'UP':
                $keys[] = behat_keys::ARROW_UP;
                break;
            case 'DOWN':
                $keys[] = behat_keys::ARROW_DOWN;
                break;
            case 'LEFT':
                $keys[] = behat_keys::ARROW_LEFT;
                break;
            case 'RIGHT':
                $keys[] = behat_keys::ARROW_RIGHT;
                break;
            case 'HOME':
                $keys[] = behat_keys::HOME;
                break;
            case 'END':
                $keys[] = behat_keys::END;
                break;
            case 'INSERT':
                $keys[] = behat_keys::INSERT;
                break;
            case 'BACKSPACE':
                $keys[] = behat_keys::BACKSPACE;
                break;
            case 'DELETE':
                $keys[] = behat_keys::DELETE;
                break;
            case 'PAGEUP':
            case 'PAGE_UP':
                $keys[] = behat_keys::PAGE_UP;
                break;
            case 'PAGEDOWN':
            case 'PAGE_DOWN':
                $keys[] = behat_keys::PAGE_DOWN;
                break;
            case 'ESCAPE':
                $keys[] = behat_keys::ESCAPE;
                break;
            case 'ENTER':
                $keys[] = behat_keys::ENTER;
                break;
            case 'TAB':
                $keys[] = behat_keys::TAB;
                break;
            case 'SPACE':
                $keys[] = behat_keys::SPACE;
                break;
            case 'MULTIPLY':
                $keys[] = behat_keys::MULTIPLY;
                break;
            default:
                // You can enter a single ASCII character (e.g. a letter) to directly type that key.
                if (strlen($key) === 1) {
                    $keys[] = strtolower($key);
                } else {
                    throw new \coding_exception("Unknown key '$key'}");
                }
        }

        behat_base::type_keys($this->getSession(), $keys);
    }

    /**
     * Trigger a keydown event for a key on a specific element.
     *
     * @When /^I press key "(?P<key_string>(?:[^"]|\\")*)" in "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $key either char-code or character itself,
     *               may optionally be prefixed with ctrl-, alt-, shift- or meta-
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @throws DriverException
     * @throws ExpectationException
     */
    public function i_press_key_in_element($key, $element, $selectortype) {
        if (!$this->running_javascript()) {
            throw new DriverException('Key down step is not available with Javascript disabled');
        }
        // Gets the node based on the requested selector type and locator.
        $node = $this->get_selected_node($selectortype, $element);
        $modifier = null;
        $validmodifiers = array('ctrl', 'alt', 'shift', 'meta');
        $char = $key;
        if (strpos($key, '-')) {
            list($modifier, $char) = preg_split('/-/', $key, 2);
            $modifier = strtolower($modifier);
            if (!in_array($modifier, $validmodifiers)) {
                throw new ExpectationException(sprintf('Unknown key modifier: %s.', $modifier),
                    $this->getSession());
            }
        }
        if (is_numeric($char)) {
            $char = (int)$char;
        }

        $node->keyDown($char, $modifier);
        $node->keyPress($char, $modifier);
        $node->keyUp($char, $modifier);
    }

    /**
     * Press tab key on a specific element.
     *
     * @When /^I press tab key in "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @throws DriverException
     * @throws ExpectationException
     */
    public function i_post_tab_key_in_element($element, $selectortype) {
        if (!$this->running_javascript()) {
            throw new DriverException('Tab press step is not available with Javascript disabled');
        }
        // Gets the node based on the requested selector type and locator.
        $node = $this->get_selected_node($selectortype, $element);
        $this->execute('behat_general::i_click_on', [$node, 'NodeElement']);
        $this->execute('behat_general::i_press_named_key', ['', 'tab']);
    }

    /**
     * Checks if database family used is using one of the specified, else skip. (mysql, postgres, mssql, oracle, etc.)
     *
     * @Given /^database family used is one of the following:$/
     * @param TableNode $databasefamilies list of database.
     * @return void.
     * @throws \Moodle\BehatExtension\Exception\SkippedException
     */
    public function database_family_used_is_one_of_the_following(TableNode $databasefamilies) {
        global $DB;

        $dbfamily = $DB->get_dbfamily();

        // Check if used db family is one of the specified ones. If yes then return.
        foreach ($databasefamilies->getRows() as $dbfamilytocheck) {
            if ($dbfamilytocheck[0] == $dbfamily) {
                return;
            }
        }

        throw new \Moodle\BehatExtension\Exception\SkippedException();
    }

    /**
     * Checks if given plugin is installed, and skips the current scenario if not.
     *
     * @Given the :plugin plugin is installed
     * @param string $plugin frankenstyle plugin name, e.g. 'filter_embedquestion'.
     * @throws \Moodle\BehatExtension\Exception\SkippedException
     */
    public function plugin_is_installed(string $plugin): void {
        $path = core_component::get_component_directory($plugin);
        if (!is_readable($path . '/version.php')) {
            throw new \Moodle\BehatExtension\Exception\SkippedException(
                    'Skipping this scenario because the ' . $plugin . ' is not installed.');
        }
    }

    /**
     * Checks focus is with the given element.
     *
     * @Then /^the focused element is( not)? "(?P<node_string>(?:[^"]|\\")*)" "(?P<node_selector_string>[^"]*)"$/
     * @param string $not optional step verifier
     * @param string $nodeelement Element identifier
     * @param string $nodeselectortype Element type
     * @throws DriverException If not using JavaScript
     * @throws ExpectationException
     */
    public function the_focused_element_is($not, $nodeelement, $nodeselectortype) {
        if (!$this->running_javascript()) {
            throw new DriverException('Checking focus on an element requires JavaScript');
        }

        $element = $this->find($nodeselectortype, $nodeelement);
        $xpath = addslashes_js($element->getXpath());
        $script = 'return (function() { return document.activeElement === document.evaluate("' . $xpath . '",
                document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue; })(); ';
        $targetisfocused = $this->evaluate_script($script);
        if ($not == ' not') {
            if ($targetisfocused) {
                throw new ExpectationException("$nodeelement $nodeselectortype is focused", $this->getSession());
            }
        } else {
            if (!$targetisfocused) {
                throw new ExpectationException("$nodeelement $nodeselectortype is not focused", $this->getSession());
            }
        }
    }

    /**
     * Checks focus is with the given element.
     *
     * @Then /^the focused element is( not)? "(?P<n>(?:[^"]|\\")*)" "(?P<ns>[^"]*)" in the "(?P<c>(?:[^"]|\\")*)" "(?P<cs>[^"]*)"$/
     * @param string $not string optional step verifier
     * @param string $element Element identifier
     * @param string $selectortype Element type
     * @param string $nodeelement Element we look in
     * @param string $nodeselectortype The type of selector where we look in
     * @throws DriverException If not using JavaScript
     * @throws ExpectationException
     */
    public function the_focused_element_is_in_the($not, $element, $selectortype, $nodeelement, $nodeselectortype) {
        if (!$this->running_javascript()) {
            throw new DriverException('Checking focus on an element requires JavaScript');
        }
        $element = $this->get_node_in_container($selectortype, $element, $nodeselectortype, $nodeelement);
        $xpath = addslashes_js($element->getXpath());
        $script = 'return (function() { return document.activeElement === document.evaluate("' . $xpath . '",
                document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue; })(); ';
        $targetisfocused = $this->evaluate_script($script);
        if ($not == ' not') {
            if ($targetisfocused) {
                throw new ExpectationException("$nodeelement $nodeselectortype is focused", $this->getSession());
            }
        } else {
            if (!$targetisfocused) {
                throw new ExpectationException("$nodeelement $nodeselectortype is not focused", $this->getSession());
            }
        }
    }

    /**
     * Manually press tab key.
     *
     * @When /^I press( shift)? tab$/
     * @param string $shift string optional step verifier
     * @throws DriverException
     */
    public function i_manually_press_tab($shift = '') {
        if (empty($shift)) {
            $this->execute('behat_general::i_press_named_key', ['', 'tab']);
        } else {
            $this->execute('behat_general::i_press_named_key', ['shift', 'tab']);
        }
    }

    /**
     * Trigger click on node via javascript instead of actually clicking on it via pointer.
     * This function resolves the issue of nested elements.
     *
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" skipping visibility check$/
     * @param string $element
     * @param string $selectortype
     */
    public function i_click_on_skipping_visibility_check($element, $selectortype) {

        // Gets the node based on the requested selector type and locator.
        $node = $this->get_selected_node($selectortype, $element);
        $this->js_trigger_click($node);
    }

    /**
     * Checks, that the specified element contains the specified text a certain amount of times.
     * When running Javascript tests it also considers that texts may be hidden.
     *
     * @Then /^I should see "(?P<elementscount_number>\d+)" occurrences of "(?P<text_string>(?:[^"]|\\")*)" in the "(?P<element_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)"$/
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @param int    $elementscount How many occurrences of the element we look for.
     * @param string $text
     * @param string $element Element we look in.
     * @param string $selectortype The type of element where we are looking in.
     */
    public function i_should_see_occurrences_of_in_element($elementscount, $text, $element, $selectortype) {

        // Getting the container where the text should be found.
        $container = $this->get_selected_node($selectortype, $element);

        // Looking for all the matching nodes without any other descendant matching the
        // same xpath (we are using contains(., ....).
        $xpathliteral = behat_context_helper::escape($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
                "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        $nodes = $this->find_all('xpath', $xpath, false, $container);

        if ($this->running_javascript()) {
            $nodes = array_filter($nodes, function($node) {
                return $node->isVisible();
            });
        }

        if ($elementscount != count($nodes)) {
            throw new ExpectationException('Found '.count($nodes).' elements in column. Expected '.$elementscount,
                    $this->getSession());
        }
    }

    /**
     * Checks, that the specified element contains the specified node type a certain amount of times.
     * When running Javascript tests it also considers that texts may be hidden.
     *
     * @Then /^I should see "(?P<elementscount_number>\d+)" node occurrences of type "(?P<node_type>(?:[^"]|\\")*)" in the "(?P<element_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)"$/
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @param int    $elementscount How many occurrences of the element we look for.
     * @param string $nodetype
     * @param string $element Element we look in.
     * @param string $selectortype The type of element where we are looking in.
     */
    public function i_should_see_node_occurrences_of_type_in_element(int $elementscount, string $nodetype, string $element, string $selectortype) {

        // Getting the container where the text should be found.
        $container = $this->get_selected_node($selectortype, $element);

        $xpath = "/descendant-or-self::$nodetype [count(descendant::$nodetype) = 0]";

        $nodes = $this->find_all('xpath', $xpath, false, $container);

        if ($this->running_javascript()) {
            $nodes = array_filter($nodes, function($node) {
                return $node->isVisible();
            });
        }

        if ($elementscount != count($nodes)) {
            throw new ExpectationException('Found '.count($nodes).' elements in column. Expected '.$elementscount,
                $this->getSession());
        }
    }

    /**
     * Manually press enter key.
     *
     * @When /^I press enter/
     * @throws DriverException
     */
    public function i_manually_press_enter() {
        $this->execute('behat_general::i_press_named_key', ['', 'enter']);
    }

    /**
     * Visit a local URL relative to the behat root.
     *
     * @When I visit :localurl
     *
     * @param string|moodle_url $localurl The URL relative to the behat_wwwroot to visit.
     */
    public function i_visit($localurl): void {
        $localurl = new moodle_url($localurl);
        $this->getSession()->visit($this->locate_path($localurl->out_as_local_url(false)));
    }

    /**
     * Increase the webdriver timeouts.
     *
     * This should be reset between scenarios, or can be called again to decrease the timeouts.
     *
     * @Given I mark this test as slow setting a timeout factor of :factor
     */
    public function i_mark_this_test_as_long_running(int $factor = 2): void {
        $this->set_test_timeout_factor($factor);
    }

    /**
     * Click on a dynamic tab to load its content
     *
     * @Given /^I click on the "(?P<tab_string>(?:[^"]|\\")*)" dynamic tab$/
     *
     * @param string $tabname
     */
    public function i_click_on_the_dynamic_tab(string $tabname): void {
        $xpath = "//*[@id='dynamictabs-tabs'][descendant::a[contains(text(), '" . $this->escape($tabname) . "')]]";
        $this->execute('behat_general::i_click_on_in_the',
            [$tabname, 'link', $xpath, 'xpath_element']);
    }

    /**
     * Enable an specific plugin.
     *
     * @When /^I enable "(?P<plugin_string>(?:[^"]|\\")*)" "(?P<plugintype_string>[^"]*)" plugin$/
     * @param string $plugin Plugin we look for
     * @param string $plugintype The type of the plugin
     */
    public function i_enable_plugin($plugin, $plugintype) {
        $class = core_plugin_manager::resolve_plugininfo_class($plugintype);
        $class::enable_plugin($plugin, true);
    }

    /**
     * Set the default text editor to the named text editor.
     *
     * @Given the default editor is set to :editor
     * @param string $editor
     * @throws ExpectationException If the specified editor is not available.
     */
    public function the_default_editor_is_set_to(string $editor): void {
        global $CFG;

        // Check if the provided editor is available.
        if (!array_key_exists($editor, editors_get_available())) {
            throw new ExpectationException(
                "Unable to set the editor to {$editor} as it is not installed. The available editors are: " .
                    implode(', ', array_keys(editors_get_available())),
                $this->getSession()
            );
        }

        // Make the provided editor the default one in $CFG->texteditors by
        // moving it to the first [editor],atto,tiny,textarea on the list.
        $list = explode(',', $CFG->texteditors);
        array_unshift($list, $editor);
        $list = array_unique($list);

        // Set the list new list of editors.
        set_config('texteditors', implode(',', $list));
    }

    /**
     * Allow to check for minimal Moodle version.
     *
     * @Given the site is running Moodle version :minversion or higher
     * @param string $minversion The minimum version of Moodle required (inclusive).
     */
    public function the_site_is_running_moodle_version_or_higher(string $minversion): void {
        global $CFG;
        require_once($CFG->libdir . '/environmentlib.php');

        $currentversion = normalize_version(get_config('', 'release'));

        if (version_compare($currentversion, $minversion, '<')) {
            throw new Moodle\BehatExtension\Exception\SkippedException(
                'Site must be running Moodle version ' . $minversion . ' or higher'
            );
        }
    }

    /**
     * Allow to check for maximum Moodle version.
     *
     * @Given the site is running Moodle version :maxversion or lower
     * @param string $maxversion The maximum version of Moodle required (inclusive).
     */
    public function the_site_is_running_moodle_version_or_lower(string $maxversion): void {
        global $CFG;
        require_once($CFG->libdir . '/environmentlib.php');

        $currentversion = normalize_version(get_config('', 'release'));

        if (version_compare($currentversion, $maxversion, '>')) {
            throw new Moodle\BehatExtension\Exception\SkippedException(
                'Site must be running Moodle version ' . $maxversion . ' or lower'
            );
        }
    }

    /**
     * Check that the page title contains a given string.
     *
     * @Given the page title should contain ":title"
     * @param string $title The string that should be present on the page title.
     */
    public function the_page_title_should_contain(string $title): void {
        $session = $this->getSession();
        if ($this->running_javascript()) {
            // When running on JS, the page title can be changed via JS, so it's more reliable to get the actual page title via JS.
            $actualtitle = $session->evaluateScript("return document.title");
        } else {
            $titleelement = $session->getPage()->find('css', 'head title');
            if ($titleelement === null) {
                // Throw an exception if a page title is not present on the page.
                throw new ElementNotFoundException(
                    $this->getSession(),
                    '<title> element',
                    'css',
                    'head title'
                );
            }
            $actualtitle = $titleelement->getText();
        }

        if (!str_contains($actualtitle, $title)) {
            throw new ExpectationException(
                "'$title' was not found from the current page title '$actualtitle'",
                $session
            );
        }
    }
}
