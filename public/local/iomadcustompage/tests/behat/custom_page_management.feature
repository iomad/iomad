# IOMAD Custom Page Management Features
#
# @package    local_iomadcustompage
# @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@local @local_iomadcustompage @javascript
Feature: IOMAD Custom page management
  In order to create and manage custom pages
  As an administrator
  I need to be able to create, edit, view and delete custom pages

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | admin    | Admin     | User     | admin@example.com    |
      | teacher  | Teacher   | User     | teacher@example.com  |
      | student  | Student   | User     | student@example.com  |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | teacher | editingteacher | System       |           |
    And I log in as "admin"

  @local_iomadcustompage_create
  Scenario: Admin can create a new custom page
    Given I navigate to "Plugins > Local plugins > IOMAD Custom pages" in site administration
    When I click on "Create new page" "button"
    And I set the following fields to these values:
      | Page name | Test IOMAD Custom Page |
      | Page title | My Test Page |
    And I press "Save changes"
    Then I should see "Page created successfully"
    And I should see "Test IOMAD Custom Page" in the "pages-list-container" "css_element"

  @local_iomadcustompage_create_container
  Scenario: Admin can create a container page
    Given I navigate to "Plugins > Local plugins > IOMAD Custom pages" in site administration
    When I click on "Create new page" "button"
    And I set the following fields to these values:
      | Page name | Container Page |
      | Is container | 1 |
    And I press "Save changes"
    Then I should see "Page created successfully"
    And I should see "Container Page" in the "pages-list-container" "css_element"

  @local_iomadcustompage_edit
  Scenario: Admin can edit an existing custom page
    Given the following "local_iomadcustompage > pages" exist:
      | name           | title        |
      | Editable Page  | Edit Me      |
    And I navigate to "Plugins > Local plugins > IOMAD Custom pages" in site administration
    When I click on "Edit" "link" in the "Editable Page" "table_row"
    And I set the following fields to these values:
      | Page name | Updated Page Name |
      | Page title | Updated Title |
    And I press "Save changes"
    Then I should see "Page updated successfully"
    And I should see "Updated Page Name" in the "pages-list-container" "css_element"

  @local_iomadcustompage_view
  Scenario: Users can view custom pages they have access to
    Given the following "local_iomadcustompage > pages" exist:
      | name          | title         |
      | Viewable Page | View This     |
    And the following "local_iomadcustompage > audiences" exist:
      | page          | classname                                     |
      | Viewable Page | local_iomadcustompage\iomadcustompage\audience\allusers |
    When I am on the "Viewable Page" "local_iomadcustompage > page" page
    Then I should see "View This"
    And the page should contain "Viewable Page"

  @local_iomadcustompage_navigation
  Scenario: IOMAD Custom pages appear in site navigation
    Given the following "local_iomadcustompage > pages" exist:
      | name         | title       | showinprimarynav |
      | Nav Page     | Navigation  | 1                |
    And the following "local_iomadcustompage > audiences" exist:
      | page     | classname                                     |
      | Nav Page | local_iomadcustompage\iomadcustompage\audience\allusers |
    When I reload the page
    Then I should see "Navigation" in the ".navbar" "css_element"

  @local_iomadcustompage_hierarchy
  Scenario: Admin can create hierarchical page structure
    Given the following "local_iomadcustompage > pages" exist:
      | name        | title       | parent      |
      | Parent Page | Parent      |             |
      | Child Page  | Child       | Parent Page |
    And I navigate to "Plugins > Local plugins > IOMAD Custom pages" in site administration
    Then I should see "Parent Page" in the "pages-list-container" "css_element"
    And I should see "Child Page" in the "pages-list-container" "css_element"

  @local_iomadcustompage_delete
  Scenario: Admin can delete custom pages
    Given the following "local_iomadcustompage > pages" exist:
      | name            | title           |
      | Deletable Page  | Delete Me       |
    And I navigate to "Plugins > Local plugins > IOMAD Custom pages" in site administration
    When I click on "Delete" "link" in the "Deletable Page" "table_row"
    And I click on "Delete" "button" in the "Confirmation" "dialogue"
    Then I should see "Page deleted successfully"
    And I should not see "Deletable Page" in the "pages-list-container" "css_element"

  @local_iomadcustompage_permissions
  Scenario: Users without permissions cannot access admin pages
    Given I log out
    And I log in as "student"
    When I am on "/local/iomadcustompage/index.php"
    Then I should see "You are not allowed to do that"

  @local_iomadcustompage_audience_management
  Scenario: Admin can manage page audiences
    Given the following "local_iomadcustompage > pages" exist:
      | name           | title        |
      | Audience Page  | Manage Access |
    And I am on the "Audience Page" "local_iomadcustompage > page edit" page
    When I click on "Audiences" "tab"
    And I click on "Add audience" "button"
    And I set the field "Audience type" to "All users"
    And I press "Save"
    Then I should see "Audience added successfully"

  @local_iomadcustompage_breadcrumb
  Scenario: Page breadcrumbs work correctly for hierarchical pages
    Given the following "local_iomadcustompage > pages" exist:
      | name         | title        | parent       |
      | Parent Page  | Parent       |              |
      | Child Page   | Child        | Parent Page  |
      | Grandchild   | Grandchild   | Child Page   |
    And the following "local_iomadcustompage > audiences" exist:
      | page        | classname                                     |
      | Grandchild  | local_iomadcustompage\iomadcustompage\audience\allusers |
    When I am on the "Grandchild" "local_iomadcustompage > page" page
    Then I should see "Parent" in the ".breadcrumb" "css_element"
    And I should see "Child" in the ".breadcrumb" "css_element"
    And I should see "Grandchild" in the ".breadcrumb" "css_element"

  @local_iomadcustompage_container_editing
  Scenario: Container pages cannot be edited in block editing mode
    Given the following "local_iomadcustompage > pages" exist:
      | name           | title        | iscontainer |
      | Container Page | Container    | 1           |
    And the following "local_iomadcustompage > audiences" exist:
      | page           | classname                                     |
      | Container Page | local_iomadcustompage\iomadcustompage\audience\allusers |
    When I am on the "Container Page" "local_iomadcustompage > page" page
    Then I should not see "Turn editing on"
    And I should not see "Add a block"

  @local_iomadcustompage_inplace_editing
  Scenario: Admin can edit page names inline
    Given the following "local_iomadcustompage > pages" exist:
      | name         | title       |
      | Inline Page  | Inline Edit |
    And I navigate to "Plugins > Local plugins > IOMAD Custom pages" in site administration
    When I click on "Edit page name" "link" in the "Inline Page" "table_row"
    And I set the field "New value" to "Updated Inline Page"
    And I press key "13" in the field "New value"
    Then I should see "Updated Inline Page" in the "pages-list-container" "css_element"

  @local_iomadcustompage_search
  Scenario: Admin can search for custom pages
    Given the following "local_iomadcustompage > pages" exist:
      | name           | title        |
      | Searchable One | Search One   |
      | Searchable Two | Search Two   |
      | Different Page | Other        |
    And I navigate to "Plugins > Local plugins > IOMAD Custom pages" in site administration
    When I set the field "Search" to "Searchable"
    And I press "Search"
    Then I should see "Searchable One" in the "pages-list-container" "css_element"
    And I should see "Searchable Two" in the "pages-list-container" "css_element"
    And I should not see "Different Page" in the "pages-list-container" "css_element"
