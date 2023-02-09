@core @theme @theme_ventura @javascript
Feature: Update theme settings
  In order to change theme colour
  As a user
  I need to confirm that updating theme settings works as expected

  Background:
    Given I log in as "admin"
    And I am on site homepage
    And I navigate to "Ventura" node in "Site administration > Appearance > Themes"

  Scenario: Confirm default colours apply
    Then element ":root" should have a css property "--color-state" with a value of "#4b7e2b"
    And element ":root" should have a css property "--color-primary" with a value of "#69bd45"

  Scenario: Navigate to theme settings and update theme colours
    When I click on "Colours" "link"
    And I set the field "Primary brand colour" to "#FF000B"
    And I set the field "Accent colour" to "#00FFE6"
    And I click on "Save Colours Settings" "button"
    And I reload the page
    Then element ":root" should have a css property "--color-state" with a value of "#FF000B"
    And element ":root" should have a css property "--color-primary" with a value of "#00FFE6"