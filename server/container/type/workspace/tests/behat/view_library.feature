@container @workspace @container_workspace @totara @totara_engage @engage @javascript
Feature: View library
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "users" exist:
      | username   | firstname | lastname | email               |
      | user_one   | User      | One      | one@example.com     |
      | user_two   | User      | Two      | two@example.com     |
      | user_three | User      | Three    | three@example.com   |

    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |

    And the following "workspaces" exist in "container_workspace" plugin:
      | name          | owner    | summary               |
      | Workspace 101 | user_one | This is workspace 101 |

  Scenario: View number of items on library page of workspace
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I follow "Workspace 101"
    When I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"
    Then I should see "0 items" in the ".tui-contributionBaseContent__counter" "css_element"
    And the following "articles" exist in "engage_article" plugin:
      | name           | username    | content | access  | topics |
      | Test Article 1 | user_one    | blah    | PUBLIC  | Topic1 |
    And I click on "Contribute" "button"
    And I click on "select an existing resource" "button"
    And I click the select all checkbox in the tui datatable
    When I confirm the tui confirmation modal
    Then I should see "1 item" in the ".tui-contributionBaseContent__counter" "css_element"

  Scenario: View number of resources on your library page
    Given I log in as "user_one"
    When I click on "Your Library" in the totara menu
    Then I should see "0 resources" in the ".tui-contributionBaseContent__counter" "css_element"
    And the following "articles" exist in "engage_article" plugin:
      | name           | username    | content | access  | topics |
      | Test Article 1 | user_one    | blah    | PUBLIC  | Topic1 |
    When I click on "Your Library" in the totara menu
    Then I should see "1 resource" in the ".tui-contributionBaseContent__counter" "css_element"

  Scenario: View member adder on workspace
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I follow "Workspace 101"
    When I click on "Owner" "button"
    Then I should see "Add members"
    And I should see "Edit workspace"
    And I should see "Transfer ownership"
    And I should see "Delete workspace"
    When I click on "Add members" "link"
    Then I should see "Items selected: 0" in the ".tui-adder__footer > .tui-adder__summary" "css_element"
    When I toggle the selection of all rows in the tui select table
    Then I should see "Items selected: 3" in the ".tui-adder__footer > .tui-adder__summary" "css_element"
