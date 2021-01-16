@block @block_course_summary
Feature: Course summary block used on the frontpage
  In order to help particpants know the summary of a site
  As admin
  I can use the course summary block on the frontpage

  Background:
    Given I log in as "admin"
    And I am on site homepage
    And I turn editing mode on
    And I add the "Course/site summary" block
    And I navigate to "Edit settings" node in "Front page settings"
    And I set the following fields to these values:
      | summary | Proved the summary block works! |
    And I press "Save changes"
    And I log out
    # The course summary block a default front page block, so no need to add it.

  Scenario: Guest can view site summary
    When I am on site homepage
    Then "Course/site summary" "block" should exist
    And I should not see "Course summary" in the "Course/site summary" "block"
    And I should see "Proved the summary block works!" in the "Course/site summary" "block"

  Scenario: Admin can not see edit icon when edit mode is off
    When I log in as "admin"
    And I am on site homepage
    Then I should see "Proved the summary block works!" in the "Course/site summary" "block"
    And I should not see "Course summary" in the "Course/site summary" "block"
    And "Edit" "link" should not exist in the "Course/site summary" "block"
