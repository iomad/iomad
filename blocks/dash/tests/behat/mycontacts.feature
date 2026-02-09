@block @block_dash
Feature: Add user contacts widget in dash block
  In order to enable the contacts widgets in dash block on the dashboard
  As an admin
  I can add the dash block to the dashboard

  Background:
    Given the following "categories" exist:
      | name        | category | idnumber |
      | Category 1  | 0        | CAT1     |
      | Category 2  | 0        | CAT2     |
      | Category 3  | CAT2     | CAT3     |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion|
      | Course 1 | C1        | 0        | 1 |
      | Course 2 | C2        | CAT1     | 0 |
      | Course 3 | C3        | CAT2     | 1 |
      | Course 4 | C4        | CAT3     | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | First    | student1@example.com |
      | student2 | Student   | Two   | student2@example.com |
      | student3 | Student   | Three   | student3@example.com |
      | teacher1 | Teacher   | First    | teacher1@example.com |
      | user1    | user      | First    | teacher1@example.com |
      | student4 | Student   | Four    | student2@example.com |
      | student5 | Student   | Five   | student3@example.com |
    And the following "course enrolments" exist:
      | user | course | role           |
      | student1 | C1 | student |
      | student2 | C1 | teacher |
      | user1    | C1 | student |
      | student1 | C2 | student |
      | student1 | C3 | student |
    And the following "cohorts" exist:
      | name | idnumber |
      | Cohort 1 | CH1 |
    And the following "cohort members" exist:
      | user  | cohort |
      | student3 | CH1    |
      | user1    | CH1    |
    And the following "groups" exist:
      | name    | course | idnumber | enablemessaging |
      | Group 1 | C1     | G1       | 1               |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1 |
      | student2 | G1 |
      | user1 | G1 |
    And the following "group messages" exist:
      | user     | group  | message                   |
      | student1 | G1     | Hi!                       |
      | student2 | G1     | How are you?              |
      | student1 | G1     | Can somebody help me?     |
    And the following "message contacts" exist:
      | user     | contact |
      | student1 | student2 |
      | student1 | student3 |

    And the following "private messages" exist:
      | user     | contact  | message       |
      | student1 | student2 | Hi!           |
      | student2 | student1 | Hello!        |
      | student1 | student2 | Are you free? |

    And I log in as "admin"
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    And I add the "Dash" block
    And I click on "#id_config_data_source_idnumber_block_dashlocalwidgetcontactscontacts_widget" "css_element"
    And I click on "Reset Dashboard for all users" "button"
    And I log out

  @javascript
  Scenario: Confirm the user contact list and course
    Given I log in as "student1"
    And I should see "Student Two" in the "New Dash" "block"
    And I should see "Student Three" in the "New Dash" "block"
    And I should see "2" in the ".block_dash-community-block .contact-element .row div.col-xl-3:nth-child(1) .badge-block" "css_element"
    And ".badge-block" "css_element" should not exist in the ".block_dash-community-block .contact-element .row div:nth-child(2)" "css_element"
    And I hover ".block_dash-community-block .contact-element .row div.col-xl-3:nth-child(1)" "css_element"
    And I click on ".contact-widget-viewgroup" "css_element" in the ".block_dash-community-block .contact-element .row div.col-xl-3:nth-child(1)" "css_element"
    And I should see "Groups" in the ".modal-title" "css_element"
    And "Group 1" "table_row" should exist
    And I click on "button" "css_element" in the ".modal-header" "css_element"
    And I hover ".block_dash-community-block .contact-element .row div.col-xl-3:nth-child(2)" "css_element"
    And I click on ".contact-widget-viewgroup" "css_element" in the ".block_dash-community-block .contact-element .row div.col-xl-3:nth-child(2)" "css_element"
    And I should see "Groups" in the ".modal-title" "css_element"
    Then I should see "Nothing to display" in the ".modal-body" "css_element"

  @javascript
  Scenario: Check the suggest user display options
    Given I log in as "user1"
    And I should not see "Student First" in the "New Dash" "block"
    And I should not see "Student Two" in the "New Dash" "block"
    And I should not see "Student Three" in the "New Dash" "block"
    And I should not see "Student Four" in the "New Dash" "block"
    And I should not see "Student Five" in the "New Dash" "block"
    Then I log in as "admin"
    Then I navigate to "Plugins > Blocks > Dash" in site administration
    Then I should see "Suggest users that have same groups"
    And I set the field "Suggest users that have same groups" to "5"
    And I press "Save changes"
    Then I log in as "user1"
    And I should see "Student First" in the "New Dash" "block"
    And I should see "Student Two" in the "New Dash" "block"
    Then I log in as "admin"
    Then I navigate to "Plugins > Blocks > Dash" in site administration
    Then I should see "Suggest users that have same cohort"
    And I set the field "Suggest users that have same cohort" to "5"
    And I press "Save changes"
    Then I log in as "user1"
    And I should see "Student Three" in the "New Dash" "block"
    Then I log in as "admin"
    Then I navigate to "Plugins > Blocks > Dash" in site administration
    Then I should see "Suggest users that have same users"
    And I set the field "Suggest users that have same users" to "Student Four"
    And I press "Save changes"
    Then I log in as "user1"
    And I should see "Student Four" in the "New Dash" "block"
