@core @core_badges
Feature: Add badges to the system
  In order to give badges to users for their achievements
  As an admin
  I need to manage badges in the system

  Background:
    Given I am on homepage
    And I log in as "admin"

  @javascript
  Scenario: Setting badges settings
    Given I navigate to "Badges settings" node in "Site administration > Badges"
    And I set the field "Default badge issuer name" to "Test Badge Site"
    And I set the field "Default badge issuer contact details" to "testuser@example.com"
    And I press "Save changes"
    And I follow "Badges"
    When I follow "Add a new badge"
    Then the field "issuercontact" matches value "testuser@example.com"
    And the field "issuername" matches value "Test Badge Site"

  @javascript
  Scenario: Accessing the badges
    And I press "Customise this page"
   # TODO MDL-57120 site "Badges" link not accessible without navigation block.
    And I add the "Navigation" block if not present
    Given I navigate to "Site badges" node in "Site pages"
    Then I should see "There are no badges available."

  @javascript @_file_upload
  Scenario: Add a badge
    Given I navigate to "Add a new badge" node in "Site administration > Badges"
    And I set the following fields to these values:
      | Name | Test badge with 'apostrophe' and other friends (<>&@#) |
      | Description | Test badge description |
      | issuername | Test Badge Site |
      | issuercontact | testuser@example.com |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    When I press "Create badge"
    Then I should see "Edit details"
    And I should see "Test badge with 'apostrophe' and other friends (&@#)"
    And I should not see "Create badge"
    And I follow "Manage badges"
    And I should see "Number of badges available: 1"
    And I should not see "There are no badges available."
