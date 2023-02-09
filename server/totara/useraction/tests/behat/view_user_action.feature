@totara @totara_useraction @javascript
Feature: View user actions

  Background:
    Given the following "cohorts" exist:
      | name            | idnumber |
      | Test audience   | AUD001   |
      | Test audience 2  | AUD002   |
      | Test audience 3  | AUD003   |
    And the following "scheduled rules" exist in "totara_useraction" plugin:
      | name                | description    | status | user_status | data_source     | duration_unit | duration_value | applies_to     |
      | Germany GDPR Purge  | German users   | 0      | SUSPENDED   | DATE_SUSPENDED  | MONTH         | 1              | AUD001, AUD003 |
      | France GDPR Purge   | French users   | 1      | SUSPENDED   | DATE_SUSPENDED  | YEAR          | 11             | ALL_USERS      |
      | Austria GDPR Purge  | Austrian users | 1      | SUSPENDED   | DATE_SUSPENDED  | DAY           | 8              | ALL_USERS      |
      | Mars GDPR Purge     | Martian users  | 1      | SUSPENDED   | DATE_SUSPENDED  | DAY           | 8              | ALL_USERS      |

  Scenario: Viewing user action list
    Given I log in as "admin"
    And I navigate to "Users > Scheduled user actions" in site administration
    Then I should see "Scheduled user actions"
    And I should see the tui datatable contains:
      | Action name           | Action type | Status   |
      | Mars GDPR Purge       | Delete user | Enabled  |
      | Austria GDPR Purge    | Delete user | Enabled  |
      | France GDPR Purge     | Delete user | Enabled  |
      | Germany GDPR Purge    | Delete user | Disabled |

    # Germany
    Then I click on "Germany GDPR Purge details" "button" in the tui datatable row with "Germany GDPR Purge" "Action name"
    # - Description
    And I should see "German users"
    # - Applies to
    And I should see "Test audience"
    And I should see "Test audience 3"
    And I should not see "Test audience 2"
    And I should not see "All users"
    # - User status
    And I should see "Suspended"
    # - Data source
    And I should see "Date suspended"
    # - Duration
    And I should see "1 month"
    And I click on "Germany GDPR Purge details" "button" in the tui datatable row with "Germany GDPR Purge" "Action name"

    # France
    Then I click on "France GDPR Purge details" "button" in the tui datatable row with "France GDPR Purge" "Action name"
    # - Description
    And I should see "French users"
    # - Applies to
    And I should see "All users"
    # - User status
    And I should see "Suspended"
    # - Data source
    And I should see "Date suspended"
    # - Duration
    And I should see "11 years"

