@totara @engage @container_workspace @core_container
Feature: Workspaces should be ordered correctly when applying filters
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | user_1   | User      | One      | one@example.com |
    Given the following "workspaces" exist in "container_workspace" plugin:
      | name        | owner  | summary       | public  |
      | workspace_a | user_1 | workspace one | 1       |

  @javascript
  Scenario: Workspaces should be sorted correctly when changing sort by filter
    Given I log in as "user_1"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Create a workspace" "button"
    And I set the field "Workspace name" to "workspace_b"
    And I click on "Submit" "button"
    And I click on "Find Workspaces" in the totara menu
    Then "workspace_b" "text" should appear before "workspace_a" "text"

    When I set the field "Sort by" to "A-Z"
    Then "workspace_a" "text" should appear before "workspace_b" "text"

    When I set the field "Sort by" to "Recent"
    Then "workspace_b" "text" should appear before "workspace_a" "text"