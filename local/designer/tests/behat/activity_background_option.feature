@local @local_designer @designer_section_option @javascript @_file_upload
Feature: To display the section background image & color options
  In order to use the features
  As admin
  I need to be able to configure the designer plugin to display the section background option

  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
    And the following "course" exist:
      | fullname    | shortname | category | enablecompletion | format |
      | Course 1    | C1        | 0        |  1         | designer |
      | Course 2    | C2        | 0        |  1         | designer |
      | Course 3    | C3        | 0        |  1         | designer |
    And the following "activities" exist:
      | activity | name       | course | idnumber | intro            | section  | completion  |
      | page     | Test page1 | C1     | page1    | Page description | 1        | 1           |
      | page     | Test page4 | C1     | page4    | Page description | 1        | 1           |
      | page     | Test page5 | C1     | page5    | Page description | 0        | 1           |
      | page     | Test page6 | C1     | page6    | Page description | 1        | 1           |
      | page     | Test page2 | C2     | page1    | Page description | 2        | 1           |
      | page     | Test page3 | C3     | page1    | Page description | 3        | 1           |
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
    And I am on "Course 3" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Test page3" to "1"
    And I press "Save changes"

  Scenario: Show the section background options
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I press "Save and display"
    And I navigate to "Plugins > Course format > Designer format > General setting" in site administration
    And I click on "Section" "link"
    And I upload "local/designer/tests/assets/place.png" file to "Section mask image" filemanager
    And I press "Save changes"
    And I am on the "Course 1" course page
    And I turn block editing mode on
    When I open section "1" edit menu in designer
    And I expand all fieldsets
    And I upload "local/designer/tests/assets/background.jpg" file to "Background image" filemanager
    And I set the following fields to these values:
      | Background Position      | Left Center   |
      | Background Size          | Contain       |
      | Background Repeat        | No            |
      | Mask image               | Place         |
      | Mask size                | Contain       |
      | Mask position            | Center Center |
    And I press "Save changes"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I check designer css "0% 50%" "li#section-1 .course-section-header .sectiondesign-header" "background-position"
    And I check designer css "contain" "li#section-1 .course-section-header .sectiondesign-header" "background-size"
    And I check designer css "repeat" "li#section-1 .course-section-header .sectiondesign-header" "background-repeat"
    And I log out
    And I log in as "admin"
    And I am on the "Course 1" course page
    And I turn block editing mode on
    When I open section "1" edit menu in designer
    And I expand all fieldsets
    And I set the following fields to these values:
      | Background Position          | Custom        |
      | Custom Background Position   | Center Top    |
      | Background Size              | Custom        |
      | Custom Background Size       | Cover         |
      | Mask size                    | Custom        |
      | Custom Mask Size             | Cover         |
      | Mask position                | Custom        |
      | Custom Mask Position         | Left Bottom   |
    And I press "Save changes"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I check designer css "50% 0%" "li#section-1 .course-section-header .sectiondesign-header" "background-position"
    And I check designer css "cover" "li#section-1 .course-section-header .sectiondesign-header" "background-size"
    And I log out
    And I log in as "admin"
    And I am on the "Course 1" course page
    And I turn block editing mode on
    When I open section "1" edit menu in designer
    And I expand all fieldsets
    And I set the following fields to these values:
      | Apply the background to      | Whole section   |
    And I press "Save changes"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I check designer css "50% 0%" "li#section-1 .section-content-wrapper .section-design-whole" "background-position"
    And I check designer css "cover" "li#section-1 .section-content-wrapper .section-design-whole" "background-size"
    And I check designer css "repeat" "li#section-1 .section-content-wrapper .section-design-whole" "background-repeat"
    And I log out

  Scenario: Show the Activity background options
    Given I log in as "admin"
    And I navigate to "Plugins > Course format > Designer format > General setting" in site administration
    And I click on "Activity" "link"
    And I upload "local/designer/tests/assets/place.png" file to "Mask image" filemanager
    And I press "Save changes"
    And I am on the "Course 1" course page
    And I turn block editing mode on
    When I open "Test page1" actions menu
    And I click on "Edit settings" "link" in the "Test page1" activity
    And I expand all fieldsets
    And I upload "local/designer/tests/assets/background.jpg" file to "Background image" filemanager
    And I set the following fields to these values:
      | Background Position      | Left Center   |
      | Background Size          | Cover         |
      | Background Repeat        | No            |
      | Mask image               | Place         |
      | Mask size                | Cover         |
      | Mask position            | Center Center |
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I check designer css "0% 50%" "li#section-1 .activity-block .activity-background-style" "background-position"
    And I check designer css "cover" "li#section-1 .activity-block .activity-background-style" "background-size"
    And I check designer css "no-repeat" "li#section-1 .activity-block .activity-background-style" "background-repeat"
    And I log out
    And I log in as "admin"
    And I am on the "Course 1" course page
    And I turn block editing mode on
    When I open section "1" edit menu in designer
    And I expand all fieldsets
    And I set the following fields to these values:
    | Apply the background to      | Whole section   |
    And I press "Save changes"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I check designer css "0% 50%" "li#section-1.section-design-whole .activity-block .activity-background-style" "background-position"
    And I check designer css "cover" "li#section-1.section-design-whole .activity-block .activity-background-style" "background-size"
    And I check designer css "repeat" "li#section-1.section-design-whole .activity-block .activity-background-style" "background-repeat"
    And I log out

  Scenario: Course completion is contingent upon the completion of Completion criteria
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Courses available" to "Course 2, Course 3"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I wait until the page is ready
    And I expand all fieldsets
    And I set the following fields to these values:
      | Type                             | Hero                       |
      | Course Progress                  | Progress bar               |
      | Calculation of course progress   | Completion criteria        |
      | Completion status indicator      | Below course progress      |
      | Summary                          | Trimmed                    |
      | Additional Content               | Demo content summary       |
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I should see "0%" in the ".course-progress-blocks .progress-bar.progress-not-started .percentage" "css_element"
    And I should see "Enrolled" in the ".course-progress-blocks .course-indicator-status .status-enrolled" "css_element"
    And I am on the "Test page1" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "25%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "In progress" in the ".course-progress-blocks .course-indicator-status .status-inprogress" "css_element"
    And I am on the "Test page5" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "25%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "In progress" in the ".course-progress-blocks .course-indicator-status .status-inprogress" "css_element"
    And I am on the "Test page4" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "50%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I am on the "Course 2" course page
    And I am on the "Test page2" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "75%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I am on the "Course 3" course page
    And I am on the "Test page3" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "100%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "Completed " in the ".course-progress-blocks .course-indicator-status .status-completed" "css_element"
    And I log out

  Scenario: Course completion is contingent upon the completion of relevant activities
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I wait until the page is ready
    And I expand all fieldsets
    And I set the following fields to these values:
      | Type                             | Hero                       |
      | Course Progress                  | Progress bar               |
      | Calculation of course progress   | Relevant activities        |
      | Completion status indicator      | Below course progress      |
      | Summary                          | Trimmed                    |
      | Additional Content               | Demo content summary       |
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I should see "0%" in the ".course-progress-blocks .progress-bar.progress-not-started .percentage" "css_element"
    And I should see "Enrolled" in the ".course-progress-blocks .course-indicator-status .status-enrolled" "css_element"
    And I am on the "Test page1" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "50%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "In progress" in the ".course-progress-blocks .course-indicator-status .status-inprogress" "css_element"
    And I am on the "Test page5" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "50%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "In progress" in the ".course-progress-blocks .course-indicator-status .status-inprogress" "css_element"
    And I am on the "Test page4" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "100%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "Completed " in the ".course-progress-blocks .course-indicator-status .status-completed" "css_element"
    And I log out

  Scenario: Course completion is contingent upon the completion of all activities
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I wait until the page is ready
    And I expand all fieldsets
    And I set the following fields to these values:
      | Type                             | Hero                       |
      | Course Progress                  | Progress bar               |
      | Calculation of course progress   | All activities             |
      | Completion status indicator      | Below course progress      |
      | Summary                          | Trimmed                    |
      | Additional Content               | Demo content summary       |
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I should see "0%" in the ".course-progress-blocks .progress-bar.progress-not-started .percentage" "css_element"
    And I should see "Enrolled" in the ".course-progress-blocks .course-indicator-status .status-enrolled" "css_element"
    And I am on the "Test page1" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "25%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "In progress" in the ".course-progress-blocks .course-indicator-status .status-inprogress" "css_element"
    And I am on the "Test page4" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "50%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "In progress" in the ".course-progress-blocks .course-indicator-status .status-inprogress" "css_element"
    And I am on the "Test page6" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "75%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "In progress" in the ".course-progress-blocks .course-indicator-status .status-inprogress" "css_element"
    And I am on the "Test page5" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "100%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "Completed " in the ".course-progress-blocks .course-indicator-status .status-completed" "css_element"
    And I log out

  Scenario: Course completion is contingent upon the section's completion
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I wait until the page is ready
    And I expand all fieldsets
    And I set the following fields to these values:
      | Type                             | Content                    |
      | Course Progress                  | Progress bar               |
      | Calculation of course progress   | Sections                   |
      | Section Progress                 | Progress bar               |
      | Calculation of course progress   | All activities             |
      | Calculation of section progress  | Relevant activities        |
      | Completion status indicator      | With course metadata       |
      | Summary                          | Trimmed                    |
      | Additional Content               | Demo content summary       |
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I should see "0%" in the ".section-progress-info .progress-bar.progress-not-started .percentage" "css_element"
    And I should see "Enrolled" in the ".coursefields .course-indicator-status .status-enrolled" "css_element"
    And I am on the "Test page1" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "50%" in the "li#section-1 .section-progress-info .progress-bar .percentage" "css_element"
    And I should see "25%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "In progress" in the ".coursefields .course-indicator-status .status-inprogress" "css_element"
    And I am on the "Test page4" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "Section completed" in the "li#section-1 .section-progress-info .section-progress-completed .badge" "css_element"
    And I should see "50%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "In progress" in the ".coursefields .course-indicator-status .status-inprogress" "css_element"
    And I am on the "Test page5" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "Section completed" in the "li#section-1 .section-progress-info .section-progress-completed .badge" "css_element"
    And I should see "75%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "In progress" in the ".coursefields .course-indicator-status .status-inprogress" "css_element"

    And I am on the "Test page6" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "Section completed" in the "li#section-1 .section-progress-info .section-progress-completed .badge" "css_element"
    And I should see "100%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "Completed " in the ".coursefields .course-indicator-status .status-completed" "css_element"
    And I log out

  Scenario: Course completion is contingent upon the completion of section's relevant activity
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I wait until the page is ready
    And I expand all fieldsets
    And I set the following fields to these values:
      | Type                             | Content                    |
      | Course Progress                  | Progress bar               |
      | Calculation of course progress   | Sections                   |
      | Section Progress                 | Progress bar               |
      | Calculation of section progress  | Relevant activities        |
      | Completion status indicator      | With course metadata       |
      | Summary                          | Trimmed                    |
      | Additional Content               | Demo content summary       |
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I should see "0%" in the "#section-1 .section-progress-info .progress-bar.progress-not-started .percentage" "css_element"
    And I should see "Enrolled" in the ".coursefields .course-indicator-status .status-enrolled" "css_element"
    And I am on the "Test page6" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I press "Done"
    And I wait "3" seconds
    And I am on the "Course 1" course page
    And I should see "0%" in the "#section-1 .section-progress-info .progress-bar.progress-not-started .percentage" "css_element"
    And I should see "Enrolled" in the ".coursefields .course-indicator-status .status-enrolled" "css_element"
    And I am on the "Test page1" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "50%" in the "#section-1 .section-progress-info .progress-bar.progress-bg-success .percentage" "css_element"
    And I should see "Enrolled" in the ".coursefields .course-indicator-status .status-enrolled" "css_element"
    And I am on the "Test page4" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "Section completed" in the "#section-1 .section-progress-info .section-progress-completed .badge" "css_element"
    And I should see "Completed " in the ".coursefields .course-indicator-status .status-completed" "css_element"
    And I log out

  Scenario: Course completion is contingent upon the completion of section's all activity
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Settings" "link" in the ".secondary-navigation" "css_element"
    And I expand all fieldsets
    And I set the field "Format" to "Designer format"
    And I wait until the page is ready
    And I expand all fieldsets
    And I set the following fields to these values:
      | Type                             | Content                    |
      | Course Progress                  | Progress bar               |
      | Calculation of course progress   | Sections                   |
      | Section Progress                 | Progress bar               |
      | Calculation of section progress  | All activities             |
      | Completion status indicator      | With course metadata       |
      | Summary                          | Trimmed                    |
      | Additional Content               | Demo content summary       |
    And I press "Save and display"
    And I log out
    And I am on the "Course 1" course page logged in as student1
    And I should see "0%" in the "#section-1 .section-progress-info .progress-bar.progress-not-started .percentage" "css_element"
    And I should see "0%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "Enrolled" in the ".coursefields .course-indicator-status .status-enrolled" "css_element"
    And I am on the "Test page6" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "33%" in the "#section-1 .section-progress-info .progress-bar.progress-bg-success" "css_element"
    And I should see "0%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "Enrolled" in the ".coursefields .course-indicator-status .status-enrolled" "css_element"
    And I am on the "Test page1" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "67%" in the "#section-1 .section-progress-info .progress-bar.progress-bg-success .percentage" "css_element"
    And I should see "0%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "Enrolled" in the ".coursefields .course-indicator-status .status-enrolled" "css_element"
    And I am on the "Test page4" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "Section completed" in the "#section-1 .section-progress-info .section-progress-completed .badge" "css_element"
    And I should see "0%" in the "#section-0 .section-progress-info .progress-bar.progress-not-started .percentage" "css_element"
    And I should see "50%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "In progress" in the ".coursefields .course-indicator-status .status-inprogress" "css_element"
    And I am on the "Test page5" "page activity" page
    And I press "Mark as done"
    And I wait until "Done" "button" exists
    And I am on the "Course 1" course page
    And I should see "Section completed" in the "#section-0 .section-progress-info .section-progress-completed .badge" "css_element"
    And I should see "100%" in the ".course-progress-blocks .progress-bar .percentage" "css_element"
    And I should see "Completed " in the ".coursefields .course-indicator-status .status-completed" "css_element"
    And I log out
