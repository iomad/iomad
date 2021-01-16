@mod @mod_h5pactivity @core_h5p @_file_upload @_switch_iframe
Feature: Add H5P activity
  In order to let students access a H5P package
  As a teacher
  I need to add H5P activity to a course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "permission overrides" exist:
      | capability                 | permission | role           | contextlevel | reference |
      | moodle/h5p:updatelibraries | Allow      | editingteacher | System       |           |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: Add a h5pactivity activity to a course
    When I add a "H5P" to section "1"
    And I set the following fields to these values:
      | Name        | Awesome H5P package      |
      | Description | H5P activity Description |
    And I upload "h5p/tests/fixtures/ipsums.h5p" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I wait until the page is ready
    Then I should see "H5P activity Description"
    And I switch to "h5p-player" class iframe
    And I switch to "h5p-iframe" class iframe
    And I should see "Lorum ipsum"
    And I should not see "Reuse"
    And I should not see "Rights of use"
    And I should not see "Embed"
    And I switch to the main frame

  @javascript
  Scenario: Add a h5pactivity activity with download
    When I add a "H5P" to section "1"
    And I set the following fields to these values:
      | Name                       | Awesome H5P package |
      | Description                | Description         |
      | Allow download             | 1                   |
    And I upload "h5p/tests/fixtures/ipsums.h5p" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I wait until the page is ready
    Then I switch to "h5p-player" class iframe
    And I switch to "h5p-iframe" class iframe
    And I should see "Reuse"
    And I should not see "Rights of use"
    And I should not see "Embed"
    And I switch to the main frame

  @javascript
  Scenario: Add a h5pactivity activity with embed
    When I add a "H5P" to section "1"
    And I set the following fields to these values:
      | Name              | Awesome H5P package |
      | Description       | Description         |
      | Embed button      | 1                   |
    And I upload "h5p/tests/fixtures/ipsums.h5p" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I wait until the page is ready
    Then I switch to "h5p-player" class iframe
    And I switch to "h5p-iframe" class iframe
    And I should not see "Reuse"
    And I should not see "Rights of use"
    And I should see "Embed"
    And I switch to the main frame

  @javascript
  Scenario: Add a h5pactivity activity with copyright
    When I add a "H5P" to section "1"
    And I set the following fields to these values:
      | Name                  | Awesome H5P package |
      | Description           | Description         |
      | Copyright button      | 1                   |
    And I upload "h5p/tests/fixtures/guess-the-answer.h5p" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I wait until the page is ready
    Then I switch to "h5p-player" class iframe
    And I switch to "h5p-iframe" class iframe
    And I should not see "Reuse"
    And I should see "Rights of use"
    And I should not see "Embed"
    And I switch to the main frame

  @javascript
  Scenario: Add a h5pactivity activity with copyright in a content without copyright
    When I add a "H5P" to section "1"
    And I set the following fields to these values:
      | Name                  | Awesome H5P package |
      | Description           | Description         |
      | Copyright button      | 1                   |
    And I upload "h5p/tests/fixtures/ipsums.h5p" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I wait until the page is ready
    Then I switch to "h5p-player" class iframe
    And I switch to "h5p-iframe" class iframe
    And I should not see "Reuse"
    And I should not see "Rights of use"
    And I should not see "Embed"
    And I switch to the main frame

  @javascript
  Scenario: Add a h5pactivity activity to a course with all display options enabled
    When I add a "H5P" to section "1"
    And I set the following fields to these values:
      | Name                       | Awesome H5P package |
      | Description                | Description         |
      | Allow download             | 1                   |
      | Embed button               | 1                   |
      | Copyright button           | 1                   |
    And I upload "h5p/tests/fixtures/guess-the-answer.h5p" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I wait until the page is ready
    Then I switch to "h5p-player" class iframe
    And I switch to "h5p-iframe" class iframe
    And I should see "Reuse"
    And I should see "Rights of use"
    And I should see "Embed"
    And I switch to the main frame
