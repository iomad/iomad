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
 * Steps definitions related with forms.
 *
 * @package    core
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../lib/behat/behat_field_manager.php');

use Behat\Gherkin\Node\{TableNode, PyStringNode};
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\{ElementNotFoundException, ExpectationException};

/**
 * Forms-related steps definitions.
 *
 * Note, Behat tests to verify that the steps defined here work as advertised
 * are kept in admin/tool/behat/tests/behat.
 *
 * @package    core
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_forms extends behat_base {

    /**
     * Presses button with specified id|name|title|alt|value.
     *
     * @When /^I press "(?P<button_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $button
     */
    public function press_button($button) {
        $this->execute('behat_general::i_click_on', [$button, 'button']);
    }

    /**
     * Press button with specified id|name|title|alt|value and switch to main window.
     *
     * @When /^I press "(?P<button_string>(?:[^"]|\\")*)" and switch to main window$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $button
     */
    public function press_button_and_switch_to_main_window($button) {
        // Ensures the button is present, before pressing.
        $buttonnode = $this->find_button($button);
        $buttonnode->press();
        $this->wait_for_pending_js();
        $this->look_for_exceptions();

        // Switch to main window.
        $this->execute('behat_general::switch_to_the_main_window');
    }

    /**
     * Fills a form with field/value data.
     *
     * @Given /^I set the following fields to these values:$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param TableNode $data
     */
    public function i_set_the_following_fields_to_these_values(TableNode $data) {

        // Expand all fields in case we have.
        $this->expand_all_fields();

        $datahash = $data->getRowsHash();

        // The action depends on the field type.
        foreach ($datahash as $locator => $value) {
            $this->set_field_value($locator, $value);
        }
    }

    /**
     * Fills a form with field/value data.
     *
     * @Given /^I set the following fields in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)" to these values:$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $containerelement Element we look in
     * @param string $containerselectortype The type of selector where we look in
     * @param TableNode $data
     */
    public function i_set_the_following_fields_in_container_to_these_values(
            $containerelement, $containerselectortype, TableNode $data) {

        // Expand all fields in case we have.
        $this->expand_all_fields();

        $datahash = $data->getRowsHash();

        // The action depends on the field type.
        foreach ($datahash as $locator => $value) {
            $this->set_field_value_in_container($locator, $value, $containerselectortype, $containerelement);
        }
    }

    /**
     * Expands all moodleform's fields, including collapsed fieldsets and advanced fields if they are present.
     * @Given /^I expand all fieldsets$/
     */
    public function i_expand_all_fieldsets() {
        $this->expand_all_fields();
    }

    /**
     * Expands all moodle form fieldsets if they exists.
     *
     * Externalized from i_expand_all_fields to call it from
     * other form-related steps without having to use steps-group calls.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find_all
     * @return void
     */
    protected function expand_all_fields() {
        // Expand only if JS mode, else not needed.
        if (!$this->running_javascript()) {
            return;
        }

        // We already know that we waited for the DOM and the JS to be loaded, even the editor
        // so, we will use the reduced timeout as it is a common task and we should save time.
        try {
            $this->wait_for_pending_js();
            // Expand all fieldsets link - which will only be there if there is more than one collapsible section.
            $expandallxpath = "//div[@class='collapsible-actions']" .
                "//a[contains(concat(' ', @class, ' '), ' collapsed ')]" .
                "//span[contains(concat(' ', @class, ' '), ' expandall ')]";
            // Else, look for the first expand fieldset link (old theme structure).
            $expandsectionold = "//legend[@class='ftoggler']" .
                    "//a[contains(concat(' ', @class, ' '), ' icons-collapse-expand ') and @aria-expanded = 'false']";
            // Else, look for the first expand fieldset link (current theme structure).
            $expandsectioncurrent = "//fieldset//div[contains(concat(' ', @class, ' '), ' ftoggler ')]" .
                    "//a[contains(concat(' ', @class, ' '), ' icons-collapse-expand ') and @aria-expanded = 'false']";

            $collapseexpandlink = $this->find('xpath', $expandallxpath . '|' . $expandsectionold . '|' . $expandsectioncurrent,
                    false, false, behat_base::get_reduced_timeout());
            $collapseexpandlink->click();
            $this->wait_for_pending_js();

        } catch (ElementNotFoundException $e) {
            // The behat_base::find() method throws an exception if there are no elements,
            // we should not fail a test because of this. We continue if there are not expandable fields.
        }

        // Different try & catch as we can have expanded fieldsets with advanced fields on them.
        try {

            // Expand all fields xpath.
            $showmorexpath = "//a[normalize-space(.)='" . get_string('showmore', 'form') . "']" .
                "[contains(concat(' ', normalize-space(@class), ' '), ' moreless-toggler')]";

            // We don't wait here as we already waited when getting the expand fieldsets links.
            if (!$showmores = $this->getSession()->getPage()->findAll('xpath', $showmorexpath)) {
                return;
            }

            if ($this->getSession()->getDriver() instanceof \DMore\ChromeDriver\ChromeDriver) {
                // Chrome Driver produces unique xpaths for each element.
                foreach ($showmores as $showmore) {
                    $showmore->click();
                }
            } else {
                // Funny thing about this, with findAll() we specify a pattern and each element matching the pattern
                // is added to the array with of xpaths with a [0], [1]... sufix, but when we click on an element it
                // does not matches the specified xpath anymore (now is a "Show less..." link) so [1] becomes [0],
                // that's why we always click on the first XPath match, will be always the next one.
                $iterations = count($showmores);
                for ($i = 0; $i < $iterations; $i++) {
                    $showmores[0]->click();
                }
            }

        } catch (ElementNotFoundException $e) {
            // We continue with the test.
        }

    }

    /**
     * Sets the field to wwwroot plus the given path. Include the first slash.
     *
     * @Given /^I set the field "(?P<field_string>(?:[^"]|\\")*)" to local url "(?P<field_path_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $field
     * @param string $path
     * @return void
     */
    public function i_set_the_field_to_local_url($field, $path) {
        global $CFG;
        $this->set_field_value($field, $CFG->wwwroot . $path);
    }

    /**
     * Sets the specified value to the field.
     *
     * @Given /^I set the field "(?P<field_string>(?:[^"]|\\")*)" to "(?P<field_value_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $field
     * @param string $value
     * @return void
     */
    public function i_set_the_field_to($field, $value) {
        $this->set_field_value($field, $value);
    }

    /**
     * Sets the specified value to the field.
     *
     * @Given /^I set the field "(?P<field_string>(?:[^"]|\\")*)" in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)" to "(?P<field_value_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $field
     * @param string $containerelement Element we look in
     * @param string $containerselectortype The type of selector where we look in
     * @param string $value
     */
    public function i_set_the_field_in_container_to($field, $containerelement, $containerselectortype, $value) {
        $this->set_field_value_in_container($field, $value, $containerselectortype, $containerelement);
    }

    /**
     * Press the key in the field to trigger the javascript keypress event
     *
     * Note that the character key will not actually be typed in the input field
     *
     * @Given /^I press key "(?P<key_string>(?:[^"]|\\")*)" in the field "(?P<field_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $key either char-code or character itself,
     *          may optionally be prefixed with ctrl-, alt-, shift- or meta-
     * @param string $field
     * @return void
     */
    public function i_press_key_in_the_field($key, $field) {
        if (!$this->running_javascript()) {
            throw new DriverException('Key press step is not available with Javascript disabled');
        }
        $fld = behat_field_manager::get_form_field_from_label($field, $this);
        $modifier = null;
        $char = $key;
        if (preg_match('/-/', $key)) {
            list($modifier, $char) = preg_split('/-/', $key, 2);
        }
        if (is_numeric($char)) {
            $char = (int)$char;
        }
        $fld->key_press($char, $modifier);
    }

    /**
     * Sets the specified value to the field.
     *
     * @Given /^I set the field "(?P<field_string>(?:[^"]|\\")*)" to multiline:$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $field
     * @param PyStringNode $value
     * @return void
     */
    public function i_set_the_field_to_multiline($field, PyStringNode $value) {
        $this->set_field_value($field, (string)$value);
    }

    /**
     * Sets the specified value to the field with xpath.
     *
     * @Given /^I set the field with xpath "(?P<fieldxpath_string>(?:[^"]|\\")*)" to "(?P<field_value_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $field
     * @param string $value
     * @return void
     */
    public function i_set_the_field_with_xpath_to($fieldxpath, $value) {
        $this->set_field_node_value($this->find('xpath', $fieldxpath), $value);
    }

    /**
     * Checks, the field matches the value.
     *
     * @Then /^the field "(?P<field_string>(?:[^"]|\\")*)" matches value "(?P<field_value_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $field
     * @param string $value
     * @return void
     */
    public function the_field_matches_value($field, $value) {

        // Get the field.
        $formfield = behat_field_manager::get_form_field_from_label($field, $this);

        // Checks if the provided value matches the current field value.
        if (!$formfield->matches($value)) {
            $fieldvalue = $formfield->get_value();
            throw new ExpectationException(
                'The \'' . $field . '\' value is \'' . $fieldvalue . '\', \'' . $value . '\' expected' ,
                $this->getSession()
            );
        }
    }

    /**
     * Checks, the field contains the value.
     *
     * @Then /^the field "(?P<field_string>(?:[^"]|\\")*)" (?P<doesnot_bool>does not )?match(?:es)* expression "(?P<expression_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $field The naem or reference to the field
     * @param bool $doesnot
     * @param string $expression The Perl-like regular expression, including any delimeters and flag
     * @return void
     */
    public function the_field_matches_expression(
        string $field,
        bool $doesnot,
        string $expression,
    ): void {
        // Get the field.
        $formfield = behat_field_manager::get_form_field_from_label($field, $this);

        // Checks if the provided value matches the current field value.
        $fieldvalue = $formfield->get_value();
        $matches = preg_match($expression, $fieldvalue);
        if ($matches === 1 && $doesnot) {
            throw new ExpectationException(
                "The '{$field}' field matches the expression '{$expression}' and it should not",
                $this->getSession()
            );
        } else if ($matches === 0 && !$doesnot) {
            throw new ExpectationException(
                "The '{$field}' field does not match the expression '{$expression}'",
                $this->getSession()
            );
        } else if ($matches === false) {
            throw new coding_exception(
                "The expression '{$expression}' was not valid",
            );
        }
    }

    /**
     * Checks, the field matches the value.
     *
     * @Then /^the field "(?P<field_string>(?:[^"]|\\")*)" matches multiline:$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $field
     * @param PyStringNode $value
     * @return void
     */
    public function the_field_matches_multiline($field, PyStringNode $value) {
        $this->the_field_matches_value($field, (string)$value);
    }

    /**
     * Checks, the field does not match the value.
     *
     * @Then /^the field "(?P<field_string>(?:[^"]|\\")*)" does not match value "(?P<field_value_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $field
     * @param string $value
     */
    public function the_field_does_not_match_value($field, $value) {

        // Get the field.
        $formfield = behat_field_manager::get_form_field_from_label($field, $this);

        // Checks if the provided value matches the current field value.
        if ($formfield->matches($value)) {
            throw new ExpectationException(
                'The \'' . $field . '\' value matches \'' . $value . '\' and it should not match it' ,
                $this->getSession()
            );
        }
    }

    /**
     * Checks, the field matches the value.
     *
     * @Then /^the field "(?P<field_string>(?:[^"]|\\")*)" in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)" matches value "(?P<field_value_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $field
     * @param string $containerelement Element we look in
     * @param string $containerselectortype The type of selector where we look in
     * @param string $value
     */
    public function the_field_in_container_matches_value($field, $containerelement, $containerselectortype, $value) {

        // Get the field.
        $node = $this->get_node_in_container('field', $field, $containerselectortype, $containerelement);
        $formfield = behat_field_manager::get_form_field($node, $this->getSession());

        // Checks if the provided value matches the current field value.
        if (!$formfield->matches($value)) {
            $fieldvalue = $formfield->get_value();
            throw new ExpectationException(
                    'The \'' . $field . '\' value is \'' . $fieldvalue . '\', \'' . $value . '\' expected' ,
                    $this->getSession()
            );
        }
    }

    /**
     * Checks, the field does not match the value.
     *
     * @Then /^the field "(?P<field_string>(?:[^"]|\\")*)" in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)" does not match value "(?P<field_value_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $field
     * @param string $containerelement Element we look in
     * @param string $containerselectortype The type of selector where we look in
     * @param string $value
     */
    public function the_field_in_container_does_not_match_value($field, $containerelement, $containerselectortype, $value) {

        // Get the field.
        $node = $this->get_node_in_container('field', $field, $containerselectortype, $containerelement);
        $formfield = behat_field_manager::get_form_field($node, $this->getSession());

        // Checks if the provided value matches the current field value.
        if ($formfield->matches($value)) {
            throw new ExpectationException(
                    'The \'' . $field . '\' value matches \'' . $value . '\' and it should not match it' ,
                    $this->getSession()
            );
        }
    }

    /**
     * Checks, the field matches the value.
     *
     * @Then /^the field with xpath "(?P<xpath_string>(?:[^"]|\\")*)" matches value "(?P<field_value_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $fieldxpath
     * @param string $value
     * @return void
     */
    public function the_field_with_xpath_matches_value($fieldxpath, $value) {

        // Get the field.
        $fieldnode = $this->find('xpath', $fieldxpath);
        $formfield = behat_field_manager::get_form_field($fieldnode, $this->getSession());

        // Checks if the provided value matches the current field value.
        if (!$formfield->matches($value)) {
            $fieldvalue = $formfield->get_value();
            throw new ExpectationException(
                'The \'' . $fieldxpath . '\' value is \'' . $fieldvalue . '\', \'' . $value . '\' expected' ,
                $this->getSession()
            );
        }
    }

    /**
     * Checks, the field does not match the value.
     *
     * @Then /^the field with xpath "(?P<xpath_string>(?:[^"]|\\")*)" does not match value "(?P<field_value_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $fieldxpath
     * @param string $value
     * @return void
     */
    public function the_field_with_xpath_does_not_match_value($fieldxpath, $value) {

        // Get the field.
        $fieldnode = $this->find('xpath', $fieldxpath);
        $formfield = behat_field_manager::get_form_field($fieldnode, $this->getSession());

        // Checks if the provided value matches the current field value.
        if ($formfield->matches($value)) {
            throw new ExpectationException(
                'The \'' . $fieldxpath . '\' value matches \'' . $value . '\' and it should not match it' ,
                $this->getSession()
            );
        }
    }

    /**
     * Checks, the provided field/value matches.
     *
     * @Then /^the following fields match these values:$/
     * @throws ExpectationException
     * @param TableNode $data Pairs of | field | value |
     */
    public function the_following_fields_match_these_values(TableNode $data) {

        // Expand all fields in case we have.
        $this->expand_all_fields();

        $datahash = $data->getRowsHash();

        // The action depends on the field type.
        foreach ($datahash as $locator => $value) {
            $this->the_field_matches_value($locator, $value);
        }
    }

    /**
     * Checks that the provided field/value pairs don't match.
     *
     * @Then /^the following fields do not match these values:$/
     * @throws ExpectationException
     * @param TableNode $data Pairs of | field | value |
     */
    public function the_following_fields_do_not_match_these_values(TableNode $data) {

        // Expand all fields in case we have.
        $this->expand_all_fields();

        $datahash = $data->getRowsHash();

        // The action depends on the field type.
        foreach ($datahash as $locator => $value) {
            $this->the_field_does_not_match_value($locator, $value);
        }
    }

    /**
     * Checks, the provided field/value matches.
     *
     * @Then /^the following fields in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)" match these values:$/
     * @throws ExpectationException
     * @param string $containerelement Element we look in
     * @param string $containerselectortype The type of selector where we look in
     * @param TableNode $data Pairs of | field | value |
     */
    public function the_following_fields_in_container_match_these_values(
            $containerelement, $containerselectortype, TableNode $data) {

        // Expand all fields in case we have.
        $this->expand_all_fields();

        $datahash = $data->getRowsHash();

        // The action depends on the field type.
        foreach ($datahash as $locator => $value) {
            $this->the_field_in_container_matches_value($locator, $containerelement, $containerselectortype, $value);
        }
    }

    /**
     * Checks that the provided field/value pairs don't match.
     *
     * @Then /^the following fields in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)" do not match these values:$/
     * @throws ExpectationException
     * @param string $containerelement Element we look in
     * @param string $containerselectortype The type of selector where we look in
     * @param TableNode $data Pairs of | field | value |
     */
    public function the_following_fields_in_container_do_not_match_these_values(
            $containerelement, $containerselectortype, TableNode $data) {

        // Expand all fields in case we have.
        $this->expand_all_fields();

        $datahash = $data->getRowsHash();

        // The action depends on the field type.
        foreach ($datahash as $locator => $value) {
            $this->the_field_in_container_does_not_match_value($locator, $containerelement, $containerselectortype, $value);
        }
    }

    /**
     * Checks, that given select box contains the specified option.
     *
     * @Then /^the "(?P<select_string>(?:[^"]|\\")*)" select box should contain "(?P<option_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $select The select element name
     * @param string $option The option text/value. Plain value or comma separated
     *                       values if multiple. Commas in multiple values escaped with backslash.
     */
    public function the_select_box_should_contain($select, $option) {

        $selectnode = $this->find_field($select);
        $multiple = $selectnode->hasAttribute('multiple');
        $optionsarr = array(); // Array of passed value/text options to test.

        if ($multiple) {
            // Can pass multiple comma separated, with valuable commas escaped with backslash.
            foreach (preg_replace('/\\\,/', ',',  preg_split('/(?<!\\\),/', $option)) as $opt) {
                $optionsarr[] = trim($opt);
            }
        } else {
            // Only one option has been passed.
            $optionsarr[] = trim($option);
        }

        // Now get all the values and texts in the select.
        $options = $selectnode->findAll('xpath', '//option');
        $values = array();
        foreach ($options as $opt) {
            $values[trim($opt->getValue())] = trim($opt->getText());
        }

        foreach ($optionsarr as $opt) {
            // Verify every option is a valid text or value.
            if (!in_array($opt, $values) && !array_key_exists($opt, $values)) {
                throw new ExpectationException(
                    'The select box "' . $select . '" does not contain the option "' . $opt . '"',
                    $this->getSession()
                );
            }
        }
    }

    /**
     * Checks, that given select box does not contain the specified option.
     *
     * @Then /^the "(?P<select_string>(?:[^"]|\\")*)" select box should not contain "(?P<option_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $select The select element name
     * @param string $option The option text/value. Plain value or comma separated
     *                       values if multiple. Commas in multiple values escaped with backslash.
     */
    public function the_select_box_should_not_contain($select, $option) {

        $selectnode = $this->find_field($select);
        $multiple = $selectnode->hasAttribute('multiple');
        $optionsarr = array(); // Array of passed value/text options to test.

        if ($multiple) {
            // Can pass multiple comma separated, with valuable commas escaped with backslash.
            foreach (preg_replace('/\\\,/', ',',  preg_split('/(?<!\\\),/', $option)) as $opt) {
                $optionsarr[] = trim($opt);
            }
        } else {
            // Only one option has been passed.
            $optionsarr[] = trim($option);
        }

        // Now get all the values and texts in the select.
        $options = $selectnode->findAll('xpath', '//option');
        $values = array();
        foreach ($options as $opt) {
            $values[trim($opt->getValue())] = trim($opt->getText());
        }

        foreach ($optionsarr as $opt) {
            // Verify every option is not a valid text or value.
            if (in_array($opt, $values) || array_key_exists($opt, $values)) {
                throw new ExpectationException(
                    'The select box "' . $select . '" contains the option "' . $opt . '"',
                    $this->getSession()
                );
            }
        }
    }

    /**
     * Generic field setter.
     *
     * Internal API method, a generic *I set "VALUE" to "FIELD" field*
     * could be created based on it.
     *
     * @param string $fieldlocator The pointer to the field, it will depend on the field type.
     * @param string $value
     * @return void
     */
    protected function set_field_value($fieldlocator, $value) {
        // We delegate to behat_form_field class, it will
        // guess the type properly as it is a select tag.
        $field = behat_field_manager::get_form_field_from_label($fieldlocator, $this);
        $field->set_value($value);
    }

    /**
     * Generic field setter to be used by chainable steps.
     *
     * @param NodeElement $fieldnode
     * @param string $value
     */
    public function set_field_node_value(NodeElement $fieldnode, string $value): void {
        $field = behat_field_manager::get_form_field($fieldnode, $this->getSession());
        $field->set_value($value);
    }

    /**
     * Generic field setter.
     *
     * Internal API method, a generic *I set "VALUE" to "FIELD" field*
     * could be created based on it.
     *
     * @param string $fieldlocator The pointer to the field, it will depend on the field type.
     * @param string $value the value to set
     * @param string $containerselectortype The type of selector where we look in
     * @param string $containerelement Element we look in
     */
    protected function set_field_value_in_container($fieldlocator, $value, $containerselectortype, $containerelement) {
        $node = $this->get_node_in_container('field', $fieldlocator, $containerselectortype, $containerelement);
        $this->set_field_node_value($node, $value);
    }

    /**
     * Select a value from single select and redirect.
     *
     * @Given /^I select "(?P<singleselect_option_string>(?:[^"]|\\")*)" from the "(?P<singleselect_name_string>(?:[^"]|\\")*)" singleselect$/
     */
    public function i_select_from_the_singleselect($option, $singleselect) {

        $this->execute('behat_forms::i_set_the_field_to', array($this->escape($singleselect), $this->escape($option)));

        if (!$this->running_javascript()) {
            // Press button in the specified select container.
            $containerxpath = "//div[" .
                "(contains(concat(' ', normalize-space(@class), ' '), ' singleselect ') " .
                    "or contains(concat(' ', normalize-space(@class), ' '), ' urlselect ')".
                ") and (
                .//label[contains(normalize-space(string(.)), '" . $singleselect . "')] " .
                    "or .//select[(./@name='" . $singleselect . "' or ./@id='". $singleselect . "')]" .
                ")]";

            $this->execute('behat_general::i_click_on_in_the',
                array(get_string('go'), "button", $containerxpath, "xpath_element")
            );
        }
    }

    /**
     * Select item from autocomplete list.
     *
     * @Given /^I click on "([^"]*)" item in the autocomplete list$/
     *
     * @param string $item
     */
    public function i_click_on_item_in_the_autocomplete_list($item) {
        $xpathtarget = "//ul[@class='form-autocomplete-suggestions']//*[contains(concat('|', string(.), '|'),'|" . $item . "|')]";

        $this->execute('behat_general::i_click_on', [$xpathtarget, 'xpath_element']);
    }

    /**
     * Open the auto-complete suggestions list (Assuming there is only one on the page.).
     *
     * @Given I open the autocomplete suggestions list
     * @Given I open the autocomplete suggestions list in the :container :containertype
     */
    public function i_open_the_autocomplete_suggestions_list($container = null, $containertype = null) {
        $csstarget = ".form-autocomplete-downarrow";
        if ($container && $containertype) {
            $this->execute('behat_general::i_click_on_in_the', [$csstarget, 'css_element', $container, $containertype]);
        } else {
            $this->execute('behat_general::i_click_on', [$csstarget, 'css_element']);
        }
    }

    /**
     * Expand the given autocomplete list
     *
     * @Given /^I expand the "(?P<field_string>(?:[^"]|\\")*)" autocomplete$/
     *
     * @param string $field Field name
     */
    public function i_expand_the_autocomplete($field) {
        $csstarget = '.form-autocomplete-downarrow';
        $node = $this->get_node_in_container('css_element', $csstarget, 'form_row', $field);
        $node->click();
    }

    /**
     * Assert the given option exist in the given autocomplete list
     *
     * @Given /^I should see "(?P<option_string>(?:[^"]|\\")*)" in the list of options for the "(?P<field_string>(?:[^"]|\\")*)" autocomplete$$/
     *
     * @param string $option Name of option
     * @param string $field Field name
     */
    public function i_should_see_in_the_list_of_option_for_the_autocomplete($option, $field) {
        $xpathtarget = "//div[contains(@class, 'form-autocomplete-selection') and contains(.//div, '" . $option . "')]";
        $node = $this->get_node_in_container('xpath_element', $xpathtarget, 'form_row', $field);
        $this->ensure_node_is_visible($node);
    }

    /**
     * Checks whether the select menu contains an option with specified text or not.
     *
     * @Then the :name select menu should contain :option
     * @Then the :name select menu should :not contain :option
     *
     * @throws ExpectationException When the expectation is not satisfied
     * @param string $label The label of the select menu element
     * @param string $option The string that is used to identify an option within the select menu. If the string
     *                       has two items separated by '>' (ex. "Group > Option"), the first item ("Group") will be
     *                       used to identify a particular group within the select menu, while the second ("Option")
     *                       will be used to identify an option within that group. Otherwise, a string with a single
     *                       item (ex. "Option") will be used to identify an option within the select menu regardless
     *                       of any existing groups.
     * @param string|null $not If set, the select menu should not contain the specified option. If null, the option
     *                         should be present.
     */
    public function the_select_menu_should_contain(string $label, string $option, ?string $not = null) {

        $field = behat_field_manager::get_form_field_from_label($label, $this);

        if (!method_exists($field, 'has_option')) {
            throw new coding_exception('Field does not support the has_option function.');
        }

        // If the select menu contains the specified option but it should not.
        if ($field->has_option($option) && $not) {
            throw new ExpectationException(
                "The select menu should not contain \"{$option}\" but it does.",
                $this->getSession()
            );
        }
        // If the select menu does not contain the specified option but it should.
        if (!$field->has_option($option) && !$not) {
            throw new ExpectationException(
                "The select menu should contain \"{$option}\" but it does not.",
                $this->getSession()
            );
        }
    }
}
