@editor @editor_tiny @tiny @tiny_multilang2 @javascript
Feature: Tiny editor multilang plugin for default behaviour with no multilangfilter2 installed
  To put content in Tiny in different languages, I use the language button to encapsulate the text in language tags.

  Background: I login as admin and add a text to the description with two paragraphs.
    Given the following config values are set as admin:
      | requiremultilang2 | 0 | tiny_multilang2 |
      | highlight | 1 | tiny_multilang2 |
    And the following "language packs" exist:
      | language |
      | de       |
    And the "multilang" filter is "on"
    And the "multilang" filter applies to "content and headings"
    And the "multilang2" filter is "on"
    And the "multilang2" filter applies to "content and headings"
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity | name       | intro      | introformat | course | content                                       | contentformat | idnumber |
      | page     | PageName1  | PageDesc1  | 1           | C1     | <p>Some plain text</p><p>Ein anderer Text</p> | 1             | 1        |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode off
    And I am on the "PageName1" "page activity" page
    Then I should see "Some plain text"
    And I should see "Ein anderer Text"

  Scenario: I login as admin and select the paragraphs and set the language to english and german
    When I am on "Course 1" course homepage
    And I am on the "PageName1" "page activity editing" page logged in as admin
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I select the inner "p" element in position "0" of the "Page content" TinyMCE editor
    And I click on the "Format > Language > English (en)" submenu item for the "Page content" TinyMCE editor
    And I click on "//h1" "xpath_element"
    And I select the inner "p" element in position "1" of the "Page content" TinyMCE editor
    And I click on the "Format > Language > Deutsch (de)" submenu item for the "Page content" TinyMCE editor
    And I press "Save and display"
    Then I should see "Some plain text"
    And I should not see "Ein anderer Text"
    And I follow "Language" in the user menu
    And I follow "Deutsch ‎(de)‎"
    And I should see "Ein anderer Text"
    And I should not see "Some plain text"
    And I am on the "PageName1" "page activity editing" page logged in as admin
    And I expand all fieldsets
    And I select the "span" element in position "2" of the "Page content" TinyMCE editor
    And I click on the "Format > Language > English (en)" submenu item for the "Page content" TinyMCE editor
    And I press "Save and display"
    Then I should see "Some plain text"
    And I should see "Ein anderer Text"
