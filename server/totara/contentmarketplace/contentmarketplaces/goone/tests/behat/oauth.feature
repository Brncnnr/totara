@totara @totara_contentmarketplace @contentmarketplace_goone @javascript
Feature: Establish API connection with Go1

  @_switch_window
  Scenario: Enable Go1 marketplace
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Plugins > Content marketplace > Manage content marketplaces" in site administration
    And I should see "Manage content marketplaces"
    And I should see "Disabled" in the ".contentmarketplace_goone" "css_element"
    When I click on "Set up" "link" in the ".contentmarketplace_goone" "css_element"
    And I switch to "setup" window
    And I should see "Allow Totara to access Go1"
    And the following should exist in the "state" table:
      | full_name       | Admin User         |
      | email           | moodle@example.com |
      | users_total     | 1                  |
    And I click on "Authorize Totara" "button"
    And I switch to the main window
    Then I should see "Subscription details"
    And I should see "testing.mygo1.com"
    And I click on "Continue" "button"
    And I should see "All content (82,137)"
    And I click on "Save and explore Go1" "button"
    And I should see "Explore content marketplace: Go1"
    And I should see "82,137 results"
    And I am on site homepage
    And I navigate to "Plugins > Content marketplace > Manage content marketplaces" in site administration
    And I should not see "What is content marketplace?"

  @_switch_window
  Scenario: Begin process of enabling Go1 marketplace but cancel before completion
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Plugins > Content marketplace > Manage content marketplaces" in site administration
    And I should see "Manage content marketplaces"
    And I should see "Disabled" in the ".contentmarketplace_goone" "css_element"
    When I click on "Set up" "link" in the ".contentmarketplace_goone" "css_element"
    And I switch to "setup" window
    And I should see "Allow Totara to access Go1"
    And the following should exist in the "state" table:
      | full_name       | Admin User         |
      | email           | moodle@example.com |
      | users_total     | 1                  |
    And I click on "Authorize Totara" "button"
    And I switch to the main window
    Then I should see "Subscription details"
    And I should see "testing.mygo1.com"
    And I click on "Cancel" "button"
    And I should see "Manage content marketplaces"
    And I should see "Disabled" in the ".contentmarketplace_goone" "css_element"
    And I am on site homepage
    And I navigate to "Plugins > Content marketplace > Manage content marketplaces" in site administration
    And I should see "Manage content marketplaces"
    And I should see "Disabled" in the ".contentmarketplace_goone" "css_element"

  @_switch_window
  Scenario: Set up a previously enabled Go1 marketplace
    Given I am on a totara site
    And the following config values are set as admin:
      | enabled            | 1                        | contentmarketplace_goone |
      | oauth_access_token | --INVALID-ACCESS-TOKEN-- | contentmarketplace_goone |
    And I log in as "admin"
    And I navigate to "Plugins > Content marketplace > Manage content marketplaces" in site administration
    And I should not see "What is content marketplace?"
    And I should see "Set up" in the ".contentmarketplace_goone" "css_element"
    When I click on "Set up" "link" in the ".contentmarketplace_goone" "css_element"
    And I switch to "setup" window
    And I should see "Allow Totara to access Go1"
    And the following should exist in the "state" table:
      | full_name       | Admin User         |
      | email           | moodle@example.com |
      | users_total     | 1                  |
    And I click on "Authorize Totara" "button"
    And I switch to the main window
    Then I should see "Subscription details"
    And I should see "testing.mygo1.com"
    And I click on "Continue" "button"
    And I should see "All content (82,137)"
    And I click on "Save and explore Go1" "button"
    And I should see "Explore content marketplace: Go1"
    And I should see "82,137 results"

  Scenario: Skip content marketplace introduction after at least one marketplace has been enabled at some point
    Given I am on a totara site
    And the following config values are set as admin:
      | enabled | 0 | contentmarketplace_goone |
    And I log in as "admin"
    And I navigate to "Plugins > Content marketplace > Manage content marketplaces" in site administration
    And I should not see "What is content marketplace?"
    And I should see "Set up" in the ".contentmarketplace_goone" "css_element"