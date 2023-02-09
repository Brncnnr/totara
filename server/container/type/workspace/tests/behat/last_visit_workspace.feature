@totara @engage @container_workspace @core_container
Feature: Last visit workspace should not redirect user to a course
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | userone  | User      | One      | one@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | c101     | c101      | topics |
    And the following "course enrolments" exist:
      | user    | course | role    |
      | userone | c101   | student |

  @javascript
  Scenario: Normal user should be navigated to the last visted workspace
    Given I log in as "userone"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Create a workspace" "button"
    And I set the field "Workspace name" to "Workspace 101"
    When I click on "Submit" "button"
    Then I should see "Workspace 101"
    And I am on "c101" course homepage
    When I click on "Your Workspaces" in the totara menu
    Then I should see "Workspace 101"
    And I should not see "c101"