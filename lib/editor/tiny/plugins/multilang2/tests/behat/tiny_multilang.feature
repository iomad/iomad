@editor @editor_tiny @tiny @tiny_multilang2 @javascript
Feature: Tiny editor multilang plugin with multilangfilter2
  To put content in Tiny in different languages, I use the language button to encapsulate the text in language tags.

  Background: I login as admin and add a text to the description with two paragraphs.
    Given the following config values are set as admin:
      | requiremultilang2  | 0 | tiny_multilang2 |
      | highlight          | 1 | tiny_multilang2 |
      | simulatemultilang2 | 1 | tiny_multilang2 |
    And the following "language packs" exist:
      | language |
      | de       |
    And I log in as "admin"
    And I open my profile in edit mode
    And I wait until the page is ready
    And I set the field "Description" to "<p>Some plain text</p><p>Ein anderer Text</p>"
    And I press "Update profile"
    Then I should see "Some plain text"
    And I should see "Ein anderer Text"

  Scenario: I login as admin and add a language tag at the beginning of the description.
    Given I log in as "admin"
    And I open my profile in edit mode
    And I wait until the page is ready
    And I click on the "Format > Language > English (en)" submenu item for the "Description" TinyMCE editor
    And I press "Update profile"
    Then I should see "{mlang en} {mlang}Some plain text"
    And I should see "Ein anderer Text"
    And I should not see "Ein anderer Text{mlang}"

  Scenario: I login as admin and and and later change the language of the first paragraph.
    Given I log in as "admin"
    And I open my profile in edit mode
    And I wait until the page is ready
    And I select the inner "p" element in position "0" of the "Description" TinyMCE editor
    And I click on the "Format > Language > English (en)" submenu item for the "Description" TinyMCE editor
    And I press "Update profile"
    Then I should see "{mlang en}Some plain text{mlang}"
    And I open my profile in edit mode
    And I wait until the page is ready
    And I select the "span" element in position "1" of the "Description" TinyMCE editor
    And I click on the "Format > Language > Fallback (other)" submenu item for the "Description" TinyMCE editor
    And I press "Update profile"
    Then I should see "{mlang other}Some plain text{mlang}"
    And I should not see "{mlang en}"

  Scenario: Test add languages manually.
    Given the following config values are set as admin:
      | addlanguage | 1 | tiny_multilang2 |
    And I open my profile in edit mode
    And I wait until the page is ready
    And I select the inner "p" element in position "0" of the "Description" TinyMCE editor
    When I click on the "Format > Language" submenu item for the "Description" TinyMCE editor
    Then I should see "Remove all lang tags"
    And I should see "Chinese (zh)"
    And I should see "English (en)"
    And I should see "Hindi (hi)"
    And I should see "Spanish; Castilian (es)"
    And I should see "Malay (ms)"
    And I should see "Russian (ru)"
    And I should see "Bengali (bn)"

  Scenario: Test remove all language tags.
    Given I log in as "admin"
    And I open my profile in edit mode
    And I wait until the page is ready
    And I select the inner "p" element in position "0" of the "Description" TinyMCE editor
    And I click on the "Format > Language > English (en)" submenu item for the "Description" TinyMCE editor
    And I select the inner "p" element in position "1" of the "Description" TinyMCE editor
    And I click on the "Format > Language > Deutsch (de)" submenu item for the "Description" TinyMCE editor
    And I press "Update profile"
    Then "//p[contains(text(), '{mlang en}Some plain text{mlang}')]" "xpath_element" should exist
    And "//p[contains(text(), '{mlang de}Ein anderer Text{mlang}')]" "xpath_element" should exist
    When I click on "Edit profile" "link"
    And I wait until the page is ready
    And I click on the "Format > Language > Remove all lang tags" submenu item for the "Description" TinyMCE editor
    And I press "Update profile"
    Then "//p[contains(text(), 'Some plain text')]" "xpath_element" should exist
    And "//p[contains(text(), 'Ein anderer Text')]" "xpath_element" should exist

  Scenario: Test language direction.
    Given the following config values are set as admin:
      | addlanguage        | 1 | tiny_multilang2 |
      | simulatemultilang2 | 0 | tiny_multilang2 |
    # And I navigate to "Plugins > Text editors > TinyMCE editor > Multi-Language Content (v2)" in site administration
    # doesn't work because the same menu item exists for the Atto editor which comes first (apparently the level before,
    # the editor, seems not to work in this navigation string).
    And I log in as "admin"
    And I click on "Site administration" "link"
    And I click on "Plugins" "link"
    And I click on "//a[contains(@href, 'tiny_multilang2_settings')]" "xpath"
    When I set the field "id_s_tiny_multilang2_languageoptions" to multiline:
    """
    zh
    sd
    ar
    vi
    """
    And I press "Save changes"
    And I open my profile in edit mode
    And I wait until the page is ready
    And I set the field "Description" to "<p>Đây là Tiếng Việt</p><p>السلام عليكم</p>"
    And I select the inner "p" element in position "0" of the "Description" TinyMCE editor
    And I click on the "Format > Language > Vietnamese (vi)" submenu item for the "Description" TinyMCE editor
    And I select the inner "p" element in position "1" of the "Description" TinyMCE editor
    And I click on the "Format > Language > Arabic (ar)" submenu item for the "Description" TinyMCE editor
    And I press "Update profile"
    And I should see "Đây là Tiếng Việt" in the "//span[@class='multilang'][contains(@lang, 'vi')]" "xpath_element"
    And I should see "السلام عليكم" in the "//span[@class='multilang'][contains(@lang, 'ar')]" "xpath_element"

  Scenario: Test add complex multi language types.
    Given the following config values are set as admin:
      | addlanguage        | 1 | tiny_multilang2 |
      | simulatemultilang2 | 0 | tiny_multilang2 |
    # And I navigate to "Plugins > Text editors > TinyMCE editor > Multi-Language Content (v2)" in site administration
    And I log in as "admin"
    And I click on "Site administration" "link"
    And I click on "Plugins" "link"
    And I click on "//a[contains(@href, 'tiny_multilang2_settings')]" "xpath"
    When I set the field "id_s_tiny_multilang2_languageoptions" to multiline:
    """
    AZ
    zh-Latn-pinyin
    zh-no-exist
    noexist
    en-US
    ff-Latn-NG
    """
    And I press "Save changes"
    And I open my profile in edit mode
    And I wait until the page is ready
    And I select the inner "p" element in position "0" of the "Description" TinyMCE editor
    When I click on the "Format > Language" submenu item for the "Description" TinyMCE editor
    Then I should see "Chinese (zh-Latn-pinyin)"
    And I should see "Chinese (zh-no-exist)"
    And I should see "English (en-US)"
    And I should see "Azerbaijani (az)"
    And I should see "Fulah (ff-Latn-NG)"
    And I should not see "noexist"
