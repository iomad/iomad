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
 * Behat course-related steps definitions.
 *
 * @package    core_course
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Course-related steps definitions.
 *
 * @package    core_course
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_course extends behat_base {

    /**
     * Turns editing mode on.
     * @Given /^I turn editing mode on$/
     */
    public function i_turn_editing_mode_on() {

        try {
            $this->execute("behat_forms::press_button", get_string('turneditingon'));
        } catch (Exception $e) {
            $this->execute("behat_navigation::i_navigate_to_in_current_page_administration", [get_string('turneditingon')]);
        }
    }

    /**
     * Turns editing mode off.
     * @Given /^I turn editing mode off$/
     */
    public function i_turn_editing_mode_off() {

        try {
            $this->execute("behat_forms::press_button", get_string('turneditingoff'));
        } catch (Exception $e) {
            $this->execute("behat_navigation::i_navigate_to_in_current_page_administration", [get_string('turneditingoff')]);
        }
    }

    /**
     * Creates a new course with the provided table data matching course settings names with the desired values.
     *
     * @Given /^I create a course with:$/
     * @param TableNode $table The course data
     */
    public function i_create_a_course_with(TableNode $table) {

        // Go to course management page.
        $this->i_go_to_the_courses_management_page();
        // Ensure you are on course management page.
        $this->execute("behat_course::i_should_see_the_courses_management_page", get_string('categories'));

        // Select Miscellaneous category.
        $this->i_click_on_category_in_the_management_interface(get_string('miscellaneous'));
        $this->execute("behat_course::i_should_see_the_courses_management_page", get_string('categoriesandcoures'));

        // Click create new course.
        $this->execute('behat_general::i_click_on_in_the',
            array(get_string('createnewcourse'), "link", "#course-listing", "css_element")
        );

        // If the course format is one of the fields we change how we
        // fill the form as we need to wait for the form to be set.
        $rowshash = $table->getRowsHash();
        $formatfieldrefs = array(get_string('format'), 'format', 'id_format');
        foreach ($formatfieldrefs as $fieldref) {
            if (!empty($rowshash[$fieldref])) {
                $formatfield = $fieldref;
            }
        }

        // Setting the format separately.
        if (!empty($formatfield)) {

            // Removing the format field from the TableNode.
            $rows = $table->getRows();
            $formatvalue = $rowshash[$formatfield];
            foreach ($rows as $key => $row) {
                if ($row[0] == $formatfield) {
                    unset($rows[$key]);
                }
            }
            $table = new TableNode($rows);

            // Adding a forced wait until editors are loaded as otherwise selenium sometimes tries clicks on the
            // format field when the editor is being rendered and the click misses the field coordinates.
            $this->execute("behat_forms::i_expand_all_fieldsets");

            $this->execute("behat_forms::i_set_the_field_to", array($formatfield, $formatvalue));
        }

        // Set form fields.
        $this->execute("behat_forms::i_set_the_following_fields_to_these_values", $table);

        // Save course settings.
        $this->execute("behat_forms::press_button", get_string('savechangesanddisplay'));

    }

    /**
     * Goes to the system courses/categories management page.
     *
     * @Given /^I go to the courses management page$/
     */
    public function i_go_to_the_courses_management_page() {

        $parentnodes = get_string('administrationsite') . ' > ' . get_string('courses', 'admin');

        // Go to home page.
        $this->execute("behat_general::i_am_on_homepage");

        // Navigate to course management page via navigation block.
        $this->execute("behat_navigation::i_navigate_to_node_in",
            array(get_string('coursemgmt', 'admin'), $parentnodes)
        );

    }

    /**
     * Adds the selected activity/resource filling the form data with the specified field/value pairs. Sections 0 and 1 are also allowed on frontpage.
     *
     * @When /^I add a "(?P<activity_or_resource_name_string>(?:[^"]|\\")*)" to section "(?P<section_number>\d+)" and I fill the form with:$/
     * @param string $activity The activity name
     * @param int $section The section number
     * @param TableNode $data The activity field/value data
     */
    public function i_add_to_section_and_i_fill_the_form_with($activity, $section, TableNode $data) {

        // Add activity to section.
        $this->execute("behat_course::i_add_to_section",
            array($this->escape($activity), $this->escape($section))
        );

        // Wait to be redirected.
        $this->execute('behat_general::wait_until_the_page_is_ready');

        // Set form fields.
        $this->execute("behat_forms::i_set_the_following_fields_to_these_values", $data);

        // Save course settings.
        $this->execute("behat_forms::press_button", get_string('savechangesandreturntocourse'));
    }

    /**
     * Opens the activity chooser and opens the activity/resource form page. Sections 0 and 1 are also allowed on frontpage.
     *
     * @Given /^I add a "(?P<activity_or_resource_name_string>(?:[^"]|\\")*)" to section "(?P<section_number>\d+)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $activity
     * @param int $section
     */
    public function i_add_to_section($activity, $section) {

        if ($this->getSession()->getPage()->find('css', 'body#page-site-index') && (int)$section <= 1) {
            // We are on the frontpage.
            if ($section) {
                // Section 1 represents the contents on the frontpage.
                $sectionxpath = "//body[@id='page-site-index']/descendant::div[contains(concat(' ',normalize-space(@class),' '),' sitetopic ')]";
            } else {
                // Section 0 represents "Site main menu" block.
                $sectionxpath = "//div[contains(concat(' ',normalize-space(@class),' '),' block_site_main_menu ')]";
            }
        } else {
            // We are inside the course.
            $sectionxpath = "//li[@id='section-" . $section . "']";
        }

        $activityliteral = behat_context_helper::escape(ucfirst($activity));

        if ($this->running_javascript()) {

            // Clicks add activity or resource section link.
            $sectionxpath = $sectionxpath . "/descendant::div[@class='section-modchooser']/span/a";
            $sectionnode = $this->find('xpath', $sectionxpath);
            $sectionnode->click();

            // Clicks the selected activity if it exists.
            $activityxpath = "//div[@id='chooseform']/descendant::label" .
                "/descendant::span[contains(concat(' ', normalize-space(@class), ' '), ' typename ')]" .
                "[normalize-space(.)=$activityliteral]" .
                "/parent::label/child::input";
            $activitynode = $this->find('xpath', $activityxpath);
            $activitynode->doubleClick();

        } else {
            // Without Javascript.

            // Selecting the option from the select box which contains the option.
            $selectxpath = $sectionxpath . "/descendant::div[contains(concat(' ', normalize-space(@class), ' '), ' section_add_menus ')]" .
                "/descendant::select[option[normalize-space(.)=$activityliteral]]";
            $selectnode = $this->find('xpath', $selectxpath);
            $selectnode->selectOption($activity);

            // Go button.
            $gobuttonxpath = $selectxpath . "/ancestor::form/descendant::input[@type='submit']";
            $gobutton = $this->find('xpath', $gobuttonxpath);
            $gobutton->click();
        }

    }


    /**
     * Opens a section edit menu if it is not already opened.
     *
     * @Given /^I open section "(?P<section_number>\d+)" edit menu$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $sectionnumber
     */
    public function i_open_section_edit_menu($sectionnumber) {
        if (!$this->running_javascript()) {
            throw new DriverException('Section edit menu not available when Javascript is disabled');
        }

        // Wait for section to be available, before clicking on the menu.
        $this->i_wait_until_section_is_available($sectionnumber);

        // If it is already opened we do nothing.
        $xpath = $this->section_exists($sectionnumber);
        $xpath .= "/descendant::div[contains(@class, 'section-actions')]/descendant::a[contains(@class, 'textmenu')]";

        $exception = new ExpectationException('Section "' . $sectionnumber . '" was not found', $this->getSession());
        $menu = $this->find('xpath', $xpath, $exception);
        $menu->click();
        $this->i_wait_until_section_is_available($sectionnumber);
    }

    /**
     * Deletes course section.
     *
     * @Given /^I delete section "(?P<section_number>\d+)"$/
     * @param int $sectionnumber The section number
     */
    public function i_delete_section($sectionnumber) {
        // Ensures the section exists.
        $xpath = $this->section_exists($sectionnumber);

        // We need to know the course format as the text strings depends on them.
        $courseformat = $this->get_course_format();
        if (get_string_manager()->string_exists('deletesection', $courseformat)) {
            $strdelete = get_string('deletesection', $courseformat);
        } else {
            $strdelete = get_string('deletesection');
        }

        // If javascript is on, link is inside a menu.
        if ($this->running_javascript()) {
            $this->i_open_section_edit_menu($sectionnumber);
        }

        // Click on delete link.
        $this->execute('behat_general::i_click_on_in_the',
            array($strdelete, "link", $this->escape($xpath), "xpath_element")
        );

    }

    /**
     * Turns course section highlighting on.
     *
     * @Given /^I turn section "(?P<section_number>\d+)" highlighting on$/
     * @param int $sectionnumber The section number
     */
    public function i_turn_section_highlighting_on($sectionnumber) {

        // Ensures the section exists.
        $xpath = $this->section_exists($sectionnumber);

        // If javascript is on, link is inside a menu.
        if ($this->running_javascript()) {
            $this->i_open_section_edit_menu($sectionnumber);
        }

        // Click on highlight topic link.
        $this->execute('behat_general::i_click_on_in_the',
            array(get_string('markthistopic'), "link", $this->escape($xpath), "xpath_element")
        );
    }

    /**
     * Turns course section highlighting off.
     *
     * @Given /^I turn section "(?P<section_number>\d+)" highlighting off$/
     * @param int $sectionnumber The section number
     */
    public function i_turn_section_highlighting_off($sectionnumber) {

        // Ensures the section exists.
        $xpath = $this->section_exists($sectionnumber);

        // If javascript is on, link is inside a menu.
        if ($this->running_javascript()) {
            $this->i_open_section_edit_menu($sectionnumber);
        }

        // Click on un-highlight topic link.
        $this->execute('behat_general::i_click_on_in_the',
            array(get_string('markedthistopic'), "link", $this->escape($xpath), "xpath_element")
        );
    }

    /**
     * Shows the specified hidden section. You need to be in the course page and on editing mode.
     *
     * @Given /^I show section "(?P<section_number>\d+)"$/
     * @param int $sectionnumber
     */
    public function i_show_section($sectionnumber) {
        $showlink = $this->show_section_link_exists($sectionnumber);

        // Ensure section edit menu is open before interacting with it.
        if ($this->running_javascript()) {
            $this->i_open_section_edit_menu($sectionnumber);
        }
        $showlink->click();

        if ($this->running_javascript()) {
            $this->getSession()->wait(self::TIMEOUT * 1000, self::PAGE_READY_JS);
            $this->i_wait_until_section_is_available($sectionnumber);
        }
    }

    /**
     * Hides the specified visible section. You need to be in the course page and on editing mode.
     *
     * @Given /^I hide section "(?P<section_number>\d+)"$/
     * @param int $sectionnumber
     */
    public function i_hide_section($sectionnumber) {
        // Ensures the section exists.
        $xpath = $this->section_exists($sectionnumber);

        // We need to know the course format as the text strings depends on them.
        $courseformat = $this->get_course_format();
        if (get_string_manager()->string_exists('hidefromothers', $courseformat)) {
            $strhide = get_string('hidefromothers', $courseformat);
        } else {
            $strhide = get_string('hidesection');
        }

        // If javascript is on, link is inside a menu.
        if ($this->running_javascript()) {
            $this->i_open_section_edit_menu($sectionnumber);
        }

        // Click on delete link.
        $this->execute('behat_general::i_click_on_in_the',
              array($strhide, "link", $this->escape($xpath), "xpath_element")
        );

        if ($this->running_javascript()) {
            $this->getSession()->wait(self::TIMEOUT * 1000, self::PAGE_READY_JS);
            $this->i_wait_until_section_is_available($sectionnumber);
        }
    }

    /**
     * Go to editing section page for specified section number. You need to be in the course page and on editing mode.
     *
     * @Given /^I edit the section "(?P<section_number>\d+)"$/
     * @param int $sectionnumber
     */
    public function i_edit_the_section($sectionnumber) {
        // If javascript is on, link is inside a menu.
        if ($this->running_javascript()) {
            $this->i_open_section_edit_menu($sectionnumber);
        }

        // We need to know the course format as the text strings depends on them.
        $courseformat = $this->get_course_format();
        if ($sectionnumber > 0 && get_string_manager()->string_exists('editsection', $courseformat)) {
            $stredit = get_string('editsection', $courseformat);
        } else {
            $stredit = get_string('editsection');
        }

        // Click on un-highlight topic link.
        $this->execute('behat_general::i_click_on_in_the',
            array($stredit, "link", "#section-" . $sectionnumber, "css_element")
        );

    }

    /**
     * Edit specified section and fill the form data with the specified field/value pairs.
     *
     * @When /^I edit the section "(?P<section_number>\d+)" and I fill the form with:$/
     * @param int $sectionnumber The section number
     * @param TableNode $data The activity field/value data
     */
    public function i_edit_the_section_and_i_fill_the_form_with($sectionnumber, TableNode $data) {

        // Edit given section.
        $this->execute("behat_course::i_edit_the_section", $sectionnumber);

        // Set form fields.
        $this->execute("behat_forms::i_set_the_following_fields_to_these_values", $data);

        // Save section settings.
        $this->execute("behat_forms::press_button", get_string('savechanges'));
    }

    /**
     * Checks if the specified course section hightlighting is turned on. You need to be in the course page on editing mode.
     *
     * @Then /^section "(?P<section_number>\d+)" should be highlighted$/
     * @throws ExpectationException
     * @param int $sectionnumber The section number
     */
    public function section_should_be_highlighted($sectionnumber) {

        // Ensures the section exists.
        $xpath = $this->section_exists($sectionnumber);

        // The important checking, we can not check the img.
        $this->execute('behat_general::should_exist_in_the', ['This topic is highlighted as the current topic', 'icon', $xpath, 'xpath_element']);
    }

    /**
     * Checks if the specified course section highlighting is turned off. You need to be in the course page on editing mode.
     *
     * @Then /^section "(?P<section_number>\d+)" should not be highlighted$/
     * @throws ExpectationException
     * @param int $sectionnumber The section number
     */
    public function section_should_not_be_highlighted($sectionnumber) {

        // We only catch ExpectationException, ElementNotFoundException should be thrown if the specified section does not exist.
        try {
            $this->section_should_be_highlighted($sectionnumber);
        } catch (ExpectationException $e) {
            // ExpectedException means that it is not highlighted.
            return;
        }

        throw new ExpectationException('The "' . $sectionnumber . '" section is highlighted', $this->getSession());
    }

    /**
     * Checks that the specified section is visible. You need to be in the course page. It can be used being logged as a student and as a teacher on editing mode.
     *
     * @Then /^section "(?P<section_number>\d+)" should be hidden$/
     * @throws ExpectationException
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param int $sectionnumber
     */
    public function section_should_be_hidden($sectionnumber) {

        $sectionxpath = $this->section_exists($sectionnumber);

        // Preventive in case there is any action in progress.
        // Adding it here because we are interacting (click) with
        // the elements, not necessary when we just find().
        $this->i_wait_until_section_is_available($sectionnumber);

        // Section should be hidden.
        $exception = new ExpectationException('The section is not hidden', $this->getSession());
        $this->find('xpath', $sectionxpath . "[contains(concat(' ', normalize-space(@class), ' '), ' hidden ')]", $exception);
    }

    /**
     * Checks that all actiities in the specified section are hidden. You need to be in the course page. It can be used being logged as a student and as a teacher on editing mode.
     *
     * @Then /^all activities in section "(?P<section_number>\d+)" should be hidden$/
     * @throws ExpectationException
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param int $sectionnumber
     */
    public function section_activities_should_be_hidden($sectionnumber) {
        $sectionxpath = $this->section_exists($sectionnumber);

        // Preventive in case there is any action in progress.
        // Adding it here because we are interacting (click) with
        // the elements, not necessary when we just find().
        $this->i_wait_until_section_is_available($sectionnumber);

        // The checking are different depending on user permissions.
        if ($this->is_course_editor()) {

            // The section must be hidden.
            $this->show_section_link_exists($sectionnumber);

            // If there are activities they should be hidden and the visibility icon should not be available.
            if ($activities = $this->get_section_activities($sectionxpath)) {

                $dimmedexception = new ExpectationException('There are activities that are not dimmed', $this->getSession());
                foreach ($activities as $activity) {
                    // Dimmed.
                    $this->find('xpath', "//div[contains(concat(' ', normalize-space(@class), ' '), ' activityinstance ')]" .
                        "//a[contains(concat(' ', normalize-space(@class), ' '), ' dimmed ')]", $dimmedexception, $activity);
                }
            }
        } else {
            // There shouldn't be activities.
            if ($this->get_section_activities($sectionxpath)) {
                throw new ExpectationException('There are activities in the section and they should be hidden', $this->getSession());
            }
        }

    }

    /**
     * Checks that the specified section is visible. You need to be in the course page. It can be used being logged as a student and as a teacher on editing mode.
     *
     * @Then /^section "(?P<section_number>\d+)" should be visible$/
     * @throws ExpectationException
     * @param int $sectionnumber
     */
    public function section_should_be_visible($sectionnumber) {

        $sectionxpath = $this->section_exists($sectionnumber);

        // Section should not be hidden.
        $xpath = $sectionxpath . "[not(contains(concat(' ', normalize-space(@class), ' '), ' hidden '))]";
        if (!$this->getSession()->getPage()->find('xpath', $xpath)) {
            throw new ExpectationException('The section is hidden', $this->getSession());
        }

        // Edit menu should be visible.
        if ($this->is_course_editor()) {
            $xpath = $sectionxpath .
                     "/descendant::div[contains(@class, 'section-actions')]" .
                     "/descendant::a[contains(@class, 'textmenu')]";
            if (!$this->getSession()->getPage()->find('xpath', $xpath)) {
                throw new ExpectationException('The section edit menu is not available', $this->getSession());
            }
        }
    }

    /**
     * Moves up the specified section, this step only works with Javascript disabled. Editing mode should be on.
     *
     * @Given /^I move up section "(?P<section_number>\d+)"$/
     * @throws DriverException Step not available when Javascript is enabled
     * @param int $sectionnumber
     */
    public function i_move_up_section($sectionnumber) {

        if ($this->running_javascript()) {
            throw new DriverException('Move a section up step is not available with Javascript enabled');
        }

        // Ensures the section exists.
        $sectionxpath = $this->section_exists($sectionnumber);

        // If javascript is on, link is inside a menu.
        if ($this->running_javascript()) {
            $this->i_open_section_edit_menu($sectionnumber);
        }

        // Follows the link
        $moveuplink = $this->get_node_in_container('link', get_string('moveup'), 'xpath_element', $sectionxpath);
        $moveuplink->click();
    }

    /**
     * Moves down the specified section, this step only works with Javascript disabled. Editing mode should be on.
     *
     * @Given /^I move down section "(?P<section_number>\d+)"$/
     * @throws DriverException Step not available when Javascript is enabled
     * @param int $sectionnumber
     */
    public function i_move_down_section($sectionnumber) {

        if ($this->running_javascript()) {
            throw new DriverException('Move a section down step is not available with Javascript enabled');
        }

        // Ensures the section exists.
        $sectionxpath = $this->section_exists($sectionnumber);

        // If javascript is on, link is inside a menu.
        if ($this->running_javascript()) {
            $this->i_open_section_edit_menu($sectionnumber);
        }

        // Follows the link
        $movedownlink = $this->get_node_in_container('link', get_string('movedown'), 'xpath_element', $sectionxpath);
        $movedownlink->click();
    }

    /**
     * Checks that the specified activity is visible. You need to be in the course page. It can be used being logged as a student and as a teacher on editing mode.
     *
     * @Then /^"(?P<activity_or_resource_string>(?:[^"]|\\")*)" activity should be visible$/
     * @param string $activityname
     * @throws ExpectationException
     */
    public function activity_should_be_visible($activityname) {

        // The activity must exists and be visible.
        $activitynode = $this->get_activity_node($activityname);

        if ($this->is_course_editor()) {

            // The activity should not be dimmed.
            try {
                $xpath = "/descendant-or-self::a[contains(concat(' ', normalize-space(@class), ' '), ' dimmed ')] | ".
                         "/descendant-or-self::div[contains(concat(' ', normalize-space(@class), ' '), ' dimmed_text ')]";
                $this->find('xpath', $xpath, false, $activitynode);
                throw new ExpectationException('"' . $activityname . '" is hidden', $this->getSession());
            } catch (ElementNotFoundException $e) {
                // All ok.
            }

            // Additional check if this is a teacher in editing mode.
            if ($this->is_editing_on()) {
                // The 'Hide' button should be available.
                $nohideexception = new ExpectationException('"' . $activityname . '" doesn\'t have a "' .
                    get_string('hide') . '" icon', $this->getSession());
                $this->find('named_partial', array('link', get_string('hide')), $nohideexception, $activitynode);
            }
        }
    }

    /**
     * Checks that the specified activity is visible. You need to be in the course page.
     * It can be used being logged as a student and as a teacher on editing mode.
     *
     * @Then /^"(?P<activity_or_resource_string>(?:[^"]|\\")*)" activity should be available but hidden from course page$/
     * @param string $activityname
     * @throws ExpectationException
     */
    public function activity_should_be_available_but_hidden_from_course_page($activityname) {

        if ($this->is_course_editor()) {

            // The activity must exists and be visible.
            $activitynode = $this->get_activity_node($activityname);

            // The activity should not be dimmed.
            try {
                $xpath = "/descendant-or-self::a[contains(concat(' ', normalize-space(@class), ' '), ' dimmed ')] | " .
                    "/descendant-or-self::div[contains(concat(' ', normalize-space(@class), ' '), ' dimmed_text ')]";
                $this->find('xpath', $xpath, false, $activitynode);
                throw new ExpectationException('"' . $activityname . '" is hidden', $this->getSession());
            } catch (ElementNotFoundException $e) {
                // All ok.
            }

            // Should has "stealth" class.
            $exception = new ExpectationException('"' . $activityname . '" does not have CSS class "stealth"', $this->getSession());
            $xpath = "/descendant-or-self::a[contains(concat(' ', normalize-space(@class), ' '), ' stealth ')]";
            $this->find('xpath', $xpath, $exception, $activitynode);

            // Additional check if this is a teacher in editing mode.
            if ($this->is_editing_on()) {
                // Also has either 'Hide' or 'Make unavailable' edit control.
                $nohideexception = new ExpectationException('"' . $activityname . '" has neither "' . get_string('hide') .
                    '" nor "' . get_string('makeunavailable') . '" icons', $this->getSession());
                try {
                    $this->find('named_partial', array('link', get_string('hide')), false, $activitynode);
                } catch (ElementNotFoundException $e) {
                    $this->find('named_partial', array('link', get_string('makeunavailable')), $nohideexception, $activitynode);
                }
            }

        } else {

            // Student should not see the activity at all.
            try {
                $this->get_activity_node($activityname);
                throw new ExpectationException('The "' . $activityname . '" should not appear', $this->getSession());
            } catch (ElementNotFoundException $e) {
                // This is good, the activity should not be there.
            }
        }
    }

    /**
     * Checks that the specified activity is hidden. You need to be in the course page. It can be used being logged as a student and as a teacher on editing mode.
     *
     * @Then /^"(?P<activity_or_resource_string>(?:[^"]|\\")*)" activity should be hidden$/
     * @param string $activityname
     * @throws ExpectationException
     */
    public function activity_should_be_hidden($activityname) {

        if ($this->is_course_editor()) {

            // The activity should exist.
            $activitynode = $this->get_activity_node($activityname);

            // Should be hidden.
            $exception = new ExpectationException('"' . $activityname . '" is not dimmed', $this->getSession());
            $xpath = "/descendant-or-self::a[contains(concat(' ', normalize-space(@class), ' '), ' dimmed ')] | ".
                     "/descendant-or-self::div[contains(concat(' ', normalize-space(@class), ' '), ' dimmed_text ')]";
            $this->find('xpath', $xpath, $exception, $activitynode);

            // Additional check if this is a teacher in editing mode.
            if ($this->is_editing_on()) {
                // Also has either 'Show' or 'Make available' edit control.
                $noshowexception = new ExpectationException('"' . $activityname . '" has neither "' . get_string('show') .
                    '" nor "' . get_string('makeavailable') . '" icons', $this->getSession());
                try {
                    $this->find('named_partial', array('link', get_string('show')), false, $activitynode);
                } catch (ElementNotFoundException $e) {
                    $this->find('named_partial', array('link', get_string('makeavailable')), $noshowexception, $activitynode);
                }
            }

        } else {

            // It should not exist at all.
            try {
                $this->get_activity_node($activityname);
                throw new ExpectationException('The "' . $activityname . '" should not appear', $this->getSession());
            } catch (ElementNotFoundException $e) {
                // This is good, the activity should not be there.
            }
        }

    }

    /**
     * Checks that the specified activity is dimmed. You need to be in the course page.
     *
     * @Then /^"(?P<activity_or_resource_string>(?:[^"]|\\")*)" activity should be dimmed$/
     * @param string $activityname
     * @throws ExpectationException
     */
    public function activity_should_be_dimmed($activityname) {

        // The activity should exist.
        $activitynode = $this->get_activity_node($activityname);

        // Should be hidden.
        $exception = new ExpectationException('"' . $activityname . '" is not dimmed', $this->getSession());
        $xpath = "/descendant-or-self::a[contains(concat(' ', normalize-space(@class), ' '), ' dimmed ')] | ".
            "/descendant-or-self::div[contains(concat(' ', normalize-space(@class), ' '), ' dimmed_text ')]";
        $this->find('xpath', $xpath, $exception, $activitynode);

    }

    /**
     * Moves the specified activity to the first slot of a section. This step is experimental when using it in Javascript tests. Editing mode should be on.
     *
     * @Given /^I move "(?P<activity_name_string>(?:[^"]|\\")*)" activity to section "(?P<section_number>\d+)"$/
     * @param string $activityname The activity name
     * @param int $sectionnumber The number of section
     */
    public function i_move_activity_to_section($activityname, $sectionnumber) {

        // Ensure the destination is valid.
        $sectionxpath = $this->section_exists($sectionnumber);

        $activitynode = $this->get_activity_element('Move', 'icon', $activityname);

        // JS enabled.
        if ($this->running_javascript()) {

            $destinationxpath = $sectionxpath . "/descendant::ul[contains(concat(' ', normalize-space(@class), ' '), ' yui3-dd-drop ')]";

            $this->execute("behat_general::i_drag_and_i_drop_it_in",
                array($this->escape($activitynode->getXpath()), "xpath_element",
                    $this->escape($destinationxpath), "xpath_element")
            );

        } else {
            // Following links with no-JS.

            // Moving to the fist spot of the section (before all other section's activities).
            $this->execute('behat_course::i_click_on_in_the_activity',
                array("a.editing_move", "css_element", $this->escape($activityname))
            );

            $this->execute('behat_general::i_click_on_in_the',
                array("li.movehere a", "css_element", $this->escape($sectionxpath), "xpath_element")
            );
        }
    }

    /**
     * Edits the activity name through the edit activity; this step only works with Javascript enabled. Editing mode should be on.
     *
     * @Given /^I change "(?P<activity_name_string>(?:[^"]|\\")*)" activity name to "(?P<new_name_string>(?:[^"]|\\")*)"$/
     * @throws DriverException Step not available when Javascript is disabled
     * @param string $activityname
     * @param string $newactivityname
     */
    public function i_change_activity_name_to($activityname, $newactivityname) {

        if (!$this->running_javascript()) {
            throw new DriverException('Change activity name step is not available with Javascript disabled');
        }

        $activity = $this->escape($activityname);

        $this->execute('behat_course::i_click_on_in_the_activity',
            array(get_string('edittitle'), "link", $activity)
        );

        // Adding chr(10) to save changes.
        $this->execute('behat_forms::i_set_the_field_to',
            array('title', $this->escape($newactivityname) . chr(10))
        );

    }

    /**
     * Opens an activity actions menu if it is not already opened.
     *
     * @Given /^I open "(?P<activity_name_string>(?:[^"]|\\")*)" actions menu$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $activityname
     */
    public function i_open_actions_menu($activityname) {

        if (!$this->running_javascript()) {
            throw new DriverException('Activities actions menu not available when Javascript is disabled');
        }

        // If it is already opened we do nothing.
        $activitynode = $this->get_activity_node($activityname);
        $classes = array_flip(explode(' ', $activitynode->getAttribute('class')));
        if (!empty($classes['action-menu-shown'])) {
            return;
        }

        $this->execute('behat_course::i_click_on_in_the_activity',
            array("a[role='menuitem']", "css_element", $this->escape($activityname))
        );

    }

    /**
     * Closes an activity actions menu if it is not already closed.
     *
     * @Given /^I close "(?P<activity_name_string>(?:[^"]|\\")*)" actions menu$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $activityname
     */
    public function i_close_actions_menu($activityname) {

        if (!$this->running_javascript()) {
            throw new DriverException('Activities actions menu not available when Javascript is disabled');
        }

        // If it is already closed we do nothing.
        $activitynode = $this->get_activity_node($activityname);
        $classes = array_flip(explode(' ', $activitynode->getAttribute('class')));
        if (empty($classes['action-menu-shown'])) {
            return;
        }

        $this->execute('behat_course::i_click_on_in_the_activity',
            array("a[role='menuitem']", "css_element", $this->escape($activityname))
        );
    }

    /**
     * Checks that the specified activity's action menu is open.
     *
     * @Then /^"(?P<activity_name_string>(?:[^"]|\\")*)" actions menu should be open$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $activityname
     */
    public function actions_menu_should_be_open($activityname) {

        if (!$this->running_javascript()) {
            throw new DriverException('Activities actions menu not available when Javascript is disabled');
        }

        // If it is already closed we do nothing.
        $activitynode = $this->get_activity_node($activityname);
        $classes = array_flip(explode(' ', $activitynode->getAttribute('class')));
        if (empty($classes['action-menu-shown'])) {
            throw new ExpectationException(sprintf("The action menu for '%s' is not open", $activityname), $this->getSession());
        }
    }

    /**
     * Checks that the specified activity's action menu contains an item.
     *
     * @Then /^"(?P<activity_name_string>(?:[^"]|\\")*)" actions menu should have "(?P<menu_item_string>(?:[^"]|\\")*)" item$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $activityname
     * @param string $menuitem
     */
    public function actions_menu_should_have_item($activityname, $menuitem) {
        $activitynode = $this->get_activity_node($activityname);

        $notfoundexception = new ExpectationException('"' . $activityname . '" doesn\'t have a "' .
            $menuitem . '" item', $this->getSession());
        $this->find('named_partial', array('link', $menuitem), $notfoundexception, $activitynode);
    }

    /**
     * Checks that the specified activity's action menu does not contains an item.
     *
     * @Then /^"(?P<activity_name_string>(?:[^"]|\\")*)" actions menu should not have "(?P<menu_item_string>(?:[^"]|\\")*)" item$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $activityname
     * @param string $menuitem
     */
    public function actions_menu_should_not_have_item($activityname, $menuitem) {
        $activitynode = $this->get_activity_node($activityname);

        try {
            $this->find('named_partial', array('link', $menuitem), false, $activitynode);
            throw new ExpectationException('"' . $activityname . '" has a "' . $menuitem .
                '" item when it should not', $this->getSession());
        } catch (ElementNotFoundException $e) {
            // This is good, the menu item should not be there.
        }
    }

    /**
     * Indents to the right the activity or resource specified by it's name. Editing mode should be on.
     *
     * @Given /^I indent right "(?P<activity_name_string>(?:[^"]|\\")*)" activity$/
     * @param string $activityname
     */
    public function i_indent_right_activity($activityname) {

        $activity = $this->escape($activityname);
        if ($this->running_javascript()) {
            $this->i_open_actions_menu($activity);
        }

        $this->execute('behat_course::i_click_on_in_the_activity',
            array(get_string('moveright'), "link", $this->escape($activity))
        );

    }

    /**
     * Indents to the left the activity or resource specified by it's name. Editing mode should be on.
     *
     * @Given /^I indent left "(?P<activity_name_string>(?:[^"]|\\")*)" activity$/
     * @param string $activityname
     */
    public function i_indent_left_activity($activityname) {

        $activity = $this->escape($activityname);
        if ($this->running_javascript()) {
            $this->i_open_actions_menu($activity);
        }

        $this->execute('behat_course::i_click_on_in_the_activity',
            array(get_string('moveleft'), "link", $this->escape($activity))
        );

    }

    /**
     * Deletes the activity or resource specified by it's name. This step is experimental when using it in Javascript tests. You should be in the course page with editing mode on.
     *
     * @Given /^I delete "(?P<activity_name_string>(?:[^"]|\\")*)" activity$/
     * @param string $activityname
     */
    public function i_delete_activity($activityname) {
        $steps = array();
        $activity = $this->escape($activityname);
        if ($this->running_javascript()) {
            $this->i_open_actions_menu($activity);
        }

        $this->execute('behat_course::i_click_on_in_the_activity',
            array(get_string('delete'), "link", $this->escape($activity))
        );

        // JS enabled.
        // Not using chain steps here because the exceptions catcher have problems detecting
        // JS modal windows and avoiding interacting them at the same time.
        if ($this->running_javascript()) {
            $this->execute('behat_general::i_click_on_in_the',
                array(get_string('yes'), "button", "Confirm", "dialogue")
            );
        } else {
            $this->execute("behat_forms::press_button", get_string('yes'));
        }

        return $steps;
    }

    /**
     * Duplicates the activity or resource specified by it's name. You should be in the course page with editing mode on.
     *
     * @Given /^I duplicate "(?P<activity_name_string>(?:[^"]|\\")*)" activity$/
     * @param string $activityname
     */
    public function i_duplicate_activity($activityname) {
        $steps = array();
        $activity = $this->escape($activityname);
        if ($this->running_javascript()) {
            $this->i_open_actions_menu($activity);
        }
        $this->execute('behat_course::i_click_on_in_the_activity',
            array(get_string('duplicate'), "link", $activity)
        );

    }

    /**
     * Duplicates the activity or resource and modifies the new activity with the provided data. You should be in the course page with editing mode on.
     *
     * @Given /^I duplicate "(?P<activity_name_string>(?:[^"]|\\")*)" activity editing the new copy with:$/
     * @param string $activityname
     * @param TableNode $data
     */
    public function i_duplicate_activity_editing_the_new_copy_with($activityname, TableNode $data) {

        $activity = $this->escape($activityname);
        $activityliteral = behat_context_helper::escape($activityname);

        $this->execute("behat_course::i_duplicate_activity", $activity);

        // Determine the future new activity xpath from the former one.
        $duplicatedxpath = "//li[contains(concat(' ', normalize-space(@class), ' '), ' activity ')]" .
            "[contains(., $activityliteral)]/following-sibling::li";
        $duplicatedactionsmenuxpath = $duplicatedxpath . "/descendant::a[@role='menuitem']";

        if ($this->running_javascript()) {
            // We wait until the AJAX request finishes and the section is visible again.
            $hiddenlightboxxpath = "//li[contains(concat(' ', normalize-space(@class), ' '), ' activity ')]" .
                "[contains(., $activityliteral)]" .
                "/ancestor::li[contains(concat(' ', normalize-space(@class), ' '), ' section ')]" .
                "/descendant::div[contains(concat(' ', @class, ' '), ' lightbox ')][contains(@style, 'display: none')]";

            $this->execute("behat_general::wait_until_exists",
                array($this->escape($hiddenlightboxxpath), "xpath_element")
            );

            // Close the original activity actions menu.
            $this->i_close_actions_menu($activity);

            // The next sibling of the former activity will be the duplicated one, so we click on it from it's xpath as, at
            // this point, it don't even exists in the DOM (the steps are executed when we return them).
            $this->execute('behat_general::i_click_on',
                array($this->escape($duplicatedactionsmenuxpath), "xpath_element")
            );
        }

        // We force the xpath as otherwise mink tries to interact with the former one.
        $this->execute('behat_general::i_click_on_in_the',
            array(get_string('editsettings'), "link", $this->escape($duplicatedxpath), "xpath_element")
        );

        $this->execute("behat_forms::i_set_the_following_fields_to_these_values", $data);
        $this->execute("behat_forms::press_button", get_string('savechangesandreturntocourse'));

    }

    /**
     * Waits until the section is available to interact with it. Useful when the section is performing an action and the section is overlayed with a loading layout.
     *
     * Using the protected method as this method will be usually
     * called by other methods which are not returning a set of
     * steps and performs the actions directly, so it would not
     * be executed if it returns another step.
     *
     * Hopefully we would not require test writers to use this step
     * and we will manage it from other step definitions.
     *
     * @Given /^I wait until section "(?P<section_number>\d+)" is available$/
     * @param int $sectionnumber
     * @return void
     */
    public function i_wait_until_section_is_available($sectionnumber) {

        // Looks for a hidden lightbox or a non-existent lightbox in that section.
        $sectionxpath = $this->section_exists($sectionnumber);
        $hiddenlightboxxpath = $sectionxpath . "/descendant::div[contains(concat(' ', @class, ' '), ' lightbox ')][contains(@style, 'display: none')]" .
            " | " .
            $sectionxpath . "[count(child::div[contains(@class, 'lightbox')]) = 0]";

        $this->ensure_element_exists($hiddenlightboxxpath, 'xpath_element');
    }

    /**
     * Clicks on the specified element of the activity. You should be in the course page with editing mode turned on.
     *
     * @Given /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" in the "(?P<activity_name_string>(?:[^"]|\\")*)" activity$/
     * @param string $element
     * @param string $selectortype
     * @param string $activityname
     */
    public function i_click_on_in_the_activity($element, $selectortype, $activityname) {
        $element = $this->get_activity_element($element, $selectortype, $activityname);
        $element->click();
    }

    /**
     * Clicks on the specified element inside the activity container.
     *
     * @throws ElementNotFoundException
     * @param string $element
     * @param string $selectortype
     * @param string $activityname
     * @return NodeElement
     */
    protected function get_activity_element($element, $selectortype, $activityname) {
        $activitynode = $this->get_activity_node($activityname);

        // Transforming to Behat selector/locator.
        list($selector, $locator) = $this->transform_selector($selectortype, $element);
        $exception = new ElementNotFoundException($this->getSession(), '"' . $element . '" "' . $selectortype . '" in "' . $activityname . '" ');

        return $this->find($selector, $locator, $exception, $activitynode);
    }

    /**
     * Checks if the course section exists.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param int $sectionnumber
     * @return string The xpath of the section.
     */
    protected function section_exists($sectionnumber) {

        // Just to give more info in case it does not exist.
        $xpath = "//li[@id='section-" . $sectionnumber . "']";
        $exception = new ElementNotFoundException($this->getSession(), "Section $sectionnumber ");
        $this->find('xpath', $xpath, $exception);

        return $xpath;
    }

    /**
     * Returns the show section icon or throws an exception.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param int $sectionnumber
     * @return NodeElement
     */
    protected function show_section_link_exists($sectionnumber) {

        // Gets the section xpath and ensure it exists.
        $xpath = $this->section_exists($sectionnumber);

        // We need to know the course format as the text strings depends on them.
        $courseformat = $this->get_course_format();

        // Checking the show button alt text and show icon.
        $showtext = get_string('showfromothers', $courseformat);
        $linkxpath = $xpath . "/descendant::a[@title=" . behat_context_helper::escape($showtext) . "]";

        $exception = new ElementNotFoundException($this->getSession(), 'Show section link ');

        // Returing the link so both Non-JS and JS browsers can interact with it.
        return $this->find('xpath', $linkxpath, $exception);
    }

    /**
     * Returns the hide section icon link if it exists or throws exception.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param int $sectionnumber
     * @return NodeElement
     */
    protected function hide_section_link_exists($sectionnumber) {

        // Gets the section xpath and ensure it exists.
        $xpath = $this->section_exists($sectionnumber);

        // We need to know the course format as the text strings depends on them.
        $courseformat = $this->get_course_format();

        // Checking the hide button alt text and hide icon.
        $hidetext = behat_context_helper::escape(get_string('hidefromothers', $courseformat));
        $linkxpath = $xpath . "/descendant::a[@title=$hidetext]";

        $exception = new ElementNotFoundException($this->getSession(), 'Hide section icon ');
        $this->find('icon', 'Hide', $exception);

        // Returing the link so both Non-JS and JS browsers can interact with it.
        return $this->find('xpath', $linkxpath, $exception);
    }

    /**
     * Gets the current course format.
     *
     * @throws ExpectationException If we are not in the course view page.
     * @return string The course format in a frankenstyled name.
     */
    protected function get_course_format() {

        $exception = new ExpectationException('You are not in a course page', $this->getSession());

        // The moodle body's id attribute contains the course format.
        $node = $this->getSession()->getPage()->find('css', 'body');
        if (!$node) {
            throw $exception;
        }

        if (!$bodyid = $node->getAttribute('id')) {
            throw $exception;
        }

        if (strstr($bodyid, 'page-course-view-') === false) {
            throw $exception;
        }

        return 'format_' . str_replace('page-course-view-', '', $bodyid);
    }

    /**
     * Gets the section's activites DOM nodes.
     *
     * @param string $sectionxpath
     * @return array NodeElement instances
     */
    protected function get_section_activities($sectionxpath) {

        $xpath = $sectionxpath . "/descendant::li[contains(concat(' ', normalize-space(@class), ' '), ' activity ')]";

        // We spin here, as activities usually require a lot of time to load.
        try {
            $activities = $this->find_all('xpath', $xpath);
        } catch (ElementNotFoundException $e) {
            return false;
        }

        return $activities;
    }

    /**
     * Returns the DOM node of the activity from <li>.
     *
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $activityname The activity name
     * @return NodeElement
     */
    protected function get_activity_node($activityname) {

        $activityname = behat_context_helper::escape($activityname);
        $xpath = "//li[contains(concat(' ', normalize-space(@class), ' '), ' activity ')][contains(., $activityname)]";

        return $this->find('xpath', $xpath);
    }

    /**
     * Gets the activity instance name from the activity node.
     *
     * @throws ElementNotFoundException
     * @param NodeElement $activitynode
     * @return string
     */
    protected function get_activity_name($activitynode) {
        $instancenamenode = $this->find('xpath', "//span[contains(concat(' ', normalize-space(@class), ' '), ' instancename ')]", false, $activitynode);
        return $instancenamenode->getText();
    }

    /**
     * Returns whether the user can edit the course contents or not.
     *
     * @return bool
     */
    protected function is_course_editor() {

        // We don't need to behat_base::spin() here as all is already loaded.
        if (!$this->getSession()->getPage()->findButton(get_string('turneditingoff')) &&
                !$this->getSession()->getPage()->findButton(get_string('turneditingon'))) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether the user can edit the course contents and the editing mode is on.
     *
     * @return bool
     */
    protected function is_editing_on() {
        return $this->getSession()->getPage()->findButton(get_string('turneditingoff')) ? true : false;
    }

    /**
     * Returns the id of the category with the given idnumber.
     *
     * Please note that this function requires the category to exist. If it does not exist an ExpectationException is thrown.
     *
     * @param string $idnumber
     * @return string
     * @throws ExpectationException
     */
    protected function get_category_id($idnumber) {
        global $DB;
        try {
            return $DB->get_field('course_categories', 'id', array('idnumber' => $idnumber), MUST_EXIST);
        } catch (dml_missing_record_exception $ex) {
            throw new ExpectationException(sprintf("There is no category in the database with the idnumber '%s'", $idnumber));
        }
    }

    /**
     * Returns the id of the course with the given idnumber.
     *
     * Please note that this function requires the category to exist. If it does not exist an ExpectationException is thrown.
     *
     * @param string $idnumber
     * @return string
     * @throws ExpectationException
     */
    protected function get_course_id($idnumber) {
        global $DB;
        try {
            return $DB->get_field('course', 'id', array('idnumber' => $idnumber), MUST_EXIST);
        } catch (dml_missing_record_exception $ex) {
            throw new ExpectationException(sprintf("There is no course in the database with the idnumber '%s'", $idnumber));
        }
    }

    /**
     * Returns the category node from within the listing on the management page.
     *
     * @param string $idnumber
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function get_management_category_listing_node_by_idnumber($idnumber) {
        $id = $this->get_category_id($idnumber);
        $selector = sprintf('#category-listing .listitem-category[data-id="%d"] > div', $id);
        return $this->find('css', $selector);
    }

    /**
     * Returns a category node from within the management interface.
     *
     * @param string $name The name of the category.
     * @param bool $link If set to true we'll resolve to the link rather than just the node.
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function get_management_category_listing_node_by_name($name, $link = false) {
        $selector = "//div[@id='category-listing']//li[contains(concat(' ', normalize-space(@class), ' '), ' listitem-category ')]//a[text()='{$name}']";
        if ($link === false) {
            $selector .= "/ancestor::li[@data-id][1]";
        }
        return $this->find('xpath', $selector);
    }

    /**
     * Returns a course node from within the management interface.
     *
     * @param string $name The name of the course.
     * @param bool $link If set to true we'll resolve to the link rather than just the node.
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function get_management_course_listing_node_by_name($name, $link = false) {
        $selector = "//div[@id='course-listing']//li[contains(concat(' ', @class, ' '), ' listitem-course ')]//a[text()='{$name}']";
        if ($link === false) {
            $selector .= "/ancestor::li[@data-id]";
        }
        return $this->find('xpath', $selector);
    }

    /**
     * Returns the course node from within the listing on the management page.
     *
     * @param string $idnumber
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function get_management_course_listing_node_by_idnumber($idnumber) {
        $id = $this->get_course_id($idnumber);
        $selector = sprintf('#course-listing .listitem-course[data-id="%d"] > div', $id);
        return $this->find('css', $selector);
    }

    /**
     * Clicks on a category in the management interface.
     *
     * @Given /^I click on category "(?P<name_string>(?:[^"]|\\")*)" in the management interface$/
     * @param string $name
     */
    public function i_click_on_category_in_the_management_interface($name) {
        $node = $this->get_management_category_listing_node_by_name($name, true);
        $node->click();
    }

    /**
     * Clicks on a course in the management interface.
     *
     * @Given /^I click on course "(?P<name_string>(?:[^"]|\\")*)" in the management interface$/
     * @param string $name
     */
    public function i_click_on_course_in_the_management_interface($name) {
        $node = $this->get_management_course_listing_node_by_name($name, true);
        $node->click();
    }

    /**
     * Clicks on a category checkbox in the management interface, if not checked.
     *
     * @Given /^I select category "(?P<name_string>(?:[^"]|\\")*)" in the management interface$/
     * @param string $name
     */
    public function i_select_category_in_the_management_interface($name) {
        $node = $this->get_management_category_listing_node_by_name($name);
        $node = $node->findField('bcat[]');
        if (!$node->isChecked()) {
            $node->click();
        }
    }

    /**
     * Clicks on a category checkbox in the management interface, if checked.
     *
     * @Given /^I unselect category "(?P<name_string>(?:[^"]|\\")*)" in the management interface$/
     * @param string $name
     */
    public function i_unselect_category_in_the_management_interface($name) {
        $node = $this->get_management_category_listing_node_by_name($name);
        $node = $node->findField('bcat[]');
        if ($node->isChecked()) {
            $node->click();
        }
    }

    /**
     * Clicks course checkbox in the management interface, if not checked.
     *
     * @Given /^I select course "(?P<name_string>(?:[^"]|\\")*)" in the management interface$/
     * @param string $name
     */
    public function i_select_course_in_the_management_interface($name) {
        $node = $this->get_management_course_listing_node_by_name($name);
        $node = $node->findField('bc[]');
        if (!$node->isChecked()) {
            $node->click();
        }
    }

    /**
     * Clicks course checkbox in the management interface, if checked.
     *
     * @Given /^I unselect course "(?P<name_string>(?:[^"]|\\")*)" in the management interface$/
     * @param string $name
     */
    public function i_unselect_course_in_the_management_interface($name) {
        $node = $this->get_management_course_listing_node_by_name($name);
        $node = $node->findField('bc[]');
        if ($node->isChecked()) {
            $node->click();
        }
    }

    /**
     * Move selected categories to top level in the management interface.
     *
     * @Given /^I move category "(?P<name_string>(?:[^"]|\\")*)" to top level in the management interface$/
     * @param string $name
     */
    public function i_move_category_to_top_level_in_the_management_interface($name) {
        $this->i_select_category_in_the_management_interface($name);

        $this->execute('behat_forms::i_set_the_field_to',
            array('menumovecategoriesto', coursecat::get(0)->get_formatted_name())
        );

        // Save event.
        $this->execute("behat_forms::press_button", "bulkmovecategories");
    }

    /**
     * Checks that a category is a subcategory of specific category.
     *
     * @Given /^I should see category "(?P<subcatidnumber_string>(?:[^"]|\\")*)" as subcategory of "(?P<catidnumber_string>(?:[^"]|\\")*)" in the management interface$/
     * @throws ExpectationException
     * @param string $subcatidnumber
     * @param string $catidnumber
     */
    public function i_should_see_category_as_subcategory_of_in_the_management_interface($subcatidnumber, $catidnumber) {
        $categorynodeid = $this->get_category_id($catidnumber);
        $subcategoryid = $this->get_category_id($subcatidnumber);
        $exception = new ExpectationException('The category '.$subcatidnumber.' is not a subcategory of '.$catidnumber, $this->getSession());
        $selector = sprintf('#category-listing .listitem-category[data-id="%d"] .listitem-category[data-id="%d"]', $categorynodeid, $subcategoryid);
        $this->find('css', $selector, $exception);
    }

    /**
     * Checks that a category is not a subcategory of specific category.
     *
     * @Given /^I should not see category "(?P<subcatidnumber_string>(?:[^"]|\\")*)" as subcategory of "(?P<catidnumber_string>(?:[^"]|\\")*)" in the management interface$/
     * @throws ExpectationException
     * @param string $subcatidnumber
     * @param string $catidnumber
     */
    public function i_should_not_see_category_as_subcategory_of_in_the_management_interface($subcatidnumber, $catidnumber) {
        try {
            $this->i_should_see_category_as_subcategory_of_in_the_management_interface($subcatidnumber, $catidnumber);
        } catch (ExpectationException $e) {
            // ExpectedException means that it is not highlighted.
            return;
        }
        throw new ExpectationException('The category '.$subcatidnumber.' is a subcategory of '.$catidnumber, $this->getSession());
    }

    /**
     * Click to expand a category revealing its sub categories within the management UI.
     *
     * @Given /^I click to expand category "(?P<idnumber_string>(?:[^"]|\\")*)" in the management interface$/
     * @param string $idnumber
     */
    public function i_click_to_expand_category_in_the_management_interface($idnumber) {
        $categorynode = $this->get_management_category_listing_node_by_idnumber($idnumber);
        $exception = new ExpectationException('Category "' . $idnumber . '" does not contain an expand or collapse toggle.', $this->getSession());
        $togglenode = $this->find('css', 'a[data-action=collapse],a[data-action=expand]', $exception, $categorynode);
        $togglenode->click();
    }

    /**
     * Checks that a category within the management interface is visible.
     *
     * @Given /^category in management listing should be visible "(?P<idnumber_string>(?:[^"]|\\")*)"$/
     * @param string $idnumber
     */
    public function category_in_management_listing_should_be_visible($idnumber) {
        $id = $this->get_category_id($idnumber);
        $exception = new ExpectationException('The category '.$idnumber.' is not visible.', $this->getSession());
        $selector = sprintf('#category-listing .listitem-category[data-id="%d"][data-visible="1"]', $id);
        $this->find('css', $selector, $exception);
    }

    /**
     * Checks that a category within the management interface is dimmed.
     *
     * @Given /^category in management listing should be dimmed "(?P<idnumber_string>(?:[^"]|\\")*)"$/
     * @param string $idnumber
     */
    public function category_in_management_listing_should_be_dimmed($idnumber) {
        $id = $this->get_category_id($idnumber);
        $selector = sprintf('#category-listing .listitem-category[data-id="%d"][data-visible="0"]', $id);
        $exception = new ExpectationException('The category '.$idnumber.' is visible.', $this->getSession());
        $this->find('css', $selector, $exception);
    }

    /**
     * Checks that a course within the management interface is visible.
     *
     * @Given /^course in management listing should be visible "(?P<idnumber_string>(?:[^"]|\\")*)"$/
     * @param string $idnumber
     */
    public function course_in_management_listing_should_be_visible($idnumber) {
        $id = $this->get_course_id($idnumber);
        $exception = new ExpectationException('The course '.$idnumber.' is not visible.', $this->getSession());
        $selector = sprintf('#course-listing .listitem-course[data-id="%d"][data-visible="1"]', $id);
        $this->find('css', $selector, $exception);
    }

    /**
     * Checks that a course within the management interface is dimmed.
     *
     * @Given /^course in management listing should be dimmed "(?P<idnumber_string>(?:[^"]|\\")*)"$/
     * @param string $idnumber
     */
    public function course_in_management_listing_should_be_dimmed($idnumber) {
        $id = $this->get_course_id($idnumber);
        $exception = new ExpectationException('The course '.$idnumber.' is visible.', $this->getSession());
        $selector = sprintf('#course-listing .listitem-course[data-id="%d"][data-visible="0"]', $id);
        $this->find('css', $selector, $exception);
    }

    /**
     * Toggles the visibility of a course in the management UI.
     *
     * If it was visible it will be hidden. If it is hidden it will be made visible.
     *
     * @Given /^I toggle visibility of course "(?P<idnumber_string>(?:[^"]|\\")*)" in management listing$/
     * @param string $idnumber
     */
    public function i_toggle_visibility_of_course_in_management_listing($idnumber) {
        $id = $this->get_course_id($idnumber);
        $selector = sprintf('#course-listing .listitem-course[data-id="%d"][data-visible]', $id);
        $node = $this->find('css', $selector);
        $exception = new ExpectationException('Course listing "' . $idnumber . '" does not contain a show or hide toggle.', $this->getSession());
        if ($node->getAttribute('data-visible') === '1') {
            $toggle = $this->find('css', '.action-hide', $exception, $node);
        } else {
            $toggle = $this->find('css', '.action-show', $exception, $node);
        }
        $toggle->click();
    }

    /**
     * Toggles the visibility of a category in the management UI.
     *
     * If it was visible it will be hidden. If it is hidden it will be made visible.
     *
     * @Given /^I toggle visibility of category "(?P<idnumber_string>(?:[^"]|\\")*)" in management listing$/
     */
    public function i_toggle_visibility_of_category_in_management_listing($idnumber) {
        $id = $this->get_category_id($idnumber);
        $selector = sprintf('#category-listing .listitem-category[data-id="%d"][data-visible]', $id);
        $node = $this->find('css', $selector);
        $exception = new ExpectationException('Category listing "' . $idnumber . '" does not contain a show or hide toggle.', $this->getSession());
        if ($node->getAttribute('data-visible') === '1') {
            $toggle = $this->find('css', '.action-hide', $exception, $node);
        } else {
            $toggle = $this->find('css', '.action-show', $exception, $node);
        }
        $toggle->click();
    }

    /**
     * Moves a category displayed in the management interface up or down one place.
     *
     * @Given /^I click to move category "(?P<idnumber_string>(?:[^"]|\\")*)" (?P<direction>up|down) one$/
     *
     * @param string $idnumber The category idnumber
     * @param string $direction The direction to move in, either up or down
     */
    public function i_click_to_move_category_by_one($idnumber, $direction) {
        $node = $this->get_management_category_listing_node_by_idnumber($idnumber);
        $this->user_moves_listing_by_one('category', $node, $direction);
    }

    /**
     * Moves a course displayed in the management interface up or down one place.
     *
     * @Given /^I click to move course "(?P<idnumber_string>(?:[^"]|\\")*)" (?P<direction>up|down) one$/
     *
     * @param string $idnumber The course idnumber
     * @param string $direction The direction to move in, either up or down
     */
    public function i_click_to_move_course_by_one($idnumber, $direction) {
        $node = $this->get_management_course_listing_node_by_idnumber($idnumber);
        $this->user_moves_listing_by_one('course', $node, $direction);
    }

    /**
     * Moves a course or category listing within the management interface up or down by one.
     *
     * @param string $listingtype One of course or category
     * @param \Behat\Mink\Element\NodeElement $listingnode
     * @param string $direction One of up or down.
     * @param bool $highlight If set to false we don't check the node has been highlighted.
     */
    protected function user_moves_listing_by_one($listingtype, $listingnode, $direction, $highlight = true) {
        $up = (strtolower($direction) === 'up');
        if ($up) {
            $exception = new ExpectationException($listingtype.' listing does not contain a moveup button.', $this->getSession());
            $button = $this->find('css', 'a.action-moveup', $exception, $listingnode);
        } else {
            $exception = new ExpectationException($listingtype.' listing does not contain a movedown button.', $this->getSession());
            $button = $this->find('css', 'a.action-movedown', $exception, $listingnode);
        }
        $button->click();
        if ($this->running_javascript() && $highlight) {
            $listitem = $listingnode->getParent();
            $exception = new ExpectationException('Nothing was highlighted, ajax didn\'t occur or didn\'t succeed.', $this->getSession());
            $this->spin(array($this, 'listing_is_highlighted'), $listitem->getTagName().'#'.$listitem->getAttribute('id'), 2, $exception, true);
        }
    }

    /**
     * Used by spin to determine the callback has been highlighted.
     *
     * @param behat_course $self A self reference (default first arg from a spin callback)
     * @param \Behat\Mink\Element\NodeElement $selector
     * @return bool
     */
    protected function listing_is_highlighted($self, $selector) {
        $listitem = $this->find('css', $selector);
        return $listitem->hasClass('highlight');
    }

    /**
     * Check that one course appears before another in the course category management listings.
     *
     * @Given /^I should see course listing "(?P<preceedingcourse_string>(?:[^"]|\\")*)" before "(?P<followingcourse_string>(?:[^"]|\\")*)"$/
     *
     * @param string $preceedingcourse The first course to find
     * @param string $followingcourse The second course to find (should be AFTER the first course)
     * @throws ExpectationException
     */
    public function i_should_see_course_listing_before($preceedingcourse, $followingcourse) {
        $xpath = "//div[@id='course-listing']//li[contains(concat(' ', @class, ' '), ' listitem-course ')]//a[text()='{$preceedingcourse}']/ancestor::li[@data-id]//following::a[text()='{$followingcourse}']";
        $msg = "{$preceedingcourse} course does not appear before {$followingcourse} course";
        if (!$this->getSession()->getDriver()->find($xpath)) {
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * Check that one category appears before another in the course category management listings.
     *
     * @Given /^I should see category listing "(?P<preceedingcategory_string>(?:[^"]|\\")*)" before "(?P<followingcategory_string>(?:[^"]|\\")*)"$/
     *
     * @param string $preceedingcategory The first category to find
     * @param string $followingcategory The second category to find (should be after the first category)
     * @throws ExpectationException
     */
    public function i_should_see_category_listing_before($preceedingcategory, $followingcategory) {
        $xpath = "//div[@id='category-listing']//li[contains(concat(' ', @class, ' '), ' listitem-category ')]//a[text()='{$preceedingcategory}']/ancestor::li[@data-id]//following::a[text()='{$followingcategory}']";
        $msg = "{$preceedingcategory} category does not appear before {$followingcategory} category";
        if (!$this->getSession()->getDriver()->find($xpath)) {
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * Checks that we are on the course management page that we expect to be on and that no course has been selected.
     *
     * @Given /^I should see the "(?P<mode_string>(?:[^"]|\\")*)" management page$/
     * @param string $mode The mode to expected. One of 'Courses', 'Course categories' or 'Course categories and courses'
     */
    public function i_should_see_the_courses_management_page($mode) {
        $this->execute("behat_general::assert_element_contains_text",
            array("Course and category management", "h2", "css_element")
        );

        switch ($mode) {
            case "Courses":
                $this->execute("behat_general::should_not_exist", array("#category-listing", "css_element"));
                $this->execute("behat_general::should_exist", array("#course-listing", "css_element"));
                break;

            case "Course categories":
                $this->execute("behat_general::should_exist", array("#category-listing", "css_element"));
                $this->execute("behat_general::should_exist", array("#course-listing", "css_element"));
                break;

            case "Courses categories and courses":
            default:
                $this->execute("behat_general::should_exist", array("#category-listing", "css_element"));
                $this->execute("behat_general::should_exist", array("#course-listing", "css_element"));
                break;
        }

        $this->execute("behat_general::should_not_exist", array("#course-detail", "css_element"));
    }

    /**
     * Checks that we are on the course management page that we expect to be on and that a course has been selected.
     *
     * @Given /^I should see the "(?P<mode_string>(?:[^"]|\\")*)" management page with a course selected$/
     * @param string $mode The mode to expected. One of 'Courses', 'Course categories' or 'Course categories and courses'
     */
    public function i_should_see_the_courses_management_page_with_a_course_selected($mode) {
        $this->execute("behat_general::assert_element_contains_text",
            array("Course and category management", "h2", "css_element"));

        switch ($mode) {
            case "Courses":
                $this->execute("behat_general::should_not_exist", array("#category-listing", "css_element"));
                $this->execute("behat_general::should_exist", array("#course-listing", "css_element"));
                break;

            case "Course categories":
                $this->execute("behat_general::should_exist", array("#category-listing", "css_element"));
                $this->execute("behat_general::should_exist", array("#course-listing", "css_element"));
                break;

            case "Courses categories and courses":
            default:
                $this->execute("behat_general::should_exist", array("#category-listing", "css_element"));
                $this->execute("behat_general::should_exist", array("#course-listing", "css_element"));
                break;
        }

        $this->execute("behat_general::should_exist", array("#course-detail", "css_element"));
    }

    /**
     * Locates a course in the course category management interface and then triggers an action for it.
     *
     * @Given /^I click on "(?P<action_string>(?:[^"]|\\")*)" action for "(?P<name_string>(?:[^"]|\\")*)" in management course listing$/
     *
     * @param string $action The action to take. One of
     * @param string $name The name of the course as it is displayed in the management interface.
     */
    public function i_click_on_action_for_item_in_management_course_listing($action, $name) {
        $node = $this->get_management_course_listing_node_by_name($name);
        $this->user_clicks_on_management_listing_action('course', $node, $action);
    }

    /**
     * Locates a category in the course category management interface and then triggers an action for it.
     *
     * @Given /^I click on "(?P<action_string>(?:[^"]|\\")*)" action for "(?P<name_string>(?:[^"]|\\")*)" in management category listing$/
     *
     * @param string $action The action to take. One of
     * @param string $name The name of the category as it is displayed in the management interface.
     */
    public function i_click_on_action_for_item_in_management_category_listing($action, $name) {
        $node = $this->get_management_category_listing_node_by_name($name);
        $this->user_clicks_on_management_listing_action('category', $node, $action);
    }

    /**
     * Clicks to expand or collapse a category displayed on the frontpage
     *
     * @Given /^I toggle "(?P<categoryname_string>(?:[^"]|\\")*)" category children visibility in frontpage$/
     * @throws ExpectationException
     * @param string $categoryname
     */
    public function i_toggle_category_children_visibility_in_frontpage($categoryname) {

        $headingtags = array();
        for ($i = 1; $i <= 6; $i++) {
            $headingtags[] = 'self::h' . $i;
        }

        $exception = new ExpectationException('"' . $categoryname . '" category can not be found', $this->getSession());
        $categoryliteral = behat_context_helper::escape($categoryname);
        $xpath = "//div[@class='info']/descendant::*[" . implode(' or ', $headingtags) . "][@class='categoryname'][./descendant::a[.=$categoryliteral]]";
        $node = $this->find('xpath', $xpath, $exception);
        $node->click();

        // Smooth expansion.
        $this->getSession()->wait(1000);
    }

    /**
     * Finds the node to use for a management listitem action and clicks it.
     *
     * @param string $listingtype Either course or category.
     * @param \Behat\Mink\Element\NodeElement $listingnode
     * @param string $action The action being taken
     * @throws Behat\Mink\Exception\ExpectationException
     */
    protected function user_clicks_on_management_listing_action($listingtype, $listingnode, $action) {
        $actionsnode = $listingnode->find('xpath', "//*[contains(concat(' ', normalize-space(@class), ' '), '{$listingtype}-item-actions')]");
        if (!$actionsnode) {
            throw new ExpectationException("Could not find the actions for $listingtype", $this->getSession());
        }
        $actionnode = $actionsnode->find('css', '.action-'.$action);
        if (!$actionnode) {
            throw new ExpectationException("Expected action was not available or not found ($action)", $this->getSession());
        }
        if ($this->running_javascript() && !$actionnode->isVisible()) {
            $actionsnode->find('css', 'a.toggle-display')->click();
            $actionnode = $actionsnode->find('css', '.action-'.$action);
        }
        $actionnode->click();
    }

    /**
     * Clicks on a category in the management interface.
     *
     * @Given /^I click on "(?P<categoryname_string>(?:[^"]|\\")*)" category in the management category listing$/
     * @param string $name The name of the category to click.
     */
    public function i_click_on_category_in_the_management_category_listing($name) {
        $node = $this->get_management_category_listing_node_by_name($name);
        $node->find('css', 'a.categoryname')->click();
    }

    /**
     * Go to the course participants
     *
     * @Given /^I navigate to course participants$/
     */
    public function i_navigate_to_course_participants() {
        $coursestr = behat_context_helper::escape(get_string('courses'));
        $mycoursestr = behat_context_helper::escape(get_string('mycourses'));
        $xpath = "//div[contains(@class,'block')]//li[p/*[string(.)=$coursestr or string(.)=$mycoursestr]]";
        $this->execute('behat_general::i_click_on_in_the', [get_string('participants'), 'link', $xpath, 'xpath_element']);
    }
}
