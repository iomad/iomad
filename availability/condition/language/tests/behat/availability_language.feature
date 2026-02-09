@ewallah @availability @availability_language
Feature: availability_language modules
  In order to control student access to activities
  As a admin
  I need to add language
  As a teacher
  I need to set language conditions which prevent student access

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name | intro   | course | section |
      | page     | P1   | Test l  | C1     | 1       |
      | page     | P2   | Test 2  | C1     | 1       |
      | page     | P3   | Test 3  | C1     | 1       |
      | page     | P4   | Test 4  | C1     | 1       |
      | page     | P5   | Test 5  | C1     | 0       |
      | page     | P6   | Test 6  | C1     | 0       |
    And I log in as "admin"
    And I navigate to "Language > Language packs" in site administration
    And I set the field "Available language packs" to "en_ar"
    And I press "Install selected language pack(s)"
    And I log out

  @javascript
  Scenario: Restriction based on language when two language packs are installed
    # Page P1 for English users only.
    Given I am on the "P1" "page activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Language" "button" should exist in the "Add restriction..." "dialogue"
    And I click on "Language" "button" in the "Add restriction..." "dialogue"
    Then I should see "Please set" in the "region-main" "region"
    And I set the field "Language" to "en"
    Then I should not see "Please set" in the "region-main" "region"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I click on "Save and return to course" "button"

    # Page P2 for pirate English users only.
    When I am on the "P2" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Language" "button"
    And I set the field "Language" to "en_ar"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I click on "Save and return to course" "button"

    # Page P3 for English users hidden.
    When I am on the "P3" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Language" "button"
    And I set the field "Language" to "en"
    And I click on "Save and return to course" "button"

    # Page P4 for pirate English users hidden.
    When I am on the "P4" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Language" "button"
    And I set the field "Language" to "en_ar"
    And I click on "Save and return to course" "button"

    # Page P5 for pirate English users hidden in section 0.
    When I am on the "P5" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Language" "button"
    And I set the field "Language" to "en"
    And I click on "Save and return to course" "button"

    # Page P6 for pirate English users hidden in section 0.
    When I am on the "P6" "page activity editing" page
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Language" "button"
    And I set the field "Language" to "en_ar"
    And I click on "Save and return to course" "button"
    And I log out

    # Log in as student.
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "P1" in the "region-main" "region"
    And I should see "P2" in the "region-main" "region"
    And I should see "P3" in the "region-main" "region"
    And I should not see "P4" in the "region-main" "region"
    And I should see "Not available unless: The student" in the ".availabilityinfo" "css_element"
    And I should see "pirate" in the ".availabilityinfo" "css_element"
    And I should see "P5" in the "region-main" "region"
    And I should not see "P6" in the "region-main" "region"
    When I follow "Preferences" in the user menu
    And I follow "Preferred language"
    And I set the field "lang" to "en_ar"
    And I click on "Save changes" "button"
    And I am on "Course 1" course homepage
    Then I should see "P1" in the "region-main" "region"
    And I should see "P2" in the "region-main" "region"
    And I should not see "P3" in the "region-main" "region"
    And I should see "P4" in the "region-main" "region"
    And I should see "Not available unless: The student's language is English ‎(en)‎" in the ".availabilityinfo" "css_element"
    And I should not see "P5" in the "region-main" "region"
    And I should see "P6" in the "region-main" "region"

  @javascript
  Scenario: Restrict activity in section0
    When I am on the "P5" "page activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Language" "button" should exist in the "Add restriction..." "dialogue"
    And I click on "Language" "button" in the "Add restriction..." "dialogue"
    Then I should see "Please set" in the "region-main" "region"
    And I set the field "Language" to "en_ar"
    Then I should not see "Please set" in the "region-main" "region"
    And I click on "Save and return to course" "button"
    And I log out

    # Log in as student.
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should not see "P5" in the "region-main" "region"
    And I follow "Preferences" in the user menu
    And I follow "Preferred language"
    And I set the field "lang" to "en_ar"
    And I click on "Save changes" "button"
    And I am on "Course 1" course homepage
    Then I should see "P5" in the "region-main" "region"

  @javascript
  Scenario: Restrict activity in section0 hidden
    When I am on the "P5" "page activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Language" "button" should exist in the "Add restriction..." "dialogue"
    And I click on "Language" "button" in the "Add restriction..." "dialogue"
    Then I should see "Please set" in the "region-main" "region"
    And I set the field "Language" to "en_ar"
    Then I should not see "Please set" in the "region-main" "region"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I click on "Save and return to course" "button"
    And I log out

    # Log in as student.
    When I am on the "C1" "Course" page logged in as "student1"
    Then I should see "P5" in the "region-main" "region"
    And I follow "Preferences" in the user menu
    And I follow "Preferred language"
    And I set the field "lang" to "en_ar"
    And I click on "Save changes" "button"
    And I am on "Course 1" course homepage
    Then I should see "P5" in the "region-main" "region"
