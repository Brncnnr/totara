@totara @totara_engage @container @container_workspace @engage
Feature: Workspace general feature
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email              |
      | user1    | First     | User     | first@example.com  |
      | user2    | Second    | User     | second@example.com |

  @javascript
  Scenario: Create a new workspace
    Given I am on a totara site
    And I log in as "user1"
    When I click on "Your Workspaces" in the totara menu
    Then I should see "You don't currently belong to any workspaces"
    And I click on "Create a workspace" "button"
    And I set the field "Workspace name" to "Workspace 101"
    And I activate the weka editor with css ".tui-workspaceForm__editor"
    And I type "Some description with \"quotes\". Tag <example@example.com> and test icon tag: <i class=\"fab fa-accessible-icon\"></i> stuff" in the weka editor
    And I wait for the next second
    When I click on "Submit" "button"
    Then I should see "Workspace 101"
    And I should see "Some description with \"quotes\". Tag <example@example.com> and test icon tag: <i class=\"fab fa-accessible-icon\"></i> stuff"
    And I should see "Members (1)"
    And I should see "Discuss"
    And I should see "Library"
    When I click on "Members (1)" "link"
    Then I should see "First User"

  @javascript
  Scenario: Join the workspace via find spaces
    Given I am on a totara site
    And I log in as "user1"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Create a workspace" "button"
    And I set the field "Workspace name" to "Workspace 101"
    And I click on "Submit" "button"
    And I log out
    And I log in as "user2"
    When I click on "Find Workspaces" in the totara menu
    Then I should see "Workspace 101"
    And I should see "Join"
    And I click on "Join workspace Workspace 101" "button"
    Then I should see "Joined"
    When I follow "Workspace 101"
    Then I should see "Members (2)"
    And I follow "Members (2)"
    Then I should see "First User"
    And I should see "Second User"

  @javascript
  Scenario: Delete the workspace
    Given I am on a totara site
    And I log in as "user1"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Create a workspace" "button"
    And I set the field "Workspace name" to "Workspace 101"
    And I click on "Submit" "button"
    And I click on "Owner" "button"
    And I should see "Delete workspace"
    And I click on "Delete workspace" "link"
    And I confirm the tui confirmation modal
    And I should see "The workspace \"Workspace 101\" was deleted successfully"

  @javascript
  Scenario: Edit the workspace
    Given I am on a totara site
    And I log in as "user1"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Create a workspace" "button"
    And I set the field "Workspace name" to "Workspace 101"
    When I click on "Submit" "button"
    Then I should see "Workspace 101"
    And I click on "Owner" "button"
    And I click on "Edit workspace" "link"
    Then I should see "Workspace 101"
    And I set the field "Workspace name" to "Workspace 102"
    And I activate the weka editor with css ".tui-workspaceForm__editor"
    And I type "Some description with \"quotes\". Tag <example@example.com> and test icon tag: <i class=\"fab fa-accessible-icon\"></i> stuff" in the weka editor
    And I wait for the next second
    When I click on "Submit" "button"
    Then I should see "Workspace 102"
    And I should not see "Workspace 101"
    And I should see "Some description with \"quotes\". Tag <example@example.com> and test icon tag: <i class=\"fab fa-accessible-icon\"></i> stuff"