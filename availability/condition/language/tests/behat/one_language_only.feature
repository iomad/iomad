@ewallah @availability @availability_language @javascript
Feature: one language only availability_language
  When there is only one language installed
  As a teacher
  I cannot use a language condition to prevent student access

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "activities" exist:
      | activity | name       | intro      | course | idnumber |
      | page     | PageName1  | PageDesc1  | C1     | PAGE1    |
    And the following "users" exist:
      | username |
      | teacher1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  Scenario: Only one language pack installed
    When I am on the "PageName1" "page activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Language" "button" should not exist in the "Add restriction..." "dialogue"
