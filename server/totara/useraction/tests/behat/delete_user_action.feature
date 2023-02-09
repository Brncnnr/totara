@totara @totara_useraction @javascript
Feature: Delete user action

  Background:
    Given the following "scheduled rules" exist in "totara_useraction" plugin:
      | name              | description  | status |
      | Germany GDPR      | German users |        |
      | France GDPR Purge | French users | true   |

  Scenario: Deleting user action
    Given I log in as "admin"
    And I navigate to "Users > Scheduled user actions" in site administration
    Then I should see "Scheduled user actions"
    And I click on "more" "button" in the tui datatable row with "Germany GDPR" "Action name"
    And I click on "Delete" option in the dropdown menu
    Then I should see "Delete scheduled action"
    Then I confirm the tui confirmation modal
    And I should see the tui datatable contains:
      | Action name           | Action type | Status   |
      | France GDPR Purge     | Delete user | Enabled  |
    And I should not see "Germany GDPR"