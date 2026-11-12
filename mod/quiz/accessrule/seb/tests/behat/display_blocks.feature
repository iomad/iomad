@mod @mod_quiz @quizaccess @quizaccess_seb
Feature: Display blocks in Safe Exam Browser quizzes
  In order to control the secure quiz environment
  As an administrator
  I need to configure whether blocks are shown before and after SEB quiz attempts

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | course | name   | idnumber | seb_requiresafeexambrowser |
      | quiz     | C1     | Quiz 1 | quiz1    | 1                          |
    And the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | quiz1     | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext   |
      | Test questions   | truefalse | TF1  | True or false? |
    And quiz "Quiz 1" contains the following questions:
      | question | page |
      | TF1      | 1    |

  Scenario Outline: Blocks are shown or hidden before the quiz attempt starts
    Given the following "blocks" exist:
      | blockname    | contextlevel    | reference | pagetypepattern | defaultregion |
      | online_users | Activity module | quiz1     | mod-quiz-view   | side-pre      |
    And the following config values are set as admin:
      | displayblocksbeforestart | <showblocks> | quizaccess_seb |
    When I am on the "Quiz 1" "quiz activity" page logged in as "student1"
    Then "Online users" "block" <visibility> exist

    Examples:
      | showblocks | visibility       |
      | 1          | should           |
      | 0          | should not       |

  Scenario Outline: Blocks are shown or hidden after the quiz attempt finishes
    Given the following "blocks" exist:
      | blockname    | contextlevel    | reference | pagetypepattern | defaultregion |
      | online_users | Activity module | quiz1     | mod-quiz-*      | side-pre      |
    And the following config values are set as admin:
      | displayblockswhenfinished | <showblocks> | quizaccess_seb |
    And user "student1" has attempted "Quiz 1" with responses:
      | slot | response |
      | 1    | True     |
    When I am on the "Quiz 1" "quiz activity" page logged in as "student1"
    Then "Online users" "block" <visibility> exist

    Examples:
      | showblocks | visibility       |
      | 1          | should           |
      | 0          | should not       |
