@local @local_designer @javascript
Feature: Sections and activity design options in designer pro format
  In order to rearrange my course contents
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

  Scenario: Check desginer activity design settings
    Given I open "Test assignment name" actions menu
    And I click on "Edit settings" "link" in the "Test assignment name" activity
    And I should see "Design"
    And I set the following fields to these values:
      | id_designer_backgradient | linear-gradient(90deg, #fff 0%, #000 100%) |
    Then I press "Save and return to course"
    And I check designer css "linear-gradient(90deg, rgb(255, 255, 255) 0%, rgb(0, 0, 0) 100%)" "#section-0 .activity .activity-block .bg-overlay" "background"
    Then I open "Test book name" actions menu
    And I click on "Edit settings" "link" in the "Test book name" activity
    And I set the following fields to these values:
      | designer_textcolor | #3277FA |
    Then I press "Save and return to course"
    And I edit the section "0" to layout "cards"
    And I check designer css "rgb(50, 119, 250)" "li#section-0 .activity:nth-child(2) .instancename" "color"

  @javascript
  Scenario: Min height for activities
    Given I open "Test assignment name" actions menu
    And I click on "Edit settings" "link" in the "Test assignment name" activity
    And I expand all fieldsets
    And I should see "Minimum height"
    And I set the field "Minimum height" to "300px"
    And I press "Save and return to course"
    Given Element "li#section-0 .activity:nth-child(1) .activity-block" should contain style "min-height" "300px"

  @javascript
  Scenario: Activity elements display method
    Given I open "Test assignment name" actions menu
    And I click on "Edit settings" "link" in the "Test assignment name" activity
    And I expand all fieldsets
    And I set the following fields to these values:
      | Activity icon   | Hide |
      | Activity visits | Hide |
    And I press "Save and return to course"
    And Element "li#section-0 .activity:nth-child(1) .img-block" should contain style "visibility" "hidden"
    And Element "li#section-0 .activity:nth-child(1) .mod-visits-block p" should contain style "visibility" "hidden"
