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
 * Backup and restore actions to help behat feature files writting.
 *
 * @package    core_backup
 * @category   test
 * @copyright  2013 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../../lib/behat/behat_field_manager.php');
require_once(__DIR__ . '/../../../../../lib/tests/behat/behat_navigation.php');

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException,
    Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Backup-related steps definitions.
 *
 * @package    core_backup
 * @category   test
 * @copyright  2013 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_backup extends behat_base {

    /**
     * Backups the specified course using the provided options. If you are interested in restoring this backup would be
     * useful to provide a 'Filename' option.
     *
     * @Given /^I backup "(?P<course_fullname_string>(?:[^"]|\\")*)" course using this options:$/
     * @param string $backupcourse
     * @param TableNode $options Backup options or false if no options provided
     */
    public function i_backup_course_using_this_options($backupcourse, $options = false) {
        // We can not use other steps here as we don't know where the provided data
        // table elements are used, and we need to catch exceptions contantly.

        // Go to homepage.
        $this->getSession()->visit($this->locate_path('/?redirect=0'));
        $this->execute("behat_general::wait_until_the_page_is_ready");

        // Click the course link.
        $this->execute("behat_general::click_link", $backupcourse);

        // Click the backup link.
        $this->execute("behat_navigation::i_navigate_to_in_current_page_administration", get_string('backup'));

        // Initial settings.
        $this->fill_backup_restore_form($this->get_step_options($options, "Initial"));
        $this->execute("behat_forms::press_button", get_string('backupstage1action', 'backup'));

        // Schema settings.
        $this->fill_backup_restore_form($this->get_step_options($options, "Schema"));
        $this->execute("behat_forms::press_button", get_string('backupstage2action', 'backup'));

        // Confirmation and review, backup filename can also be specified.
        $this->fill_backup_restore_form($this->get_step_options($options, "Confirmation"));
        $this->execute("behat_forms::press_button", get_string('backupstage4action', 'backup'));

        // Waiting for it to finish.
        $this->execute("behat_general::wait_until_the_page_is_ready");

        // Last backup continue button.
        $this->execute("behat_general::i_click_on", array(get_string('backupstage16action', 'backup'), 'button'));
    }

    /**
     * Performs a quick (one click) backup of a course.
     *
     * Please note that because you can't set settings with this there is no way to know what the filename
     * that was produced was. It contains a timestamp making it hard to find.
     *
     * @Given /^I perform a quick backup of course "(?P<course_fullname_string>(?:[^"]|\\")*)"$/
     * @param string $backupcourse
     */
    public function i_perform_a_quick_backup_of_course($backupcourse) {
        // We can not use other steps here as we don't know where the provided data
        // table elements are used, and we need to catch exceptions contantly.

        // Go to homepage.
        $this->getSession()->visit($this->locate_path('/?redirect=0'));

        // Click the course link.
        $this->execute("behat_general::click_link", $backupcourse);

        // Click the backup link.
        $this->execute("behat_navigation::i_navigate_to_in_current_page_administration", get_string('backup'));

        // Initial settings.
        $this->execute("behat_forms::press_button", get_string('jumptofinalstep', 'backup'));

        // Waiting for it to finish.
        $this->execute("behat_general::wait_until_the_page_is_ready");

        // Last backup continue button.
        $this->execute("behat_general::i_click_on", array(get_string('backupstage16action', 'backup'), 'button'));
    }

    /**
     * Imports the specified origin course into the other course using the provided options.
     *
     * Keeping it separatelly from backup & restore, it the number of
     * steps and duplicate code becomes bigger a common method should
     * be generalized.
     *
     * @Given /^I import "(?P<from_course_fullname_string>(?:[^"]|\\")*)" course into "(?P<to_course_fullname_string>(?:[^"]|\\")*)" course using this options:$/
     * @param string $fromcourse
     * @param string $tocourse
     * @param TableNode $options
     */
    public function i_import_course_into_course($fromcourse, $tocourse, $options = false) {

        // We can not use other steps here as we don't know where the provided data
        // table elements are used, and we need to catch exceptions contantly.

        // Go to homepage.
        $this->getSession()->visit($this->locate_path('/?redirect=0'));
        $this->execute("behat_general::wait_until_the_page_is_ready");

        // Click the course link.
        $this->execute("behat_general::click_link", $tocourse);

        // Click the import link.
        $this->execute("behat_navigation::i_navigate_to_in_current_page_administration", get_string('import'));

        // Select the course.
        $exception = new ExpectationException('"' . $fromcourse . '" course not found in the list of courses to import from',
            $this->getSession());

        // The argument should be converted to an xpath literal.
        $fromcourse = behat_context_helper::escape($fromcourse);
        $xpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' ics-results ')]" .
            "/descendant::tr[contains(., $fromcourse)]" .
            "/descendant::input[@type='radio']";
        $radionode = $this->find('xpath', $xpath, $exception);
        $radiofield = new behat_form_field($this->getSession(), $radionode);
        $radiofield->set_value(1);

        $this->execute("behat_forms::press_button", get_string('continue'));

        // Initial settings.
        $this->fill_backup_restore_form($this->get_step_options($options, "Initial"));
        $this->execute("behat_forms::press_button", get_string('importbackupstage1action', 'backup'));

        // Schema settings.
        $this->fill_backup_restore_form($this->get_step_options($options, "Schema"));
        $this->execute("behat_forms::press_button", get_string('importbackupstage2action', 'backup'));

        // Run it.
        $this->execute("behat_forms::press_button", get_string('importbackupstage4action', 'backup'));

        // Wait to ensure restore is complete.
        $this->execute("behat_general::wait_until_the_page_is_ready");

        // Continue and redirect to 'to' course.
        $this->execute("behat_general::i_click_on", array(get_string('continue'), 'button'));
    }

    /**
     * Restores the backup into the specified course and the provided options.
     *
     * You should be in the 'Restore' page where the backup is.
     *
     * @Given /^I restore "(?P<backup_filename_string>(?:[^"]|\\")*)" backup into "(?P<existing_course_fullname_string>(?:[^"]|\\")*)" course using this options:$/
     * @param string $backupfilename
     * @param string $existingcourse
     * @param TableNode $options Restore forms options or false if no options provided
     */
    public function i_restore_backup_into_course_using_this_options($backupfilename, $existingcourse, $options = false) {

        // Confirm restore.
        $this->select_backup($backupfilename);

        // The argument should be converted to an xpath literal.
        $existingcourse = behat_context_helper::escape($existingcourse);

        // Selecting the specified course (we can not call behat_forms::select_radio here as is in another behat subcontext).
        $radionodexpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' bcs-existing-course ')]" .
            "/descendant::div[contains(concat(' ', normalize-space(@class), ' '), ' restore-course-search ')]" .
            "/descendant::tr[contains(., $existingcourse)]" .
            "/descendant::input[@type='radio']";
        $this->execute("behat_general::i_click_on", array($radionodexpath, 'xpath_element'));

        // Pressing the continue button of the restore into an existing course section.
        $continuenodexpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' bcs-existing-course ')]" .
            "/descendant::input[@type='submit'][@value='" . get_string('continue') . "']";
        $this->execute("behat_general::i_click_on", array($continuenodexpath, 'xpath_element'));

        // Common restore process using provided key/value options.
        $this->process_restore($options);
    }

    /**
     * Restores the specified backup into a new course using the provided options.
     *
     * You should be in the 'Restore' page where the backup is.
     *
     * @Given /^I restore "(?P<backup_filename_string>(?:[^"]|\\")*)" backup into a new course using this options:$/
     * @param string $backupfilename
     * @param TableNode $options Restore forms options or false if no options provided
     */
    public function i_restore_backup_into_a_new_course_using_this_options($backupfilename, $options = false) {

        // Confirm restore.
        $this->select_backup($backupfilename);

        // The first category in the list.
        $radionodexpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' bcs-new-course ')]" .
            "/descendant::div[contains(concat(' ', normalize-space(@class), ' '), ' restore-course-search ')]" .
            "/descendant::input[@type='radio']";
        $this->execute("behat_general::i_click_on", array($radionodexpath, 'xpath_element'));

        // Pressing the continue button of the restore into an existing course section.
        $continuenodexpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' bcs-new-course ')]" .
            "/descendant::input[@type='submit'][@value='" . get_string('continue') . "']";
        $this->execute("behat_general::i_click_on", array($continuenodexpath, 'xpath_element'));

        // Common restore process using provided key/value options.
        $this->process_restore($options);
    }

    /**
     * Merges the backup into the current course using the provided restore options.
     *
     * You should be in the 'Restore' page where the backup is.
     *
     * @Given /^I merge "(?P<backup_filename_string>(?:[^"]|\\")*)" backup into the current course using this options:$/
     * @param string $backupfilename
     * @param TableNode $options Restore forms options or false if no options provided
     */
    public function i_merge_backup_into_the_current_course($backupfilename, $options = false) {

        // Confirm restore.
        $this->select_backup($backupfilename);

        // Merge without deleting radio option.
        $radionodexpath = "//div[contains(concat(' ', normalize-space(@class), ' '), 'bcs-current-course')]" .
            "/descendant::input[@type='radio'][@name='target'][@value='1']";
        $this->execute("behat_general::i_click_on", array($radionodexpath, 'xpath_element'));

        // Pressing the continue button of the restore merging section.
        $continuenodexpath = "//div[contains(concat(' ', normalize-space(@class), ' '), 'bcs-current-course')]" .
            "/descendant::input[@type='submit'][@value='" . get_string('continue') . "']";
        $this->execute("behat_general::i_click_on", array($continuenodexpath, 'xpath_element'));

        // Common restore process using provided key/value options.
        $this->process_restore($options);
    }

    /**
     * Merges the backup into the current course after deleting this contents, using the provided restore options.
     *
     * You should be in the 'Restore' page where the backup is.
     *
     * @Given /^I merge "(?P<backup_filename_string>(?:[^"]|\\")*)" backup into the current course after deleting it's contents using this options:$/
     * @param string $backupfilename
     * @param TableNode $options Restore forms options or false if no options provided
     */
    public function i_merge_backup_into_current_course_deleting_its_contents($backupfilename, $options = false) {

        // Confirm restore.
        $this->select_backup($backupfilename);

        // Delete contents radio option.
        $radionodexpath = "//div[contains(concat(' ', normalize-space(@class), ' '), 'bcs-current-course')]" .
            "/descendant::input[@type='radio'][@name='target'][@value='0']";
        $this->execute("behat_general::i_click_on", array($radionodexpath, 'xpath_element'));

        // Pressing the continue button of the restore merging section.
        $continuenodexpath = "//div[contains(concat(' ', normalize-space(@class), ' '), 'bcs-current-course')]" .
            "/descendant::input[@type='submit'][@value='" . get_string('continue') . "']";
        $this->execute("behat_general::i_click_on", array($continuenodexpath, 'xpath_element'));

        // Common restore process using provided key/value options.
        $this->process_restore($options);
    }

    /**
     * Selects the backup to restore.
     *
     * @throws ExpectationException
     * @param string $backupfilename
     * @return void
     */
    protected function select_backup($backupfilename) {

        // Using xpath as there are other restore links before this one.
        $exception = new ExpectationException('The "' . $backupfilename . '" backup file can not be found in this page',
            $this->getSession());

        // The argument should be converted to an xpath literal.
        $backupfilename = behat_context_helper::escape($backupfilename);

        $xpath = "//tr[contains(., $backupfilename)]/descendant::a[contains(., '" . get_string('restore') . "')]";
        $restorelink = $this->find('xpath', $xpath, $exception);
        $restorelink->click();

        // Confirm the backup contents.
        $this->find_button(get_string('continue'))->press();
    }

    /**
     * Executes the common steps of all restore processes.
     *
     * @param TableNode $options The backup and restore options or false if no options provided
     * @return void
     */
    protected function process_restore($options) {

        // We can not use other steps here as we don't know where the provided data
        // table elements are used, and we need to catch exceptions contantly.

        // Settings.
        $this->fill_backup_restore_form($this->get_step_options($options, "Settings"));
        $this->execute("behat_forms::press_button", get_string('restorestage4action', 'backup'));

        // Schema.
        $this->fill_backup_restore_form($this->get_step_options($options, "Schema"));
        $this->execute("behat_forms::press_button", get_string('restorestage8action', 'backup'));

        // Review, no options here.
        $this->execute("behat_forms::press_button", get_string('restorestage16action', 'backup'));

        // Wait till the final button is visible.
        $this->execute("behat_general::wait_until_the_page_is_ready");

        // Last restore continue button, redirected to restore course after this.
        $this->execute("behat_general::i_click_on", array(get_string('restorestage32action', 'backup'), 'button'));
    }

    /**
     * Tries to fill the current page form elements with the provided options.
     *
     * This step is slow as it spins over each provided option, we are
     * not expected to have lots of provided options, anyways, is better
     * to be conservative and wait for the elements to appear rather than
     * to have false failures.
     *
     * @param TableNode $options The backup and restore options or false if no options provided
     * @return void
     */
    protected function fill_backup_restore_form($options) {

        // Nothing to fill if no options are provided.
        if (!$options) {
            return;
        }

        // If we find any of the provided options in the current form we should set the value.
        $datahash = $options->getRowsHash();
        foreach ($datahash as $locator => $value) {
            $field = behat_field_manager::get_form_field_from_label($locator, $this);
            $field->set_value($value);
        }
    }

    /**
     * Get the options specific to this step of the backup/restore process.
     *
     * @param TableNode $options The options table to filter
     * @param string $step The name of the step
     * @return TableNode The filtered options table
     * @throws ExpectationException
     */
    protected function get_step_options($options, $step) {
        // Nothing to fill if no options are provided.
        if (!$options) {
            return;
        }

        $rows = $options->getRows();
        $newrows = array();
        foreach ($rows as $k => $data) {
            if (count($data) !== 3) {
                // Not enough information to guess the page.
                throw new ExpectationException("The backup/restore step must be specified for all backup options",
                    $this->getSession());
            } else if ($data[0] == $step) {
                unset($data[0]);
                $newrows[] = $data;
            }
        }
        $pageoptions = new TableNode($newrows);

        return $pageoptions;
    }
}
