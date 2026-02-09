@format @format_designer @javascript
Feature: Sections can be check activity completion element in designer format
  In order to rearrange my course contents
  As a teacher
  I need to check activiyt completion in designer format

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | First        | teacher1@example.com |
      | student1 | Student   | First       | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections | Enable completion tracking |
      | Course 1 | C1        | designer | 0             | 5           | yes                      |
    And the following "activities" exist:
      | activity   | name                   | intro                         | course | idnumber    | section | completionexpected        |
      | assign     | Test assignment name   | Test assignment description   | C1     | assign1     | 0       | ##last day of +5 days##   |
      | assign     | Test assignment name 1 | Test assignment1 description  | C1     | assign2     | 0       | 1   |
      | assign     | Test assignment name 2 | Test assignment2 description  | C1     | assign3     | 0       | ##5 days ago##            |
      | choice     | Test choice name       | Test choice description       | C1     | choice1     | 0       | ##tomorrow##              |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I edit the section "0" to layout "list"
    Then "#section-0.section-type-list" "css_element" should exist
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Enable completion tracking | Yes |
    And I press "Save and display"
    Then I log out
    And I log in as "admin"
    And I navigate to "Plugins > Course formats > Designer format" in site administration
    And I set the following fields to these values:
    | Date format | ##today##%d %B %Y## |
    And I press "Save changes"
    Then I log out

  Scenario: Check the manual mark completion the activity
    Given I am on the "Test assignment name" "assign activity editing" page logged in as teacher1
    And I expand all fieldsets
    Then I set the designer manual completion
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I check the activity "assign1" to element "/descendant::div[contains(@class, 'notstarted')]"
    And I am on "Course 1" course homepage
    And the manual completion button of "Test assignment name" is displayed as "Mark as done"
    And I toggle the manual completion state of "Test assignment name"
    And I am on "Course 1" course homepage
    And I check the activity "assign1" to element "/descendant::div[contains(@class, 'success')]"
    And I log out

  # Scenario: Check the manual date completion the activity
  #   Given I log in as "teacher1"
  #   And I am on "Course 1" course homepage
  #   #And I click on activity "assign2"
  #   And I am on the "Test assignment name 1" "assign activity" page
  #   And I navigate to "Settings" in current page administration
  #   And I set the following fields to these values:
  #     | Completion tracking           | Show activity as complete when conditions are met |
  #     | completionview                | 1    |
  #     | id_completionexpected_enabled | 1    |
  #   And I press "Save and return to course"
  #   And I log out
  #   And I log in as "student1"
  #   And I am on "Course 1" course homepage
  #   #Then I check the activity "assign2" to element "/descendant::div[contains(@class, 'notstarted')]"
  #   Then I check the activity "assign2" to element "/descendant::div[contains(@class, 'completion-info')]/descendant::i[contains(@class, 'fa-exclamation-triangle')]"
  #   And I should see designerinfo "assign2" "Due today" ""
  #   And I am on the "Test assignment name 1" "assign activity" page
  #   And I am on "Course 1" course homepage
  #   #Then I check the activity "assign2" to element "/descendant::div[contains(@class, 'success')]"
  #   Then I check the activity "assign2" to element "/descendant::div[contains(@class, 'completion-info')]/descendant::i[contains(@class, 'fa-check-circle')]"
  #   And I should see designerinfo "assign2" "Completed on " "##today##%d %B %Y##"
  #   Then I log out

  # Scenario: Check the due date completion the activity
  #   Given I am on the "Test assignment name 2" "assign activity editing" page logged in as teacher1
  #   And I follow "Expand all"
  #   And I set the following fields to these values:
  #     | Completion tracking           | Show activity as complete when conditions are met |
  #     | completionview                | 1    |
  #     | id_completionexpected_enabled | 1    |
  #     | id_completionexpected_day     | 1    |
  #     | id_completionexpected_month   | 1    |
  #     | id_completionexpected_year    | 2017 |

  #   And I press "Save and return to course"
  #   And I log out
  #   And I log in as "student1"
  #   And I am on "Course 1" course homepage
  #   #Then I check the activity "assign3" to element "/descendant::div[contains(@class, 'danger')]"
  #   Then I check the activity "assign3" to element "/descendant::div[contains(@class, 'completion-info')]/descendant::i[contains(@class, 'fa-exclamation-circle')]"
