@mod @mod_scorm
Feature: Unstable network
  In order to avoid losing progress in a SCORM package
  As a learner
  I need to be notified if my network is unstable

  Background:
    Given the following "users" exist:
      | username | firstname  | lastname  | email                |
      | student1 | Student    | 1         | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user      | course | role    |
      | student1  | C1     | student |
    And the following "activities" exist:
      | activity | course | name              | popup |
      | scorm    | C1     | Same window SCORM | 0     |
      | scorm    | C1     | New window SCORM  | 1     |

  @javascript
  Scenario: The unstable network modal renders on the SCORM player page when network is unstable
    When I am on the "Same window SCORM" "scorm activity" page logged in as "student1"
    And I press "Enter"
    And my network is unstable
    Then I should see "It looks like you have an unstable internet connection or your session has timed out"
    And I should see "Cancel" in the "Unstable network" "dialogue"

  @javascript
  Scenario: The unstable network modal can be closed without a redirect the first time it is shown
    When I am on the "Same window SCORM" "scorm activity" page logged in as "student1"
    And I press "Enter"
    And my network is unstable
    And I click on "Cancel" "button" in the "Unstable network" "dialogue"
    Then I should not see "It looks like you have an unstable internet connection or your session has timed out"
    And I should not see "Enter"

  @javascript
  Scenario: The unstable network modal cannot be closed without a redirect the second time it is shown
    When I am on the "Same window SCORM" "scorm activity" page logged in as "student1"
    And I press "Enter"
    And my network is unstable
    And my network is unstable
    Then I should not see "Cancel" in the "Unstable network" "dialogue"
    And I click on "Leave page" "button" in the "Unstable network" "dialogue"
    And I should see "Enter"

  @javascript
  Scenario: Unstable network must be detected twice consecutively before a user must leave the SCORM activity
    When I am on the "Same window SCORM" "scorm activity" page logged in as "student1"
    And I press "Enter"
    And my network is unstable
    And I click on "Cancel" "button" in the "Unstable network" "dialogue"
    And the session is touched
    And my network is unstable
    Then I should see "Cancel" in the "Unstable network" "dialogue"
    And my network is unstable
    And I should not see "Cancel" in the "Unstable network" "dialogue"

  @javascript @_switch_window
  Scenario: Unstable network modal redirects correctly when SCORM opens in new window
    When I am on the "New window SCORM" "scorm activity" page logged in as "student1"
    And I press "Enter"
    And I switch to a second window
    And my network is unstable
    And I click on "Leave page" "button" in the "Unstable network" "dialogue"
    Then I should see "Enter"
    And I should see "New window SCORM"
