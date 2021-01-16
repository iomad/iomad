@mod @mod_data
Feature: Users can view and search database entries
  In order to find the database entries that I am looking for
  As a user
  I need to list and search the database entries

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Bob       | 1        | student1@example.com |
      | student2 | Alice     | 2        | student2@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "tags" exist:
      | name | isstandard |
      | Tag1 | 1          |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity | name               | intro          | course | idnumber |
      | data     | Test database name | Database intro | C1     | data1    |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I add a "Text input" field to "Test database name" database and I fill the form with:
      | Field name        | Test field name        |
      | Field description | Test field description |
    And I add a "Text input" field to "Test database name" database and I fill the form with:
      | Field name        | Test field 2 name        |
      | Field description | Test field 2 description |
    # To generate the default templates.
    And I follow "Templates"
    And I log out

  Scenario: Students can add view, list and search entries
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test database name"
    And I add an entry to "Test database name" database with:
      | Test field name | Student entry 1 |
    And I press "Save and add another"
    And I add an entry to "Test database name" database with:
      | Test field name | Student entry 2 |
    And I press "Save and add another"
    And I add an entry to "Test database name" database with:
      | Test field name | Student entry 3 |
    And I press "Save and view"
    And I follow "Test database name"
    Then I should see "Student entry 1"
    And I should see "Student entry 2"
    And I should see "Student entry 3"
    And I follow "View single"
    And I should see "Student entry 1"
    And I should not see "Student entry 2"
    And "2" "link" should exist
    And "3" "link" should exist
    And I follow "Next"
    And I should see "Student entry 2"
    And I should not see "Student entry 1"
    And I click on "3" "link" in the "region-main" "region"
    And I should see "Student entry 3"
    And I should not see "Student entry 2"
    And I follow "Previous"
    And I should see "Student entry 2"
    And I should not see "Student entry 1"
    And I should not see "Student entry 3"
    And I follow "Search"
    And I set the field "Test field name" to "Student entry 1"
    And I press "Save settings"
    And I should see "Student entry 1"
    And I should not see "Student entry 2"
    And I should not see "Student entry 3"
    And I follow "Search"
    And I set the field "Test field name" to "Student entry"
    And I set the field "Order" to "Descending"
    And I press "Save settings"
    And "Student entry 3" "text" should appear before "Student entry 2" "text"
    And "Student entry 2" "text" should appear before "Student entry 1" "text"

  @javascript
  Scenario: Check that searching by tags works as expected
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I add an entry to "Test database name" database with:
      | Test field name   | Student original entry untagged   |
      | Test field 2 name | Student original entry untagged 2 |
    And I add an entry to "Test database name" database with:
      | Test field name   | Student original entry tagged   |
      | Test field 2 name | Student original entry tagged 2 |
    And I set the field with xpath "//div[@class='datatagcontrol']//input[@type='text']" to "Tag1"
    And I click on "[data-value='Tag1']" "css_element"
    And I press "Save and view"
    And I should see "Student original entry"
    And I should see "Tag1" in the "div.tag_list" "css_element"
    And I follow "Edit"
    And I should see "Tag1" in the ".form-autocomplete-selection" "css_element"
    And I follow "View list"
    And I should see "Tag1" in the "div.tag_list" "css_element"
    And I follow "Search"
    And I set the field with xpath "//div[@class='datatagcontrol']//input[@type='text']" to "Tag1"
    And I click on "[data-value='Tag1']" "css_element"
    When I press "Save settings"
    Then I should see "Student original entry tagged"
    And I should see "Student original entry tagged 2"
    And I should not see "Student original entry untagged"
    And I should not see "Student original entry untagged 2"

  Scenario: Check that searching by first and last name works as expected
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I add an entry to "Test database name" database with:
      | Test field name | Student entry 1 |
    And I press "Save and view"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I add an entry to "Test database name" database with:
      | Test field name | Student entry 2 |
    And I press "Save and view"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test database name"
    And I follow "Search"
    And I set the field "Author first name" to "Bob"
    And I press "Save settings"
    Then I should see "Student entry 1"
    And I should not see "Student entry 2"
    And I set the field "Author first name" to ""
    And I set the field "Author surname" to "2"
    And I press "Save settings"
    And I should not see "Student entry 1"
    And I should see "Student entry 2"
