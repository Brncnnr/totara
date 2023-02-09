@totara @totara_engage @container_workspace @engage
Feature: Tracking user's visited workspace
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | userone  | User      | One      | one@example.com |

  @javascript
  Scenario: User goes to last visited workspace
    Given I log in as "userone"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Create a workspace" "button"
    And I set the field "Workspace name" to "Workspace 101"
    And I click on "Submit" "button"
    And I click on "Create a workspace" "button" in the ".tui-contributeWorkspace" "css_element"
    And I set the field "Workspace name" to "Workspace 102"
    When I click on "Submit" "button"
    And I follow "Workspace 102"
    Then I should see "Workspace 102" in the page title
    When I click on "Your Workspaces" in the totara menu
    Then I should see "Workspace 102" in the page title
    And I follow "Workspace 101"
    Then I should see "Workspace 101" in the page title
    When I click on "Your Workspaces" in the totara menu
    Then I should see "Workspace 101" in the page title