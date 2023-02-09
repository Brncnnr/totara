@container @container_workspace @totara @engage
Feature: View logs page for workspace
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email           |
      | user_one | User      | One      | one@example.com |
    And the following "workspaces" exist in "container_workspace" plugin:
      | name          | owner    | summary               |
      | Workspace 101 | user_one | This is workspace 101 |

  @javascript
  Scenario: Admin user is able to see logs in workspace context
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I should see "Workspace 101"
    And I log out
    And I log in as "admin"
    And I navigate to "Server > Logs" in site administration
    And I set the field "Select a course" to "Workspace 101"
    When I click on "Get these logs" "button"
    # Here we are trying to make sure that we are still in the log page.
    Then "Get these logs" "button" should exist
    And I should see "User full name"
