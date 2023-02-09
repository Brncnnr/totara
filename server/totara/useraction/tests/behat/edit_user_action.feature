@totara @totara_useraction @javascript
Feature: edit user action

  Background:
    Given the following "scheduled rules" exist in "totara_useraction" plugin:
      | name              | description  | status |
      | Germany GDPR      | German users | false  |
      | France GDPR Purge | French users | true   |

  Scenario: Editing user action with valid data
    Given I log in as "admin"
    And I navigate to "Users > Scheduled user actions" in site administration
    Then I should see "Scheduled user actions"
    And I click on "more" "button" in the tui datatable row with "Germany GDPR" "Action name"
    And I click on "Edit" option in the dropdown menu
    Then I should see "Edit user action"

    When I set the following fields to these values:
      | Action name     | Germany GDPR Purge |
    Then I press "Save"
    Then I wait to be redirected
    Then I should see "Scheduled user actions"
    Then I should see the tui datatable contains:
      | Action name           |
      | Germany GDPR Purge    |
      | France GDPR Purge     |
