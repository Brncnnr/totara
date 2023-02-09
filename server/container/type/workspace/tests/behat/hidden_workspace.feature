@totara @engage @container_workspace @container @javascript
Feature: Hidden workspace workflow
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | user_one | User      | One      | one@example.com |
      | user_two | User      | Two      | two@example.com |

  Scenario: Create hidden workspace
    Given I am on a totara site
    And I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Create a workspace" "button"
    And I set the field "Workspace name" to "This is hidden workspace"
    And I should not see "Hide this workspace from non-members"
    When I click on "Private" "text" in the ".tui-radioGroup" "css_element"
    Then I should see "Hide this workspace from non-members"
    And I click on "Hide this workspace from non-members" "text" in the ".tui-checkbox" "css_element"
    When I click on "Submit" "button"
    Then I should see "This is hidden workspace"
    And I should see "Hidden workspace"

  Scenario: User should not be able to see hidden workspaces
    Given the following "workspaces" exist in "container_workspace" plugin:
      | name       | owner    | summary              | private | hidden |
      | Hidden one | user_one | Hidden workspace one | 1       | 1      |
      | Hidden two | user_two | Hidden workspace two | 1       | 1      |

    # Check if admin is able to see hidden workspaces or not.
    And I log in as "admin"
    When I click on "Find Workspaces" in the totara menu
    Then I should see "Hidden one"
    And I should see "Hidden two"
    And I log out

    # User 2 should see their own hidden workspace but not the other
    When I log in as "user_two"
    And I click on "Find Workspaces" in the totara menu
    Then I should not see "Hidden one"
    And I should see "Hidden two"
