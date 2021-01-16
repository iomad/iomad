@customfield @customfield_textarea @javascript @_file_upload
Feature: Default value for the textarea custom field can contain images
  In order to see images on custom fields
  As a manager
  I need to be able to add images to the default value

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher  | Teacher   | 1        | teacher1@example.com |
      | manager  | Manager   | 1        | manager1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
    And the following "system role assigns" exist:
      | user    | course               | role    |
      | manager | Acceptance test site | manager |
    And the following "custom field categories" exist:
      | name              | component   | area   | itemid |
      | Category for test | core_course | course | 0      |
    # Upload an image into the private files.
    And I log in as "admin"
    And I follow "Manage private files"
    And I upload "lib/tests/fixtures/gd-logo.png" file to "Files" filemanager
    And I click on "Save changes" "button"
    And I navigate to "Courses > Course custom fields" in site administration
    And I click on "Add a new custom field" "link"
    And I click on "Text area" "link"
    And I set the following fields to these values:
      | Name       | Test field |
      | Short name | testfield  |
      | Default value | v       |
    # Embed the image into Default value.
    And I select the text in the "Default value" Atto editor
    And I click on "Insert or edit image" "button" in the "//*[@data-fieldtype='editor']/*[descendant::*[@id='id_configdata_defaultvalue_editoreditable']]" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "gd-logo.png" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "Example"
    And I click on "Save image" "button"
    And I press "Save changes"
    And I log out

  Scenario: For the courses that existed before the custom field was created the default value is displayed
    When I am on site homepage
    Then the image at "//*[contains(@class, 'frontpage-course-list-all')]//*[contains(@class, 'customfield_textarea')]//img[contains(@src, 'pluginfile.php') and contains(@src, '/customfield_textarea/defaultvalue/') and @alt='Example']" "xpath_element" should be identical to "lib/tests/fixtures/gd-logo.png"

  Scenario: Teacher will see textarea default value when editing a course created before custom field was created
     # Teacher will see the image when editing existing course.
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    Then "//*[@id='id_customfield_testfield_editoreditable']//img[contains(@src, 'draftfile.php') and contains(@src, '/gd-logo.png') and @alt='Example']" "xpath_element" should exist
    # Save the course without changing the default value.
    And I press "Save and display"
    And I log out
    # Now the same image is displayed as "value" and not as "defaultvalue".
    And I am on site homepage
    And "//img[contains(@src, '/customfield_textarea/defaultvalue/')]" "xpath_element" should not exist
    And the image at "//*[contains(@class, 'frontpage-course-list-all')]//*[contains(@class, 'customfield_textarea')]//img[contains(@src, 'pluginfile.php') and contains(@src, '/customfield_textarea/value/') and @alt='Example']" "xpath_element" should be identical to "lib/tests/fixtures/gd-logo.png"

  Scenario: Manager can create a course and the default value for textarea custom field will apply.
    When I log in as "manager"
    And I go to the courses management page
    And I click on "Create new course" "link" in the "#course-listing" "css_element"
    And I set the following fields to these values:
      | Course full name      | Course 2     |
      | Course short name     | C2           |
    And I expand all fieldsets
    Then "//*[@id='id_customfield_testfield_editoreditable']//img[contains(@src, 'draftfile.php') and contains(@src, '/gd-logo.png') and @alt='Example']" "xpath_element" should exist
    And I press "Save and display"
    And I log out
    # Now the same image is displayed as "value" and not as "defaultvalue".
    And I am on site homepage
    And the image at "//*[contains(@class, 'frontpage-course-list-all')]//*[contains(@class, 'customfield_textarea')]//img[contains(@src, 'pluginfile.php') and contains(@src, '/customfield_textarea/value/') and @alt='Example']" "xpath_element" should be identical to "lib/tests/fixtures/gd-logo.png"
