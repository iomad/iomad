@core @core_course
Feature: Managers can create courses
  In order to group users and contents
  As a manager
  I need to create courses and set default values on them

  @javascript
  Scenario: Courses are created with the default announcements forum
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And I log in as "admin"
    And I create a course with:
      | Course full name | Course 1 |
      | Course short name | C1 |
    And I enrol "Teacher 1" user as "Teacher"
    And I enrol "Student 1" user as "Student"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Latest announcements" block
    Then "Latest announcements" "block" should exist
    And I follow "Announcements"
    And "Add a new topic" "button" should exist
    And "Subscription mode > Forced subscription" "link" should not exist in current page administration
    And "Subscription mode > Forced subscription" "text" should exist in current page administration
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Announcements"
    And "Add a new topic" "button" should not exist
    And "Forced subscription" "text" should exist in current page administration

  Scenario: Create a course from the management interface and return to it
    Given the following "courses" exist:
      | fullname | shortname | idnumber | startdate | enddate   |
      | Course 1 | Course 1  | C1       | 957139200 | 960163200 |
    And I log in as "admin"
    And I go to the courses management page
    And I should see the "Categories" management page
    And I click on category "Miscellaneous" in the management interface
    And I should see the "Course categories and courses" management page
    And I click on "Create new course" "link" in the "#course-listing" "css_element"
    When I set the following fields to these values:
      | Course full name | Course 2 |
      | Course short name | Course 2 |
      | Course summary | Course 2 summary |
      | id_startdate_day | 24 |
      | id_startdate_month | October |
      | id_startdate_year | 2015 |
      | id_enddate_day | 24 |
      | id_enddate_month | October |
      | id_enddate_year | 2016 |
    And I press "Save and return"
    Then I should see the "Course categories and courses" management page
    And I click on "Sort courses" "link"
    And I click on "Sort by Course time created ascending" "link" in the ".course-listing-actions" "css_element"
    And I should see course listing "Course 1" before "Course 2"
    And I click on "Course 2" "link" in the "region-main" "region"
    And I click on "Edit" "link" in the ".course-detail" "css_element"
    And the following fields match these values:
      | Course full name | Course 2 |
      | Course short name | Course 2 |
      | Course summary | Course 2 summary |
      | id_startdate_day | 24 |
      | id_startdate_month | October |
      | id_startdate_year | 2015 |
      | id_enddate_day | 24 |
      | id_enddate_month | October |
      | id_enddate_year | 2016 |
