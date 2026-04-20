@javascript @theme_boost
Feature: Navbar user menu
  To keep the header layout consistent
  As a logged-in user
  I need the user menu to remain at the right edge of the navbar

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario Outline: User menu is in the right edge of the navbar
    Given I log in as "<username>"
    When I am on <location>
    Then "#usernavigation .usermenu-container" "css_element" should appear after "#usernavigation <preselector>" "css_element"

    Examples:
      | username | location                    | preselector                |
      | teacher1 | site homepage               | .popover-region-container  |
      | student1 | site homepage               | .popover-region-container  |
      | teacher1 | "Course 1" course homepage  | .editmode-switch-form      |
      | student1 | "Course 1" course homepage  | .popover-region-container  |
