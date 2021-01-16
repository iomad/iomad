@media @media_videojs @_file_upload
Feature: Embed videos without the media filter
  In order to add helpful resources for students
  As a teacher
  I need to be able to embed videos URL, file and lesson modules

  Background:
    Given I log in as "admin"
    And I am on site homepage
    And I navigate to "Turn editing on" node in "Front page settings"

  @javascript
  Scenario: Add a video in a URL resource. Make sure media filters work
    When I add a "URL" to section "1" and I fill the form with:
      | Name | Video URL |
      | Description | Example of a video url |
      | External URL | http://download.moodle.org/mediatest/quicktime_320_180.mov |
    And I follow "Video URL"
    Then ".video-js" "css_element" should exist
    And I am on site homepage

  @javascript
  Scenario: Add a video as a File resource. Make sure media filters work
    When I add a "File" to section "1"
    And I set the following fields to these values:
      | Name | Video File |
      | Description | Example of a video file |
    And I upload "media/player/videojs/tests/fixtures/test.mov" file to "Select files" filemanager
    And I press "Save and display"
    Then ".video-js" "css_element" should exist

  @javascript
  Scenario: Add a video as content to a lesson. Make sure media filters work
    When I add a "Lesson" to section "1"
    And I set the following fields to these values:
      | Name | Lesson with video |
      | Description | Example of a video in a lesson |
    And I expand all fieldsets
    And I upload "media/player/videojs/tests/fixtures/test.mov" file to "Linked media" filemanager
    And I press "Save and display"
    And I follow "Add a content page"
    And I set the following fields to these values:
      | Page title | Placeholder content |
      | Description | Just so we can preview the lesson |
    And I press "Save page"
    And I navigate to "Preview" in current page administration
    And I follow "Click here to view"
    And I switch to "lessonmediafile" window
    Then ".video-js" "css_element" should exist
    And I switch to the main window
