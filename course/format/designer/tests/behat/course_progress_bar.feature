@format @format_designer @course_progress_bar @javascript
Feature: Course progress bar checking criteria in designer format
  In order to rearrange the course completions option
  Need to check the completion criteria.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | First        | teacher1@example.com |
      | student1 | Student   | First       | student1@example.com |
      | student2 | Student   | Second       | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | enablecompletion |activityprogress |
      | Course 1 | C1        | designer | 0             | 5           | 1              |  1        |
      | Course 2 | C2        | designer | 0             | 5           | 1              |  1        |
      | Course 3 | C3        | designer | 0             | 5           | 1              |  1        |
      | Course 4 | C4        | designer | 0             | 5           | 1              |  1        |
      | Course 5 | C5        | designer | 0             | 5           | 1              |  1        |
    And the following "activities" exist:
      | activity   | name                   | intro                         | course | idnumber    | section | completion |
      | assign     | Test assignment name   | Test assignment description   | C1     | assign1     | 0       |   1        |
      | assign     | Test assignment name 1 | Test assignment1 description  | C1     | assign2     | 0       |   1        |
      | assign     | Test assignment name 2 | Test assignment2 description  | C1     | assign3     | 0       |   1        |
      | choice     | Test choice name       | Test choice description       | C1     | choice1     | 0       |   1        |
      | assign     | Demo assign 01         | Test assignment 1 description | C2     | assign1     | 0       |   1        |
      | assign     | Demo assign 02         | Test assignment 2 description | C2     | assign2     | 0       |   1        |
      | assign     | Demo assign 03         | Test assignment 3 description | C2     | assign3     | 0       |   1        |
      | assign     | Demo assign 01         | Test assignment 1 description | C3     | assign1     | 0       |   1        |
      | assign     | Demo assign 02         | Test assignment 2 description | C3     | assign2     | 0       |   1        |
      | assign     | Demo assign 01         | Test assignment 1 description | C4     | assign1     | 0       |   1        |
      | assign     | Demo assign 01         | Test assignment 1 description | C5     | assign1     | 0       |   1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | teacher1 | C2     | editingteacher |
      | student1 | C2     | student        |
      | teacher1 | C3     | editingteacher |
      | student1 | C3     | student        |
      | teacher1 | C4     | editingteacher |
      | student1 | C4     | student        |
      | teacher1 | C5     | editingteacher |
      | student1 | C5     | student        |

    When I log in as "admin"
    And I am on "Course 4" course homepage with editing mode on
    And I navigate to "Course completion" in current page administration
    And I click on "Condition: Activity completion" "link"
    Then I click on "Select all/none" "link"
    And I press "Save changes"
    And I am on "Course 5" course homepage with editing mode on
    And I navigate to "Course completion" in current page administration
    And I click on "Condition: Activity completion" "link"
    Then I click on "Select all/none" "link"
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Course completion" in current page administration
    And I click on "Condition: Completion of other courses" "link"
    And I set the following fields to these values:
      | Courses available| Course 4, Course 5|
    And I press "Save changes"
    And I am on "Course 2" course homepage with editing mode on
    And I navigate to "Course completion" in current page administration
    And I click on "Condition: Activity completion" "link"
    Then I click on "Select all/none" "link"
    And I press "Save changes"
    And I am on "Course 3" course homepage with editing mode on
    And I navigate to "Course completion" in current page administration
    And I click on "Condition: Activity completion" "link"
    Then I click on "Select all/none" "link"
    And I click on "Condition: Completion of other courses" "link"
    And I set the following fields to these values:
      | Courses available| Course 4, Course 5|
    And I press "Save changes"

  Scenario: Check the progress bar first case for student
    Given I log in as "student1"
    # Check the course criteria completion.
    Then I am on "Course 1" course homepage
    And ".progress-block .activity-completed-block" "css_element" should exist
    And I should see "0 of 2 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    Then I am on "Course 4" course homepage
    And I should see "0 of 1 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    And the manual completion button of "Demo assign 01" is displayed as "Mark as done"
    And I toggle the manual completion state of "Demo assign 01"
    And I should see "1 of 1 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    Then I am on "Course 1" course homepage
    And I should see "1 of 2 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    Then I am on "Course 5" course homepage
    And I should see "0 of 1 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    And the manual completion button of "Demo assign 01" is displayed as "Mark as done"
    And I toggle the manual completion state of "Demo assign 01"
    And I should see "1 of 1 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    Then I am on "Course 1" course homepage
    And I should see "2 of 2 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    # Check the activity criteria completion.
    Then I am on "Course 2" course homepage
    And ".progress-block .activity-completed-block" "css_element" should exist
    And I should see "0 of 3 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    And the manual completion button of "Demo assign 01" is displayed as "Mark as done"
    And I toggle the manual completion state of "Demo assign 01"
    Then I am on "Course 2" course homepage
    And I should see "1 of 3 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    And the manual completion button of "Demo assign 02" is displayed as "Mark as done"
    And I toggle the manual completion state of "Demo assign 02"
    Then I am on "Course 2" course homepage
    And I should see "2 of 3 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    And the manual completion button of "Demo assign 03" is displayed as "Mark as done"
    And I toggle the manual completion state of "Demo assign 03"
    Then I am on "Course 2" course homepage
    And I should see "3 of 3 criteria completed" in the ".progress-block .activity-completed-block" "css_element"

  Scenario: Check the progress bar second case for student.
    Given I log in as "student1"
    # Check the activity and course criteria completion.
    Then I am on "Course 3" course homepage
    And ".progress-block .activity-completed-block" "css_element" should exist
    And I should see "0 of 4 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    Then I am on "Course 4" course homepage
    And I should see "0 of 1 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    And the manual completion button of "Demo assign 01" is displayed as "Mark as done"
    And I toggle the manual completion state of "Demo assign 01"
    And I should see "1 of 1 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    Then I am on "Course 3" course homepage
    And I should see "1 of 4 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    And the manual completion button of "Demo assign 01" is displayed as "Mark as done"
    And I toggle the manual completion state of "Demo assign 01"
    And I should see "2 of 4 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    Then I am on "Course 5" course homepage
    And I should see "0 of 1 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    And the manual completion button of "Demo assign 01" is displayed as "Mark as done"
    And I toggle the manual completion state of "Demo assign 01"
    And I should see "1 of 1 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    Then I am on "Course 3" course homepage
    And I should see "3 of 4 criteria completed" in the ".progress-block .activity-completed-block" "css_element"
    And the manual completion button of "Demo assign 02" is displayed as "Mark as done"
    And I toggle the manual completion state of "Demo assign 02"
    And I should see "4 of 4 criteria completed" in the ".progress-block .activity-completed-block" "css_element"

  Scenario: Display the course completion progress sorted by completion date
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I press "Save and display"
    And I am on "Course 1" course homepage
    And I turn block editing mode on
    And I am on the "Test assignment name" "assign activity" page
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the following fields to these values:
        | id_completionexpected_enabled | 1 |
    Then I set the designer completion expected "##1 Feb 2024 08:00##"
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I am on the "Test assignment name 1" "assign activity" page
    And I press "Mark as done"
    And I am on "Course 1" course homepage
    Then ".completion-info .badge-success" "css_element" should exist
    And I am on the "Test assignment name" "assign activity" page
    And I press "Mark as done"
    And I am on "Course 1" course homepage
    Then ".completion-info  .badge-due-success" "css_element" should exist
