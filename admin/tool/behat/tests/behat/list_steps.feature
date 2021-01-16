@tool @tool_behat
Feature: List the system steps definitions
  In order to create new tests
  As a tests writer
  I need to list and filter the system steps definitions

  Background:
    Given I am on homepage
    And I log in as "admin"
    And I navigate to "Acceptance testing" node in "Site administration > Development"

  @javascript
  Scenario: Accessing the list
    Then I should see "Steps definitions"
    And I should not see "There aren't steps definitions matching this filter"

  @javascript
  Scenario: Filtering by type
    Given I set the field "Type" to "Then. Checkings to ensure the outcomes are the expected ones"
    When I press "Filter"
    Then I should see "Checks, that page contains specified text."
    And I should not see "Opens Moodle homepage."

  @javascript
  Scenario: Filtering by keyword
    Given I set the field "Contains" to "homepage"
    When I press "Filter"
    Then I should see "Opens Moodle homepage."
