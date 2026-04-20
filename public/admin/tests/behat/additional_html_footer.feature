@javascript
Feature: Additional HTML footer content including multilang and scripting support
  In order to verify Additional HTML footer behavior
  As an admin
  I need to confirm script and filter execution plus footer popover rendering

  Scenario: Admin sets additionalhtmlfooter with various elements and sees the expected result
    Given I log in as "admin"
    And I navigate to "Plugins > Filters > Manage filters" in site administration
    And I set the field "newstate" in the "Multi-language content" "table_row" to "On"
    And I navigate to "Appearance > Additional HTML" in site administration
    And I set the field "Before BODY is closed" to multiline:
      """
      <strong>Bold test text</strong><br>
      <script>
      window.onload = () => {
          setTimeout(() => alert('Oh look, an alert!'), 500);
          let testtext = document.createElement('div');
          testtext.innerHTML = '<span class="multilang" lang="en">Hello in English</span>
              <span class="multilang" lang="es">Hola en español</span>';
          document.getElementById('page-footer').appendChild(testtext);
      }
      </script>
      """
    When I press "Save changes"
    And I accept the currently displayed dialog
    # Navigate home otherwise all text is on the page in the setting itself.
    And I am on site homepage
    # Confirm an alert dialog appears, by closing it.
    Then I accept the currently displayed dialog
    # Test multi-lang filtering.
    And I should see "Hello in English"
    And I should not see "Hola en español"
    And I should see "Bold test text" in the "page-footer" "region"
    And I should see "Bold test text" in the "strong" "css_element"
