@totara @totara_useraction @javascript
Feature: View past actions report

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | u1       | User 1    | 1        | user1@example.com |
      | u2       | User 2    | 2        | user2@example.com |
      | u3       | User 3    | 3        | user3@example.com |
    And the following "scheduled rules" exist in "totara_useraction" plugin:
      | name   | status |
      | Rule 1 | true   |
      | Rule 2 | true   |
      | Rule 3 | false  |
    And the following "history entries" exist in "totara_useraction" plugin:
      | user | rule   | message              | created          | success |
      | u1   | Rule 1 | Heya                 | 2022-01-04T12:01 | yes     |
      | u2   | Rule 1 |                      | 2022-01-04T12:02 | no      |
      | u3   | Rule 1 | Nobody said anything | 2022-01-04T12:03 | yes     |
      | u1   | Rule 2 | Yep                  | 2022-01-04T12:04 | yes     |

  Scenario: Viewing the past actions report
    Given I log in as "admin"
    And I navigate to "Users > Scheduled user actions" in site administration
    And I click on "more" "button" in the tui datatable row with "Rule 1" "Action name"
    And I click on "Past actions report" option in the dropdown menu
    Then I should see "Scheduled user action report: Rule 1"
    And the "useraction_history" table should contain the following:
      | Date                   | Scheduled user action | User's Fullname | Action      | Success | Notes                |
      | 4 Jan 2022 at 12:01:00 | Rule 1                | User 1 1        | Delete user | Yes     | Heya                 |
      | 4 Jan 2022 at 12:02:00 | Rule 1                | User 2 2        | Delete user | No      |                      |
      | 4 Jan 2022 at 12:03:00 | Rule 1                | User 3 3        | Delete user | Yes     | Nobody said anything |

    When I press "Show more..."
    And I set the field "Yes" to "1"
    And I press exact "Search"
    And the "useraction_history" table should contain the following:
      | Date                   | Scheduled user action | User's Fullname | Action      | Success | Notes                |
      | 4 Jan 2022 at 12:01:00 | Rule 1                | User 1 1        | Delete user | Yes     | Heya                 |
      | 4 Jan 2022 at 12:03:00 | Rule 1                | User 3 3        | Delete user | Yes     | Nobody said anything |
    And the "useraction_history" table should not contain the following:
      | Date                   | Scheduled user action | User's Fullname | Action      | Success | Notes |
      | 4 Jan 2022 at 12:02:00 | Rule 1                | User 2 2        | Delete user | No      |       |

    When I navigate to "Users > Scheduled user actions" in site administration
    And I click on "more" "button" in the tui datatable row with "Rule 2" "Action name"
    And I click on "Past actions report" option in the dropdown menu
    Then I should see "Scheduled user action report: Rule 2"
    And the "useraction_history" table should contain the following:
      | Date                   | Scheduled user action | User's Fullname | Action      | Success | Notes |
      | 4 Jan 2022 at 12:04:00 | Rule 2                | User 1 1        | Delete user | Yes     | Yep   |
    And the "useraction_history" table should not contain the following:
      | Date                   | Scheduled user action | User's Fullname | Action      | Success | Notes |
      | 4 Jan 2022 at 12:02:00 | Rule 1                | User 2 2        | Delete user | No      |       |
