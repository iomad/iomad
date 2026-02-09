@local @local_designer @javascript @designer_global_options
Feature: Sections pro designer global settings
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
    And I log in as "admin"

  @javascript
  Scenario: Setup course default options.
    Given I navigate to "Plugins > Course formats > Designer format > General settings" in site administration
    And I click on "Course" "link" in the "#adminsettings" "css_element"
    Then I set the following fields to these values:
        | Course type | Collapsible sections |
        | Accordion | Disable |
        | Initial State | Collapsed |
    And I press "Save changes"
    Then I navigate to "Courses > Add a new course" in site administration
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    Then the field "Course type" matches value "Collapsible sections"
    And the field "Accordion" matches value "Disable"
    And the field "Initial State" matches value "Collapsed"
    Given I navigate to "Plugins > Course formats > Designer format > General settings" in site administration
    And I click on "Course" "link" in the "#adminsettings" "css_element"
    Then I wait "5" seconds
    Then I set the following fields to these values:
        | Course type    | Flow      |
        | Accordion      | Enable    |
        | Initial State  | First expanded |
    And I press "Save changes"
    Then I navigate to "Courses > Add a new course" in site administration
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    Then the field "Course type" matches value "Flow"
    And the field "Accordion" matches value "Enable"
    And the field "Initial State" matches value "First expanded"

  @javascript
  Scenario: Setup section default options.
    Given I navigate to "Plugins > Course formats > Designer format > General settings" in site administration
    And I click on "Section" "link"
    Then I set the following fields to these values:
        | s_format_designer_sectiondesignerbackgroundcolor | #A5C3FD |
        | s_format_designer_sectiondesignertextcolor | #0F003C |
        | Minimum height | 200px |
    And I press "Save changes"
    When I am on "Course 1" course homepage with editing mode on
    And I click on "a[data-action='addSection']" "css_element"
    And I should see "Designer section 3"
    And I check designer css "rgb(165, 195, 253)" "li#section-3 .section-header-content" "background-color"
    And I check designer css "rgb(15, 0, 60)" "li#section-3 .sectionname a" "color"
    And I check designer css "200px" "li#section-3 .section-header-content" "min-height"
    Given I navigate to "Plugins > Course formats > Designer format > General settings" in site administration
    And I click on "Section" "link"
    Then I set the following fields to these values:
        | s_format_designer_sectiondesignerbackgroundcolor | #FFDB75 |
        | s_format_designer_sectiondesignertextcolor | #8E0B00 |
        | Minimum height | 250px |
    And I press "Save changes"
    When I am on "Course 1" course homepage with editing mode on
    And I click on "a[data-action='addSection']" "css_element"
    And I should see "Designer section 4"
    And I check designer css "rgb(255, 219, 117)" "li#section-4 .section-header-content" "background-color"
    And I check designer css "rgb(142, 11, 0)" "li#section-4 .sectionname a" "color"
    And I check designer css "250px" "li#section-4 .section-header-content" "min-height"
