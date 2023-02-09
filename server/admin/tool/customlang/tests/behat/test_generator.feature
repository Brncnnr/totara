@tool_customlang @javascript @totara @totara_core
Feature: Test tool_customlang generator
  Scenario: Test tool_customlang generator
    Given the following "language customisation" exist in "tool_customlang" plugin:
      | id          | string  |
      | login       | takiuru |
    And the following "language customisation" exist in "tool_customlang" plugin:
      | component   | id          | string             |
      | core        | loggedinnot | ya ain't logged in |
      | totara_core | totaralearn | t0T4r\100\x2013aRN |
    And I reload the page
    Then I should see "ya ain't logged in" in the ".login" "css_element"
    And I should see "takiuru" in the ".login" "css_element"
    And I should see "t0T4r@ 13aRN" in the ".page-footer-poweredby" "css_element"
