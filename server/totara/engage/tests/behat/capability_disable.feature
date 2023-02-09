@totara_engage @totara @engage @javascript
Feature: Library cannot be seen when the capability is disabled.

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And I enable the "engage_resources" advanced feature
    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User      | One      | user1@test.com |
    And I log in as "admin"

  Scenario: Can see the library menu item when it's enabled
    When I set the following system permissions of "Authenticated User" role:
      | totara/engage:viewlibrary | Allow |
    And I log out
    And I log in as "user1"
    Then I should see "Your Library" in the totara menu

  Scenario: Cannot see the library menu item when it's disabled
    When I set the following system permissions of "Authenticated User" role:
      | totara/engage:viewlibrary | Prohibit |
    And I log out
    And I log in as "user1"
    Then I should not see "Your Library" in the totara menu