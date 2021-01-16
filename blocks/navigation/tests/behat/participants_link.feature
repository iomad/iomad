@block @block_navigation
Feature: Displaying the link to the Participants page
  In order to see the course / site participants
  As a student / admin respectively
  I need a link to the Participants page be displayed (but only if I can access that page)

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course1  | C1        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I log in as "admin"
    And I am on site homepage
    And I turn editing mode on
    And I add the "Navigation" block if not present
    And I configure the "Navigation" block
    And I set the following fields to these values:
      | Page contexts | Display throughout the entire site |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Course participants link is displayed to enrolled students after expanding the course node
    When I log in as "student1"
    And I expand "C1" node
    Then "Participants" "link" should exist in the "Navigation" "block"
    And I click on "Participants" "link" in the "Navigation" "block"
    And I should see "Participants"
    And "Student One" "link" should exist
    And "Student Two" "link" should not exist

  Scenario: Site participants link is displayed to admins
    When I log in as "admin"
    Then "Participants" "link" should exist in the "Navigation" "block"
    And I click on "Participants" "link" in the "Navigation" "block"
    And I should see "Participants"
    And "Student One" "link" should exist
    And "Student Two" "link" should exist

  @javascript
  Scenario: Site participants link is not displayed to students (MDL-55667)
    Given I log in as "admin"
    And I set the following administration settings values:
      | defaultfrontpageroleid | Student (student) |
    And I log out
    When I log in as "student2"
    And I expand "Site pages" node
    Then "Participants" "link" should not exist in the "Navigation" "block"
