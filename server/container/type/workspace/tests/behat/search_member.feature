@totara @totara_engage @container @container_workspace @engage @javascript
Feature: Search for members in workspaces
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username  | firstname | lastname | email             |
      | userone   | User      | One      | one@example.com   |

    And the following "workspaces" exist in "container_workspace" plugin:
      | name          | summary | owner   |
      | Workspace 101 | Spaces  | userone |

  Scenario: Other user should be able to see workspaces
    Given I log in as "userone"
    And I click on "Find Workspaces" in the totara menu
    And I follow "Workspace 101"
    And I click on "Members (1)" "link" in the ".tui-tabs__tabs" "css_element"
    And I set the field "Search members" to "test"
    Then I should see "No members match your search. Please try again." in the ".tui-workspaceMembersTab__message" "css_element"
    When I click on "Clear this search term" "button"
    Then I should see "User One" in the ".tui-miniProfileCard__row-link" "css_element"