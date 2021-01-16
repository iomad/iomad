@editor @editor_atto @atto @_file_upload
Feature: Atto Autosave
  To reduce frustration, atto should save drafts of my work.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | teacher2 | Teacher | 2 | teacher2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | teacher2 | C1 | editingteacher |
    And I log in as "admin"
    And I navigate to "Atto toolbar settings" node in "Site administration > Plugins > Text editors > Atto HTML editor"
    And I set the field "Autosave frequency" to "3"
    And I set the field with xpath "//select[@name='s_editor_atto_autosavefrequency[u]']" to "seconds"
    And I click on "Save changes" "button"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field with xpath "//select[@name='summary_editor[format]']" to "1"
    And I click on "Save and display" "button"
    And I log out

  @javascript
  Scenario: Restore a draft
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Course summary" to "This is my draft"
    # Wait for the autosave
    And I wait "5" seconds
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    # Wait for the autorestore
    And I wait "2" seconds
    Then I should see "This is my draft"

  @javascript
  Scenario: Do not restore a draft if files have been modified
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Course summary" to "This is my draft"
    # Wait for the autosave
    And I wait "5" seconds
    And I log out
    And I log in as "teacher2"
    And I follow "Manage private files..."
    And I upload "lib/editor/atto/tests/fixtures/moodle-logo.png" file to "Files" filemanager
    And I click on "Save changes" "button"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Course summary" to "<p>Image test</p>"
    And I select the text in the "Course summary" Atto editor
    And I click on "Image" "button"
    And I click on "Browse repositories..." "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "moodle-logo.png" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image" to "It's the Moodle"
    # Wait for the page to "settle".
    And I wait until the page is ready
    And I click on "Save image" "button"
    And I click on "Save and display" "button"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    Then I should not see "This is my draft"

  @javascript
  Scenario: Do not restore a draft if text has been modified
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Course summary" to "This is my draft"
    # Wait for the autosave
    And I wait "5" seconds
    And I log out
    And I log in as "teacher2"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Course summary" to "Modified text"
    And I click on "Save and display" "button"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    Then I should not see "This is my draft"
    And I should see "Modified text"
