@container @workspace @container_workspace @totara @totara_engage @engage @javascript
Feature: Unshare resources from workspace

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |

    And the following "workspaces" exist in "container_workspace" plugin:
      | name             | summary   | owner |
      | Test Workspace 1 | Worskpace | user1 |

  Scenario: Unshare resource from workspace
    Given I log in as "user1"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Test Workspace 1" "link" in the ".tui-workspaceMenu" "css_element"
    And I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"

    # Create the resource.
    And I click on "Contribute" "button"
    When I follow "Resource"
    And I set the field "Enter resource title" to "Test Article 1"
    And I activate the weka editor with css ".tui-engageArticleForm__description"
    And I set the weka editor to "New article"
    And I wait for the next second
    And I click on "Next" "button"
    And I wait for the next second
    When I click on "5 to 10 mins" "text"
    And I click on "Tag list" "button" in the ".tui-topicsSelector" "css_element"
    And I click on "Topic1" option in the dropdown menu
    And I click on "Done" "button"
    Then I should see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I log out

    When I log in as "user2"
    And I click on "Find Workspaces" in the totara menu
    And I follow "Test Workspace 1"
    And I click on "Join workspace" "button"
    When I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"
    Then I should see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Remove from Library"
    And I log out

    # Unshare the resource
    When I log in as "user1"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Test Workspace 1" "link" in the ".tui-workspaceMenu" "css_element"
    And I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"
    When I click on "Remove from Library" "button"
    Then I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"

  Scenario: member cannot remove the content shared but owner can remove the content shared
    Given I log in as "user2"
    And I click on "Find Workspaces" in the totara menu
    And I follow "Test Workspace 1"
    And I click on "Join workspace" "button"
    And I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"

    #Create the resource
    When I click on "Contribute" "button"
    And I follow "Resource"
    And I set the field "Enter resource title" to "Test Article 1"
    And I activate the weka editor with css ".tui-engageArticleForm__description"
    And I set the weka editor to "New article"
    And I wait for the next second
    And I click on "Next" "button"
    And I wait for the next second
    And I click on "5 to 10 mins" "text"
    And I click on "Tag list" "button" in the ".tui-topicsSelector" "css_element"
    And I click on "Topic1" option in the dropdown menu
    When I click on "Done" "button"
    Then I should see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Remove from Library"
    And I log out

    When I log in as "user1"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Test Workspace 1" "link" in the ".tui-workspaceMenu" "css_element"
    And I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"
    And I click on "Remove from Library" "button"
    Then I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"