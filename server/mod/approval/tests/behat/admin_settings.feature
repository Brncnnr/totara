@totara @mod_approval @javascript
Feature: Walk through the admin settings page of Approval workflow
  Background:
    Given I am on a totara site
    And I log in as "admin"

  Scenario: mod_approval_100: Enable/disable approval form plugins
    Given I navigate to "Manage approval form plugins" node in "Site administration > Plugins > Approval form plugins"
    Then I should see "Disable Simple Request Form" in the "Simple Request Form" "table_row"
    But I should not see "Settings" in the "Simple Request Form" "table_row"
    But I should not see "Enable Simple Request Form" in the "Simple Request Form" "table_row"
    When I click on "Disable Simple Request Form" "link"
    Then I should see "Enable Simple Request Form" in the "Simple Request Form" "table_row"
    But I should not see "Settings" in the "Simple Request Form" "table_row"
    But I should not see "Disable Simple Request Form" in the "Simple Request Form" "table_row"

  Scenario: mod_approval_101: Test link to workflow dashboard
    And I skip the scenario until issue "QA" lands
    Given  I toggle open the admin quick access menu
    Then I should see "Approval Workflows" in the admin quick access menu
    # Disable mod_approval
    When I navigate to "Manage activities" node in "Site administration > Plugins > Activity modules"
    And I click on "Hide Approval workflow" "link" in the "Approval workflow" "table_row"
    And I am on homepage
    And I log out
    And I log in as "admin"
    And I toggle open the admin quick access menu
    Then I should not see "Approval Workflows" in the admin quick access menu
