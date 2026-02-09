@format @format_designer @javascript
Feature: Sections can be switch the layouts in designer format
  In order to rearrange my course contents
  As a teacher
  I need to switch the designer layouts

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1        | designer | 0             | 5           |
    And the following "activities" exist:
      | activity   | name                   | intro                         | course | idnumber    | section |
      | assign     | Test assignment name   | Test assignment description   | C1     | assign1     | 0       |
      | book       | Test book name         | Test book description         | C1     | book1       | 0       |
      | chat       | Test chat name         | Test chat description         | C1     | chat1       | 4       |
      | choice     | Test choice name       | Test choice description       | C1     | choice1     | 5       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  Scenario: Check the switch the section layout
    Given "#section-0" "css_element" should be visible
    And I edit the section "0" to layout "list"
    Then "#section-0.section-type-list" "css_element" should exist
    And I edit the section "0" to layout "cards"
    Then "#section-0.section-type-cards" "css_element" should exist
    And I edit the section "0" to layout "link"
    Then "#section-0.section-type-default" "css_element" should exist
