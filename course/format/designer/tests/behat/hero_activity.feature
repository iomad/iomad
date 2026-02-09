@format @format_designer @javascript @designer_hero_activity
Feature: Activities can be check hero activity in designer format
  In order to rearrange my course contents
  As a teacher
  I need to check hero activity in designer format

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | First        | teacher1@example.com |
      | student1 | Student   | First       | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | Enable completion tracking |
      | Course 1 | C1        | designer | 0             | 5           | yes                      |
      | Course 2 | C2        | designer | 0             | 5           | yes                      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | teacher1 | C2     | editingteacher |
      | student1 | C2     | student        |
    And the following "activities" exist:
      | activity      | name                  | intro                         | course | idnumber    | section | completion |
      | assign        | Demo assign 1         | Test assignment1 description  | C1     | assign1     | 0       |   1        |
      | assign        | Demo assign 2         | Test assignment2 description  | C1     | assign2     | 0       |   1        |
      | assign        | Demo assign 3         | Test assignment3 description  | C1     | assign3     | 1       |   1        |
      | assign        | Demo assign 4         | Test assignment4 description  | C2     | assign4     | 0       |   1        |
      | assign        | Demo assign 5         | Test assignment5 description  | C2     | assign5     | 1       |   1        |

  Scenario: Check the hero activity to see everywhere
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I press "Add an activity or resource"
    And I click on "Add a new Forum" "link" in the "Add an activity or resource" "dialogue"
    And I expand all fieldsets
    And I set the field "Forum name" to "My forum name"
    And I set the field "Show as tab" to "Everywhere"
    And I set the field "Order" to "1"
    And I press "Save and return to course"
    And I press "Add an activity or resource"
    And I click on "Add a new Forum" "link" in the "Add an activity or resource" "dialogue"
    And I expand all fieldsets
    And I set the field "Forum name" to "My forum name1"
    And I set the field "Show as tab" to "Only on course main page"
    And I set the field "Order" to "-1"
    And I press "Save and return to course"
    Then I wait "3" seconds
    And I reload the page
    And I log out

  Scenario: Check the hero activity prevent duplicates.
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    Then I am on the "Demo assign 1" "assign activity editing" page
    And I expand all fieldsets
    And I set the following fields to these values:
      | Show as tab | Everywhere |
      | Order       | 1 |
      | Secondary menu title | Custom |
      | Custom title     |  Demo assignment |
    And I press "Save and return to course"
    Then I should see "Demo assignment" in the ".secondary-navigation" "css_element"
    Then I am on the "Demo assign 1" "assign activity" page
    Then I should see "Assignment" in the ".secondary-navigation" "css_element"
    Then I should see "Demo assignment" in the ".secondary-navigation" "css_element"
    Then I am on the "Demo assign 2" "assign activity" page
    Then I should see "Assignment" in the ".secondary-navigation" "css_element"
    Then I should see "Demo assignment" in the ".secondary-navigation" "css_element"
    And I navigate to "Plugins > Course formats > Designer format" in site administration
    And I set the following fields to these values:
      | Avoid duplicate entry | 1 |
    And I press "Save changes"
    Then I am on the "Demo assign 1" "assign activity" page
    And I should not see "Assignment" in the ".secondary-navigation" "css_element"
    And I should see "Demo assignment" in the ".secondary-navigation" "css_element"
    Then I am on the "Demo assign 2" "assign activity" page
    Then I should see "Assignment" in the ".secondary-navigation" "css_element"
    Then I should see "Demo assignment" in the ".secondary-navigation" "css_element"

  Scenario: Check the hero activity secondary title.
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    Then I am on the "Demo assign 1" "assign activity editing" page
    And I expand all fieldsets
    And I set the following fields to these values:
      | Show as tab | Everywhere |
      | Order       | 1 |
      | Secondary menu title | Activity title |
    And I press "Save and return to course"
    And I should see "Demo assign 1" in the ".secondary-navigation" "css_element"
    Then I am on the "Demo assign 1" "assign activity" page
    And I should see "Demo assign 1" in the ".secondary-navigation" "css_element"
    Then I am on the "Demo assign 1" "assign activity editing" page
    And I expand all fieldsets
    And I set the following fields to these values:
      | Secondary menu title | Activity type |
    And I press "Save and return to course"
    And I should not see "Demo assign 1" in the ".secondary-navigation" "css_element"
    And I should see "Assignment" in the ".secondary-navigation" "css_element"
    Then I am on the "Demo assign 1" "assign activity editing" page
    And I expand all fieldsets
    And I set the following fields to these values:
      | Secondary menu title | Custom |
      | Custom title     | Demo assign custom |
    And I press "Save and return to course"
    And I should not see "Demo assign 1" in the ".secondary-navigation" "css_element"
    And I should not see "Assignment" in the ".secondary-navigation" "css_element"
    And I should see "Demo assign custom" in the ".secondary-navigation" "css_element"
    Then I am on the "Demo assign 1" "assign activity editing" page
    And I expand all fieldsets
    And I set the following fields to these values:
      | Custom title     | Demo assign (Modified)|
    And I press "Save and return to course"
    And I should not see "Demo assign custom" in the ".secondary-navigation" "css_element"
    And I should see "Demo assign (Modified)" in the ".secondary-navigation" "css_element"
    Then I am on the "Demo assign 2" "assign activity editing" page
    And I expand all fieldsets
    And I set the following fields to these values:
      | Show as tab | Everywhere |
      | Order       | 1 |
      | Secondary menu title | Custom |
      | Custom title     | Demo assign 2 (Modified)|
    And I press "Save and return to course"
    And I should see "Demo assign 2 (Modified)" in the ".secondary-navigation" "css_element"
    Then I log in as "student 1"
    And I am on "Course 1" course homepage
    And I should not see "Demo assign custom" in the ".secondary-navigation" "css_element"
    And I should see "Demo assign (Modified)" in the ".secondary-navigation" "css_element"
    And I should see "Demo assign 2 (Modified)" in the ".secondary-navigation" "css_element"

  Scenario: Check the hero activity secondary course pages
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    Then I am on the "Demo assign 1" "assign activity editing" page
    And I expand all fieldsets
    And I set the following fields to these values:
      | Show as tab | Everywhere |
      | Order       | 1 |
      | Secondary menu title | Custom |
      | Custom title     | Demo Assign Test|
    And I press "Save and return to course"
    And I should see "Demo Assign Test" in the ".secondary-navigation" "css_element"
    And I should not see "Demo Assign Test" in the "li.activity:nth-child(1)" "css_element"
    Then I click on ".drawer-toggler.drawer-left-toggle" "css_element"
    And I should not see "Demo Assign Test" in the "#courseindex-content" "css_element"
    Then I am on the "Demo assign 1" "assign activity editing" page
    And I expand all fieldsets
    And I set the following fields to these values:
      | Use custom name on course/section pages | 1 |
    And I press "Save and return to course"
    And I should see "Demo Assign Test" in the ".secondary-navigation" "css_element"
    And I should see "Demo Assign Test" in the "li.activity:nth-child(1)" "css_element"
    Then I click on ".drawer-toggler.drawer-left-toggle" "css_element"
    And I should not see "Demo Assign Test" in the "#courseindex-content" "css_element"
    Then I am on the "Demo assign 1" "assign activity editing" page
    And I expand all fieldsets
    And I set the following fields to these values:
      | Use custom name in course index | 1 |
    And I press "Save and return to course"
    And I should see "Demo Assign Test" in the ".secondary-navigation" "css_element"
    And I should see "Demo Assign Test" in the "li.activity:nth-child(1)" "css_element"
    Then I click on ".drawer-toggler.drawer-left-toggle" "css_element"
    And I should see "Demo Assign Test" in the "#courseindex-content" "css_element"
    Then I log in as "student 1"
    And I am on "Course 1" course homepage
    And I should see "Demo Assign Test" in the ".secondary-navigation" "css_element"
    And I should see "Demo Assign Test" in the "li.activity:nth-child(1)" "css_element"
    And I should see "Demo Assign Test" in the "#courseindex-content" "css_element"
    Then I am on the "Demo assign 1" "assign activity" page
    And I should see "Demo Assign Test" in the "#courseindex-content" "css_element"

  Scenario: Check the section zero activities.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I should not see "Demo assign 1" in the ".secondary-navigation" "css_element"
    And I should not see "Demo assign 2" in the ".secondary-navigation" "css_element"
    And I should not see "Demo assign 3" in the ".secondary-navigation" "css_element"
    And I should see "General"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | Section 0 activities | Make hero activity and hide section 0 |
      | Show as tab         |  Everywhere |
    Then I press "Save and display"
    And I should see "Demo assign 1" in the ".secondary-navigation" "css_element"
    And I should see "Demo assign 2" in the ".secondary-navigation" "css_element"
    And I should not see "Demo assign 3" in the ".secondary-navigation" "css_element"
    Then I turn editing mode off
    And I should not see "General"
    Then I turn editing mode on
    And I should see "General"
    And I am on "Course 2" course homepage with editing mode on
    And I should not see "Demo assign 4" in the ".secondary-navigation" "css_element"
    And I should not see "Demo assign 5" in the ".secondary-navigation" "css_element"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | Section 0 activities | Make hero activity and hide section 0 |
      | Show as tab         |  Everywhere |
    Then I press "Save and display"
    And I should not see "Demo assign 1" in the ".secondary-navigation" "css_element"
    And I should not see "Demo assign 2" in the ".secondary-navigation" "css_element"
    And I should not see "Demo assign 5" in the ".secondary-navigation" "css_element"
    And I should see "Demo assign 4" in the ".secondary-navigation" "css_element"
    And I am on "Course 1" course homepage
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | Section 0 activities | Make hero activity and keep section 0 visible |
    Then I press "Save and display"
    And I should see "Demo assign 1" in the ".secondary-navigation" "css_element"
    And I should see "Demo assign 2" in the ".secondary-navigation" "css_element"
    Then I turn editing mode off
    And I should see "General"
    Then I turn editing mode on
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | Section 0 activities | Disabled |
    Then I press "Save and display"
    And I should not see "Demo assign 1" in the ".secondary-navigation" "css_element"
    And I should not see "Demo assign 2" in the ".secondary-navigation" "css_element"
