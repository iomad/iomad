@core @core_grades @javascript
Feature: View gradebook when scales are used
  In order to use scales to grade activities
  As an teacher
  I need to be able to view gradebook with scales

  Background:
    # The grade_aggregations_visible value lists every aggregation type, by id:
    # 0 Mean of grades, 10 Weighted mean of grades, 11 Simple weighted mean of grades,
    # 12 Mean of grades (with extra credits), 2 Median of grades, 4 Lowest grade,
    # 6 Highest grade, 8 Mode of grades, 13 Natural.
    Given the following config values are set as admin:
      | grade_report_showranges                    | 1                     |
      | grade_report_showweight                    | 1                     |
      | grade_report_showcontributiontocoursetotal | 1                     |
      | grade_aggregations_visible                 | 0,10,11,12,2,4,6,8,13 |
    And the following "scales" exist:
      | name        | scale     |
      | Letterscale | F,D,C,B,A |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email            | idnumber |
      | teacher1 | Teacher   | 1        | teacher1@example.com | t1       |
      | student1 | Student   | 1        | student1@example.com | s1       |
      | student2 | Student   | 2        | student2@example.com | s2       |
      | student3 | Student   | 3        | student3@example.com | s3       |
      | student4 | Student   | 4        | student4@example.com | s4       |
      | student5 | Student   | 5        | student5@example.com | s5       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
      | student5 | C1     | student        |
    And the following "grade categories" exist:
      | fullname       | course |
      | Sub category 1 | C1     |
    And the following "activities" exist:
      | activity | course | idnumber | name                | intro             | gradecategory  | grade       |
      | assign   | C1     | a1       | Test assignment one | Submit something! | Sub category 1 | Letterscale |
    And the following "grade grades" exist:
      | gradeitem           | user     | grade |
      | Test assignment one | student1 | 5     |
      | Test assignment one | student2 | 4     |
      | Test assignment one | student3 | 3     |
      | Test assignment one | student4 | 2     |
      | Test assignment one | student5 | 1     |
    And I am on the "Course 1" "grades > Grader report > View" page logged in as "teacher1"
    And I turn editing mode on

  Scenario: Test displaying scales in gradebook in aggregation method Natural
    When I turn editing mode off
    Then the following should exist in the "user-grades" table:
      | -1-                | -2-                  | -3-      | -4-            | -5-          |
      | Student 1          | student1@example.com | A        | 5.00           | 5.00         |
      | Student 2          | student2@example.com | B        | 4.00           | 4.00         |
      | Student 3          | student3@example.com | C        | 3.00           | 3.00         |
      | Student 4          | student4@example.com | D        | 2.00           | 2.00         |
      | Student 5          | student5@example.com | F        | 1.00           | 1.00         |
    And the following should exist in the "user-grades" table:
      | -1-                | -2-      | -3-            | -4-          |
      | Range              | F–A      | 0.00–5.00      | 0.00–5.00    |
      | Overall average    | C        | 3.00           | 3.00         |
    And I navigate to "View > User report" in the course gradebook
    And I click on "Student 3" in the "Search users" search combo box
    And the following should exist in the "user-grade" table:
      | Grade item          | Grade | Range | Percentage | Contribution to course total |
      | Test assignment one | C     | F–A   | 50.00 %    | 60.00 %                      |
      | Sub category 1 total      | 3.00  | 0–5   | 60.00 %    | -                            |
      | Course total        | 3.00  | 0–5   | 60.00 %    | -                            |
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And the following should exist in the "grade_edit_tree_table" table:
      | Name                | Max grade |
      | Test assignment one | 5.00      |
      | Sub category 1 total      | 5.00      |
      | Course total        | 5.00      |
    And I log out
    And I log in as "student2"
    And I follow "Grades" in the user menu
    And I click on "Course 1" "link" in the "region-main" "region"
    And the following should exist in the "user-grade" table:
      | Grade item          | Grade | Range | Percentage | Contribution to course total |
      | Test assignment one | B     | F–A   | 75.00 %    | 80.00 %                      |
      | Sub category 1 total      | 4.00  | 0–5   | 80.00 %    | -                            |
      | Course total        | 4.00  | 0–5   | 80.00 %    | -                            |

  Scenario Outline: Test displaying scales in gradebook in all other aggregation methods
    Given I set the following settings for grade item "Course 1" of type "course" on "grader" page:
      | Aggregation | <aggregation> |
    And I set the following settings for grade item "Sub category 1" of type "category" on "grader" page:
      | Aggregation   | <aggregation>                |
      | Category name | Sub category (<aggregation>) |
      | Maximum grade | 5                            |
      | Minimum grade | 1                            |
    And I turn editing mode off
    Then the following should exist in the "user-grades" table:
      | -1-                | -2-                  | -3-      | -4-            | -5-            |
      | Student 1          | student1@example.com | A        | 5.00           | <coursetotal1> |
      | Student 2          | student2@example.com | B        | 4.00           | <coursetotal2> |
      | Student 3          | student3@example.com | C        | 3.00           | <coursetotal3> |
      | Student 4          | student4@example.com | D        | 2.00           | <coursetotal4> |
      | Student 5          | student5@example.com | F        | 1.00           | <coursetotal5> |
    And the following should exist in the "user-grades" table:
      | -1-                | -2-      | -3-            | -4-            |
      | Range              | F–A      | 1.00–5.00      | 0.00–100.00    |
      | Overall average    | C        | 3.00           | <overallavg>   |
    And I navigate to "View > User report" in the course gradebook
    And I click on "Student 3" in the "Search users" search combo box
    And the following should exist in the "user-grade" table:
      | Grade item                          | Grade          | Range | Percentage    | Contribution to course total |
      | Test assignment one                 | C              | F–A   | 50.00 %       | <contrib3>                   |
      | Sub category (<aggregation>) total  | 3.00           | 1–5   | 50.00 %       | -                            |
      | Course total                        | <coursetotal3> | 0–100 | <courseperc3> | -                            |
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And the following should exist in the "grade_edit_tree_table" table:
      | Name                | Max grade |
      | Test assignment one | A (5)     |
      | Sub category (<aggregation>) total |           |
      | Course total   |           |
    And I log out
    And I log in as "student2"
    And I follow "Grades" in the user menu
    And I click on "Course 1" "link" in the "region-main" "region"
    And the following should exist in the "user-grade" table:
      | Grade item                          | Grade          | Range | Percentage    | Contribution to course total |
      | Test assignment one                 | B              | F–A   | 75.00 %       | <contrib2>                   |
      | Sub category (<aggregation>) total  | 4.00           | 1–5   | 75.00 %       | -                            |
      | Course total                        | <coursetotal2> | 0–100 | <courseperc2> | -                            |

    Examples:
      | aggregation                         | coursetotal1 | coursetotal2 | coursetotal3 | coursetotal4 | coursetotal5 |overallavg | courseperc2 | courseperc3 | contrib2 | contrib3 |
      | Mean of grades                      | 100.00       | 75.00        | 50.00        | 25.00        | 0.00         | 50.00     | 75.00 %     | 50.00 %     | 75.00 %  | 50.00 %  |
      | Weighted mean of grades             | 100.00       | 75.00        | 50.00        | 25.00        | 0.00         | 50.00     | 75.00 %     | 50.00 %     | 75.00 %  | 50.00 %  |
      | Simple weighted mean of grades      | 100.00       | 75.00        | 50.00        | 25.00        | 0.00         | 50.00     | 75.00 %     | 50.00 %     | 75.00 %  | 50.00 %  |
      | Mean of grades (with extra credits) | 100.00       | 75.00        | 50.00        | 25.00        | 0.00         | 50.00     | 75.00 %     | 50.00 %     | 75.00 %  | 50.00 %  |
      | Median of grades                    | 100.00       | 75.00        | 50.00        | 25.00        | 0.00         | 50.00     | 75.00 %     | 50.00 %     | 75.00 %  | 50.00 %  |
      | Lowest grade                        | 100.00       | 75.00        | 50.00        | 25.00        | 0.00         | 50.00     | 75.00 %     | 50.00 %     | 75.00 %  | 50.00 %  |
      | Highest grade                       | 100.00       | 75.00        | 50.00        | 25.00        | 0.00         | 50.00     | 75.00 %     | 50.00 %     | 75.00 %  | 50.00 %  |
      | Mode of grades                      | 100.00       | 75.00        | 50.00        | 25.00        | 0.00         | 50.00     | 75.00 %     | 50.00 %     | 75.00 %  | 50.00 %  |
