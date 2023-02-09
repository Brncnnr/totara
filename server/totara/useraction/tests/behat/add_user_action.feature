@totara @totara_useraction @javascript
Feature: add user action

  Background:
    Given the following "cohorts" exist:
      | name            | idnumber |
      | Test audience   | AUD001   |
      | Test audience 2 | AUD002   |

  Scenario: Adding user action with valid data
    Given I log in as "admin"
    And I navigate to "Users > Scheduled user actions" in site administration
    Then I should see "Scheduled user actions"
    When I click on "Add action" "button"
    Then I should see "Add new user action"
    When I set the following fields to these values:
      | Action name     | Germany GDPR Purge                                                 |
      | Description     | Handles data purging per requirements of GDPR for users in Germany |
      | Duration        | 1                                                                  |
    Then I press "Add"
    Then I wait to be redirected
    Then I should see "Scheduled user actions"
    Then I should see the tui datatable contains:
      | Action name           |
      | Germany GDPR Purge    |

  Scenario: Add user action with valid data and audiences
    Given I log in as "admin"
    And I navigate to "Users > Scheduled user actions" in site administration
    Then I should see "Scheduled user actions"
    When I click on "Add action" "button"
    Then I should see "Add new user action"
    When I set the following fields to these values:
      | Action name     | Germany GDPR Purge                                                 |
      | Description     | Handles data purging per requirements of GDPR for users in Germany |
      | Duration        | 1                                                                  |
    # Add both audiences
    And I click on "All users" "button"
    And I click on "Audiences" option in the dropdown menu
    Then I should see "Select audiences"
    Then I click the select all checkbox in the tui datatable
    And I click on "Add" "button" in the ".tui-adder__actions > .tui-formBtnGroup" "css_element"
    # Remove audience 2
    Then I click on "[title='Remove \"Test audience 2\"']" "css_element"
    # Save
    Then I click on "Add" "button" in the ".tui-formBtnGroup" "css_element"
    Then I wait to be redirected
    Then I should see "Scheduled user actions"
    And I click on "Germany GDPR Purge details" "button" in the tui datatable row with "Germany GDPR Purge" "Action name"
    Then I should see "Test audience"
    And I should not see "Test audience 2"

  Scenario: Adding user action with invalid data
    Given I log in as "admin"
    And I navigate to "Users > Scheduled user actions" in site administration
    Then I should see "Scheduled user actions"
    When I click on "Add action" "button"
    Then I should see "Add new user action"
    # Duration can't be empty
    When I set the following fields to these values:
      | Action name     | Germany GDPR Purge                                                 |
      | Description     | Handles data purging per requirements of GDPR for users in Germany |
      | Duration        |                                                                    |
    Then I press "Add"
    Then I should see "Required"

    # Action name can't be empty
    When I set the following fields to these values:
      | Action name     |                                                                    |
      | Description     | Handles data purging per requirements of GDPR for users in Germany |
      | Duration        | 1                                                                  |
    Then I press "Add"
    Then I should see "Required"

