@block @block_online_users
Feature: The online users block allow you to see who is currently online on dashboard
  In order to use the online users block on the dashboard
  As a user
  I can view the online users block on my dashboard

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |

  Scenario: View the online users block on the dashboard and see myself
    Given I log in as "teacher1"
    Then I should see "Teacher 1" in the "Online users" "block"

  Scenario: View the online users block on the dashboard and see other logged in users
    Given I log in as "student2"
    And I log out
    And I log in as "student1"
    And I log out
    When  I log in as "teacher1"
    Then I should see "Teacher 1" in the "Online users" "block"
    And I should see "Student 1" in the "Online users" "block"
    And I should see "Student 2" in the "Online users" "block"
