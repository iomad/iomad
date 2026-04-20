@mod @mod_assign @javascript
Feature: Allocate markers to student submissions
  In order to use the marking workflow and record provisional marks before calculating a final grade
  As a teacher
  I need to allocate markers to student submissions

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | teacher2 | Teacher   | Two      | teacher2@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
    And the following "activity" exists:
      | activity              | assign       |
      | course                | C1           |
      | idnumber              | A1           |
      | name                  | Assignment 1 |
      | section               | 1            |
      | completion            | 1            |
      | markingworkflow       | 1            |
      | markingallocation     | 1            |
      | markercount           | 2            |
      | grade[modgrade_type]  | point        |
      | grade[modgrade_point] | 100          |

  Scenario: Allocate markers to students via the Allocate Markers page
    Given I am on the "A1" "assign activity" page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I set the field "selectall" to "1"
    And I click on "Allocate marker" "button" in the "sticky-footer" "region"
    And I click on "Allocate marker" "button" in the ".modal-footer" "css_element"
    When I select "Teacher One" from the "Allocated marker 1" singleselect
    And I select "Teacher Two" from the "Allocated marker 2" singleselect
    And I press "Save changes"
    Then the following should exist in the "submissions" table:
      | First name  | Marker 1    | Marker 2    |
      | Student One | Teacher One | Teacher Two |
      | Student Two | Teacher One | Teacher Two |

  Scenario: Allocate markers to students via the Quick Grading page
    Given I am on the "A1" "assign activity" page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I click on "Quick grading" "checkbox"
    When I set the field "Allocated marker 1" in the "Student One" "table_row" to "Teacher One"
    And I set the field "Allocated marker 1" in the "Student Two" "table_row" to "Teacher One"
    And I set the field "Allocated marker 2" in the "Student One" "table_row" to "Teacher Two"
    And I set the field "Allocated marker 2" in the "Student Two" "table_row" to "Teacher Two"
    And I click on "Save" "button" in the "sticky-footer" "region"
    And I press "Continue"
    And I click on "Quick grading" "checkbox"
    Then the following should exist in the "submissions" table:
      | First name  | Marker 1    | Marker 2    |
      | Student One | Teacher One | Teacher Two |
      | Student Two | Teacher One | Teacher Two |

  Scenario: Allocate markers to students via the Grader page
    Given I am on the "A1" "assign activity" page logged in as teacher1
    And I go to "Student One" "Assignment 1" activity advanced grading page
    When I set the field "Marker 1" to "Teacher One"
    And I set the field "Marker 2" to "Teacher Two"
    And I press "Save changes"
    And I am on the "A1" "assign activity" page
    And I navigate to "Submissions" in current page administration
    Then the following should exist in the "submissions" table:
      | First name  | Marker 1    | Marker 2    |
      | Student One | Teacher One | Teacher Two |
      | Student Two |             |             |

  Scenario: Allocate a duplicate marker to a student
    Given I am on the "A1" "assign activity" page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I set the field "selectall" to "1"
    And I click on "Allocate marker" "button" in the "sticky-footer" "region"
    And I click on "Allocate marker" "button" in the ".modal-footer" "css_element"
    When I select "Teacher One" from the "Allocated marker 1" singleselect
    And I select "Teacher One" from the "Allocated marker 2" singleselect
    And I press "Save changes"
    Then the following should exist in the "submissions" table:
      | First name  | Marker 1    | Marker 2 |
      | Student One | Teacher One |          |
      | Student Two | Teacher One |          |
    And the following should not exist in the "submissions" table:
      | Marker 2    |
      | Teacher One |
      | Teacher One |

  Scenario: Only allocated markers see the mark button
    Given I am on the "A1" "assign activity" page logged in as teacher1
    And I should not see "Mark allocated submissions" in the ".tertiary-navigation" "css_element"
    And I should see "Grade" in the ".tertiary-navigation" "css_element"
    And I navigate to "Submissions" in current page administration
    And I should not see "Mark allocated submissions" in the ".tertiary-navigation" "css_element"
    And I should see "Grade" in the ".tertiary-navigation" "css_element"
    And I set the field "selectall" to "1"
    And I click on "Allocate marker" "button" in the "sticky-footer" "region"
    And I click on "Allocate marker" "button" in the ".modal-footer" "css_element"
    And I select "Teacher One" from the "Allocated marker 1" singleselect
    When I press "Save changes"
    Then I should see "Mark allocated submissions" in the ".tertiary-navigation" "css_element"
    And I should see "Grade" in the ".tertiary-navigation" "css_element"
    And I am on the "A1" "assign activity" page
    And I should see "Mark allocated submissions" in the ".tertiary-navigation" "css_element"
    And I should see "Grade" in the ".tertiary-navigation" "css_element"
