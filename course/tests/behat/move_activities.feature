@core @core_course
Feature: Activities can be moved between sections
  In order to rearrange my course contents
  As a teacher
  I need to move activities between sections

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1 | topics | 0 | 5 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Recent activity" block
    And I follow "Delete Recent activity block"
    And I press "Yes"
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Description | Test forum description |

  Scenario: Move activities in a single page course with Javascript disabled
    When I move "Test forum name" activity to section "2"
    Then I should see "Test forum name" in the "Topic 2" "section"
    And I should not see "Test forum name" in the "Topic 1" "section"

  Scenario: Move activities in the course home with Javascript disabled using paged mode
    Given I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | Course layout | Show one section per page |
    And I press "Save and display"
    When I move "Test forum name" activity to section "2"
    Then I should see "Test forum name" in the "Topic 2" "section"
    And I should not see "Test forum name" in the "Topic 1" "section"

  Scenario: Move activities in a course section with Javascript disabled using paged mode
    Given I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | Course layout | Show one section per page |
    And I press "Save and display"
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Second forum name |
      | Description | Second forum description |
    And I follow "Topic 1"
    When I move "Second forum name" activity to section "1"
    Then "Second forum name" "link" should appear before "Test forum name" "link"
