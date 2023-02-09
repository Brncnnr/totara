@totara @totara_engage @container @container_workspace @engage @javascript
Feature: View the workspace files page

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | user_one | User      | One      | one@example.com |
      | user_two | User      | Two      | two@example.com |
    And the following "workspaces" exist in "container_workspace" plugin:
      | name          | owner    | summary         | private |
      | Workspace 101 | user_one | This is summary | 1       |
    And the following "discussions" exist in "container_workspace" plugin:
      | workspace     | username | content       | files    |
      | Workspace 101 | user_one | My Discussion | file.txt |

  Scenario: Workspace owner sees files
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    Then I should see a weka attachment with the name "file.txt" in the ".tui-workspaceDiscussionCard" "css_element"
    When I follow "Browse files"
    Then I should see "file.txt" in the ".tui-workspaceFileTable" "css_element"
    When I click on "View discussion" "link" in the ".tui-workspaceFileTable__action" "css_element"
    Then I should see "My Discussion" in the ".tui-workspaceDiscussionCard" "css_element"
    And I should see a weka attachment with the name "file.txt" in the ".tui-workspaceDiscussionCard" "css_element"

  Scenario: Non-member cannot see files
    And I log in as "user_two"
    And I click on "Find Workspaces" in the totara menu
    And I follow "Workspace 101"
    Then I should not see "Browse files"