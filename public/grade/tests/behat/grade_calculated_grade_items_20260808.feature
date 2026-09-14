@core @core_grades
Feature: Gradebook calculation freeze for 20260808
  In order to prevent existing grades from changing unexpectedly after upgrade
  As a teacher
  I need to be able to review and accept grade calculation changes

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | teacher1 | Teacher   | 1        | teacher1@example.com | t1       |
      | student1 | Student   | 1        | student1@example.com | s1       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: A frozen gradebook displays the grade calculation changes notice
    Given gradebook calculations for the course "C1" are frozen at version "20260808"
    And I am on the "Course 1" "grades > gradebook setup" page logged in as "teacher1"
    Then I should see "Accept grade changes and fix calculation errors"

  @javascript
  Scenario: Accepting the grade calculation changes removes the freeze notice
    Given gradebook calculations for the course "C1" are frozen at version "20260808"
    And I am on the "Course 1" "grades > gradebook setup" page logged in as "teacher1"
    And I should see "Accept grade changes and fix calculation errors"
    When I click on "Accept grade changes and fix calculation errors" "button"
    And I wait until the page is ready
    Then I should not see "Accept grade changes and fix calculation errors"

  @javascript
  Scenario: A grade affected by the pre-fix penalty calculation is unchanged while frozen and corrected after accepting the changes
    Given the following "activities" exist:
      | activity | course | name         | idnumber | grade |
      | assign   | C1     | Assignment 1 | as01     | 200   |
    And I am on the "as01" Activity page logged in as teacher1
    And I go to "Student 1" "Assignment" activity advanced grading page
    And I change window size to "medium"
    And I set the field "Grade out of 200" to "50"
    And I press "Save changes"
    And I log out
    And I am on the "Course 1" "grades > gradebook setup" page logged in as "admin"
    And I set the following settings for grade item "Assignment 1" of type "gradeitem" on "setup" page:
      | Multiplicator | 2 |
      | Offset        | 5 |
    # Simulate the pre-MDL-88407 bug by directly storing the legacy penalised rawgrade and finalgrade.
    # The Assignment grade is 50, but the legacy calculation stored (50 * 2 + 5 - 20) = 85 as rawgrade,
    # then applied the multiplicator and offset again: (85 * 2 + 5) = 175.
    And the grade for "student1" in the grade item "as01" is set to:
      | rawgrade     | 85  |
      | deductedmark | 20  |
      | finalgrade   | 175 |
    And gradebook calculations for the course "C1" are frozen at version "20260808"
    And I am on the "Course 1" "grades > Grader report > View" page logged in as "teacher1"
    Then I should see "175.00" in the "Student 1" "table_row"
    When I navigate to "Setup > Gradebook setup" in the course gradebook
    And I click on "Accept grade changes and fix calculation errors" "button"
    And I wait until the page is ready
    And I navigate to "View > Grader report" in the course gradebook
    And I should see "65.00" in the "Student 1" "table_row"
