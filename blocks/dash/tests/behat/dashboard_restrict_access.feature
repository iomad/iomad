@block @block_dash @dashboard_restrict_access @javascript @_file_upload
Feature: Dash block restrictions:restrict by course group
  In order to check the dash featuers
  I can add the dash block to the dashboard
  As an admin

  Background:
    Given the following "categories" exist:
      | name       | category | idnumber |
      | Category 1 | 0        | CAT1     |
      | Category 2 | 0        | CAT2     |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | CAT1     | 1                |
      | Course 2 | C2        | CAT1     | 1                |
      | Course 3 | C3        | CAT2     | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | First    | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
      | student3 | Student   | Three    | student3@example.com |
      | teacher1 | Teacher   | First    | teacher1@example.com |
      | teacher2 | Teacher   | Second   | teacher2@example.com |
      | teacher3 | Teacher   | Third    | teacher3@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C2     | student        |
      | student2 | C1     | student        |
      | student2 | C2     | student        |
      | student3 | C3     | student        |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
      | teacher2 | C2     | editingteacher |
      | teacher3 | C1     | teacher        |
      | teacher3 | C1     | student        |
    And the following "cohorts" exist:
      | name     | idnumber | contextlevel | reference |
      | Cohort 1 | CH1      | system       | CAT1      |
      | Cohort 2 | CH2      | system       | CAT1      |
      | Cohort 3 | CH3      | system       | CAT2      |
    And the following "cohort members" exist:
      | user     | cohort |
      | student1 | CH1    |
      | student2 | CH1    |
      | student1 | CH2    |
      | teacher3 | CH1    |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C3        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name             | questiontext                         | answer 1 | grade |
      | Test questions   | shortanswer | Short answer 001 | Where is the capital city of France? | Paris    | 100%  |
    And the following "activities" exist:
      | activity | course | idnumber | section | name            | grade | gradepass | completion | completionsubmit | assignsubmission_onlinetext_enabled |
      | choice   | C1     | choice1  | 1       | Test choice 1   |       |           | 1          |                  |   |
      | choice   | C2     | choice2  | 2       | Test choice 1   |       |           | 2          | 1                |   |
      | choice   | C2     | choice3  | 2       | Test choice 2   |       |           | 2          | 1                |   |
      | quiz     | C3     | quiz1    | 1       | SA              | 20    |   10      |  1         |                  |   |
    And quiz "SA" contains the following questions:
      | question         | page | displaynumber |
      | Short answer 001 | 1    | 1             |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 1 | C2     | G2       |
      | Group 2 | C2     | G3       |
      | Group 3 | C2     | G4       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student1 | G2    |
      | student1 | G3    |
      | student2 | G1    |
      | teacher1 | G1    |
      | teacher1 | G2    |
    #---Enroll cohort users to course 1---#
    And I am on the "Course 1" "Course" page logged in as "admin"
    And I follow "Participants"
    And I press "Enrol users"
    When I set the field "Select cohorts" to "Cohort 1"
    And I click on "Enrol selected users and cohorts" "button" in the "Enrol users" "dialogue"
    #---Set course completion to course 1---#
    And I am on "Course 1" course homepage
    And I wait "5" seconds
    And I navigate to "Course completion" in current page administration
    And I set the following fields to these values:
    | Test choice 1 | 1 |
    And I click on "Save changes" "button"
    #---Set course completion to course 2---#
    And I am on "Course 2" course homepage
    And I wait "5" seconds
    And I navigate to "Course completion" in current page administration
    And I set the following fields to these values:
    | Test choice 1 | 1 |
    | Test choice 2 | 1 |
    And I click on "Save changes" "button"

  Scenario: Dash block restrictions:restrict by cohort
    When I log in as "admin"
    And I navigate to "Appearance > Default Dashboard page" in site administration
    And I turn dash block editing mode on
    And I create dash "Users" datasource
    Then I configure the "New Dash" block
    And I set the following fields to these values:
        | Block title        | Users    |
        | Restrict by cohort | Cohort 1 |
        | Region             | content  |
    And I press "Save changes"
    And I click on "Reset Dashboard for all users" "button"
    And I press "Continue"
    And I log out
    #---Student Login---#
    And I log in as "student1"
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out
    And I log in as "student3"
    And ".block_dash" "css_element" should not exist
    And I log out

  Scenario: Dash block restrictions:restrict by parent role
    And I log in as "admin"
    And I navigate to "Users > Permissions > Define roles" in site administration
    And I click on "Add a new role" "button"
    And I click on "Continue" "button"
    #---Create new parent role---#
    And I set the following fields to these values:
      | Short name                              | Parent |
      | Custom full name                        | Parent |
      | contextlevel30                          | 1      |
      | moodle/user:viewdetails                 | 1      |
      | moodle/user:viewalldetails              | 1      |
      | moodle/user:readuserblogs               | 1      |
      | moodle/user:readuserposts               | 1      |
      | moodle/user:viewuseractivitiesreport    | 1      |
      | moodle/user:editprofile                 | 1      |
      | tool/policy:acceptbehalf                | 1      |
    And I click on "Create this role" "button"
    And I follow "Dashboard"
    #---Assign parent to child---#
    And I am on the "teacher3" "user > profile" page
    And I click on "Preferences" "link" in the ".profile_tree" "css_element"
    And I follow "Assign roles relative to this user"
    And I follow "Parent"
    And I set the field "addselect" to "Student First (student1@example.com)"
    And I click on "Add" "button" in the "#page-content" "css_element"
    #---Redirect to default dashboard page---#
    And I turn dash block editing mode on
    And I create dash "Users" datasource
    Then I configure the "New Dash" block
    And I set the following fields to these values:
        | Block title      | Users   |
        | Restrict by role | Parent  |
        | Region           | content |
    And I press "Save changes"
    And I click on "Reset Dashboard for all users" "button"
    And I press "Continue"
    And I log out
    #---Parent role - student Login---#
    And I log in as "student1"
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out
    #---Child role - teacher Login---#
    And I log in as "teacher3"
    And ".block_dash" "css_element" should not exist
    And I log out

  Scenario Outline: Dash block restrictions:restrict by role
    #---Course page---#
    And I am on the "Course 2" "Course" page logged in as "admin"
    #---Dash content:full layout added---#
    And I turn editing mode on
    And I add the "Dash" block
    And I click on "Users" "radio"
    Then I configure the "New Dash" block
    And I set the following fields to these values:
        | Block title      | Users     |
        | Restrict by role | <role>    |
        | Context          | <context> |
    And I press "Save changes"
    And I log out
    #---Course users Login---#
    When I am on the "Course 2" "course" page logged in as "<user>"
    And ".block_dash" "css_element" should <exists>
    And I log out

    Examples:
      | context | user     | role    | exists    |
      | 1       | student1 | Student | exist     |
      | 2       | student1 | Student | not exist |
      | 1       | teacher1 | Teacher | exist     |
      | 2       | teacher1 | Teacher | exist     |

  Scenario: Dash block restrictions:restrict by course group
    When I am on the "Course 2" "course" page logged in as "admin"
    #---Dash content:full layout added---#
    And I turn editing mode on
    And I add the "Dash" block
    And I click on "Users" "radio"
    Then I configure the "New Dash" block
    And I set the following fields to these values:
        | Block title              | Users   |
        | Restrict by course group | Group 1 |
    And I press "Save changes"
    And I log out
    #---Student login---#
    When I am on the "Course 2" "course" page logged in as "student1"
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out
    #---Teacher login---#
    When I am on the "Course 2" "course" page logged in as "student3"
    And ".block_dash" "css_element" should not exist
    And I log out

  Scenario Outline: Dash block restrictions:restrict by course grade range
    When I am on the "Course 3" "course" page logged in as "admin"
    And I turn dash block editing mode on
    #---Course 3 page user block added---#
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    #---Enable course grade restriction---#
    And I set the field "Block title" to "Users"
    And I set the field "Restrict by course grade" to "<restrictby>"
    And I set the field with xpath "//div[@class='modal-content']//form[@class='mform']//fieldset[starts-with(@id,'id_restrictaccessheading_')]//div[starts-with(@id,'fitem_id_config_restrict_grademin_')]//input[@name='config_restrict_grademin']" to "20"
    And I press "Save changes"
    And I log out
    #---Student3 login---#
    Then I log in as "student3"
    And I am on the "C3" "course" page

    #---Short answer attempt---#
    And I click on "SA" "link"
    And I press "Attempt quiz"
    And I set the field "Answer" to "toad"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I click on "Finish review" "link"
    And I should see "16.00 out of 20.00"
    #---Course grade restrict role---#
    And I am on "Course 3" course homepage
    And ".block_dash" "css_element" should <exist>
    And I log out

    Examples:
      | restrictby | exist     |
      | lowerthan  | exist     |
      | higherthan | not exist |

  Scenario Outline: Dash block restrictions:restrict by course grade-between
    When I am on the "Course 3" "course" page logged in as "admin"
    And I turn dash block editing mode on
    #---Course 3 page user block added---#
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    #---Enable course grade restriction---#
    And I set the field "Block title" to "Users"
    And I set the field "Restrict by course grade" to "between"
    And I set the field with xpath "//div[@class='modal-content']//form[@class='mform']//fieldset[starts-with(@id,'id_restrictaccessheading_')]//div[starts-with(@id,'fitem_id_config_restrict_grademin_')]//input[@name='config_restrict_grademin']" to "<grademin>"
    And I set the field with xpath "//div[@class='modal-content']//form[@class='mform']//fieldset[starts-with(@id,'id_restrictaccessheading_')]//div[starts-with(@id,'fitem_id_config_restrict_grademax_')]//input[@name='config_restrict_grademax']" to "<grademax>"
    And I press "Save changes"
    And I log out
    #---Student3 login---#
    Then I log in as "student3"
    And I am on the "C3" "course" page
    #---Short answer attempt---#
    And I click on "SA" "link"
    And I press "Attempt quiz"
    And I set the field "Answer" to "toad"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I click on "Finish review" "link"
    And I should see "16.00 out of 20.00"
    #---Course grade restrict role---#
    And I am on "Course 3" course homepage
    And ".block_dash" "css_element" should <exist>
    And I log out

    Examples:
      | grademin | grademax | exist     |
      | 14       | 20       | exist     |
      | 10       | 15       | not exist |

  Scenario: Dash block restrictions:restrict by course completion status: completed, in-progress
    #---Admin login---#
    And I am on the "Course 1" "Course" page logged in as "admin"
    And I turn dash block editing mode on
    #---Course 1 page user block added---#
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    #---Enable course completion restriction---#
    And I set the following fields to these values:
      | Block title                          | Users     |
      | Restrict by course completion status | Completed |
    And I press "Save changes"
    And I am on "Course 2" course homepage
    And I turn dash block editing mode on
    #---Course 2 page course block added---#
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    #---Enable course completion restriction---#
    And I set the following fields to these values:
      | Block title                          | Users       |
      | Restrict by course completion status | In progress |
    And I press "Save changes"
    And I log out
    #---Student 1 login course completed role---#
    And I am on the "Course 1" "Course" page logged in as "student1"
    And I click on "Mark as done" "button"
    And I reload the page
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out
    #---Student 2 login course in-progress role---#
    And I am on the "Course 2" "Course" page logged in as "student2"
    And I click on "Test choice 1" "link"
    And I click on "#choice_2" "css_element"
    And I press "Save my choice"
    And I am on "Course 2" course homepage
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out

  Scenario: Dash block restrictions:restrict by course completion status: enrolled, not-enrolled
    #---Admin login---#
    And I am on the "Course 1" "Course" page logged in as "admin"
    And I turn dash block editing mode on
    #---Course 1 page course block added---#
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    #---Enable course completion restriction---#
    And I set the following fields to these values:
      | Block title                          | Users    |
      | Restrict by course completion status | Enrolled |
    And I press "Save changes"
    And I am on "Course 2" course homepage
    And I turn dash block editing mode on
    #---Course 2 page course block added---#
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    #---Enable course completion restriction---#
    And I set the following fields to these values:
      | Block title                          | Users        |
      | Restrict by course completion status | Not enrolled |
    And I press "Save changes"
    And I log out
    #---Student:teacher 3 login Course enrolled role---#
    And I am on the "Course 1" "Course" page logged in as "teacher3"
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out
    #---Student 3 login course not enrolled role---#
    And I am on the "Course 2" "Course" page logged in as "student3"
    And I should see "You cannot enrol yourself in this course."
    And I log out

  Scenario: Dash block restrictions:restrict by activity completion status course page
  #---restrict role by incomplete---#
    And I am on the "Course 3" "course" page logged in as "admin"
    And I turn dash block editing mode on
    #---Course 3 page course block added---#
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    #---Enable course completion restriction---#
    And I set the following fields to these values:
      | Block title                        | Users      |
      | config_restrict_activitycompletion | incomplete |
    And I expand the "Select activity" autocomplete
    And I click on "SA" item in the autocomplete list
    And I press "Save changes"
    #---student login---#
    And I am on the "Course 3" "course" page logged in as "student3"
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out
  #---restrict role by complete---#
    #---Admin log in---#
    And I am on the "Course 3" "course" page logged in as "admin"
    And I turn dash block editing mode on
    #---Course 3 page course block added---#
    And I configure the "Users" block
    #---Enable course completion restriction---#
    And I set the following fields to these values:
      | config_restrict_activitycompletion | complete |
    And I press "Save changes"
    #---student login---#
    And I am on the "Course 3" "course" page logged in as "student3"
    And I click on "Mark as done" "button"
    And I reload the page
    And I should see "Users" in the ".block_dash" "css_element"

  Scenario: Dash block restrictions:restrict by activity completion complete & incomplete role
  #---Complete & Incomplete role---#
    And I am on the "Course 1" "course" page logged in as "admin"
    And I click on "Test choice 1" "link" in the "region-main" "region"
    And I turn dash block editing mode on
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    #---Enable course completion restriction---#
    And I set the following fields to these values:
     | Block title                            | Users    |
     | Restrict by activity completion status | complete |
    And I press "Save changes"
    And I log out
    #---Student 1 login course completed role---#
    And I am on the "Course 1" "Course" page logged in as "student1"
    And I click on "Mark as done" "button"
    And I click on "Test choice 1" "link" in the "region-main" "region"
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out
    #---Admin log in---#
    And I am on the "Course 1" "course" page logged in as "admin"
    And I click on "Test choice 1" "link" in the "region-main" "region"
    And I turn dash block editing mode on
    And I configure the "Users" block
    #---Enable course completion restriction---#
    And I set the field "Restrict by activity completion status" to "incomplete"
    And I press "Save changes"
    And I log out
    #---Student 1 login course incompleted role---#
    And I am on the "Course 1" "Course" page logged in as "student2"
    And I click on "Test choice 1" "link" in the "region-main" "region"
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out

  Scenario: Dash block restrictions:restrict by activity completion pass & fail role
  #---Pass & Fail role---#
    And I am on the "Course 3" "course" page logged in as "admin"
    And I click on "SA" "link" in the "region-main" "region"
    And I turn dash block editing mode on
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    #---Enable course completion restriction---#
    And I set the following fields to these values:
     | Block title                            | Users  |
     | Restrict by activity completion status | failed |
    And I press "Save changes"
    #---Enable passing grade---#
    And I am on the "SA" "quiz activity editing" page
    And I expand all fieldsets
    And I set the field "Add requirements" to "1"
    And I set the field "completionusegrade" to "1"
    And I press "Save and return to course"
    And I log out
    #---Student 3 login course incompleted role---#
    And I am on the "Course 3" "Course" page logged in as "student3"
    And I click on "SA" "link" in the "region-main" "region"
    And I press "Attempt quiz"
    And I set the field "Answer" to "snail"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I click on "Finish review" "link"
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out
    #---Admin login---#
    And I am on the "Course 3" "course" page logged in as "admin"
    And I click on "SA" "link" in the "region-main" "region"
    And I turn dash block editing mode on
    And I configure the "Users" block
    #---Enable course completion restriction---#
    And I set the field "Restrict by activity completion status" to "passed"
    And I press "Save changes"
    And I log out
    #---Student 3 login course incompleted role---#
    And I am on the "Course 3" "Course" page logged in as "student3"
    And I click on "SA" "link" in the "region-main" "region"
    And I press "Re-attempt quiz"
    And I set the field "Answer" to "frog"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    And I click on "Finish review" "link"
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out

  Scenario: Dash block restrictions:Operator
  #---Restrict operator ALL---#
    And I am on the "Course 1" "course" page logged in as "admin"
    And I turn dash block editing mode on
    #---Course 1 page course block added---#
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    #---Enable course completion restriction---#
    And I set the following fields to these values:
      | Block title                          | Users     |
      | Operator                             | 2         |
      | Restrict by cohort                   | Cohort 1  |
      | Restrict by course group             | Group 1   |
      | Restrict by course completion status | Completed |
    And I press "Save changes"
    And I log out
    #---Student 2 login course completed role---#
    And I am on the "Course 1" "Course" page logged in as "student2"
    And I click on "Mark as done" "button"
    And I reload the page
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out
  #---Restrict operator ANY---#
    And I am on the "Course 1" "course" page logged in as "admin"
    And I turn dash block editing mode on
    #---Course 1 page course block added---#
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    #---Enable course completion restriction---#
    And I set the following fields to these values:
      | Block title                          | Users     |
      | Operator                             | 1         |
      | Restrict by cohort                   | Cohort 1  |
      | Restrict by course group             | Group 1   |
      | Restrict by course completion status | Completed |
    And I press "Save changes"
    And I log out
    #---Student 1 login course completed role---#
    And I am on the "Course 1" "Course" page logged in as "student1"
    And I click on "Mark as done" "button"
    And I reload the page
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out

  Scenario: Empty state implementation
    When I am on the "Course 1" "Course" page logged in as "admin"
    And I turn dash block editing mode on
    And I add the "Dash" block
    And I click on "Users" "radio"
    And I configure the "New Dash" block
    #---Enable hide when empty and content added---#
    And I set the following fields to these values:
        | Block title        | Users   |
        | Hide when empty    | 0       |
        | Content            | Welcome |
        | Restrict by role   | Teacher |
    And I press "Save changes"
    And I log out
    #---Student Login---#
    When I am on the "Course 1" "Course" page logged in as "student1"
    And ".block_dash" "css_element" should exist
    And I should see "Welcome" in the ".block_dash .card-body .card-text" "css_element"
    And I log out
    #---Teacher Login---#
    When I am on the "Course 1" "Course" page logged in as "teacher1"
    And ".block_dash" "css_element" should exist
    And I should not see "Welcome" in the ".block_dash .card-body .card-text" "css_element"
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out
    #---Admin Login---#
    When I am on the "Course 1" "Course" page logged in as "admin"
    And I turn dash block editing mode on
    And I configure the "Users" block
    #---Enable hide when empty---#
    And I set the following fields to these values:
      | Hide when empty | 1 |
    And I press "Save changes"
    And I log out
    #---Student Login---#
    When I am on the "Course 1" "Course" page logged in as "student1"
    And ".block_dash" "css_element" should not exist
    And I log out
    #---Teacher Login---#
    When I am on the "Course 1" "Course" page logged in as "teacher1"
    And ".block_dash" "css_element" should exist
    And I should not see "Welcome" in the ".block_dash .card-body .card-text" "css_element"
    And I should see "Users" in the ".block_dash" "css_element"
    And I log out
