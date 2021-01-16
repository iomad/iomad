@format @format_singleactivity
Feature: Edit format course to Single Activity format
  In order to set the format course to single activity course
  As a teacher
  I need to edit the course settings and see the dropdown type activity

  Scenario: Edit a format course as a teacher
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | summary | format |
      | Course 1 | C1 | <p>Course summary</p> | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | Course full name  | My first course |
      | Course short name | myfirstcourse |
      | Format | Single activity format |
    And I press "Update format"
    Then I should see "Forum" in the "Type of activity" "field"
    And I press "Save and display"
    And I should see "Adding a new Forum"
