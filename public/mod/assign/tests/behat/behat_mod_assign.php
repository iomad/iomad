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
 * Behat custom steps and configuration for mod_bigbluebuttonbn.
 *
 * @package   mod_assign
 * @category  test
 * @copyright 2024 Simey Lameze <simey@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;

/**
 * Behat custom steps and configuration for mod_assign.
 *
 * @package   mod_assign
 * @category  test
 * @copyright 2024 Simey Lameze <simey@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_assign extends behat_base {

    /**
     * Check that the marking guide information is displayed correctly.
     *
     * @Then /^I should see the marking guide information displayed as:$/
     * @param TableNode $table The table of marking guide information to check.
     */
    public function i_should_see_marking_guide_information(TableNode $table) {

        if (!$table->getRowsHash()) {
            return;
        }

        $criteriacheck = 1;
        foreach ($table as $row) {

            $locator = "//table[@id='guide0-criteria']/tbody/tr[$criteriacheck]/td";

            $this->assertSession()->elementContains('xpath', "{$locator}[@class='descriptionreadonly']", $row['criteria']);
            $this->assertSession()->elementContains('xpath', "{$locator}[@class='descriptionreadonly']", $row['description']);

            if (!empty($row['remark'])) {
                $this->assertSession()->elementContains('xpath', "{$locator}[@class='remark']", $row['remark']);
            }

            if (!empty($row['maxscore'])) {
                $this->assertSession()->elementContains('xpath', "{$locator}[@class='descriptionreadonly']", $row['maxscore']);
            }

            if (!empty($row['criteriascore'])) {
                $this->assertSession()->elementContains('xpath', "{$locator}[@class='score']", $row['criteriascore']);
            }

            $criteriacheck++;
        }
    }

    /**
     * Enable grade penalty.
     *
     * @Given I enable grade penalties for assignment
     */
    public function i_enable_grade_penalties_for_assignment(): void {
        global $DB;

        \core_grades\penalty_manager::enable_module('assign');
        \core\plugininfo\gradepenalty::enable_plugin('duedate', true);

        $rule = ['contextid' => 1, 'overdueby' => DAYSECS, 'penalty' => 10, 'sortorder' => 0];
        $DB->insert_record('gradepenalty_duedate_rule', (object) $rule);
    }

    /**
     * Goes to the student's advanced marking page.
     *
     * @Given /^I go to "(?P<user_fullname>(?:[^"]|\\")*)" "(?P<activity_name>(?:[^"]|\\")*)" activity advanced marking page$/
     * @param string $userfullname The user's full name including firstname and lastname.
     * @param string $activityname The activity name
     */
    public function i_go_to_activity_advanced_marking_page(string $userfullname, string $activityname): void {

        // Step to access the user grade page from the grading page.
        $this->execute('behat_navigation::go_to_breadcrumb_location', $this->escape($activityname));

        $this->execute('behat_general::click_link', get_string('gradeitem:submissions', 'mod_assign'));

        $this->execute(
            'behat_general::i_click_on_in_the',
            [
                $this->escape(get_string('markactions', 'assign')),
                'actionmenu',
                $this->escape($userfullname),
                'table_row',
            ]
        );

        $this->execute('behat_action_menu::i_choose_in_the_open_action_menu', get_string('markverb', 'mod_assign'));
    }
}
