@mod @mod_quiz
Feature: Edit quiz page - remove questions
  In order to change the layout of a quiz I built
  As a teacher
  I need to be able to delete questions.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | T1        | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "activities" exist:
      | activity   | name   | course | idnumber |
      | quiz       | Quiz 1 | C1     | quiz1    |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"

  @javascript
  Scenario: Delete questions by clicking on the delete icon.
    Given the following "questions" exist:
      | questioncategory | qtype     | name       | questiontext        |
      | Test questions   | truefalse | Question A | This is question 01 |
      | Test questions   | truefalse | Question B | This is question 02 |
      | Test questions   | truefalse | Question C | This is question 03 |
    And quiz "Quiz 1" contains the following questions:
      | question   | page |
      | Question A | 1    |
      | Question B | 1    |
      | Question C | 2    |
    And I navigate to "Edit quiz" in current page administration

    # Confirm the starting point.
    Then I should see "Question A" on quiz page "1"
    And I should see "Question B" on quiz page "1"
    And I should see "Question C" on quiz page "2"
    And I should see "Total of marks: 3.00"
    And I should see "Questions: 3"
    And I should see "This quiz is open"

    # Delete last question in last page. Page contains multiple questions
    When I delete "Question C" in the quiz by clicking the delete icon
    Then I should see "Question A" on quiz page "1"
    And I should see "Question B" on quiz page "1"
    And I should not see "Question C" on quiz page "2"
    And I should see "Total of marks: 2.00"
    And I should see "Questions: 2"

    # Delete last question in last page. The page contains multiple questions and there are multiple pages.
    When I click on the "Add" page break icon after question "Question A"
    Then I should see "Question B" on quiz page "2"
    And the "Remove" page break icon after question "Question A" should exist
    And I delete "Question A" in the quiz by clicking the delete icon
    Then I should see "Question B" on quiz page "1"
    And I should not see "Page 2"
    And I should not see "Question A" on quiz page "2"
    And the "Remove" page break icon after question "Question B" should not exist
    And I should see "Total of marks: 1.00"

  @javascript
  Scenario: Cannot delete the last question in a section.
    Given the following "questions" exist:
      | questioncategory | qtype     | name       | questiontext        |
      | Test questions   | truefalse | Question A | This is question 01 |
      | Test questions   | truefalse | Question B | This is question 02 |
      | Test questions   | truefalse | Question C | This is question 03 |
    And quiz "Quiz 1" contains the following questions:
      | question   | page |
      | Question A | 1    |
      | Question B | 1    |
      | Question C | 2    |
    And quiz "Quiz 1" contains the following sections:
      | heading   | firstslot | shuffle |
      | Heading 1 | 1         | 1       |
      | Heading 2 | 2         | 1       |
    When I navigate to "Edit quiz" in current page administration
    Then "Delete" "link" in the "Question A" "list_item" should not be visible
    Then "Delete" "link" in the "Question B" "list_item" should be visible
    Then "Delete" "link" in the "Question C" "list_item" should be visible

  @javascript
  Scenario: Can delete the last question in a quiz.
    Given the following "questions" exist:
      | questioncategory | qtype     | name       | questiontext        |
      | Test questions   | truefalse | Question A | This is question 01 |
    And quiz "Quiz 1" contains the following questions:
      | question   | page |
      | Question A | 1    |
    When I navigate to "Edit quiz" in current page administration
    And I delete "Question A" in the quiz by clicking the delete icon
    Then I should see "Questions: 0"
