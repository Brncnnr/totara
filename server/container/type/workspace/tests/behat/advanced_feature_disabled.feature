@container @workspace @container_workspace @totara @totara_engage @engage
Feature: Workspaces will not appear when the advanced feature is disabled

  @javascript
  Scenario: Disabling the workspace feature will hide the workspace menu items
    Given I am on a totara site
    And I log in as "admin"

    When I disable the "container_workspace" advanced feature
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I press "Reset menu to default configuration"
    And I click on "permanently deleted" "radio"
    And I press "Reset"
    Then I should not see "Collaborate" in the totara menu
    And I should not see "Your Workspaces" in the totara menu
    And I should not see "Find Workspaces" in the totara menu

    When I enable the "container_workspace" advanced feature
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I press "Reset menu to default configuration"
    And I click on "permanently deleted" "radio"
    And I press "Reset"
    Then I should see "Collaborate" in the totara menu
    And I should see "Your Workspaces" in the totara menu
    And I should see "Find Workspaces" in the totara menu
