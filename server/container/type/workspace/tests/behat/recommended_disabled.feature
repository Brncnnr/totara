@container @workspace @container_workspace @totara @totara_engage @javascript @engage
Feature: Recommendations will not appear in workspaces when recommenders engine is disabled

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And I enable the "ml_recommender" advanced feature
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
    And the following "workspaces" exist in "container_workspace" plugin:
      | name                  | summary   | owner |
      | Test Workspace 1      | Workspace | user1 |
      | Recommended workspace | Workspace | user2 |
    And the following "user recommendations" exist in "ml_recommender" plugin:
      | component           | name                  | username |
      | container_workspace | Recommended workspace | admin    |

  Scenario: Disabling the recommender plugin will hide the recently viewed block from view mode
    Given I log in as "admin"
    And I click on "Your Workspaces" in the totara menu

    Then I should see "Recommended workspaces"
    And ".tui-recommendedSpaces" "css_element" should exist

    # Disable it
    When I disable the "ml_recommender" advanced feature
    And I click on "Your Workspaces" in the totara menu

    Then I should not see "Recommended workspaces"
    And ".tui-recommendedSpaces" "css_element" should not exist
