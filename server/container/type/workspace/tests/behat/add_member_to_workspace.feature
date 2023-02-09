@totara @container @container_workspace @engage
Feature: Add users to a workspace
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname | middlename | lastname | email             |
      | user_one   | User      | Jack       | One      | one@example.com   |
      | user_two   | User      | Jill       | Two      | two@example.com   |
      | user_three | User      | Jane       | Three    | three@example.com |
      | user_four  | User      | Joe        | Four     | four@example.com  |
    And the following "workspaces" exist in "container_workspace" plugin:
      | name          | owner    | summary           |
      | Workspace 101 | admin    | Workspace summary |
      | Workspace 102 | user_one | Workspace summary |
      | Workspace 103 | user_four | Workspace summary |

  @javascript
  Scenario: Workspace owner search for non member users.
    Given I am on a totara site
    And I log in as "user_four"
    When I click on "Your Workspaces" in the totara menu
    Then I should see "Workspace 103"
    And "Owner" "button" should exist
    And I click on "Owner" "button"
    And I click on "Add members" "link"
    And I should see "User One"
    And I should see "User Two"
    And I should see "User Three"
    And I set the field "Filter items by search" to "user one"
    Then I should not see "User Two"
    And I should not see "User Three"
    And I should see "User One"
    And I set the field "Filter items by search" to "two"
    Then I should not see "User One"
    And I should not see "User Three"
    And I should see "User Two"
    And I set the field "Filter items by search" to "three@example.com"
    Then I should not see "User One"
    And I should not see "User Three"
    And I should not see "User Two"

  @javascript
  Scenario: Workspace owner search for non member users with different fullnamedisplay configuration.
    Given I am on a totara site
    And the following config values are set as admin:
      | fullnamedisplay | middlename |
    And I log in as "user_four"
    When I click on "Your Workspaces" in the totara menu
    Then I should see "Workspace 103"
    And "Owner" "button" should exist
    When I click on "Owner" "button"
    And I click on "Add members" "link"
    Then I should see "Jack"
    And I should see "Jill"
    And I should see "Jane"
    And I should not see "User One"
    And I should not see "User Two"
    And I should not see "User Three"
    When I set the field "Filter items by search" to "user one"
    Then I should not see "Jack"
    And I should not see "Jill"
    And I should not see "Jane"
    When I set the field "Filter items by search" to "Jack"
    Then I should see "Jack"
    And I should not see "Jill"
    And I should not see "Jane"
    When I set the field "Filter items by search" to "Jane"
    Then I should not see "Jack"
    And I should not see "Jill"
    And I should see "Jane"

  @javascript
  Scenario: Add members should not be available if capability is removed.
    Given I log in as "admin"
    And I set the following system permissions of "Workspace Owner" role:
      | container/workspace:addmember | Prohibit |
    And I log out
    And I log in as "user_one"
    When I click on "Your Workspaces" in the totara menu
    Then I should see "Workspace 102"
    And "Owner" "button" should exist
    When I click on "Owner" "button"
    Then I should not see "Add members"

    # Admin should still see the option to add members
    When I log out
    And I log in as "admin"
    When I click on "Your Workspaces" in the totara menu
    Then I should see "Workspace 101"
    And "Owner" "button" should exist
    When I click on "Owner" "button"
    Then I should see "Add members"