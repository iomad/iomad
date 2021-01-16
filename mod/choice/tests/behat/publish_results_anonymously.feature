@mod @mod_choice
Feature: A teacher can choose whether to publish choice activity results anonymously or showing names
  In order to keep students privacy or to give more info to students
  As a teacher
  I need to select whether I want other students to know who selected what option

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
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

  Scenario: Publish anonymous results
    Given I add a "Choice" to section "1" and I fill the form with:
      | Choice name | Choice 1 |
      | Description | Choice Description |
      | option[0] | Option 1 |
      | option[1] | Option 2 |
      | Publish results | Always show results to students |
      | Privacy of results | Publish anonymous results, do not show student names |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I choose "Option 1" from "Choice 1" choice activity
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Choice 1"
    Then I should not see "Student 1"
    And I should not see "Users who chose this option"

  Scenario: Publish full results
    Given I add a "Choice" to section "1" and I fill the form with:
      | Choice name | Choice 1 |
      | Description | Choice Description |
      | option[0] | Option 1 |
      | option[1] | Option 2 |
      | Publish results | Always show results to students |
      | Privacy of results | Publish full results, showing names and their choices |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I choose "Option 1" from "Choice 1" choice activity
    And I log out
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Choice 1"
    Then I should see "Student 1"
    And I should see "Users who chose this option"
