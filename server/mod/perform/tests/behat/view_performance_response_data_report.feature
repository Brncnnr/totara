@totara @perform @mod_perform @javascript
Feature: Test view performance response data report

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

  Scenario: A user with the global capability can view response data report
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
    And "View as report" "button" should exist in the ".tui-dropdown__menu--open" "css_element"
    And I click on "View as report" "button" in the ".tui-dropdown__menu--open" "css_element"
    And I wait for pending js
    And I click on "View" "button" in the ".tui-modal" "css_element"
    Then I should see "Performance data for User4 Last4"

  Scenario: A user with per-user capabilities can see view user response data report
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
    And "View as report" "button" should exist in the ".tui-dropdown__menu--open" "css_element"
    And I click on "View as report" "button" in the ".tui-dropdown__menu--open" "css_element"
    And I wait for pending js
    And I click on "View" "button" in the ".tui-modal" "css_element"
    Then I should see "Performance data for User2 Last2"

  Scenario: I can view question response data report
    Given I log in as "manager"

    # First check the optional questions activity.
    When I navigate to the mod perform response data report for "Simple optional questions activity" activity
    Then I should see "2" in the ".tui-elementResponseReportingActionCard__record-count" "css_element"
    And the following should exist in the "perform_response_element_by_activity" table:
      | Question text | Section title | Element type         | Responding relationships | Required | Reporting ID |
      | Question one  | Part one      | Text: Short response | 1                        | No       |              |
      | Question two  | Part one      | Text: Short response | 1                        | No       |              |

    When I click on "View as report" "button"
    Then I should see "View performance activity response report" in the tui modal
    And I should see "The report may take some time to load if it contains a large number of data." in the tui modal
    When I click on "Cancel" "button" in the ".tui-modal" "css_element"
    Then I should not see "View performance activity response report"

    When I click on "View as report" "button"
    And I click on "View" "button" in the ".tui-modal" "css_element"
    And I should see "Performance data for Simple optional questions activity"

  Scenario: Access Performance response data via user profile
    # Can see link on my own profile
    Given I log in as "sitemanager"
    And I am on profile page for user "sitemanager"
    When I click on "Performance activity response data" "link" in the ".block_totara_user_profile_category_development" "css_element"
    Then I should see "Performance activity response data" in the "#page h1" "css_element"
    # Do not see link on other user's profiles (even if they are allowed to see it themselves)
    Given I am on profile page for user "manager"
    Then I should not see "Performance activity response data"
    # Can't see own link when feature disabled
    Given I log out
    And I log in as "admin"
    And I navigate to "System information > Configure features > Perform settings" in site administration
    And I set the field "Enable Performance Activities" to "0"
    And I press "Save changes"
    And I log out
    And I log in as "sitemanager"
    When I am on profile page for user "sitemanager"
    Then I should not see "Performance activity response data"

  # Note managers aren't given permission by default, but there is an override in background for this feature
  Scenario: Able to access Performance response data with limited permission
    Given I log in as "manager"
    Given I am on profile page for user "manager"
    When I click on "Performance activity response data" "link" in the ".block_totara_user_profile_category_development" "css_element"
    Then I should see "Performance activity response data" in the "#page h1" "css_element"

  Scenario: Unable to access Performance response data without permission
    Given I log in as "user1"
    Given I am on profile page for user "user1"
    Then I should not see "Performance activity response data"
