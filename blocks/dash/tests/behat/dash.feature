@block @block_dash @dash_feature @javascript @_file_upload
Feature: Add a dash to an admin pages
  In order to check the dash featuers
  I can add the dash block to the dashboard

  Background:
    Given the following "categories" exist:
      | name        | category | idnumber |
      | Category 01 | 0        | CAT1     |
      | Category 02 | 0        | CAT2     |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | CAT1     | 1                |
      | Course 2 | C2        | CAT1     | 0                |
      | Course 3 | C3        | CAT2     | 1                |
      | Course 4 | C4        | CAT2     | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | First    | student1@example.com |
      | teacher1 | Teacher   | First    | teacher1@example.com |
      | student2 | Student   | Two      | student2@example.com |
    And the following "activities" exist:
      | activity | course | idnumber | section | name             | intro                 | completion | completionview |
      | page     | C1     | page1    | 0       | Test page name   | Test page description | 2          | 1              |
      | page     | C1     | page2    | 1       | Test page name 2 | Test page description | 2          | 1              |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student1 | C2     | student |
      | teacher1 | C1     | teacher |
      | teacher1 | C2     | teacher |

  Scenario: Global Settings : Show header feature
    And I log in as "admin"
    And I navigate to "Plugins > Blocks > Dash" in site administration
    Then I set the field "Show header" to "Hidden"
    Then I press "Save changes"
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn editing mode on
    And I create dash "Users" datasource
    Then I configure the "New Dash" block
    And I set the field "Block title" to "Datasource: Users"
    And I set the following fields to these values:
      | Region | content |
    And I press "Save changes"
    Then I should see "Datasource: Users"
    Then I turn editing mode off
    Then I should not see "Datasource: Users"
    And I click on "Reset Dashboard for all users" "button"
    Then I log in as "student1"
    Then I follow "Dashboard"
    Then I turn editing mode on
    Then I should see "Datasource: Users"
    Then I turn editing mode off
    Then I should not see "Datasource: Users"
    Then I log in as "admin"
    And I navigate to "Plugins > Blocks > Dash" in site administration
    Then I set the field "Show header" to "Visible"
    Then I press "Save changes"
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn editing mode on
    And I create dash "Users" datasource
    Then I configure the "New Dash" block
    And I set the field "Block title" to "Datasource: Users Report"
    And I set the following fields to these values:
      | Region | content |
    And I press "Save changes"
    Then I should see "Datasource: Users Report"
    Then I turn editing mode off
    Then I should see "Datasource: Users Report"
    And I click on "Reset Dashboard for all users" "button"
    Then I log in as "student1"
    Then I follow "Dashboard"
    Then I turn editing mode on
    Then I should see "Datasource: Users Report"
    Then I turn editing mode off
    Then I should see "Datasource: Users Report"

  Scenario: Block Settings : Show header feature
    And I log in as "admin"
    And I navigate to "Plugins > Blocks > Dash" in site administration
    Then I set the field "Show header" to "Hidden"
    Then I press "Save changes"
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn editing mode on
    And I create dash "Users" datasource
    Then I configure the "New Dash" block
    And I set the field "Block title" to "Datasource: Users"
    And I set the following fields to these values:
      | Region | content |
      | Show header | Hidden |
    And I press "Save changes"
    Then I should see "Datasource: Users"
    Then I turn editing mode off
    Then I should not see "Datasource: Users"
    And I click on "Reset Dashboard for all users" "button"
    Then I log in as "student1"
    Then I follow "Dashboard"
    Then I turn editing mode on
    Then I should see "Datasource: Users"
    Then I turn editing mode off
    Then I should not see "Datasource: Users"

  Scenario: Block Settings: Dash settings improvements
    And I log in as "admin"
    # Gradient color
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    And I expand all fieldsets
    And I set the following fields to these values:
      | Background gradient | linear-gradient(90deg, rgba(255, 210, 0, .2) 0%, rgba(70, 210, 251, .2) 100%) |
    And I press "Save changes"
    And I click on "Reset Dashboard for all users" "button"
    And I follow dashboard
    And I check dash css "linear-gradient(90deg, rgba(255, 210, 0, 0.2) 0%, rgba(70, 210, 251, 0.2) 100%)" "section.block_dash:nth-of-type(1)" "background-image"

    # Font color picker
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    And I expand all fieldsets
    And I set the following fields to these values:
      | Block title | Users 01|
      | Font color | #c60061 |
    And I press "Save changes"
    And I click on "Reset Dashboard for all users" "button"
    And I follow dashboard
    And I check dash css "rgb(198, 0, 97)" "section.block_dash:nth-of-type(2) .card-title" "color"

    # Border color
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    And I add the "Dash" block
    And I configure the "New Dash" block
    And I expand all fieldsets
    And I set the following fields to these values:
      | Block title | Border settings |
      | Show border | Visible |
    And I press "Save changes"
    And I click on "Reset Dashboard for all users" "button"
    And I follow dashboard
    And I check dash css "1px solid rgba(0, 0, 0, 0.125)" "section.block_dash:nth-of-type(3)" "border"

    # General setting css classes
    And I navigate to "Plugins > Blocks > Dash" in site administration
    And I set the following fields to these values:
      | CSS Class | dash-card-block |
    And I press "Save changes"

    # Dash block setting css classes
    And I follow dashboard
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    And I expand all fieldsets
    And I set the following fields to these values:
      | Block title | CSS classes settings |
      | CSS Class | dash-element, dash-card |
    And I press "Save changes"
    And I click on "Reset Dashboard for all users" "button"
    And I follow dashboard
    And ".dash-element.dash-card" "css_element" should exist in the "section.block_dash:nth-of-type(4)" "css_element"

  Scenario: Default fields after selecting the data source
    And I log in as "admin"
    # Users data source
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    And I expand all fieldsets
    And I set the following fields to these values:
      | Block title | Users |
    And I press "Save changes"
    And I click on "Reset Dashboard for all users" "button"
    And I follow dashboard
    And I should see "Student"
    And I should see "First"
    And I should see "student1@example.com"
