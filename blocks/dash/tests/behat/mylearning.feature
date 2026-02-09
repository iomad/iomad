@block @block_dash
Feature: Enable the widget in dash block on the dashboard page and view it's contents
  In order to enable the widgets in dash block on the dashboard
  As an admin
  I can add the dash block to the dashboard

  Background:
    Given the following "categories" exist:
      | name       | category | idnumber |
      | Category 1 | 0        | CAT1     |
      | Category 2 | 0        | CAT2     |
      | Category 3 | CAT2     | CAT3     |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
      | Course 2 | C2        | CAT1     | 0                |
      | Course 3 | C3        | CAT2     | 1                |
      | Course 4 | C4        | CAT3     | 1                |
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
      | teacher1 | C1     | teacher |
      | student1 | C2     | student |
      | student1 | C3     | student |

    And I log in as "admin"
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    And I add the "Dash" block
    And I click on "My learning" "radio"
    And I configure the "New Dash" block
    And I set the following fields to these values:
      | Block title  | My Learning                 |
      | Region  | content                          |
      | Content | My learaning empty state content |
    And I press "Save changes"
    And I click on "Reset Dashboard for all users" "button"
    And I log out

  @javascript
  Scenario: Add the dash mylearning widget block on the dashboard
    Given I log in as "student1"
    And I should see "Course 1" in the ".course-info-block .row .desc-block a h2" "css_element"
    And I should see "Course 2" in the ".course-info-block .row:nth-child(2) .desc-block a h2" "css_element"
    And I should see "Course 3" in the ".course-info-block .row:nth-child(3) .desc-block a h2" "css_element"

  @javascript
  Scenario: Course completion status in mylearning widget
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the following fields to these values:
      | Test page name   | 1 |
      | Test page name 2 | 1 |
    And I press "Save changes"
    And I log out
    When I log in as "student1"
    Then I should see "Course 1" in the "My Learning" "block"
    And I should see "0" in the ".card-header:nth-child(1)" "css_element"
    And I click on "General" "button" in the "My Learning" "block"
    Then I click on "Test page name" "link"
    And I follow dashboard
    Then the "class" attribute of ".block_dash-info-element .card:nth-child(1)" "css_element" should contain "completed-bg"
    And ".fa.fa-check" "css_element" should exist in the ".block_dash-info-element .card:nth-child(1)" "css_element"

  @javascript @_file_upload
  Scenario: Course badges list in Mylearning widget
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Badges > Add a new badge" in current page administration
    And I set the following fields to these values:
      | id_name        | Badge 1 |
      | id_description | Badge 1 |
    And I upload "blocks/badges/tests/fixtures/badge.png" file to "Image" filemanager
    And I press "Create badge"
    And I select "Manual issue by role" from the "Add badge criteria" singleselect
    And I set the field "Teacher" to "1"
    And I press "Save"
    And I press "Enable access"
    And I click on "button.btn-primary" "css_element" in the ".modal-footer" "css_element"
    And I follow badge recipients
    And I press "Award badge"
    And I set the field "potentialrecipients[]" to "Student First (student1@example.com)"
    And I press "Award badge"
    And I log out
    When I log in as "student1"
    Then ".collected .activatebadge[alt=\"Badge 1\"]" "css_element" should exist in the "My Learning" "block"

  @javascript
  Scenario: Check the empty state option.
    Given I log in as "student2"
    Then I should see "My learaning empty state content" in the "My Learning" "block"
    And I log out
    When I log in as "student1"
    Then I should not see "My learaning empty state content" in the "My Learning" "block"
    And I log out
