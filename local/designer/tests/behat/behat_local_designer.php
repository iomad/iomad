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
 * @package    local_designer
 * @category   test
 * @copyright  2020 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
Behat\Mink\Exception\DriverException as DriverException,
Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Designer course format steps definitions.
 *
 * @package    local_designer
 * @category   test
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_designer extends behat_base {

    /**
     * Opens a section edit menu in designer format.
     *
     * @Given /^I open section "(?P<section_number>\d+)" edit menu in designer$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $sectionnumber
     */
    public function i_open_section_edit_menu_designer($sectionnumber) {
        if (!$this->running_javascript()) {
            throw new DriverException('Section edit menu not available when Javascript is disabled');
        }

        // Wait for section to be available, before clicking on the menu.
        $this->execute("behat_course::i_wait_until_section_is_available", $sectionnumber);
        $xpa = "//li[@id='section-" . $sectionnumber . "']";
        $xpa .= "/descendant::div[contains(@class, 'section_action_menu')]/descendant::a[contains(@class, 'dropdown-toggle')]";
        $exception = new ExpectationException('Designer Section "' . $sectionnumber . '" was not found', $this->getSession());
        $menu = $this->find('xpath', $xpa, $exception);
        $menu->click();

        $xpath2 = "//li[@id='section-" . $sectionnumber . "']";
        $xpath2 .= "/descendant::div[contains(@class, 'section_action_menu')]/descendant::a[contains(@class, 'edit')]";
        $menu = $this->find('xpath', $xpath2, $exception);
        $menu->click();
    }

    /**
     * Check that the focus mode enable.
     *
     * @Given /^I check designer css "(?P<color>(?:[^"]|\\")*)" "(?P<selector>(?:[^"]|\\")*)" "(?P<type>(?:[^"]|\\")*)"$/
     * @param string $value
     * @param string $selector
     * @param string $type
     * @throws ExpectationException
     */
    public function i_check_designer_css($value, $selector, $type): void {
        $stylejs = "
            return (
                Y.one('{$selector}').getComputedStyle('{$type}')
            )
        ";
        if (strpos($this->evaluate_script($stylejs), $value) === false) {
            throw new ExpectationException("Doesn't working correct style", $this->getSession());
        }
    }

    /**
     * Check that the focus mode enable.
     *
     * @Given /^I check not designer css "(?P<color>(?:[^"]|\\")*)" "(?P<selector>(?:[^"]|\\")*)" "(?P<type>(?:[^"]|\\")*)"$/
     * @param string $color
     * @param string $selector
     * @param string $type
     * @throws ExpectationException
     */
    public function i_check_not_designer_css($color, $selector, $type): void {
        $stylejs = "
            return (
                Y.one('{$selector}').getComputedStyle('{$type}')
            )
        ";
        if ($this->evaluate_script($stylejs) == $color) {
            throw new ExpectationException("Doesn't working correct designer style", $this->getSession());
        }
    }

    // @codingStandardsIgnoreStart.
    /**
     * Checks if the tile photo is set to a certain value
     *
     * @Given /^course "(?P<course_name>(?:[^"]|\\")*)" section "(?P<section_number>\d+)" should show mask "(?P<photo_name>(?:[^"]|\\")*)"$/
     * @throws \Behat\Mink\Exception\ElementNotFoundException Thrown by behat_base::find
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $coursename
     * @param int $sectionnumber
     * @param string $maskname
     * @return string The style of the image container
     */
    public function section_should_show_mask($coursename, $sectionnumber, $maskname) {
        // @codingStandardsIgnoreEnd.
        global $CFG, $DB;
        $course = $DB->get_record('course', ['fullname' => $coursename]);

        $context = context_course::instance($course->id);

        $sectionid = $DB->get_field(
            'course_sections', 'id', ['course' => $course->id, 'section' => $sectionnumber], MUST_EXIST
        );
        $sectionoptions = (object) course_get_format($course)->get_section_options($sectionid);
        $maskimage = $sectionoptions->sectiondesignermaskimage ?? '';

        if (!empty($maskimage)) {
            $maskimage = \local_designer\options::get_mask_image($maskimage, LOCAL_DESIGNER_SECTION_MASK_AREA);
        } else {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Mask image not added to $coursename designer $sectionnumber mask $maskname ",
                $this->getSession()
            );
        }

        $selector = 'li#section-'.$sectionnumber.' .section-background-style';
        $stylejs = "
            return (
                Y.one('{$selector}').getComputedStyle('-webkit-mask-image')
            )
        ";
        if (strpos($this->evaluate_script($stylejs), $maskimage) === false ) {
            throw new ExpectationException("Doesn't working correct designer style", $this->getSession());
        }
    }

    // @codingStandardsIgnoreStart.
    /**
     * Checks if the tile photo is set to a certain value
     *
     * @Given /^course "(?P<course_name>(?:[^"]|\\")*)" activity "(?P<activityname>(?:[^"]|\\")*)" should show mask "(?P<photo_name>(?:[^"]|\\")*)"$/
     * @throws \Behat\Mink\Exception\ElementNotFoundException Thrown by behat_base::find
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $coursename
     * @param string $activityname
     * @param string $maskname
     * @return string The style of the image container
     */
    public function activity_should_show_mask($coursename, $activityname, $maskname) {
        // @codingStandardsIgnoreEnd.
        global $CFG, $DB;
        $course = $DB->get_record('course', ['fullname' => $coursename]);
        $context = context_course::instance($course->id);
        $cmid = $DB->get_field(
            'course_modules', 'id', ['course' => $course->id, 'idnumber' => $activityname], MUST_EXIST
        );

        $options = (object) \local_designer\options::get_options($cmid);
        $mask = $options->maskstyle ?? '';
        if (isset($mask['image']) && !empty($mask['image'])) {
            $maskimage = \local_designer\options::get_mask_image($mask['image'], LOCAL_DESIGNER_ACTIVITY_MASK_AREA);
        } else {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Mask image not added to $coursename designer $cmid mask $maskname ",
                $this->getSession()
            );
        }

        $selector = 'li#module-'.$cmid.' .activity-background-style';
        $stylejs = "
            return (
                Y.one('{$selector}').getComputedStyle('-webkit-mask-image')
            )
        ";
        if (strpos($this->evaluate_script($stylejs), $maskimage) === false) {
            throw new ExpectationException("Doesn't working correct designer style", $this->getSession());
        }
    }

    // @codingStandardsIgnoreStart.
    /**
     * Checks if the tile photo is set to a certain value
     *
     * @Given /^course "(?P<course_name>(?:[^"]|\\")*)" activity "(?P<activityname>(?:[^"]|\\")*)" should show mask position "(?P<photo_name>(?:[^"]|\\")*)"$/
     * @throws \Behat\Mink\Exception\ElementNotFoundException Thrown by behat_base::find
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $coursename
     * @param string $activityname
     * @param string $maskposition
     * @return string The style of the image container
     */
    public function activity_should_show_mask_position($coursename, $activityname, $maskposition) {
        // @codingStandardsIgnoreEnd.
        global $CFG, $DB;
        $course = $DB->get_record('course', ['fullname' => $coursename]);
        $cmid = $DB->get_field(
            'course_modules', 'id', ['course' => $course->id, 'idnumber' => $activityname], MUST_EXIST
        );
        $selector = 'li#module-'.$cmid.' .activity-background-style';
        $stylejs = "
            return (
                Y.one('{$selector}').getComputedStyle('-webkit-mask-position')
            )
        ";
        if ($this->evaluate_script($stylejs) != $maskposition) {
            throw new ExpectationException("Doesn't working correct designer style", $this->getSession());
        }
    }

    // @codingStandardsIgnoreStart.
    /**
     * Checks if the tile photo is set to a certain value
     *
     * @Given /^course "(?P<course_name>(?:[^"]|\\")*)" activity "(?P<activityname>(?:[^"]|\\")*)" should show mask size "(?P<photo_name>(?:[^"]|\\")*)"$/
     * @throws \Behat\Mink\Exception\ElementNotFoundException Thrown by behat_base::find
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $coursename
     * @param string $activityname
     * @param string $masksize
     * @return string The style of the image container
     */
    public function activity_should_show_mask_size($coursename, $activityname, $masksize) {
        // @codingStandardsIgnoreEnd.
        global $CFG, $DB;
        $course = $DB->get_record('course', ['fullname' => $coursename]);
        $cmid = $DB->get_field(
            'course_modules', 'id', ['course' => $course->id, 'idnumber' => $activityname], MUST_EXIST
        );
        $selector = 'li#module-'.$cmid.' .activity-background-style';
        $stylejs = "
            return (
                Y.one('{$selector}').getComputedStyle('-webkit-mask-size')
            )
        ";
        if ($this->evaluate_script($stylejs) != $masksize) {
            throw new ExpectationException("Doesn't working correct designer style", $this->getSession());
        }
    }

    // @codingStandardsIgnoreStart.
    /**
     * Checks if the tile photo is set to a certain value
     *
     * @Given activity :arg1 should contain style :arg2 :arg3
     * @throws \Behat\Mink\Exception\ElementNotFoundException Thrown by behat_base::find
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $idnumber
     * @param string $style
     * @param string $value
     * @return string The style of the image container
     */
    public function activity_should_contain_style($idnumber, $style, $value) {
        global $CFG, $DB;
        $cmid = $DB->get_field(
            'course_modules', 'id', ['idnumber' => $idnumber], MUST_EXIST
        );

        $selector = 'li#module-'.$cmid.' .activity-background-style';
        $stylejs = "
            return (
                Y.one('{$selector}').getComputedStyle('$style')
            )
        ";
        echo $this->evaluate_script($stylejs);
        if ($this->evaluate_script($stylejs) != $value) {
            throw new ExpectationException("Doesn't working correct designer style", $this->getSession());
        }
    }

    // @codingStandardsIgnoreStart.
    /**
     * Checks if the tile photo is set to a certain value
     *
     * @Given Element :arg1 should contain style :arg2 :arg3
     * @throws \Behat\Mink\Exception\ElementNotFoundException Thrown by behat_base::find
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $selector
     * @param string $style
     * @param string $value
     * @return string The style of the image container
     */
    public function element_should_contain_style($selector, $style, $value) {
        $stylejs = "
            return (
                Y.one('{$selector}').getComputedStyle('$style')
            )
        ";
        if ($this->evaluate_script($stylejs) != $value) {
            throw new ExpectationException("Doesn't working correct designer style", $this->getSession());
        }
    }

    /**
     * Open a add activity form page.
     *
     * @Given I add a :activity activity to course :coursefullname designer section :sectionnum
     * @throws coding_exception
     * @param string $activity The activity name.
     * @param string $coursefullname The course full name of the course.
     * @param string $sectionnum The section number.
     */
    public function i_add_to_course_designersection(string $activity, string $coursefullname, string $sectionnum): void {
        $addurl = new moodle_url('/course/modedit.php', [
            'add' => $activity,
            'course' => $this->get_course_id($coursefullname),
            'section' => intval($sectionnum),
        ]);
        $this->execute('behat_general::i_visit', [$addurl]);
    }
}
