@mod @mod_quiz
Feature: Quiz reset
  In order to reuse past quizzes
  As a teacher
  I need to remove all previous data.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext   |
      | Test questions   | truefalse | TF1  | First question |
    And the following "activities" exist:
      | activity | name           | intro                 | course | idnumber |
      | quiz     | Test quiz name | Test quiz description | C1     | quiz1    |
    And quiz "Test quiz name" contains the following questions:
      | question | page |
      | TF1      | 1    |

  Scenario: Use course reset to clear all attempt data
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I press "Attempt quiz now"
    And I set the field "True" to "1"
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reset" node in "Course administration"
    And I set the following fields to these values:
        | Delete all quiz attempts | 1  |
    And I press "Reset course"
    And I press "Continue"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I navigate to "Results" in current page administration
    Then I should see "Attempts: 0"

  @javascript
  Scenario: Use course reset to remove user overrides.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I navigate to "User overrides" in current page administration
    And I press "Add user override"
    And I set the following fields to these values:
        | Override user    | Student1  |
        | Attempts allowed | 2 |
    And I press "Save"
    And I should see "Sam1 Student1"
    And I navigate to "Reset" node in "Course administration"
    And I set the following fields to these values:
        | Delete all user overrides | 1  |
    And I press "Reset course"
    And I press "Continue"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I navigate to "User overrides" in current page administration
    Then I should not see "Sam1 Student1"

  Scenario: Use course reset to remove group overrides.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I set the following fields to these values:
        | Override group    | Group 1  |
        | Attempts allowed | 2 |
    And I press "Save"
    And I should see "Group 1"
    And I navigate to "Reset" node in "Course administration"
    And I set the following fields to these values:
        | Delete all group overrides | 1  |
    And I press "Reset course"
    And I press "Continue"
    And I am on "Course 1" course homepage
    And I follow "Test quiz name"
    And I navigate to "Group overrides" in current page administration
    Then I should not see "Group 1"
