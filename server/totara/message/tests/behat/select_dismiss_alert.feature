@totara @totara_message @javascript @core_message
Feature: Select and dismiss alert message from the alerts report
  In order to review and dismiss alerts
  As a user
  I must be able to select and dismiss selected alert

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                   |
      | user1    | User      | One      | user1@example.com       |
      | manager1 | Manager   | One      | manager1@example.com    |
    And the following "alerts" exist in "totara_message" plugin:
      | fromuser | touser   | description |
      | user1    | manager1 | test alert  |

  Scenario: Trigger alert and confirm that they exists
    Given I log in as "manager1"
    And I click on "View all alerts" "link"
    And I should see "test alert"
    And I click on "Select message test alert" "checkbox"
    And I press "Dismiss"
    And I click on "Dismiss" "button" in the "Dismiss" "totaradialogue"
    And I should not see "test alert"
