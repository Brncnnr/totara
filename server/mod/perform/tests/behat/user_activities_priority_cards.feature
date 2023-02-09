@totara @perform @mod_perform @javascript @vuejs
Feature: Test priority cards on Performance activities

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname | email                  |
      | user1     | User      | One      | user.one@example.com   |
      | user2     | User      | Two      | user.two@example.com   |
      | user3     | User      | Three    | user.three@example.com |
      | user4     | User      | Four     | user.four@example.com  |
      | manager1  | manager   | One      | manager1@example.com   |
      | manager2  | manager   | Two      | manager2@example.com   |
      | appraiser | appraiser | User     | appraiser@example.com  |
    And the following job assignments exist:
      | user     | idnumber | manager  | managerjaidnumber | appraiser |
      | manager1 | manage1  |          |                   |           |
      | manager1 | manage2  |          |                   |           |
      | manager2 | manage   |          |                   |           |
      | user1    | job      |          |                   |           |
      | user2    | job      | manager1 | manage1           |           |
      | user3    | job      |          |                   | appraiser |
      | user4    | job      | manager1 | manage1           | appraiser |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name          | activity_status | subject_username | subject_is_participating | other_participant_username | third_participant_username | number_repeated_instances | relationships_can_answer |
      | 3 participants         | 1               | user1            | true                     |                            |                            | 1                         |                          |
      | 3 participants         | 1               | user2            | true                     | manager1                   |                            | 1                         |                          |
      | 3 participants         | 1               | user3            | true                     | appraiser                  |                            | 1                         |                          |
      | 3 participants         | 1               | user4            | true                     | manager1                   | appraiser                  | 1                         |                          |
      | 2 participants         | 1               | user1            | true                     |                            |                            | 1                         |                          |
      | 2 participants         | 1               | user2            | true                     | manager1                   |                            | 1                         |                          |
      | 2 participants         | 1               | user3            | true                     | appraiser                  |                            | 1                         |                          |
      | 2 participants         | 1               | user4            | true                     | manager1                   | appraiser                  | 1                         |                          |
      | 2 participants         | 1               | manager1         | true                     | manager1                   | appraiser                  | 1                         | subject, manager         |
      | view only appraiser    | 1               | manager1         | true                     | manager1                   | appraiser                  | 1                         | subject, manager         |

  Scenario: close action on participant management reports
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link" in the tui datatable row with "3 participants" "Name"
    Then the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject name        | Instance number | Participants |
      | User Four           | 1               | 3            |
      | User One            | 1               | 1            |
      | User Three          | 1               | 2            |
      | User Two            | 1               | 2            |

    #subject instance open/close action
    And I click on "Actions" "button" in the "User Three" "table_row"
    And I click on "Close" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Close subject instance" in the tui modal
    And I confirm the tui confirmation modal
    Then I should see "Subject instance and all its participant instances closed"

    Then I click on "User Three" "link" in the "User Three" "table_row"
    Then I click on "Log in as" "link"
    Then I click on "Continue" "button"

    Then I click on "Develop" "link"
    Then I click on "Activities" "link"
    Then I should not see "3 participants" in the ".tui-performUserActivityList__priority" "css_element"

  Scenario: close action on view-only participant management reports for the manager
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link" in the tui datatable row with "view only appraiser" "Name"
    Then the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject's full name | Instance number | Participants |
      | manager One         | 1               | 3 instances  |
    And "Close" "button" should exist in the "manager One" "table_row"

    When I click on "Actions" "button" in the "manager One" "table_row"
    And I click on "Close" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Close subject instance" in the tui modal
    And I confirm the tui confirmation modal
    Then I should see "Subject instance and all its participant instances closed"

    Then I click on "manager One" "link" in the "manager One" "table_row"
    Then I click on "Log in as" "link"
    Then I click on "Continue" "button"

    Then I click on "Develop" "link"
    Then I click on "Activities" "link"
    Then I click on "As Manager" "link"
    Then I should not see "view only appraiser" in the ".tui-performUserActivityList__priority" "css_element"
