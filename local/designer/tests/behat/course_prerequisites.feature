@local @local_designer @javascript @designer_course_prerequisites
Feature: course prerequisites in designer format
  In order to rearrange my course contents
  As a teacher
  I need to check prerequisites in designer format

  Background:
    Given the following "users" exist:
      | username | firstname | lastname     | email                |
      | teacher1 | Teacher   | First        | teacher1@example.com |
      | student1 | Student   | First        | student1@example.com |
      | student2 | Student   | Second       | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | enablecompletion |
      | Course 1 | C1        | designer | 0           | 5           | 1                |
      | Course 2 | C2        | designer | 0           | 5           | 1                |
      | Course 3 | C3        | designer | 0           | 5           | 1                |
      | Course 4 | C4        | designer | 0           | 5           | 1                |
      | Course 5 | C5        | designer | 0           | 5           | 1                |
      | Course 6 | C6        | designer | 0           | 5           | 1                |
    And the following "activities" exist:
      | activity   | name                   | intro                         | course | idnumber    | section |
      | assign     | Test assignment name   | Test assignment description   | C2     | assign1     | 0       |
      | book       | Test book name         | Test book description         | C2     | book1       | 0       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | teacher1 | C2     | editingteacher |
      | teacher1 | C6     | editingteacher |
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Course completion" in current page administration
    And I click on "Condition: Completion of other courses" "link"
    And I set the following fields to these values:
      | Courses available| Course 2, Course 3, Course 4, Course 5 |
    And I press "Save changes"

  Scenario: Set prerequisite course condition.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "Display course prerequisites" to "Above course contents"
    And I click on "Save and display" "button"
    Then I should see "Prerequisites"
    Then ".pre-course-block" "css_element" should exist
    Then I should see "Course 2"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "Display course prerequisites" to "On separate tab"
    And I click on "Save and display" "button"
    And I wait "5" seconds
    Then ".pre-course-block" "css_element" should not exist
    And I click on "Prerequisites" "link"
    Then ".pre-course-block" "css_element" should exist
    Then I should see "Course 2"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "Display course prerequisites" to "Disabled"
    And I click on "Save and display" "button"
    Then ".pre-course-block" "css_element" should not exist
    Then I should not see "Prerequisites"

  Scenario: Check the backtomaincourse opiton
    When I log in as "teacher1"
    And I am on "Course 2" course homepage
    Then I should see "Back to main course"
    Then I am on the "Test assignment name" "assign activity" page
    Then I should not see "Back to main course"
    And I am on "Course 2" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "Add course to secondary menu item on all course pages" to "1"
    And I click on "Save and display" "button"
    Then I should see "Back to main course"
    Then I am on the "Test assignment name" "assign activity" page
    Then I should see "Back to main course"
    And I am on "Course 2" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "Back to main course" to "Disable"
    And I click on "Save and display" "button"
    And I am on "Course 2" course homepage
    Then I should not see "Back to main course"
    Then I am on the "Test assignment name" "assign activity" page
    Then I should not see "Back to main course"

  @javascript
  Scenario: Check the prerequisite course condition.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | Automatically enrol students        | Always |
      | Unenrol students from prerequisites | Enable |
      | Group students in prerequisites     | Enable |
    And I click on "Save and display" "button"
    When I navigate to course participants
    And I press "Enrol users"
    And I click on "Select users" "field"
    And I type "student2@example.com"
    When I click on "Student Second" item in the autocomplete list
    And I click on "Enrol users" "button" in the ".modal" "css_element"
    And I am on "Course 1" course homepage
    Then I navigate to course participants
    And I should see "student2" in the "participants" "table"
    And I am on "Course 2" course homepage
    Then I navigate to course participants
    And I should see "student2" in the "participants" "table"
    And I am on the "Course 2" "groups" page
    Then I set the field "groups" to "C1 (2)"
    And the "members" select box should contain "Student Second (student2@example.com)"
    Then I am on "Course 1" course homepage
    And I navigate to course participants
    When I click on "//a[@data-action='unenrol']" "xpath_element" in the "student2" "table_row"
    And I click on "Unenrol" "button" in the "Unenrol" "dialogue"
    And I am on "Course 2" course homepage
    Then I navigate to course participants
    Then I should not see "Student 2" in the "participants" "table"

  @javascript
  Scenario: Check the prerequisite text.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Display course prerequisites | Above course contents |
      | Prerequisites title        | Recommend other courses |
      | Prerequisite info          | Prerequisite info content. |
    And I click on "Save and display" "button"
    Then I should not see "Prerequisites" in the ".pre-course-block h2" "css_element"
    Then I should see "Recommend other courses" in the ".pre-course-block h2" "css_element"
    Then ".pre-course-block" "css_element" should exist
    And I should see "Prerequisite info content." in the ".prerequisite-block-info" "css_element"

  @javascript
  Scenario: Check the prerequisite groups.
    When I log in as "admin"
    And I navigate to "Users > Permissions > Define roles" in site administration
    And I follow "Teacher"
    And I press "Edit"
    And I click on "local/designer:manageprerequisitesgroup" "checkbox"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Display course prerequisites | Above course contents |
    And I click on "Save and display" "button"
    And I navigate to "Prerequisite groups" in current page administration
    Then I should see "No groups found"
    And "Add new group" "button" should be visible
    And I click on "Add new group" "button"
    Then I should see "Course 1: Prerequisite groups"
    And I set the following fields to these values:
      | Group name | Group 01 |
      | Group description | Example group description |
      | Assign prerequisites courses | Course 4, Course 5 |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    Then I should see "Group 01" in the ".pre-course-block #section-content-precourse h2" "css_element"
    #Then I should see "Course 4" in the "#section-content-precourse .course-element:nth-of-type(2) .card:nth-of-type(1) .modulename" "css_element"
    #And "Course 4" "link" should exist in the "#section-content-precourse .course-element:nth-of-type(2) .card:nth-of-type(1) .modulename" "css_element"
    #Then I should see "Course 5" in the "#section-content-precourse .course-element:nth-of-type(2) .card:nth-of-type(2) .modulename" "css_element"
    #And "Course 5" "link" should exist in the "#section-content-precourse .course-element:nth-of-type(2) .card:nth-of-type(2) .modulename" "css_element"
