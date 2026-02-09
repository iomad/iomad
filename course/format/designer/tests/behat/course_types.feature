@format @format_designer @designer_course_types
Feature: Users can choose different course types.
  In order to change the course type
  As a teacher
  I need to create and edit designer

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1        | designer | 0             | 5           |
    And the following "activities" exist:
      | activity   | name                   | intro                         | course | idnumber    | section |
      | assign     | Test assignment name   | Test assignment description   | C1     | assign1     | 0       |
      | book       | Test book name         | Test book description         | C1     | book1       | 1       |
      | chat       | Test chat name         | Test chat description         | C1     | chat1       | 4       |
      | choice     | Test choice name       | Test choice description       | C1     | choice1     | 5       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage

  @javascript
  Scenario: Collapsible sections
    Given I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
        | Course type | Collapsible sections |
    And I press "Save and display"
    And "ul.course-type-collapsible" "css_element" should exist
    And I click on "#section-head-0" "css_element"
    Then the "class" attribute of "#section-head-0" "css_element" should contain "collapsed"

  @javascript @kanban_board
  Scenario: Kanban board
    Given I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
        | Course type | Kanban Board |
    And I press "Save and display"
    And "ul.course-type-kanbanboard" "css_element" should exist
    And "div.kanban-board-activities" "css_element" should exist
    And "#section-1" "css_element" should exist in the "//div[contains(concat(' ', normalize-space(@class), ' '), ' kanban-board-activities ')]" "xpath_element"
    Then the "class" attribute of "#section-head-0" "css_element" should not contain "collapsed"

  @javascript
  Scenario: Flow
    Given I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
        | Course type | Flow |
    And I press "Save and display"
    And "ul.course-type-flow" "css_element" should exist
    Then the "class" attribute of "#section-head-1" "css_element" should contain "collapsed"
    And the "class" attribute of "#section-head-1" "css_element" should contain "flow-stack"
    And the "class" attribute of "#section-head-2" "css_element" should contain "flow-none"
    And I click on "#section-head-1" "css_element"
    Then the "class" attribute of "#section-head-1" "css_element" should not contain "collapsed"
    And the "class" attribute of "#section-1 .activity" "css_element" should contain "flow-animation"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
        | Flow animation | Disable |
    And I press "Save and display"
    Then the "class" attribute of "#section-1 .activity" "css_element" should not contain "flow-animation"
    And "div.kanban-board-activities" "css_element" should not exist

  @javascript
  Scenario: Check for add course to secondary menu item.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    Then I should see "Course" in the ".secondary-navigation" "css_element"
    Then I am on the "Test assignment name" "assign activity" page
    Then I should not see "Course" in the ".secondary-navigation" "css_element"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | Add course to secondary menu item on all course pages | 1 |
    And I press "Save and display"
    Then I am on the "Test assignment name" "assign activity" page
    Then I should see "Course" in the ".secondary-navigation" "css_element"
    And I navigate to "Settings" in current page administration
    Then I should see "Course" in the ".secondary-navigation" "css_element"
    And I navigate to "Overrides" in current page administration
    Then I should see "Course" in the ".secondary-navigation" "css_element"
    And I navigate to "Advanced grading" in current page administration
    Then I should see "Course" in the ".secondary-navigation" "css_element"
