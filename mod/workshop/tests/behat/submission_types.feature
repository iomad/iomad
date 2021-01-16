@mod @mod_workshop
Feature: Submission types
  In order to Submit the correct type of materials
  As a student
  I want have a clear indication of which fields are accepted and required on the submission form

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Test     | TEST      |
    And I log in as "admin"

  @javascript
  Scenario: Test workshop settings validation
    Given I am on "Test" course homepage with editing mode on
    And I add a "Workshop" to section "0"
    When I set the following fields to these values:
      | Workshop name               | Test workshop |
      | submissiontypetextavailable | 0             |
      | submissiontypefileavailable | 0             |
    And I press "Save and display"
    Then I should see "At least one submission type must be available"
    When I set the following fields to these values:
      | submissiontypetextavailable | 1 |
    Then the "submissiontypetextrequired" "field" should be disabled
    Then the "submissiontypefilerequired" "field" should be disabled
    And the field "submissiontypetextrequired" matches value "1"
    And the field "submissiontypefilerequired" matches value "0"
    When I set the following fields to these values:
      | submissiontypetextavailable | 0 |
      | submissiontypefileavailable | 1 |
    Then the "submissiontypetextrequired" "field" should be disabled
    Then the "submissiontypefilerequired" "field" should be disabled
    And the field "submissiontypetextrequired" matches value "0"
    And the field "submissiontypefilerequired" matches value "1"
    When I set the following fields to these values:
      | submissiontypetextavailable | 1 |
      | submissiontypetextrequired  | 1 |
      | submissiontypefileavailable | 1 |
      | submissiontypefilerequired  | 1 |
    And I press "Save and display"
    Then I should see "Setup phase" in the "h3#mod_workshop-userplanheading" "css_element"
    When I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | submissiontypetextrequired | 0 |
    And I press "Save and display"
    Then I should see "Setup phase" in the "h3#mod_workshop-userplanheading" "css_element"
    When I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | submissiontypetextrequired | 1 |
      | submissiontypefilerequired | 0 |
    And I press "Save and display"
    Then I should see "Setup phase" in the "h3#mod_workshop-userplanheading" "css_element"
    When I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | submissiontypefileavailable | 0 |
    And I press "Save and display"
    Then I should see "Setup phase" in the "h3#mod_workshop-userplanheading" "css_element"
    When I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | submissiontypefileavailable | 1 |
      | submissiontypefilerequired  | 1 |
      | submissiontypetextavailable | 0 |
    And I press "Save and display"
    Then I should see "Setup phase" in the "h3#mod_workshop-userplanheading" "css_element"

  @javascript @_file_upload
  Scenario: All submission fields required
    Given the following "activities" exist:
      | activity | name         | intro                     | course | idnumber  | submissiontypetext | submissiontypefile |
      | workshop | All required | Test workshop description | TEST   | workshop1 | 2                  | 2                  |
    And I am on "Test" course homepage
    And I follow "All required"
    And I follow "Switch to the submission phase"
    And I press "Continue"
    And I press "Add submission"
    And I set the field "Title" to "Test submission"
    When I press "Save changes"
    Then I should see "You must supply a value here." in the "Submission content" "form_row"
    And I set the field "Submission content" to "Lorem ipsum dolor"
    And I press "Save changes"
    And I should see "You must supply a value here." in the "Attachment" "form_row"
    And I set the following fields to these values:
      | Attachment         | mod/workshop/tests/fixtures/moodlelogo.png |
    And I press "Save changes"
    And I should not see "You must supply a value here."
    And I should see "My submission"
    And "Edit submission" "button" should exist

  Scenario: Online text required, file attachment optional
    Given the following "activities" exist:
      | activity | name          | intro                     | course | idnumber  | submissiontypetext | submissiontypefile |
      | workshop | Optional file | Test workshop description | TEST   | workshop1 | 2                  | 1                  |
    And I am on "Test" course homepage
    And I follow "Optional file"
    And I follow "Switch to the submission phase"
    And I press "Continue"
    And I press "Add submission"
    And I set the field "Title" to "Test submission"
    When I press "Save changes"
    Then I should see "You must supply a value here." in the "Submission content" "form_row"
    And I set the following fields to these values:
      | Submission content | Lorem ipsum dolor                          |
    And I press "Save changes"
    And I should not see "You must supply a value here."
    And I should see "My submission"
    And "Edit submission" "button" should exist

  @javascript @_file_upload
  Scenario: Online text optional, file attachment required
    Given the following "activities" exist:
      | activity | name          | intro                     | course | idnumber  | submissiontypetext | submissiontypefile |
      | workshop | Optional text | Test workshop description | TEST   | workshop1 | 1                  | 2                  |
    And I am on "Test" course homepage
    And I follow "Optional text"
    And I follow "Switch to the submission phase"
    And I press "Continue"
    And I press "Add submission"
    And I set the field "Title" to "Test submission"
    When I press "Save changes"
    Then I should see "You must supply a value here." in the "Attachment" "form_row"
    And I set the following fields to these values:
      | Attachment         | mod/workshop/tests/fixtures/moodlelogo.png |
    And I press "Save changes"
    And I should not see "You must supply a value here."
    And I should see "My submission"
    And "Edit submission" "button" should exist

  Scenario: Online text only
    Given the following "activities" exist:
      | activity | name      | intro                     | course | idnumber  | submissiontypetext | submissiontypefile |
      | workshop | Only text | Test workshop description | TEST   | workshop1 | 2                  | 0                  |
    And I am on "Test" course homepage
    And I follow "Only text"
    And I follow "Switch to the submission phase"
    And I press "Continue"
    When I press "Add submission"
    Then "Attachment" "field" should not exist
    And I set the field "Title" to "Test submission"
    And I press "Save changes"
    And I should see "You must supply a value here." in the "Submission content" "form_row"
    And I set the following fields to these values:
      | Submission content | Lorem ipsum dolor                          |
    And I press "Save changes"
    And I should not see "You must supply a value here."
    And I should see "My submission"
    And "Edit submission" "button" should exist

  @javascript @_file_upload
  Scenario: File attachment only
    Given the following "activities" exist:
      | activity | name      | intro                     | course | idnumber  | submissiontypetext | submissiontypefile |
      | workshop | Only file | Test workshop description | TEST   | workshop1 | 0                  | 2                  |
    And I am on "Test" course homepage
    And I follow "Only file"
    And I follow "Switch to the submission phase"
    And I press "Continue"
    When I press "Add submission"
    Then "Submission content" "field" should not exist
    And I set the field "Title" to "Test submission"
    And I press "Save changes"
    And I should see "You must supply a value here." in the "Attachment" "form_row"
    And "Submission content" "form_row" should not exist
    And I set the following fields to these values:
      | Attachment         | mod/workshop/tests/fixtures/moodlelogo.png |
    And I press "Save changes"
    And I should not see "You must supply a value here."
    And I should see "My submission"
    And "Edit submission" "button" should exist

  @javascript @_file_upload
  Scenario: Neither submission type explicitly required
    Given the following "activities" exist:
      | activity | name             | intro                     | course | idnumber  |
      | workshop | Neither required | Test workshop description | TEST   | workshop1 |
    And I am on "Test" course homepage
    And I follow "Neither required"
    And I follow "Switch to the submission phase"
    And I press "Continue"
    And I press "Add submission"
    And I set the field "Title" to "Test submission"
    When I press "Save changes"
    Then I should see "You need to add a file or enter some text." in the "Attachment" "form_row"
    And I should see "You need to enter some text or add a file." in the "Submission content" "form_row"
    And I set the following fields to these values:
      | Submission content | Lorem ipsum dolor                          |
    And I press "Save changes"
    And I should not see "You need to add a file or enter some text."
    And I should not see "You need to enter some text or add a file."
    And I should see "My submission"
    And "Edit submission" "button" should exist
    And I press "Edit submission"
    And I set the following fields to these values:
      | Submission content |                                            |
      | Attachment         | mod/workshop/tests/fixtures/moodlelogo.png |
    And I press "Save changes"
    And I should not see "You need to add a file or enter some text."
    And I should not see "You need to enter some text or add a file."
