@local @local_designer @mask_background @javascript
Feature: Add mask images as background for activities and sections.
  In order to create mask images
  As a site administrator
  I need to add mask images in admin settings.

  Background:
    Given the following "courses" exist:
    | fullname | shortname | format | coursedisplay | numsections |
    | Course 1 | C1        | designer | 0             | 2           |
    And the following "activities" exist:
    | activity   | name                   | intro                         | course | idnumber    | section |
    | assign     | Test assignment name   | Test assignment description   | C1     | assign1     | 0       |
    Given I log in as "admin"

  @javascript @_file_upload
  Scenario: Set the background image styles.
    Given I am on "Course 1" course homepage with editing mode on
    And I add a page activity to course "Course 1" designer section "0"
    And I expand all fieldsets
    And I upload "local/designer/tests/assets/background.jpg" file to "Background image" filemanager
    And I set the following fields to these values:
      | Background Size     | contain     |
      | Background Position | Left Center |
      | Background Repeat   | Yes         |
      | Name                | Page 2      |
      | Description         | Test        |
      | Page content        | Test        |
      | ID number           | page1       |
    And I press "Save and return to course"
    Given activity "page1" should contain style "background-position" "0% 50%"
    Given activity "page1" should contain style "background-size" "contain"
    Given activity "page1" should contain style "background-repeat" "repeat"

  @javascript @_file_upload
  Scenario: Upload Mask images in plugin settings.
    Then I navigate to "Plugins > Course formats > Designer format > General settings" in site administration
    And I click on "Section" "link"
    And I upload "local/designer/tests/assets/place.png" file to "Section mask image" filemanager
    And I press "Save changes"
    Then I am on "Course 1" course homepage with editing mode on
    And I open section "0" edit menu in designer
    And I expand all fieldsets
    And the "Mask image" select box should contain "Place"
    And I select "Place" from the "Mask image" singleselect
    And I upload "local/designer/tests/assets/background.jpg" file to "Background image" filemanager
    And I press "Save changes"
    Given course "Course 1" section "0" should show mask "place.png"

  @javascript @_file_upload
  Scenario: Upload Mask images in plugin settings.
    Given I navigate to "Plugins > Course formats > Designer format > General settings" in site administration
    And I click on "Activity" "link"
    And I upload "local/designer/tests/assets/place.png" file to "Mask image" filemanager
    And I press "Save changes"
    Then I am on "Course 1" course homepage with editing mode on
    And I add a page activity to course "Course 1" designer section "0"
    And I expand all fieldsets
    And the "Mask image" select box should contain "Place"
    And I select "Place" from the "Mask image" singleselect
    And I set the following fields to these values:
      | Name         | Page 2 |
      | Description  | Test   |
      | Page content | Test   |
      | ID number    | page1  |
    And I press "Save and return to course"
    Given course "Course 1" activity "page1" should show mask "place.png"

  @javascript @_file_upload
  Scenario: Set mask image positions.
    Given I navigate to "Plugins > Course formats > Designer format > General settings" in site administration
    And I click on "Activity" "link"
    And I upload "local/designer/tests/assets/place.png" file to "Mask image" filemanager
    And I set the following fields to these values:
      | Activity mask size | cover |
      | Activity mask position | Left Top |
    And I press "Save changes"
    Then I am on "Course 1" course homepage with editing mode on
    And I add a page activity to course "Course 1" designer section "0"
    And I expand all fieldsets
    And the field "Mask position" in the "Design" "fieldset" matches value "Left Top"
    And the field "Mask size" in the "Design" "fieldset" matches value "cover"
    And I set the following fields to these values:
      | Mask image    | Place       |
      | Mask size     | Auto        |
      | Mask position | Left Center |
      | Name          | Page 2      |
      | Description   | Test        |
      | Page content  | Test        |
      | ID number     | page1       |
    And I press "Save and return to course"
    Given course "Course 1" activity "page1" should show mask position "0% 50%"
    Given course "Course 1" activity "page1" should show mask size "auto"
