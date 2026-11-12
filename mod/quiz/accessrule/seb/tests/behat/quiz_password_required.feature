@mod @mod_quiz @quizaccess @quizaccess_seb
Feature: Quiz password required for Safe Exam Browser
  In order to enforce security policies
  As an administrator
  I need to ensure quizzes requiring SEB also have a password set if globally required

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name   | course | idnumber |
      | quiz     | Quiz 1 | C1     | quiz1    |
    # Enable the global admin setting
    And the following config values are set as admin:
      | quizpasswordrequired | 1 | quizaccess_seb |

  Scenario: A Safe Exam Browser quiz refuses to save with no quiz password
    Given I am on the "Quiz 1" "quiz activity editing" page logged in as "teacher1"
    When I set the following fields to these values:
      | Require the use of Safe Exam Browser | Yes – Configure manually |
      | Require password                     |                          |
    And I press "Save and return to course"
    Then I should see "Current settings require quizzes using the Safe Exam Browser to have a quiz password set." in the "Require password" "form_row"

  Scenario Outline: A quiz saves correctly with valid Safe Exam Browser password settings
    Given I am on the "Quiz 1" "quiz activity editing" page logged in as "teacher1"
    When I set the following fields to these values:
      | Require the use of Safe Exam Browser | <seb_setting> |
      | Require password                     | <password>    |
    And I press "Save and return to course"
    Then I should not see "Current settings require quizzes using the Safe Exam Browser to have a quiz password set."
    # Reopen the form, so that we check the settings were really saved. The redisplayed
    # form after a failed save would also pass a plain "I should see" check.
    And I am on the "Quiz 1" "quiz activity editing" page
    And the field "Require the use of Safe Exam Browser" matches value "<seb_setting>"
    And the field "Require password" matches value "<password>"

    Examples:
      | seb_setting              | password    |
      | Yes – Configure manually | supersecret |
      | No                       |             |
