@core @core_grades
Feature: Gradebook calculations for calculated grade items before the fix 20150627
  In order to make sure the grades are not changed after upgrade
  As a teacher
  I need to be able to freeze gradebook calculations

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And gradebook calculations for the course "C1" are frozen at version "20150627"
    And the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | teacher1 | Teacher   | 1        | teacher1@example.com | t1       |
      | student1 | Student   | 1        | student1@example.com | s1       |
      | student2 | Student   | 2        | student2@example.com | s2       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Setup > Gradebook setup" in the course gradebook

  @javascript
  Scenario: The max grade for a category item, with a calculation using Natural aggregation, can be changed
    Given I press "Add category"
    And I set the following fields to these values:
      | Category name | Calc cat |
    And I press "Save changes"
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name | grade item 1 |
      | Grade category | Calc cat |
    And I press "Save changes"
    And I set "=[[gi1]]/2" calculation for grade category "Calc cat" with idnumbers:
      | grade item 1 | gi1 |
    And I set the following settings for grade item "Calc cat":
      | Maximum grade | 50 |
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I give the grade "75.00" to the user "Student 1" for the grade item "grade item 1"
    And I press "Save changes"
    And I navigate to "View > User report" in the course gradebook
    And I select "Student 1" from the "Select all or one user" singleselect
    And the following should exist in the "user-grade" table:
      | Grade item                          | Calculated weight | Grade  | Range | Percentage | Contribution to course total |
      | grade item 1                        | -                 | 75.00  | 0–100 | 75.00 %    | -                            |
      | Calc cat totalInclude empty grades. | 100.00 %          | 37.50  | 0–100 | 37.50 %    | -                            |
      | Course total                        | -                 | 37.50  | 0–100 | 37.50 %    | -                            |

  @javascript
  Scenario: Changing max grade for a category item with a calculation that has existing grades will display the same points with the new max grade values immediately.
    Given I press "Add category"
    And I set the following fields to these values:
      | Category name | Calc cat |
    And I press "Save changes"
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name | grade item 1 |
      | Grade category | Calc cat |
    And I press "Save changes"
    And I set "=[[gi1]]/2" calculation for grade category "Calc cat" with idnumbers:
      | grade item 1 | gi1 |
    And I set the following settings for grade item "Calc cat":
      | Maximum grade | 50 |
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I give the grade "75.00" to the user "Student 1" for the grade item "grade item 1"
    And I press "Save changes"
    And I navigate to "View > User report" in the course gradebook
    And I select "Student 1" from the "Select all or one user" singleselect
    And the following should exist in the "user-grade" table:
      | Grade item                          | Calculated weight | Grade  | Range | Percentage | Contribution to course total |
      | grade item 1                        | -                 | 75.00  | 0–100 | 75.00 %    | -                            |
      | Calc cat totalInclude empty grades. | 100.00 %          | 37.50  | 0–100 | 37.50 %    | -                            |
      | Course total                        | -                 | 37.50  | 0–100 | 37.50 %    | -                            |
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I set the following settings for grade item "Calc cat":
      | Maximum grade | 40 |
    And I navigate to "View > Grader report" in the course gradebook
    And I give the grade "65.00" to the user "Student 2" for the grade item "grade item 1"
    And I press "Save changes"
    And I navigate to "View > User report" in the course gradebook
    When I select "Student 1" from the "Select all or one user" singleselect
    Then the following should exist in the "user-grade" table:
      | Grade item                          | Calculated weight | Grade  | Range | Percentage | Contribution to course total |
      | grade item 1                        | -                 | 75.00  | 0–100 | 75.00 %    | -                            |
      | Calc cat totalInclude empty grades. | 100.00 %          | 37.50  | 0–100 | 37.50 %    | -                            |
      | Course total                        | -                 | 37.50  | 0–100 | 37.50 %    | -                            |
    And I select "Student 2" from the "Select all or one user" singleselect
    And the following should exist in the "user-grade" table:
      | Grade item                          | Calculated weight | Grade  | Range | Percentage | Contribution to course total |
      | grade item 1                        | -                 | 65.00  | 0–100 | 65.00 %    | -                            |
      | Calc cat totalInclude empty grades. | 100.00 %          | 32.50  | 0–100 | 32.50 %    | -                            |
      | Course total                        | -                 | 32.50  | 0–100 | 32.50 %    | -                            |
    And I navigate to "Setup > Course grade settings" in the course gradebook
    And I set the following fields to these values:
      | Min and max grades used in calculation | Initial min and max grades |
    And I press "Save changes"
    And I navigate to "View > User report" in the course gradebook
    And I select "Student 1" from the "Select all or one user" singleselect
    And the following should exist in the "user-grade" table:
      | Grade item                          | Calculated weight | Grade  | Range | Percentage | Contribution to course total |
      | grade item 1                        | -                 | 75.00  | 0–100 | 75.00 %    | -                            |
      | Calc cat totalInclude empty grades. | 100.00 %          | 37.50  | 0–100 | 37.50 %    | -                            |
      | Course total                        | -                 | 37.50  | 0–100 | 37.50 %    | -                            |
    And I select "Student 2" from the "Select all or one user" singleselect
    And the following should exist in the "user-grade" table:
      | Grade item                          | Calculated weight | Grade  | Range | Percentage | Contribution to course total |
      | grade item 1                        | -                 | 65.00  | 0–100 | 65.00 %    | -                            |
      | Calc cat totalInclude empty grades. | 100.00 %          | 32.50  | 0–100 | 32.50 %    | -                            |
      | Course total                        | -                 | 32.50  | 0–100 | 32.50 %    | -                            |

  @javascript
  Scenario: Values in calculated grade items are not always out of one hundred
    Given I press "Add grade item"
    And I set the following fields to these values:
      | Item name | grade item 1 |
    And I press "Save changes"
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name | calc item |
    And I press "Save changes"
    And I set "=[[gi1]]/2" calculation for grade item "calc item" with idnumbers:
      | grade item 1 | gi1 |
    And I set the following settings for grade item "calc item":
      | Maximum grade | 50 |
    And I navigate to "Setup > Course grade settings" in the course gradebook
    And I set the following fields to these values:
      | Min and max grades used in calculation | Initial min and max grades |
    And I press "Save changes"
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I give the grade "75.00" to the user "Student 1" for the grade item "grade item 1"
    And I press "Save changes"
    And I navigate to "View > User report" in the course gradebook
    When I select "Student 1" from the "Select all or one user" singleselect
    Then the following should exist in the "user-grade" table:
      | Grade item   | Calculated weight | Grade  | Range | Percentage | Contribution to course total |
      | grade item 1 | 50.00 %           | 75.00  | 0–100 | 75.00 %    | 37.50 %                      |
      | calc item    | 50.00 %           | 37.50  | 0–100 | 37.50 %    | 18.75 %                      |
      | Course total | -                 | 112.50 | 0–200 | 56.25 %    | -                            |
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I set the following settings for grade item "calc item":
      | Rescale existing grades | No |
      | Maximum grade | 40 |
    And I navigate to "View > Grader report" in the course gradebook
    And I give the grade "65.00" to the user "Student 2" for the grade item "grade item 1"
    And I press "Save changes"
    And I navigate to "View > User report" in the course gradebook
    And I select "Student 1" from the "Select all or one user" singleselect
    And the following should exist in the "user-grade" table:
      | Grade item   | Calculated weight | Grade  | Range | Percentage | Contribution to course total |
      | grade item 1 | 50.00 %           | 75.00  | 0–100 | 75.00 %    | 37.50 %                      |
      | calc item    | 50.00 %           | 37.50  | 0–100 | 37.50 %    | 18.75 %                      |
      | Course total | -                 | 112.50 | 0–200 | 56.25 %    | -                            |
    And I select "Student 2" from the "Select all or one user" singleselect
    And the following should exist in the "user-grade" table:
      | Grade item   | Calculated weight | Grade  | Range | Percentage | Contribution to course total |
      | grade item 1 | 50.00 %           | 65.00  | 0–100 | 65.00 %    | 32.50 %                      |
      | calc item    | 50.00 %           | 32.50  | 0–100 | 32.50 %    | 16.25 %                      |
      | Course total | -                 | 97.50  | 0–200 | 48.75 %    | -                            |
