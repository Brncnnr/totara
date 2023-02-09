@totara @totara_contentmarketplace @javascript
Feature: Manage content marketplace plugin
  Background:
    Given I am on a totara site
    And I log in as "admin"

  Scenario: Disable content marketplace plugin
    When I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should see "Content marketplace" in the "#quickaccess-popover-content" "css_element"
    And I navigate to "System information > Configure features > Learn settings" in site administration
    Then I set the field "Enable content marketplaces" to "0"
    And I click on "Save changes" "button"
    And I am on a totara site
    When I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should not see "Content marketplace" in the "#quickaccess-popover-content" "css_element"