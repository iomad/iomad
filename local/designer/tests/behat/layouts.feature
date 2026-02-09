@local @local_designer @javascript @designer_layouts
Feature: Sections pro designer layout test
  In order to use different layouts for my course contents
  As a teacher
  I need to switch the designer layouts

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1        | designer | 0             | 2           |
    And the following "activities" exist:
      | activity   | name                   | intro                         | course | idnumber    | section |
      | assign     | Test assignment name   | Test assignment description   | C1     | assign1     | 0       |
      | book       | Test book name         | Test book description         | C1     | book1       | 0       |
      | chat       | Test chat name         | Test chat description         | C1     | chat1       | 1       |
      | choice     | Test choice name       | Test choice description       | C1     | choice1     | 2       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: Horizontal circle layout.
    Given I am on "Course 1" course homepage with editing mode on
    And I edit the section "0" to layout "horizontal_circles"
    Then "#section-0.section-type-horizontal_circles" "css_element" should exist
    And the "class" attribute of "#section-0 li.activity" "css_element" should contain "horizontal_circles"

  Scenario: Circle sizes
    Given I am on "Course 1" course homepage with editing mode on
    And I edit the section "0" to layout "horizontal_circles"
    And I open section "0" edit menu in designer
    And I expand all fieldsets
    And I set the field "Circle size" to "Large"
    And I press "Save changes"
    Then the "class" attribute of "#section-0 li.activity" "css_element" should contain "circle-size-large"
    And I open section "0" edit menu in designer
    And I expand all fieldsets
    And I set the field "Circle size" to "Medium"
    And I press "Save changes"
    Then the "class" attribute of "#section-0 li.activity" "css_element" should contain "circle-size-medium"
