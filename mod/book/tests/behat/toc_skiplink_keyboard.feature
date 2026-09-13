@mod @mod_book
Feature: Book skip links move keyboard focus past the skipped content
  In order to navigate a book efficiently with a keyboard
  As a user
  I need the skip links to move focus beyond the content they skip, without trapping it

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "activity" exists:
      | course   | C1        |
      | activity | book      |
      | name     | Test book |
    And the following "mod_book > chapters" exist:
      | book      | title          | content               | pagenum |
      | Test book | First chapter  | <p>First chapter</p>  | 1       |
      | Test book | Second chapter | <p>Second chapter</p> | 2       |
    And I am on the "Test book" "book activity" page logged in as "admin"

  @javascript @accessibility
  Scenario: Activating the table of contents skip link moves focus past the table of contents
    Given I set the focus on the "Skip Table of contents" "link"
    When I press the enter key
    # Without the fix the target is not focusable, so activating the link does not move focus to
    # it (focus falls back to the body). Assistive technology that does not honour the sequential
    # focus navigation starting point, such as screen readers, is then left unable to move past
    # the table of contents. With the fix focus moves to the target after the table of contents.
    Then the focused element is "span[id^='sb-']" "css_element"

  @javascript @accessibility
  Scenario: Activating the skip to main content link moves focus to the main content
    Given I set the focus on the "Skip to main content" "link"
    When I press the enter key
    Then the focused element is "#maincontent" "css_element"
