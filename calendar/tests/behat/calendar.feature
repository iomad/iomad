@core @core_calendar
Feature: Perform basic calendar functionality
  In order to ensure the calendar works as expected
  As an admin
  I need to create calendar data

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
      | student3 | Student | 3 | student3@example.com |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
      | student3 | C1 | student |
      | teacher1 | C1 | teacher |
    And the following "groups" exist:
      | name | course | idnumber |
      | Group 1 | C1 | G1 |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | teacher1 | G1 |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Calendar" block
    And I log out

  @javascript
  Scenario: Create a site event
    Given I log in as "admin"
    And I create a calendar event with form data:
      | Type of event | site |
      | Event title | Really awesome event! |
      | Description | Come join this awesome event, sucka! |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "This month"
    And I should see "Really awesome event!"
    And I log out
    And I log in as "student2"
    And I follow "This month"
    And I should see "Really awesome event!"

  @javascript
  Scenario: Create a course event
    Given I log in as "teacher1"
    And I create a calendar event with form data:
      | Type of event | course |
      | Course        | Course 1 |
      | Event title | Really awesome event! |
      | Description | Come join this awesome event, sucka! |
    And I log out
    And I log in as "student1"
    When I am on "Course 1" course homepage
    And I follow "This month"
    And I click on "Really awesome event!" "link"
    And "Course 1" "link" should exist in the "Really awesome event!" "dialogue"
    And I click on "Close" "button"
    And I log out
    And I log in as "student2"
    And I follow "This month"
    Then I should not see "Really awesome event!"

  @javascript
  Scenario: Create a group event
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I create a calendar event with form data:
      | Type of event | group |
      | Group         | Group 1 |
      | Event title | Really awesome event! |
      | Description | Come join this awesome event |
    And I log out
    And I log in as "student1"
    When I am on "Course 1" course homepage
    And I follow "This month"
    Then I follow "Really awesome event!"

  @javascript
  Scenario: Create a user event
    Given I log in as "teacher1"
    And I create a calendar event with form data:
      | Type of event | user |
      | Event title | Really awesome event! |
      | Description | Come join this awesome event, sucka! |
    And I log out
    And I log in as "student1"
    When I am on "Course 1" course homepage
    And I follow "This month"
    Then I should not see "Really awesome event!"

  @javascript
  Scenario: Delete an event
    Given I log in as "teacher1"
    And I create a calendar event with form data:
      | Type of event | user |
      | Event title | Really awesome event! |
      | Description | Come join this awesome event, sucka! |
    And I am on "Course 1" course homepage
    When I follow "This month"
    And I click on "Really awesome event!" "link"
    And I click on "Delete" "button"
    And I click on "Delete event" "button"
    And I wait to be redirected
    Then I should not see "Really awesome event!"

  @javascript
  Scenario: Edit an event
    Given I log in as "teacher1"
    And I create a calendar event with form data:
      | Type of event | user |
      | Event title | Really awesome event! |
      | Description | Come join this awesome event, sucka! |
    And I am on "Course 1" course homepage
    When I follow "This month"
    And I click on "Really awesome event!" "link"
    And I click on "Edit" "button"
    And I set the following fields to these values:
      | Event title | Mediocre event :( |
      | Description | Wait, this event isn't that great. |
    And I press "Save"
    Then I should see "Mediocre event"

  @javascript
  Scenario: Module events editing
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And the following "activities" exist:
      | activity | course | idnumber | name        | intro                   | timeopen      | timeclose     |
      | choice   | C1     | choice1  | Test choice | Test choice description | ##today## | ##today##  |
    When I follow "This month"
    Then I should see "Test choice opens"
    And I should see "Test choice closes"
    When I click on "Test choice opens" "link"
    Then "Delete" "button" should not exist
    And "Edit" "button" should not exist
    And I should see "Course event"
    When I click on "Go to activity" "link"
    And I wait to be redirected
    Then I should see "Test choice"
    And I am on "Course 1" course homepage
    And I follow "This month"
    When I click on "Test choice closes" "link"
    Then "Delete" "button" should not exist
    And "Edit" "button" should not exist
    And I should see "Course event"
    When I click on "Go to activity" "link"
    And I wait to be redirected
    Then I should see "Test choice"
