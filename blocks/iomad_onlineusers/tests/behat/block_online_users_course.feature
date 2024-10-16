@block @block_iomad_onlineusers
Feature: The online users block allow you to see who is currently online
  In order to enable the online users block on an course page
  As a teacher
  I can add the online users block to a course page

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |

    And the following "course enrolments" exist:
      | user | course | role           |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student        |

  Scenario: Add the online users on course page and see myself
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I add the "Online users" block
    Then I should see "Teacher 1" in the "Online users" "block"
    And I should see "1 online user" in the "Online users" "block"

  Scenario: Add the online users on course page and see other logged in users
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Online users" block
    And I log out
    And I log in as "student2"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Teacher 1" in the "Online users" "block"
    And I should see "Student 1" in the "Online users" "block"
    And I should not see "Student 2" in the "Online users" "block"
    And I should see "2 online users" in the "Online users" "block"

  @javascript
  Scenario: Hide/show user's online status from/to other users in the online users block on course page
    Given the following config values are set as admin:
      | block_iomad_onlineusers_onlinestatushiding | 1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Online users" block
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then "Hide" "icon" should exist in the "#change-user-visibility" "css_element"
    When I click on "#change-user-visibility" "css_element"
    And I wait "1" seconds
    Then "Show" "icon" should exist in the "#change-user-visibility" "css_element"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then I should see "1 online user" in the "Online users" "block"
    And I should see "Teacher 1" in the "Online users" "block"
    And I should not see "Student 1" in the "Online users" "block"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then "Show" "icon" should exist in the "#change-user-visibility" "css_element"
    When I click on "#change-user-visibility" "css_element"
    And I wait "1" seconds
    Then "Hide" "icon" should exist in the "#change-user-visibility" "css_element"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then I should see "2 online users" in the "Online users" "block"
    And I should see "Teacher 1" in the "Online users" "block"
    And I should see "Student 1" in the "Online users" "block"

  @javascript
  Scenario: Hide/show icon is not visible in the online users block on course page when the setting is disabled
    Given the following config values are set as admin:
      | block_iomad_onlineusers_onlinestatushiding | 1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Online users" block
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And "Hide" "icon" should exist in the ".block.block_iomad_onlineusers" "css_element"
    And I log out
    And the following config values are set as admin:
      | block_iomad_onlineusers_onlinestatushiding | 0 |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Student 1" in the "Online users" "block"
    And "Hide" "icon" should not exist in the ".block.block_iomad_onlineusers" "css_element"

  @javascript
  Scenario: User is displayed in the online users block on course page when visibility setting is disabled,
            ignoring the previously set visibility state
    Given the following config values are set as admin:
      | block_iomad_onlineusers_onlinestatushiding | 1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Online users" block
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And "Hide" "icon" should exist in the "#change-user-visibility" "css_element"
    And I click on "#change-user-visibility" "css_element"
    And I wait "1" seconds
    And "Show" "icon" should exist in the "#change-user-visibility" "css_element"
    And I log out
    And the following config values are set as admin:
      | block_iomad_onlineusers_onlinestatushiding | 0 |
    And I log in as "teacher1"
    When I am on "Course 1" course homepage
    Then I should see "2 online users" in the "Online users" "block"
    And I should see "Teacher 1" in the "Online users" "block"
    And I should see "Student 1" in the "Online users" "block"
