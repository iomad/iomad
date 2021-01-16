@block @block_blog_menu @mod_assign @block_blog_recent
Feature: Students can use the recent blog entries block to view recent entries on an activity page
  In order to enable the recent blog entries block an activity page
  As a teacher
  I can add the recent blog entries block to an activity page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email | idnumber |
      | teacher1 | Teacher | 1 | teacher1@example.com | T1 |
      | student1 | Student | 1 | student1@example.com | S1 |
      | student2 | Student | 2 | student2@example.com | S2 |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment 1 |
      | Description | Offline text |
      | assignsubmission_file_enabled | 0 |
    And I follow "Test assignment 1"
    And I add the "Blog menu" block
    And I add the "Recent blog entries" block
    And I log out

  Scenario: Students use the recent blog entries block to view blogs
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment 1"
    And I follow "Add an entry about this Assignment"
    When I set the following fields to these values:
      | Entry title | S1 First Blog |
      | Blog entry body | This is my awesome blog! |
    And I press "Save changes"
    Then I should see "S1 First Blog"
    And I should see "This is my awesome blog!"
    And I follow "Test assignment 1"
    And I should see "S1 First Blog"
    And I follow "S1 First Blog"
    And I should see "This is my awesome blog!"

  Scenario: Students only see a few entries in the recent blog entries block
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment 1"
    And I follow "Add an entry about this Assignment"
    # Blog 1 of 5
    And I set the following fields to these values:
      | Entry title | S1 First Blog |
      | Blog entry body | This is my awesome blog! |
    And I press "Save changes"
    And I wait "1" seconds
    And I follow "Test assignment 1"
    And I follow "Add an entry about this Assignment"
    # Blog 2 of 5
    And I set the following fields to these values:
      | Entry title | S1 Second Blog |
      | Blog entry body | This is my awesome blog! |
    And I press "Save changes"
    And I wait "1" seconds
    And I should see "S1 Second Blog"
    And I should see "This is my awesome blog!"
    And I follow "Test assignment 1"
    And I follow "Add an entry about this Assignment"
    # Blog 3 of 5
    And I set the following fields to these values:
      | Entry title | S1 Third Blog |
      | Blog entry body | This is my awesome blog! |
    And I press "Save changes"
    And I wait "1" seconds
    And I should see "S1 Third Blog"
    And I should see "This is my awesome blog!"
    And I follow "Test assignment 1"
    And I follow "Add an entry about this Assignment"
    # Blog 4 of 5
    And I set the following fields to these values:
      | Entry title | S1 Fourth Blog |
      | Blog entry body | This is my awesome blog! |
    And I press "Save changes"
    And I wait "1" seconds
    And I should see "S1 Fourth Blog"
    And I should see "This is my awesome blog!"
    And I follow "Test assignment 1"
    And I follow "Add an entry about this Assignment"
    # Blog 5 of 5
    And I set the following fields to these values:
      | Entry title | S1 Fifth Blog |
      | Blog entry body | This is my awesome blog! |
    And I press "Save changes"
    And I should see "S1 Fifth Blog"
    And I should see "This is my awesome blog!"
    When I follow "Test assignment 1"
    And I should not see "S1 First Blog"
    And I should see "S1 Second Blog"
    And I should see "S1 Third Blog"
    And I should see "S1 Fourth Blog"
    And I should see "S1 Fifth Blog"
    And I follow "S1 Fifth Blog"
    And I should see "This is my awesome blog!"
    Then I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Test assignment 1"
    And I configure the "Recent blog entries" block
    And I set the following fields to these values:
      | id_config_numberofrecentblogentries | 2 |
    And I press "Save changes"
    And I should see "S1 Fourth Blog"
    And I should see "S1 Fifth Blog"
