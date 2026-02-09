@block @block_dash
Feature: Add My Groups widget in dash block
  In order to enable the contacts widgets in dash block on the dashboard
  As an admin
  I can add the dash block to the dashboard

  Background:
    Given the following "categories" exist:
      | name       | category | idnumber |
      | Category 1 | 0        | CAT1     |
      | Category 2 | 0        | CAT2     |
      | Category 3 | CAT2     | CAT3     |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
      | Course 2 | C2        | CAT1     | 0                |
      | Course 3 | C3        | CAT2     | 1                |
      | Course 4 | C4        | CAT3     | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | First    | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
      | student3 | Student   | Three    | student3@example.com |
      | student4 | Student   | Four     | student4@example.com |
      | student5 | Student   | Five     | student5@example.com |
      | teacher1 | Teacher   | First    | teacher1@example.com |
      | manager  | Max       | Manager  | man@example.com      |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | manager  | C1     | manager |
      | manager  | C2     | manager |
      | manager  | C3     | manager |
      | manager  | C4     | manager |
      | student1 | C1     | student |
      | student2 | C1     | student |
      | student1 | C2     | student |
      | student2 | C2     | student |
      | student1 | C3     | student |
      | student2 | C3     | student |
      | student3 | C2     | student |

    And the following "groups" exist:
      | name       | course | idnumber | enablemessaging |
      | Group C1 1 | C1     | G1       | 1               |
      | Group C1 2 | C1     | G2       | 1               |
      | Group C2 1 | C2     | G3       | 1               |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G1    |
      | student1 | G2    |
      | student1 | G3    |
      | manager  | G1    |
      | manager  | G2    |

    And I log in as "admin"
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    And I add the "Dash" block
    And I click on "My groups" "radio"
    And I configure the "New Dash" block
    And I set the following fields to these values:
      | Block title  | My groups |
      | Region       | content   |
    And I press "Save changes"
    And I click on "Reset Dashboard for all users" "button"
    And I log out

  @javascript
  Scenario: User groups widget in Dash Block
    Given I log in as "student1"
    And I should see "Group C1 1" in the "My groups" "block"
    And I should see "Group C2 1" in the "My groups" "block"
    Then the "title" attribute of ".block_dash-community-block .list-block:nth-child(1) img" "css_element" should contain "Max Manager"
    And I click on ".dropdown-toggle" "css_element" in the ".block_dash-community-block .list-block:nth-child(1)" "css_element"
    And I click on ".group-widget-viewmembers" "css_element" in the ".block_dash-community-block .list-block:nth-child(1)" "css_element"
    And "Student Two" "table_row" should exist
    And I click on "button" "css_element" in the ".modal-header" "css_element"
    And I click on ".dropdown-toggle" "css_element" in the ".block_dash-community-block .list-block:nth-child(3)" "css_element"
    And I click on ".group-widget-viewmembers" "css_element" in the ".block_dash-community-block .list-block:nth-child(3)" "css_element"
    Then I should see "Nothing to display" in the ".modal-body" "css_element"

  @javascript
  Scenario: Leave group using dash block
    Given I log in as "student1"
    And I should see "Group C2 1" in the "My groups" "block"
    And I click on ".dropdown-toggle" "css_element" in the ".block_dash-community-block .list-block:nth-child(3)" "css_element"
    And I click on ".group-widget-leavegroup" "css_element" in the ".block_dash-community-block .list-block:nth-child(3)" "css_element"
    And I should see "Do you really want to leave the group Group C2 1" in the ".modal-body" "css_element"
    And I click on "Confirm" "button"
    And I should not see "Group C2 1" in the "My groups" "block"

  @javascript
  Scenario: Add User to existing group using dash block
    Given I log in as "manager"
    And I should see "Group C1 2" in the "My groups" "block"
    And I click on ".dropdown-toggle" "css_element" in the ".block_dash-community-block .list-block:nth-child(2)" "css_element"
    And I click on ".add-group-users" "css_element" in the ".block_dash-community-block .list-block:nth-child(2)" "css_element"
    And I set the following fields to these values:
      | User | Student Two |
    And I click on "Save changes" "button" in the ".modal-footer" "css_element"
    Then the "title" attribute of ".block_dash-community-block .list-block:nth-child(2) .img-block:nth-child(2) img" "css_element" should contain "Student Two"
    And I click on ".dropdown-toggle" "css_element" in the ".block_dash-community-block .list-block:nth-child(2)" "css_element"
    And I click on ".group-widget-viewmembers" "css_element" in the ".block_dash-community-block .list-block:nth-child(2)" "css_element"
    And "Student Two" "table_row" should exist

  @javascript
  Scenario: Create a new group using dash block
    Given I log in as "manager"
    And I should not see "Group C4 1" in the "My groups" "block"
    And I click on ".dropdown-toggle" "css_element" in the ".edit-block" "css_element"
    And I click on ".create-group" "css_element" in the ".edit-block" "css_element"
    And I set the following fields to these values:
      | Group name | Group C4 1 |
    And I open the autocomplete suggestions list
    And I click on "Course 4" item in the autocomplete list
    And I click on "Save changes" "button" in the ".modal-footer" "css_element"
    And I should see "Group C4 1" in the "My groups" "block"
