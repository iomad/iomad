@local @local_designer @designer_pro
Feature: Show checkmark on the course header upon the user completion
  In order to use the features
  As admin
  I need to be able to configure the designer plugin to display the checkmark upon the course completion

  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
    And the following "course" exist:
      | fullname    | shortname | category | enablecompletion |
      | Course 1    | C1        | 0        |  1         |
      | Course 2    | C2        | 0        |  1         |
      | Course 3    | C3        | 0        |  1         |
    And the following "activities" exist:
      | activity | name       | course | idnumber |  intro           | section  |completion|
      | page     | Test page1 | C1     | page1    | Page description | 1        | 1        |
      | page     | Test page4 | C1     | page4    | Page description | 1        | 1        |
      | page     | Test page2 | C2     | page1    | Page description | 2        | 1        |
      | page     | Test page3 | C3     | page1    | Page description | 3        | 1        |
      | page     | Test page5 | C1     | page5    | Page description | 0        | 1        |
    And the following "users" exist:
      | username | firstname | lastname | email                   |
      | student1 | Student   | First    | student1@example.com    |
      | student2 | Student   | Two      | student2@example.com    |
      | student3 | Student   | Three    | student3@example.com    |
    And the following "course enrolments" exist:
      | user | course | role             |   timestart | timeend   |
      | student1 | C1 | student          |   0         |     0     |
      | student1 | C2 | student          |   0         |     0     |
      | student1 | C3 | student          |   0         |     0     |
      | student2 | C2 | student          |   0         |     0     |
      | student2 | C3 | student          |   0         |     0     |
      | admin    | C1 | manager          |   0         |     0     |
      | admin    | C2 | manager          |   0         |     0     |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Test page1" to "1"
    And I set the field "Test page4" to "1"
    And I press "Save changes"
    And I am on "Course 2" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Test page2" to "1"
    And I press "Save changes"

  @javascript
  Scenario: Enable / Disable checkmark upon the course completion on course header
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I wait until the page is ready
    And I set the following fields to these values:
      | Extended progress bar       | Show    |
      | Type                        | Hero    |
      | Course Progress             | Donut   |
      | id_completioncheckmark      | 1       |
      | id_courseheaderbgcolor      | #0366AA |
      | id_courseheadertextcolor    | #fff    |
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I am on the "Test page1" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "50%" in the ".progress-value .h2" "css_element"
    And I am on the "Test page4" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    Then ".progress-value .h2 i.fa-check" "css_element" should exist
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I set the following fields to these values:
      | id_completioncheckmark      | 0       |
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I should see "100%" in the ".progress-value .h2" "css_element"
    And I log out

  @javascript
  Scenario: Show / Hide the section's unavailable activities upon hiding the section
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I wait until the page is ready
    And I expand all fieldsets
    And I set the field "Course layout" to "Show one section per page"
    And I set the field "Display unavailable activities" to "Show"
    And I set the field "Hidden sections" to "Hidden sections are completely invisible"
    And I press "Save and display"
    And I turn block editing mode on
    When I open section "1" edit menu
    And I click on "Hide designer section" "link" in the "Designer section 1" "section"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    Then "li#section-1" "css_element" should exist in the "ul.designer" "css_element"
    And I should see "Not available" in the "li#section-1 .course-section-header .sectionbadges span" "css_element"
    Then "li#section-1 .section-summary-activities .activity-img" "css_element" should exist
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Hidden sections" to "Hidden sections are shown as not available"
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    Then "li#section-1" "css_element" should exist in the "ul.designer" "css_element"
    And I should see "Not available" in the "li#section-1 .course-section-header .sectionbadges span" "css_element"
    Then "li#section-1 .section-summary-activities .activity-img" "css_element" should exist
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Hidden sections" to "Hidden sections are shown as not available"
    And I set the field "Display unavailable activities" to "Hide"
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I should see "Not available" in the "li#section-1 .course-section-header .sectionbadges span" "css_element"
    Then "li#section-1 .section-summary-activities .activity-img" "css_element" should not exist
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Hidden sections" to "Hidden sections are completely invisible"
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    Then "li#section-1" "css_element" should not exist in the "ul.designer" "css_element"
    And I log out

  @javascript
  Scenario: Show / Hide the section's unavailable activities upon the activity restriction
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I wait until the page is ready
    And I expand all fieldsets
    And I set the field "Course layout" to "Show one section per page"
    And I set the field "Display unavailable activities" to "Show"
    And I set the field "Hidden sections" to "Hidden sections are completely invisible"
    And I press "Save and display"
    And I turn block editing mode on
    When I open section "1" edit menu
    And I click on "Edit designer section" "link" in the "Designer section 1" "section"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the following fields to these values:
      | cm         | Test page5     |
    And I press "Save changes"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    Then "li#section-1" "css_element" should exist in the "ul.designer" "css_element"
    Then ".section-restricted-action .icon" "css_element" should exist
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Hidden sections" to "Hidden sections are shown as not available"
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    Then "li#section-1" "css_element" should exist in the "ul.designer" "css_element"
    Then ".section-restricted-action .icon" "css_element" should exist
    Then "li#section-1 .section-summary-activities .activity-img" "css_element" should exist
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Hidden sections" to "Hidden sections are shown as not available"
    And I set the field "Display unavailable activities" to "Hide"
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    Then ".section-restricted-action .icon" "css_element" should exist
    Then "li#section-1 .section-summary-activities .activity-img" "css_element" should not exist
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Hidden sections" to "Hidden sections are completely invisible"
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    Then ".section-restricted-action .icon" "css_element" should exist
    Then "li#section-1 .section-summary-activities .activity-img" "css_element" should not exist
    And I log out

  @javascript
  Scenario: Display section title on course & section page
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I wait until the page is ready
    And I expand all fieldsets
    And I set the field "Course layout" to "Show one section per page"
    And I press "Save and display"
    And I turn block editing mode on
    When I open section "1" edit menu in designer
    And I expand all fieldsets
    And I set the following fields to these values:
      | Summary                      | Demo Summary                          |
      | Section title                | Display on course and section page    |
      | Section summary              | Display on course and section page    |
    And I press "Save changes"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I should see "Designer section 1" in the ".format-designer #section-1 .sectionname a:not(.aalink)" "css_element"
    And I should see "Demo Summary" in the ".format-designer #section-1 .summary" "css_element"
    And I click on "Designer section 1" "link" in the "region-main" "region"
    And I should see "Designer section 1" in the ".format-designer #section-1 .sectionname a:not(.aalink)" "css_element"
    And I should see "Demo Summary" in the ".format-designer #section-1 .summary" "css_element"
    And I log out
    And I log in as "admin"
    And I am on the "Course 1" course page
    And I turn block editing mode on
    When I open section "1" edit menu in designer
    And I expand all fieldsets
    And I set the following fields to these values:
      | Summary                      | Demo Summary             |
      | Section title                | Display on course page   |
      | Section summary              | Display on course page   |
    And I press "Save changes"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I should see "Designer section 1" in the ".format-designer #section-1 .section-head-text" "css_element"
    And I should see "Demo Summary" in the ".format-designer #section-1 .summary" "css_element"
    And I click on "Designer section 1" "link" in the "region-main" "region"
    And "Designer section 1" "link" should not exist in the "region-main" "region"
    And "Demo Summary" "text" should not exist in the "region-main" "region"
    And I log in as "admin"
    And I am on the "Course 1" course page
    And I turn block editing mode on
    When I open section "1" edit menu in designer
    And I expand all fieldsets
    And I set the following fields to these values:
      | Summary                      | Demo Summary              |
      | Section title                | Display on section page   |
      | Section summary              | Display on section page   |
    And I press "Save changes"
    And I am on the "Course 1" course page logged in as student1
    And "Designer section 1" "link" should not exist in the "region-main" "region"
    And "Demo Summary" "text" should not exist in the "region-main" "region"
    And I click on "#section-1" "css_element" in the "region-main" "region"
    And I should see "Designer section 1" in the ".format-designer #section-1 .sectionname a:not(.aalink)" "css_element"
    And I should see "Demo Summary" in the ".format-designer #section-1 .summary" "css_element"

  @javascript
  Scenario: Section cards display content options
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I wait until the page is ready
    And I expand all fieldsets
    And I set the field "Course layout" to "Show one section per page"
    And I press "Save and display"
    And I turn block editing mode on
    When I open section "1" edit menu in designer
    And I expand all fieldsets
    And I set the following fields to these values:
      | Section title                | Display on course and section page    |
      | Section summary              | Display on course and section page    |
      | Redirect to external URL     | https://www.example.com               |
      | id_sectioncardtab            | 0   |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I click on "Designer section 1" "link" in the "region-main" "region"
    And I should see "Designer section 1"
    And I log out
    And I log in as "admin"
    And I am on the "Course 1" course page
    And I turn block editing mode on
    When I open section "1" edit menu in designer
    And I expand all fieldsets
    And I set the following fields to these values:
      | Not available CTA          | The section is restrited |
    And I click on "Add restriction..." "button"
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the following fields to these values:
      | cm         | Test page5    |
      | id_sectioncardtab          | 1   |
    And I press "Save changes"
    And I am on the "Course 1" course page logged in as student1
    And I should see "The section is restrited" in the "li.section-type-defaultrestricted .section-header-content" "css_element"
    And I click on "Designer section 1" "link" in the "region-main" "region"

  @javascript
  Scenario: Display the purpose of an activity instead of the type
    Given I log in as "admin"
    And I navigate to "Plugins > Course formats > Designer format > Manage purposes" in site administration
    And ".fa-cog" "css_element" should exist in the "Administration" "table_row"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I wait until the page is ready
    And I expand all fieldsets
    And I set the field "Course layout" to "Show one section per page"
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And "ul.designer.activity-default-mode .section-summary-activities .activity-img" "css_element" should exist
    And I log out
    And I am on the "Course 1" course page logged in as admin
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Activity display mode" to "By purpose"
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And "ul.designer.activity-purpose-mode .section-summary-activities .activity-img" "css_element" should exist
    And I should see "2" in the "ul.designer.activity-purpose-mode #section-1 .section-summary-activities .activity-count" "css_element"
    And I log out
    And I am on the "Course 1" course page logged in as admin
    And I navigate to "Plugins > Course formats > Designer format > Manage purposes" in site administration
    And I click on "Create Purpose" "button"
    And I wait until the page is ready
    And I set the following fields to these values:
      | Purpose          | page1             |
      | Custom class for styling     | page-activity1   |
    And I click on ".fontawesome-picker-container .fontawesome-autocomplete" "css_element"
    And I click on ".fontawesome-icon-suggestions li .fa-book" "css_element"
    And I press "Save changes"
    And ".fa-book" "css_element" should exist in the "page1" "table_row"
    And I am on the "Course 1" course page
    And I am on the "Test page1" "page activity" page
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Purpose" to "page1"
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And "ul.designer.activity-purpose-mode #section-1 .activity-img.page-activity1" "css_element" should exist
    And I should see "1" in the "#section-1 .page-activity1 .activity-count" "css_element"
    And I log out
    And I log in as "admin"
    And I navigate to "Plugins > Course formats > Designer format > Manage purposes" in site administration
    And ".fa-book" "css_element" should exist in the "page1" "table_row"
    And I click on "Delete" "button" in the "page1" "table_row"
    And I wait until the page is ready
    And I click on "Delete" "button"
    And I am on the "Course 1" course page
    And I click on "Designer section 1" "link"
    And I am on the "Test page1" "page activity" page
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Purpose" to "Content"
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And "ul.designer.activity-purpose-mode #section-1 .activity-img" "css_element" should exist
    And I should see "2" in the "#section-1 .section-summary-activities .activity-count" "css_element"
