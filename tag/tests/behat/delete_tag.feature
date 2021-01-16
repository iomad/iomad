@core @core_tag
Feature: Manager is able to delete tags
  In order to use tags
  As a manager
  I need to be able to delete them

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                | interests      |
      | manager1 | Manager   | 1        | manager1@example.com |                |
      | user1    | User      | 1        | user1@example.com    | Cat,Dog,Turtle |
    And the following "system role assigns" exist:
      | user     | course               | role      |
      | manager1 | Acceptance test site | manager   |
    And the following "tags" exist:
      | name         | isstandard |
      | Neverusedtag | 1          |

  Scenario: Deleting a tag with javascript disabled
    When I log in as "manager1"
    And I navigate to "Manage tags" node in "Site administration > Appearance"
    And I follow "Default collection"
    And I click on "Delete" "link" in the "Dog" "table_row"
    And I should see "Tag(s) deleted"
    Then I should not see "Dog"
    And I follow "Cat"
    And I follow "User 1"
    And I should see "Cat"
    And I should not see "Dog"
    And I log out

  Scenario: Deleting multiple tags with javascript disabled
    When I log in as "manager1"
    And I navigate to "Manage tags" node in "Site administration > Appearance"
    And I follow "Default collection"
    And I set the following fields to these values:
      | Select tag Dog | 1 |
      | Select tag Neverusedtag | 1 |
    And I press "Delete selected"
    Then I should see "Tag(s) deleted"
    And I should not see "Dog"
    And I should not see "Neverusedtag"
    And I follow "Cat"
    And I follow "User 1"
    And I should see "Cat"
    And I should not see "Dog"
    And I log out

  @javascript
  Scenario: Deleting a tag with javascript enabled
    When I log in as "manager1"
    And I navigate to "Manage tags" node in "Site administration > Appearance"
    And I follow "Default collection"
    And I click on "Delete" "link" in the "Turtle" "table_row"
    Then I should see "Are you sure you want to delete this tag?"
    And I press "No"
    And I should not see "Tag(s) deleted"
    And I should see "Turtle"
    And I click on "Delete" "link" in the "Dog" "table_row"
    And I should see "Are you sure you want to delete this tag?"
    And I press "Yes"
    And I should see "Tag(s) deleted"
    And I should not see "Dog"
    And I follow "Default collection"
    And I should not see "Dog"
    And I follow "Cat"
    And I follow "User 1"
    And I should see "Cat"
    And I should not see "Dog"
    And I log out

  @javascript
  Scenario: Deleting multiple tags with javascript enabled
    When I log in as "manager1"
    And I navigate to "Manage tags" node in "Site administration > Appearance"
    And I follow "Default collection"
    And I press "Delete selected"
    And I should not see "Are you sure"
    And I should not see "Tag(s) deleted"
    And I set the following fields to these values:
      | Select tag Cat | 1 |
    And I press "Delete selected"
    And I should see "Are you sure you want to delete selected tags?"
    And I press "No"
    And I should not see "Tag(s) deleted"
    And I should see "Cat"
    And I set the following fields to these values:
      | Select tag Cat | 0 |
      | Select tag Dog | 1 |
      | Select tag Neverusedtag | 1 |
    And I press "Delete selected"
    And I should see "Are you sure you want to delete selected tags?"
    And I press "Yes"
    And I should see "Tag(s) deleted"
    And I should not see "Dog"
    And I should not see "Neverusedtag"
    And I follow "Default collection"
    And I should not see "Dog"
    And I should not see "Neverusedtag"
    And I follow "Cat"
    And I follow "User 1"
    And I should see "Cat"
    And I should not see "Dog"
    And I log out
