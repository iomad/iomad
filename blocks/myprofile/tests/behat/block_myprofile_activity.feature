@block @block_myprofile
Feature: The logged in user block allows users to view their profile information in an activity
  In order to enable the logged in user block in an activity
  As a teacher
  I can add the logged in user block to an activity and view my information

  Scenario: View the logged in user block by a user in an activity
    Given the following "users" exist:
      | username | firstname | lastname | email | idnumber |
      | teacher1 | Teacher | One | teacher1@example.com | T1 |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "activities" exist:
      | activity | course | idnumber | name           | intro                 |
      | page     | C1     | page1    | Test page name | Test page description |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Test page name"
    When I add the "Logged in user" block
    Then I should see "Teacher One" in the "Logged in user" "block"
