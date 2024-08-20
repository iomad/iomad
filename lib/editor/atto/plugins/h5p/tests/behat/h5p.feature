@editor @editor_atto @atto @atto_h5p @core_h5p @_switch_iframe
Feature: Use the Atto editor to upload an h5p package
  To write rich text - I need to add h5ps.

  Background:
    Given the following "courses" exist:
      | shortname | fullname |
      | C1        | Course 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And the following "activities" exist:
      | activity | name       | intro      | introformat | course | content  | contentformat | idnumber |
      | page     | PageName1  | PageDesc1  | 1           | C1     | H5Ptest  | 1             | 1        |
    And the "displayh5p" filter is "on"
    And the following config values are set as admin:
      | allowedsources | https://moodle.h5p.com/content/[id] | filter_displayh5p |

  @javascript @external
  Scenario: Insert an embedded h5p
    Given I change window size to "large"
    And I am on the PageName1 "page activity editing" page logged in as admin
    And I click on "Insert H5P" "button" in the "#fitem_id_page" "css_element"
    And I set the field with xpath "//input[@data-region='h5pfile']" to "https://moodle.h5p.com/content/1290772960722742119"
    And I click on "Insert H5P" "button" in the "Insert H5P" "dialogue"
    And I wait until the page is ready
    When I click on "Save and display" "button"
    Then ".h5p-placeholder" "css_element" should exist
    And I wait until the page is ready
    And I switch to "h5pcontent" iframe
    And I should see "Lorum ipsum"

  @javascript
  Scenario: Insert an h5p file
    Given the following "user private file" exists:
      | user     | admin                                   |
      | filepath | h5p/tests/fixtures/guess-the-answer.h5p |
    And I am on the "PageName1" "page activity editing" page logged in as "admin"
    And I click on "Insert H5P" "button" in the "#fitem_id_page" "css_element"
    And I click on "Browse repositories..." "button" in the "Insert H5P" "dialogue"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "guess-the-answer.h5p" "link"
    And I click on "Select this file" "button"
    And I click on "Insert H5P" "button" in the "Insert H5P" "dialogue"
    And I wait until the page is ready
    When I click on "Save and display" "button"
    Then ".h5p-placeholder" "css_element" should exist

  @javascript
  Scenario: Test an invalid url
    Given I change window size to "large"
    And I am on the PageName1 "page activity editing" page logged in as admin
    And I click on "Insert H5P" "button" in the "#fitem_id_page" "css_element"
#   This is not a real external URL, so this scenario shouldn't be labeled as external.
    And I set the field with xpath "//input[@data-region='h5pfile']" to "ftp://moodle.h5p.com/content/1290772960722742119"
    When I click on "Insert H5P" "button" in the "Insert H5P" "dialogue"
    And I wait until the page is ready
    Then I should see "Invalid URL" in the "Insert H5P" "dialogue"

  @javascript
  Scenario: No h5p capabilities
    Given the following "permission overrides" exist:
    | capability | permission | role | contextlevel | reference |
    | atto/h5p:addembed | Prohibit | editingteacher | Course | C1 |
    | moodle/h5p:deploy | Prohibit | editingteacher | Course | C1 |
    When I am on the PageName1 "page activity editing" page logged in as teacher1
    Then "Insert H5P" "button" should not exist

  @javascript
  Scenario: No embed h5p capabilities
    Given the following "permission overrides" exist:
    | capability | permission | role | contextlevel | reference |
    | atto/h5p:addembed | Prohibit | editingteacher | Course | C1 |
    And I am on the PageName1 "page activity editing" page logged in as teacher1
    And I click on "Insert H5P" "button"
    Then I should not see "H5P URL" in the "Insert H5P" "dialogue"
    And I should see "H5P file upload" in the "Insert H5P" "dialogue"
    And I should see "H5P options" in the "Insert H5P" "dialogue"

  @javascript
  Scenario: No upload h5p capabilities
    Given the following "permission overrides" exist:
    | capability | permission | role | contextlevel | reference |
    | moodle/h5p:deploy | Prohibit | editingteacher | Course | C1 |
    When I am on the PageName1 "page activity editing" page logged in as teacher1
    And I click on "Insert H5P" "button"
    Then I should not see "H5P file upload" in the "Insert H5P" "dialogue"
    And I should see "H5P URL" in the "Insert H5P" "dialogue"
    And I should not see "H5P options" in the "Insert H5P" "dialogue"

  @javascript @external
  Scenario: Edit H5P content
    Given the following "user private file" exists:
      | user     | admin                       |
      | filepath | h5p/tests/fixtures/drag.h5p |
    And I am on the "PageName1" "page activity editing" page logged in as "admin"
    And I click on "Insert H5P" "button" in the "#fitem_id_page" "css_element"
#   H5P file content
    And I click on "Browse repositories..." "button" in the "Insert H5P" "dialogue"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "drag" "link"
    And I click on "Select this file" "button"
    And I click on "Insert H5P" "button" in the "Insert H5P" "dialogue"
    And I wait until the page is ready
    When I click on "Save and display" "button"
    And I switch to "h5pcontent" iframe
    And I switch to "h5p-iframe" class iframe
    Then I should not see "reveal"
    And I should see "Cloudberries"
    And I switch to the main frame
    And I navigate to "Settings" in current page administration
    And I click on ".h5p-placeholder" "css_element"
    And I click on "Insert H5P" "button" in the "#fitem_id_page" "css_element"
#   External URL
    And I set the field with xpath "//input[@data-region='h5pfile']" to "https://moodle.h5p.com/content/1290772960722742119"
    And I click on "Insert H5P" "button" in the "Insert H5P" "dialogue"
    And I wait until the page is ready
    And I click on "Save and display" "button"
    And I wait until the page is ready
    And I switch to "h5pcontent" iframe
    And I should see "Lorum ipsum"
    And I should not see "Cloudberries"

  @javascript
  Scenario: Enable/disable H5P options atto
    Given the following "user private file" exists:
      | user     | admin                                   |
      | filepath | h5p/tests/fixtures/guess-the-answer.h5p |
    And I am on the "PageName1" "page activity editing" page logged in as "admin"
    And I click on "Insert H5P" "button" in the "#fitem_id_page" "css_element"
    And I click on "Browse repositories..." "button" in the "Insert H5P" "dialogue"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "guess-the-answer.h5p" "link"
    And I click on "Select this file" "button"
#   No display option button displayed
    And I click on "Insert H5P" "button" in the "Insert H5P" "dialogue"
    When I click on "Save and display" "button"
    And I wait until the page is ready
    And I switch to "h5pcontent" iframe
    And I switch to "h5p-iframe" class iframe
    Then ".h5p-actions" "css_element" should not exist
    And I switch to the main frame
    And I navigate to "Settings" in current page administration
    And I click on ".h5p-placeholder" "css_element"
    And I click on "Insert H5P" "button" in the "#fitem_id_page" "css_element"
    And I click on "H5P options" "link"
#   Only Allow Download button displayed
    And I click on "Allow download" "checkbox"
    And I click on "Insert H5P" "button" in the "Insert H5P" "dialogue"
    And I click on "Save and display" "button"
    And I switch to "h5pcontent" iframe
    And I switch to "h5p-iframe" class iframe
    And "Reuse" "text" should exist in the ".h5p-actions" "css_element"
    And I should not see "Embed"
    And I should not see "Rights of use"
    And I switch to the main frame
    And I navigate to "Settings" in current page administration
    And I click on ".h5p-placeholder" "css_element"
    And I click on "Insert H5P" "button" in the "#fitem_id_page" "css_element"
#   Embed and copyright buttons displayed. Download not displayed
    And I click on "Allow download" "checkbox"
    And I click on "Embed button" "checkbox"
    And I click on "Copyright button" "checkbox"
    And I click on "Insert H5P" "button" in the "Insert H5P" "dialogue"
    And I click on "Save and display" "button"
    And I switch to "h5pcontent" iframe
    And I switch to "h5p-iframe" class iframe
    And "Reuse" "text" should not exist in the ".h5p-actions" "css_element"
    And I should see "Embed"
    And I should see "Rights of use"

  @javascript @external
  Scenario: H5P options are ignored for H5P URLs
    Given I change window size to "large"
    And I am on the PageName1 "page activity editing" page logged in as admin
    And I click on "Insert H5P" "button" in the "#fitem_id_page" "css_element"
    And I set the field with xpath "//input[@data-region='h5pfile']" to "https://moodle.h5p.com/content/1291366510035871129"
    And I click on "H5P options" "link"
    And I click on "Embed button" "checkbox"
    And I click on "Insert H5P" "button" in the "Insert H5P" "dialogue"
    When I click on "Save and display" "button"
    Then ".h5p-placeholder" "css_element" should exist
    And I switch to "h5pcontent" iframe
    And I should see "Far far away"
    And I should not see "Embed"
    And I switch to the main frame
    And I navigate to "Settings" in current page administration
    And I click on ".h5p-placeholder" "css_element"
    And I click on "Insert H5P" "button" in the "#fitem_id_page" "css_element"
    And I click on "H5P options" "link"
    And "input[aria-label=\"Embed button\"]:not([checked=checked])" "css_element" should exist

  @javascript
  Scenario: Private H5P files are shown to students
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student   | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
    And the following "user private file" exists:
      | user     | admin                                   |
      | filepath | h5p/tests/fixtures/guess-the-answer.h5p |
    And I am on the "PageName1" "page activity editing" page logged in as "admin"
    And I click on "Insert H5P" "button" in the "#fitem_id_page" "css_element"
    And I click on "Browse repositories..." "button" in the "Insert H5P" "dialogue"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "guess-the-answer.h5p" "link"
    And I click on "Select this file" "button"
    And I click on "Insert H5P" "button" in the "Insert H5P" "dialogue"
    And I wait until the page is ready
    And I click on "Save and display" "button"
    And I log out
    When I am on the PageName1 "page activity" page logged in as student1
    Then I switch to "h5pcontent" iframe
    And I switch to "h5p-iframe" class iframe
    And I should see "reveal"
