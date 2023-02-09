@totara @perform @mod_perform @javascript
Feature: Test exporting performance response data

  Background:
    Given the following "users" exist:
      | username    | firstname   | lastname | email                   |
      | user1       | User1       | Last1    | user1@example.com       |
      | user2       | User2       | Last2    | user2@example.com       |
      | user3       | User3       | Last3    | user3@example.com       |
      | user4       | User4       | Last4    | user4@example.com       |
      | user5       | User5       | Last5    | user5@example.com       |
      | manager     | manager     | user     | manager.one@example.com |
      | sitemanager | sitemanager | user     | sitemanager@example.com |
    And the following "role assigns" exist:
      | user        | role    | contextlevel | reference |
      | sitemanager | manager | System       |           |
    And the following job assignments exist:
      | user  | manager | appraiser |
      | user1 | manager |           |
      | user2 | manager |           |
      | user3 |         | manager   |
    And the following "permission overrides" exist:
      | capability                                   | permission | role         | contextlevel | reference |
      | mod/perform:report_on_subject_responses      | Allow      | staffmanager | System       |           |
      | mod/perform:report_on_all_subjects_responses | Allow      | manager      | System       |           |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name                      | subject_username | subject_is_participating | include_questions | include_required_questions | activity_status |
      | Simple optional questions activity | user1            | true                     | true              |                            | Active          |
      | Simple required questions activity | user1            | true                     | true              | true                       | Active          |
      | Simple activity                    | user2            | true                     | true              | true                       | Active          |
      | Simple activity                    | user4            | true                     | true              | true                       | Active          |

  Scenario: A user with the global capability can export response data
    Given I log in as "sitemanager"
    #    And I toggle open the admin quick access menu
    #    Then I should see "Performance activity response data" in the admin quick access menu
    And I navigate to "Performance activities > Performance activity response data" in site administration
    And I switch to "Browse records by user" tab
    Then I should see "User1"
    And I should see "User4"
    And I should see "User2"
    And I should not see "User3"
    And I should not see "User5"

    And I click on "Actions" "button" in the "User4" "table_row"
    And "Export as Excel" "button" should exist in the ".tui-dropdown__menu--open" "css_element"
    When I click on "Export as Excel" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Export performance response records" in the tui modal
    And I should see "The selected records will be exported to Excel" in the tui modal
    When I click on "Cancel" "button" in the ".tui-modal" "css_element"
    Then I should not see "Export performance response records"

    When I click on "Actions" "button" in the "User2" "table_row"
    And "Export as CSV" "button" should exist in the ".tui-dropdown__menu--open" "css_element"
    And I click on "Export as CSV" "button" in the ".tui-dropdown__menu--open" "css_element"
    And I wait for pending js
    And I click on "Export" "button" in the ".tui-modal" "css_element"
    Then I should see "\"Activity name\",\"Subject name\",\"Participant name\",\"Participant relationship to subject\",\"Participant email address\",\"Reporting ID\",\"Element type\",\"Element text\",\"Element response\",\"Date section submitted\",\"Parent element type\",\"Parent element text\",\"Review type\",\"Review item name\",\"Selected by\",\"Date selected\""

  Scenario: A user with per-user capabilities can see export user response data
    Given I log in as "manager"
    When I am on "Team" page
    And I click on "view or export" "link"
    And I switch to "Browse records by user" tab
    Then I should see "Subject users: 2 records shown"
    And I should see "User1"
    And I should see "User2"
    And I should not see "User3"
    And I should not see "User4"
    And I should not see "User5"

    When I click on "Actions" "button" in the "User2" "table_row"
    And "Export as Excel" "button" should exist in the ".tui-dropdown__menu--open" "css_element"
    And I click on "Export as Excel" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Export performance response records" in the tui modal
    And I should see "The selected records will be exported to Excel" in the tui modal
    When I click on "Cancel" "button" in the ".tui-modal" "css_element"
    Then I should not see "Export performance response records"

    When I click on "Actions" "button" in the "User2" "table_row"
    Then "Export as CSV" "button" should exist in the ".tui-dropdown__menu--open" "css_element"
    And I click on "Export as CSV" "button" in the ".tui-dropdown__menu--open" "css_element"
    And I wait for pending js
    And I click on "Export" "button" in the ".tui-modal" "css_element"
    And I should see "\"Activity name\",\"Subject name\",\"Participant name\",\"Participant relationship to subject\",\"Participant email address\",\"Reporting ID\",\"Element type\",\"Element text\",\"Element response\",\"Date section submitted\",\"Parent element type\",\"Parent element text\",\"Review type\",\"Review item name\",\"Selected by\",\"Date selected\""

  Scenario: I can export question response data
    Given I log in as "manager"

    # First check the optional questions activity.
    When I navigate to the mod perform response data report for "Simple optional questions activity" activity
    Then I should see "2" in the ".tui-elementResponseReportingActionCard__record-count" "css_element"
    And the following should exist in the "perform_response_element_by_activity" table:
      | Question text | Section title | Element type         | Responding relationships | Required | Reporting ID |
      | Question one  | Part one      | Text: Short response | 1                        | No       |              |
      | Question two  | Part one      | Text: Short response | 1                        | No       |              |

    When I click on "Export" "button"
    Then "Excel" "button" should exist in the ".tui-dropdown__menu--open" "css_element"
    And I click on "Excel" "button" in the ".tui-dropdown__menu--open" "css_element"
    And I should see "Export performance response records" in the tui modal
    And I should see "The selected records will be exported to Excel" in the tui modal
    When I click on "Cancel" "button" in the ".tui-modal" "css_element"
    Then I should not see "Export performance response records"

    When I click on "Export" "button"
    And "CSV" "button" should exist in the ".tui-dropdown__menu--open" "css_element"
    And I click on "CSV" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Export performance response records" in the tui modal
    And I should see "The selected records will be exported to CSV" in the tui modal
    And I click on "Export" "button" in the ".tui-modal" "css_element"
    And I should see "\"Activity name\",\"Subject name\",\"Participant name\",\"Participant relationship to subject\",\"Participant email address\",\"Reporting ID\",\"Element type\",\"Element text\",\"Element response\",\"Date section submitted\",\"Parent element type\",\"Parent element text\",\"Review type\",\"Review item name\",\"Selected by\",\"Date selected\""

