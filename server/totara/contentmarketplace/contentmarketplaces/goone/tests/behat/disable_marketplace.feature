@totara @totara_contentmarketplace @contentmarketplace_goone @javascript
Feature: Disabling a content marketplace

  Scenario: An enabled marketplace has several actions
    Given I am on a totara site
    And the following config values are set as admin:
      | enabled | 1 | contentmarketplace_goone |
    And I log in as "admin"
    When I navigate to "Plugins > Content marketplace > Manage content marketplaces" in site administration
    Then I should see "Enabled" in the ".contentmarketplace_goone" "css_element"
    And "Settings" "link" should exist in the ".contentmarketplace_goone" "css_element"
    And "Disable" "link" should exist in the ".contentmarketplace_goone" "css_element"
    And "Enable" "link" should not exist in the ".contentmarketplace_goone" "css_element"
    And "Set up" "link" should exist in the ".contentmarketplace_goone" "css_element"

  Scenario: A disabled marketplace has several actions disabled
    Given I am on a totara site
    And the following config values are set as admin:
      | enabled | 0 | contentmarketplace_goone |
    And I log in as "admin"
    When I navigate to "Plugins > Content marketplace > Manage content marketplaces" in site administration
    Then I should see "Disabled" in the ".contentmarketplace_goone" "css_element"
    And "Settings" "link" should not exist in the ".contentmarketplace_goone" "css_element"
    And "Disable" "link" should not exist in the ".contentmarketplace_goone" "css_element"
    And "Enable" "link" should exist in the ".contentmarketplace_goone" "css_element"
    And "Set up" "link" should exist in the ".contentmarketplace_goone" "css_element"

  Scenario: An enabled marketplace can be disabled
    Given I am on a totara site
    And the following config values are set as admin:
      | enabled | 1 | contentmarketplace_goone |
    And I log in as "admin"
    And I navigate to "Plugins > Content marketplace > Manage content marketplaces" in site administration
    And I should see "Enabled" in the ".contentmarketplace_goone" "css_element"
    When I click on "Disable" "link" in the ".contentmarketplace_goone" "css_element"
    And I should see "Disable Go1 content" in the ".modal" "css_element"
    And I click on "Disable" "button" in the ".modal" "css_element"
    Then I should see "Disabled" in the ".contentmarketplace_goone" "css_element"
    And "Enable" "link" should exist in the ".contentmarketplace_goone" "css_element"
    And "Disable" "link" should not exist in the ".contentmarketplace_goone" "css_element"

  Scenario: An enabled marketplace can be disabled
    Given I am on a totara site
    And the following config values are set as admin:
      | enabled | 0 | contentmarketplace_goone |
    And I log in as "admin"
    And I navigate to "Plugins > Content marketplace > Manage content marketplaces" in site administration
    And I should see "Disabled" in the ".contentmarketplace_goone" "css_element"
    And I click on "Enable" "link" in the ".contentmarketplace_goone" "css_element"
    When I click on "Enable" "button" in the ".modal" "css_element"
    Then I should see "Enabled" in the ".contentmarketplace_goone" "css_element"
    And "Disable" "link" should exist in the ".contentmarketplace_goone" "css_element"
    And "Enable" "link" should not exist in the ".contentmarketplace_goone" "css_element"