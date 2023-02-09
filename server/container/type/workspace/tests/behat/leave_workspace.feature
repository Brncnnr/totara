@totara_engage @container_workspace @container @engage
Feature: User leaves a workspace that user is a member of
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email           |
      | user_one | User      | One      | one@example.com |
      | user_two | User      | Two      | two@example.com |

  @javascript
  Scenario: User leaves the workspace
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Create a workspace" "button"
    And I set the field "Workspace name" to "Workspace 101"
    And I click on "Submit" "button"
    And I log out
    And I log in as "user_two"
    And I click on "Find Workspaces" in the totara menu
    And I follow "Workspace 101"
    When I click on "Join workspace" "button"
    Then I should see "Joined"
    And I click on "Joined" "button"
    And I should see "Leave workspace"
    When I click on "Leave workspace" "link"
    Then I should see "Are you sure you want to leave this workspace?"
    And I click on "Leave" "button"
    And I should see "Join workspace"

  @javascript
  Scenario: User leaves the workspace from the find workspaces page
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Create a workspace" "button"
    And I set the field "Workspace name" to "Workspace 101"
    And I click on "Submit" "button"
    And I log out
    And I log in as "user_two"
    And I click on "Find Workspaces" in the totara menu
    When I click on "Join workspace" "button"
    Then I should see "Joined"
    And I reload the page
    And I click on "Joined" "button"
    And I should see "Leave workspace"
    When I click on "Leave workspace" "link"
    Then I should see "Are you sure you want to leave this workspace?"
    And I click on "Leave" "button"
    Then I should not see "Are you sure you want to leave this workspace?"
    And I should see "Join"