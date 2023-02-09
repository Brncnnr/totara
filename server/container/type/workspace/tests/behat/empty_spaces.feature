@totara @totara_engage @container @container_workspace @engage
Feature: Empty spaces page
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

    And the following "user recommendations" exist in "ml_recommender" plugin:
      | component           | name          | username |
      | container_workspace | Workspace 101 | usertwo  |

  @javascript
  Scenario: Other user should be able to see recommended spaces
    Given I log in as "usertwo"
    When I click on "Your Workspaces" in the totara menu
    Then I should see "Workspace 101"