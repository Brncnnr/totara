@totara @totara_engage @container @container_workspace @engage @javascript
Feature: Find workspaces
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | userone  | User      | One      | one@example.com |
      | usertwo  | User      | Two      | two@example.com |


    And the following "workspaces" exist in "container_workspace" plugin:
      | name          | summary | owner   |
      | Workspace 101 | Spaces  | userone |
      | Workspace 102 | Spaces  | userone |

  Scenario: Other user should be able to search workspaces
    Given I log in as "usertwo"
    When I click on "Find Workspaces" in the totara menu
    Then I should see "Workspace 101"
    And I should see "Workspace 102"
    When I set the field "Search workspaces" to "test"
    Then I should see "No workspaces match your search. Please try again." in the ".tui-spaceCardsGrid__emptyResult" "css_element"
    When  I click on "Clear this search term" "button"
    Then I should see "Workspace 101"
    And I should see "Workspace 102"