@core @core_contentbank @core_h5p @contentbank_h5p @_switch_iframe @javascript
Feature: Confirm content bank events are triggered
  In order to log content bank actions
  As an admin
  I need to be able to check triggered events

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "contentbank content" exist:
      | contextlevel | reference | contenttype     | user  | contentname | filepath                                   |
      | Course       | C1        | contenttype_h5p | admin | Existing    | /h5p/tests/fixtures/filltheblanks.h5p      |
    And the following "user private file" exists:
      | user     | admin                                |
      | filepath | h5p/tests/fixtures/filltheblanks.h5p |
    And the following config values are set as admin:
      | unaddableblocks | | theme_boost|
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Navigation" block if not present

  Scenario: Content created and uploaded events when uploading a content file
    Given I navigate to "Reports > Live logs" in site administration
    And I should not see "Content uploaded"
    And I should not see "Content created"
    And I am on "Course 1" course homepage
    And I expand "Site pages" node
    And I click on "Content bank" "link"
    When I click on "Upload" "link"
    And I click on "Choose a file..." "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "filltheblanks.h5p" "link"
    And I click on "Select this file" "button"
    And I click on "Save changes" "button"
    And I navigate to "Reports > Live logs" in site administration
    Then I should see "Content uploaded"
    And I should see "Content created"

  Scenario: Content viewed event
    Given I navigate to "Reports > Live logs" in site administration
    And I should not see "Content viewed"
    And I am on "Course 1" course homepage
    And I expand "Site pages" node
    And I click on "Content bank" "link"
    When I click on "Existing" "link"
    And I navigate to "Reports > Live logs" in site administration
    Then I should see "Content viewed"

  Scenario: Content deleted event
    Given I navigate to "Reports > Live logs" in site administration
    And I should not see "Content deleted"
    And I am on "Course 1" course homepage
    And I expand "Site pages" node
    And I click on "Content bank" "link"
    And I click on "Existing" "link"
    And  I click on "More" "button"
    When I click on "Delete" "link"
    And I click on "Delete" "button" in the "Delete content" "dialogue"
    And I navigate to "Reports > Live logs" in site administration
    Then I should see "Content deleted"

  Scenario: Content updated event when renaming
    Given I navigate to "Reports > Live logs" in site administration
    And I should not see "Content updated"
    And I am on "Course 1" course homepage
    And I expand "Site pages" node
    And I click on "Content bank" "link"
    And I click on "Existing" "link"
    And  I click on "More" "button"
    When I click on "Rename" "link"
    And I set the field "Content name" to "New name"
    And I click on "Rename" "button"
    And I navigate to "Reports > Live logs" in site administration
    Then I should see "Content updated"
