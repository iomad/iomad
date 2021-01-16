@core @core_completion
Feature: Allow teachers to manually mark users as complete when configured
  In order for teachers to mark students as complete
  As a teacher
  I need to be able to use the completion report mark complete functionality

  Scenario: Mark a student as complete using the completion report
    Given the following "courses" exist:
      | fullname          | shortname | category |
      | Completion course | CC1       | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | First    | student1@example.com |
      | teacher1 | Teacher   | First    | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | CC1    | student        |
      | teacher1 | CC1    | editingteacher |
    And I log in as "admin"
    And I am on "Completion course" course homepage
    And completion tracking is "Enabled" in current course
    And I follow "Course completion"
    And I set the field "Teacher" to "1"
    And I press "Save changes"
    And I turn editing mode on
    And I add the "Course completion status" block
    And I log out
    And I log in as "student1"
    And I am on "Completion course" course homepage
    And I should see "Status: Not yet started"
    And I log out
    When I log in as "teacher1"
    And I am on "Completion course" course homepage
    And I follow "View course report"
    And I should see "Student First"
    And I follow "Click to mark user complete"
    # Running completion task just after clicking sometimes fail, as record
    # should be created before the task runs.
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    And I am on site homepage
    And I log out
    Then I log in as "student1"
    And I am on "Completion course" course homepage
    And I should see "Status: Complete"
