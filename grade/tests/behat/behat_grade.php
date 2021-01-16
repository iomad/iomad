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
 * Behat grade related steps definitions.
 *
 * @package    core_grades
 * @category   test
 * @copyright  2014 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;

class behat_grade extends behat_base {

    /**
     * Enters a grade via the gradebook for a specific grade item and user when viewing the 'Grader report' with editing mode turned on.
     *
     * @Given /^I give the grade "(?P<grade_number>(?:[^"]|\\")*)" to the user "(?P<username_string>(?:[^"]|\\")*)" for the grade item "(?P<grade_activity_string>(?:[^"]|\\")*)"$/
     * @param int $grade
     * @param string $userfullname the user's fullname as returned by fullname()
     * @param string $itemname
     */
    public function i_give_the_grade($grade, $userfullname, $itemname) {
        $gradelabel = $userfullname . ' ' . $itemname;
        $fieldstr = get_string('useractivitygrade', 'gradereport_grader', $gradelabel);

        $this->execute('behat_forms::i_set_the_field_to', array($this->escape($fieldstr), $grade));
    }

    /**
     * Enters a quick feedback via the gradebook for a specific grade item and user when viewing
     * the 'Grader report' with editing mode turned on.
     *
     * @Given /^I give the feedback "(?P<grade_number>(?:[^"]|\\")*)" to the user "(?P<username_string>(?:[^"]|\\")*)" for the grade item "(?P<grade_activity_string>(?:[^"]|\\")*)"$/
     * @param string $feedback
     * @param string $userfullname the user's fullname as returned by fullname()
     * @param string $itemname
     */
    public function i_give_the_feedback($feedback, $userfullname, $itemname) {
        $gradelabel = $userfullname . ' ' . $itemname;
        $fieldstr = get_string('useractivityfeedback', 'gradereport_grader', $gradelabel);

        $this->execute('behat_forms::i_set_the_field_to', array($this->escape($fieldstr), $this->escape($feedback)));
    }

    /**
     * Changes the settings of a grade item or category or the course.
     *
     * Teacher must be either on the grade setup page or on the Grader report page with editing mode turned on.
     *
     * @Given /^I set the following settings for grade item "(?P<grade_item_string>(?:[^"]|\\")*)":$/
     * @param string $gradeitem
     * @param TableNode $data
     */
    public function i_set_the_following_settings_for_grade_item($gradeitem, TableNode $data) {

        $gradeitem = behat_context_helper::escape($gradeitem);

        if ($this->running_javascript()) {
            $xpath = "//tr[contains(.,$gradeitem)]//*[contains(@class,'moodle-actionmenu')]//a[contains(@class,'toggle-display')]";
            if ($this->getSession()->getPage()->findAll('xpath', $xpath)) {
                $this->execute("behat_general::i_click_on", array($this->escape($xpath), "xpath_element"));
            }
        }

        $savechanges = get_string('savechanges', 'grades');
        $edit = behat_context_helper::escape(get_string('edit') . '  ');
        $linkxpath = "//a[./img[starts-with(@title,$edit) and contains(@title,$gradeitem)]]";

        $this->execute("behat_general::i_click_on", array($this->escape($linkxpath), "xpath_element"));
        $this->execute("behat_forms::i_set_the_following_fields_to_these_values", $data);
        $this->execute('behat_forms::press_button', $this->escape($savechanges));
    }

    /**
     * Hids a grade item or category.
     *
     * Teacher must be on the grade setup page.
     *
     * @Given /^I hide the grade item "(?P<grade_item_string>(?:[^"]|\\")*)"$/
     * @param string $gradeitem
     */
    public function i_hide_the_grade_item($gradeitem) {

        $gradeitem = behat_context_helper::escape($gradeitem);

        if ($this->running_javascript()) {
            $xpath = "//tr[contains(.,$gradeitem)]//*[contains(@class,'moodle-actionmenu')]//a[contains(@class,'toggle-display')]";
            if ($this->getSession()->getPage()->findAll('xpath', $xpath)) {
                $this->execute("behat_general::i_click_on", array($this->escape($xpath), "xpath_element"));
            }
        }

        $this->execute("behat_general::i_click_on_in_the", array(get_string('hide'), 'link',
            "//tr[descendant::*[text() = " . $this->escape($gradeitem) . "]]", 'xpath_element'));
    }

    /**
     * Sets a calculated manual grade item. Needs a table with item name - idnumber relation.
     * The step requires you to be in the 'Gradebook setup' page.
     *
     * @Given /^I set "(?P<calculation_string>(?:[^"]|\\")*)" calculation for grade item "(?P<grade_item_string>(?:[^"]|\\")*)" with idnumbers:$/
     * @param string $calculation The calculation.
     * @param string $gradeitem The grade item name.
     * @param TableNode $TableNode The grade item name - idnumbers relation.
     */
    public function i_set_calculation_for_grade_item_with_idnumbers($calculation, $gradeitem, TableNode $data) {

        $gradeitem = behat_context_helper::escape($gradeitem);

        if ($this->running_javascript()) {
            $xpath = "//tr[contains(.,$gradeitem)]//*[contains(@class,'moodle-actionmenu')]//a[contains(@class,'toggle-display')]";
            if ($this->getSession()->getPage()->findAll('xpath', $xpath)) {
                $this->execute("behat_general::i_click_on", array($this->escape($xpath), "xpath_element"));
            }
        }

        // Going to edit calculation.
        $savechanges = get_string('savechanges', 'grades');
        $edit = behat_context_helper::escape(get_string('editcalculation', 'grades'));
        $linkxpath = "//a[./img[starts-with(@title,$edit) and contains(@title,$gradeitem)]]";
        $this->execute("behat_general::i_click_on", array($this->escape($linkxpath), "xpath_element"));

        // Mapping names to idnumbers.
        $datahash = $data->getRowsHash();
        foreach ($datahash as $gradeitem => $idnumber) {
            // This xpath looks for course, categories and items with the provided name.
            // Grrr, we can't equal in categoryitem and courseitem because there is a line jump...
            $inputxpath ="//input[@class='idnumber'][" .
                "parent::li[@class='item'][text()='" . $gradeitem . "']" .
                " or " .
                "parent::li[@class='categoryitem' or @class='courseitem']/parent::ul/parent::li[starts-with(text(),'" . $gradeitem . "')]" .
            "]";
            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($inputxpath, $idnumber));
        }

        $this->execute('behat_forms::press_button', get_string('addidnumbers', 'grades'));
        $this->execute('behat_forms::i_set_the_field_to', array(get_string('calculation', 'grades'), $calculation));
        $this->execute('behat_forms::press_button', $savechanges);

    }

    /**
     * Sets a calculated manual grade category total. Needs a table with item name - idnumber relation.
     * The step requires you to be in the 'Gradebook setup' page.
     *
     * @Given /^I set "(?P<calculation_string>(?:[^"]|\\")*)" calculation for grade category "(?P<grade_item_string>(?:[^"]|\\")*)" with idnumbers:$/
     * @param string $calculation The calculation.
     * @param string $gradeitem The grade item name.
     * @param TableNode $data The grade item name - idnumbers relation.
     */
    public function i_set_calculation_for_grade_category_with_idnumbers($calculation, $gradeitem, TableNode $data) {

        $gradecategorytotal = behat_context_helper::escape($gradeitem . ' total');
        $gradeitem = behat_context_helper::escape($gradeitem);

        if ($this->running_javascript()) {
            $xpath = "//tr[contains(.,$gradecategorytotal)]//*[contains(@class,'moodle-actionmenu')]" .
                "//a[contains(@class,'toggle-display')]";
            if ($this->getSession()->getPage()->findAll('xpath', $xpath)) {
                $this->execute("behat_general::i_click_on", array($this->escape($xpath), "xpath_element"));
            }
        }

        // Going to edit calculation.
        $savechanges = get_string('savechanges', 'grades');
        $edit = behat_context_helper::escape(get_string('editcalculation', 'grades'));
        $linkxpath = "//a[./img[starts-with(@title,$edit) and contains(@title,$gradeitem)]]";
        $this->execute("behat_general::i_click_on", array($this->escape($linkxpath), "xpath_element"));

        // Mapping names to idnumbers.
        $datahash = $data->getRowsHash();
        foreach ($datahash as $gradeitem => $idnumber) {
            // This xpath looks for course, categories and items with the provided name.
            // Grrr, we can't equal in categoryitem and courseitem because there is a line jump...
            $inputxpath = "//input[@class='idnumber'][" .
                "parent::li[@class='item'][text()='" . $gradeitem . "']" .
                " | " .
                "parent::li[@class='categoryitem' | @class='courseitem']" .
                "/parent::ul/parent::li[starts-with(text(),'" . $gradeitem . "')]" .
            "]";
            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($inputxpath, $idnumber));
        }

        $this->execute('behat_forms::press_button', get_string('addidnumbers', 'grades'));

        $this->execute('behat_forms::i_set_the_field_to', array(get_string('calculation', 'grades'), $calculation));
        $this->execute('behat_forms::press_button', $savechanges);
    }

    /**
     * Resets the weights for the grade category
     *
     * Teacher must be on the grade setup page.
     *
     * @Given /^I reset weights for grade category "(?P<grade_item_string>(?:[^"]|\\")*)"$/
     * @param $gradeitem
     */
    public function i_reset_weights_for_grade_category($gradeitem) {

        $steps = array();

        if ($this->running_javascript()) {
            $gradeitemliteral = behat_context_helper::escape($gradeitem);
            $xpath = "//tr[contains(.,$gradeitemliteral)]//*[contains(@class,'moodle-actionmenu')]//a[contains(@class,'toggle-display')]";
            if ($this->getSession()->getPage()->findAll('xpath', $xpath)) {
                $this->execute("behat_general::i_click_on", array($this->escape($xpath), "xpath_element"));
            }
        }

        $linktext = get_string('resetweights', 'grades', (object)array('itemname' => $gradeitem));
        $this->execute("behat_general::i_click_on", array($this->escape($linktext), "link"));
    }

    /**
     * Step allowing to test before-the-fix behaviour of the gradebook
     *
     * @Given /^gradebook calculations for the course "(?P<coursename_string>(?:[^"]|\\")*)" are frozen at version "(?P<version_string>(?:[^"]|\\")*)"$/
     * @param string $coursename
     * @param string $version
     */
    public function gradebook_calculations_for_the_course_are_frozen_at_version($coursename, $version) {
        global $DB;
        $courseid = $DB->get_field('course', 'id', array('shortname' => $coursename), MUST_EXIST);
        set_config('gradebook_calculations_freeze_' . $courseid, $version);
    }

    /**
     * Select the tab in the gradebook. We must be on one of the gradebook pages already.
     *
     * @param string $gradepath examples: "View > User report", "Letters > View", "Scales"
     */
    protected function select_in_gradebook_tabs($gradepath) {
        $gradepath = preg_split('/\s*>\s*/', trim($gradepath));
        if (count($gradepath) > 2) {
            throw new coding_exception('Grade path is too long (must have no more than two items separated with ">")');
        }

        $xpath = '//div[contains(@class,\'grade-navigation\')]';

        // If the first row of the grade-navigation tabs does not have $gradepath[0] as active tab, click on it.
        $link = '\'' . $this->escape($gradepath[0]) . '\'';
        $xpathrow1 = $xpath . '//ul[1]//*[contains(@class,\'active\') and contains(normalize-space(.), ' . $link . ')]';
        if (!$this->getSession()->getPage()->findAll('xpath', $xpathrow1)) {
            $this->find('xpath', $xpath . '//ul[1]/li/a[text()=' . $link . ']')->click();
            $this->wait_for_pending_js();
        }

        if (isset($gradepath[1])) {
            // If the second row of the grade-navigation tabs does not have $gradepath[1] as active tab, click on it.
            $link = '\'' . $this->escape($gradepath[1]) . '\'';
            $xpathrow2 = $xpath . '//ul[2]//*[contains(@class,\'active\') and contains(normalize-space(.), ' . $link . ')]';
            if (!$this->getSession()->getPage()->findAll('xpath', $xpathrow2)) {
                $this->find('xpath', $xpath . '//ul[2]/li/a[text()=' . $link . ']')->click();
                $this->wait_for_pending_js();
            }
        }
    }

    /**
     * Navigates to the course gradebook and selects a specified item from the grade navigation tabs.
     *
     * Examples:
     * - I navigate to "Setup > Gradebook setup" in the course gradebook
     * - I navigate to "Scales" in the course gradebook
     * - I navigate to "Letters > View" in the course gradebook
     * - I navigate to "View > User report" in the course gradebook // for teachers
     * - I navigate to "User report" in the course gradebook // for students
     *
     * @Given /^I navigate to "(?P<gradepath_string>(?:[^"]|\\")*)" in the course gradebook$/
     * @param string $gradepath
     */
    public function i_navigate_to_in_the_course_gradebook($gradepath) {
        // If we are not on one of the gradebook pages already, follow "Grades" link in the navigation block.
        $xpath = '//div[contains(@class,\'grade-navigation\')]';
        if (!$this->getSession()->getPage()->findAll('xpath', $xpath)) {
            $this->execute("behat_general::i_click_on_in_the", array(get_string('grades'), 'link',
                get_string('pluginname', 'block_navigation'), 'block'));
        }

        $this->select_in_gradebook_tabs($gradepath);
    }
}
