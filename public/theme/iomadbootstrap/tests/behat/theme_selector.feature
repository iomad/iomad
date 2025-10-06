@core @core_admin @theme_iomadbootstrap
Feature: Select a theme in IOMAD Bootstrap theme
  In order to choose a theme
  As an admin
  I need to preview the theme and make a selection

  Background:
    Given I log in as "admin"
    And I navigate to "Appearance > Themes" in site administration

  @javascript
  Scenario: I am able to preview a theme using a modal window
    When I click on "Preview theme 'IOMAD Bootstrap'" "button"
    Then I should see "IOMAD Bootstrap" in the "IOMAD Bootstrap" "dialogue"
    And I should see "IOMAD Bootstrap is a highly-customisable theme," in the "IOMAD Bootstrap" "dialogue"
    And I should see "Current theme" in the "IOMAD Bootstrap" "dialogue"

  @javascript
  Scenario: I am able to change the theme using the modal window select button
    Given I should see "Current theme" in the "#theme-card-iomadbootstrap" "css_element"
    When I click on "Preview theme 'Boost'" "button"
    And I click on "Select theme" "button" in the "Boost" "dialogue"
    Then I should see "Current theme" in the "#theme-card-boost" "css_element"

  Scenario: I am able to change the theme using the normal select button
    Given I should see "Current theme" in the "#theme-card-iomadbootstrap" "css_element"
    When I click on "Select theme 'Boost'" "button"
    Then I should see "Current theme" in the "#theme-card-boost" "css_element"

  Scenario: I am able to view a theme's settings page
    Given I click on "Edit theme settings 'IOMAD Bootstrap'" "link"
    Then I should see "IOMAD Bootstrap"
