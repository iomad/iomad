@qtype @qtype_multianswer
Feature: Test creating a Multianswer (Cloze) question
  As a teacher
  In order to test my students
  I need to be able to create a Cloze question

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration

  Scenario: Create a Cloze question
    When I add a "Embedded answers (Cloze)" question filling the form with:
      | Question name        | multianswer-001                                     |
      | Question text        | {1:SHORTANSWER:=Berlin} is the capital of Germany.  |
      | General feedback     | The capital of Germany is Berlin.                   |
    Then I should see "multianswer-001" in the "categoryquestions" "table"

  Scenario: Create a broken Cloze question and correct it
    Given I press "Create a new question ..."
    And I set the field "Embedded answers (Cloze)" to "1"
    And I press "Add"
    And I set the field "Question name" to "multianswer-002"
    And I set the field "Question text" to "Please select the fruits {1:MULTICHOICE:=Apple#Correct}"
    And I set the field "General feedback" to "Apple are delicious."
    When I press "id_submitbutton"
    Then I should see "This type of question requires at least 2 choices"
    And I set the following fields to these values:
      | Question text | Please select the fruits {1:MULTICHOICE:=Apple#Correct~Banana#Wrong} |
    And I press "id_submitbutton"
    And I should see "multianswer-002" in the "categoryquestions" "table"

  Scenario: Try to create a Cloze question that has no answer
    Given I press "Create a new question ..."
    And I set the field "Embedded answers (Cloze)" to "1"
    And I press "Add"
    And I set the following fields to these values:
      | Question name | multianswer-003                                |
      | Question text | {1:SHORTANSWER:=  } is the capital of Germany. |
    And I press "id_submitbutton"
    And I should see "This type of question requires at least 1 answers"
