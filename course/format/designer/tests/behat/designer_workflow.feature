@format @format_designer @javascript @designer_workflow
Feature: Check other workflow actions in designer format
  In order to rearrange my course contents
  As a teacher
  I need to check workflow actions in designer format

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | First        | teacher1@example.com |
      | student1 | Student   | First       | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | Enable completion tracking |
      | Course 1 | C1        | designer | 0             | 5           | yes                      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | manager        |
      | student1 | C1     | student        |

  Scenario: Check the staffs in course header
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Choose the staff role | manager |
    And I click on "Save and display" "button"
    Then I am on "Course 1" course homepage
    And I should see "Teacher First" in the "#courseStaffinfoControls .carousel-item .title-block h4" "css_element"
    And I should see "teacher1@example.com" in the "#courseStaffinfoControls .carousel-item .title-block h4 span" "css_element"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Choose the staff role | Student |
    And I click on "Save and display" "button"
    Then I am on "Course 1" course homepage
    And I should see "Student First" in the "#courseStaffinfoControls .carousel-item .title-block h4" "css_element"
    And I should see "student1@example.com" in the "#courseStaffinfoControls .carousel-item .title-block h4 span" "css_element"
