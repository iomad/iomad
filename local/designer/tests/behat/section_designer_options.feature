@local @local_designer @javascript
Feature: Sections can update based on config in designer format
  In order to rearrange my course contents
  As a teacher
  I need to check designer section features.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1        | designer | 0             | 5           |
    And the following "activities" exist:
      | activity   | name                   | intro                         | course | idnumber    | section |
      | assign     | Test assignment name   | Test assignment description   | C1     | assign1     | 0       |
      | book       | Test book name         | Test book description         | C1     | book1       | 0       |
      | chat       | Test chat name         | Test chat description         | C1     | chat1       | 4       |
      | choice     | Test choice name       | Test choice description       | C1     | choice1     | 5       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  Scenario: Check designer section type settings.
    When I open section "0" edit menu in designer
    And I should see "Section type"
    And I set the following fields to these values:
      | Title | Demo section type1 |
      | categorisebackcolor | #A938FB |
      | categorisetextcolor | #FFFFFE |
    And I press "Save changes"
    Then I should see "Demo section type1" in the "li#section-0 .categorise-section-block" "css_element"
    And I check designer css "rgb(169, 56, 251)" "li#section-0 .categorise-section-block span" "background-color"
    And I check designer css "rgb(255, 255, 254)" "li#section-0 .categorise-section-block span" "color"
    And I open section "1" edit menu in designer
    And I should see "Section type"
    And I set the following fields to these values:
      | Title | Demo section type2 |
      | categorisebackcolor | #A938FB |
      | categorisetextcolor | #FFFFFE |
    And I press "Save changes"
    Then I should see "Demo section type2" in the "li#section-1 .categorise-section-block" "css_element"
    And I check designer css "rgb(169, 56, 251)" "li#section-1 .categorise-section-block span" "background-color"
    And I check designer css "rgb(255, 255, 254)" "li#section-1 .categorise-section-block span" "color"
    And I open section "0" edit menu in designer
    And I set the following fields to these values:
      | Title | Demo section changed |
    And I press "Save changes"
    Then I should see "Demo section changed" in the "li#section-0 .categorise-section-block" "css_element"
    Then I should see "Demo section type2" in the "li#section-1 .categorise-section-block" "css_element"

  Scenario: Check desginer section layouts settings.
    When I open section "0" edit menu in designer
    And I should see "Layouts"
    And I set the following fields to these values:
      | Section container | Full |
      | Section content | Normal |
    And I press "Save changes"
    Then "#section-0.container-full" "css_element" should be visible
    Then "#section-0.container-boxed" "css_element" should not be visible
    And "#section-0 .content.content-normal" "css_element" should be visible
    And "#section-0 .content.content-boxed" "css_element" should not be visible
    Then I open section "0" edit menu in designer
    And I set the following fields to these values:
      | Section container       | Boxed      |
      | Section container width | 1400       |
      | Section content         | Boxed      |
      | Section content width   | 1100       |
    And I press "Save changes"
    Then "#section-0.container-full" "css_element" should not be visible
    Then "#section-0.container-boxed" "css_element" should be visible
    And "#section-0 .content.content-boxed" "css_element" should be visible
    And I check designer css "1400px" "li#section-0" "max-width"
    And I check designer css "1100px" "li#section-0 .content" "max-width"

  Scenario: Check desginer section design settings
    When I open section "0" edit menu in designer
    And I should see "Design"
    And I set the following fields to these values:
      | Apply the background to        | Section header  |
      | sectiondesignerbackgroundcolor | #CC5800         |
      | sectiondesignertextcolor       | #FFFDF9         |
    Then I press "Save changes"
    And I check designer css "rgb(204, 88, 0)" "li#section-0 .section-header-content" "background-color"
    And I check not designer css "rgb(204, 88, 0)" "li#section-0" "background-color"
    And I check not designer css "rgb(222, 58, 0)" "li#section-0 .section-header-content" "background-color"
    And I open section "0" edit menu in designer
    And I set the following fields to these values:
      | Apply the background to        | Whole section   |
      | sectiondesignerbackgroundcolor | #87D6FC         |
    Then I press "Save changes"
    And I check designer css "rgb(135, 214, 252)" "li#section-0" "background-color"
    And I check not designer css "rgb(135, 214, 252)" "li#section-0 .section-header-content" "background-color"
    Then I check not designer css "rgb(235, 214, 152)" "li#section-0" "background-color"
    And I open section "0" edit menu in designer
    And I set the following fields to these values:
      | Apply the background to        | Whole section    |
      | sectiondesignerbackgradient    | linear-gradient(#ffa98e, #afd8fd)  |
      | hidesectiontitle               | 1                |
    Then I press "Save changes"
    And I check designer css "linear-gradient(rgb(255, 169, 142), rgb(175, 216, 253))" "li#section-0 .section-design-whole" "background"
    And "#section-0 .sectionname" "css_element" should not be visible
    Then I open section "0" edit menu in designer
    And I set the following fields to these values:
    | hidesectiontitle             | 0                |
    Then I press "Save changes"
    And "#section-0 .sectionname" "css_element" should be visible

  @javascript
  Scenario: Min height for sections
    Given I open section "0" edit menu in designer
    And I expand all fieldsets
    And I should see "Minimum height"
    And I set the field "Minimum height" to "300px"
    And I press "Save changes"
    Given Element "li#section-0 .section-header-content" should contain style "min-height" "300px"
