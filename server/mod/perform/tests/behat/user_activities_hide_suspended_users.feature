@totara @perform @mod_perform @javascript @vuejs
Feature: Hide activity entries for suspended subjects

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email           | suspended |
      | subject1 | Subject   | One      | s1@example.com  | 1         |
      | subject2 | Subject   | Two      | s2@example.com  | 0         |
      | manager  | Manager   | User     | mgr@example.com | 0         |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name | activity_status | subject_username | subject_is_participating | other_participant_username | third_participant_username |
      | Test Activity | 1               | subject1         | true                     | manager                    | manager                    |
      | Test Activity | 1               | subject2         | true                     | manager                    | manager                    |

  Scenario: Toggle perform_hide_suspended_users setting
    # perform_hide_suspended_users setting disabled with one suspended user
    When I log in as "admin"
    And the following config values are set as admin:
      | perform_hide_suspended_users | 0 |
    And I log out
    And I log in as "manager"
    When I navigate to the outstanding perform activities list page
    Then I should see "As Manager"
    And I should see "As Appraiser"

    When I click on "As Manager" "link"
    # First row
    Then I should see "Test Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Subject Two" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    # Second row
    And I should see "Test Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Subject One" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-subject" "css_element"

    When I click on "As Appraiser" "link"
    # First row
    Then I should see "Test Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Subject Two" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    # Second row
    And I should see "Test Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Subject One" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-subject" "css_element"

    # perform_hide_suspended_users setting enabled with one suspended user
    When I log out
    And I log in as "admin"
    And the following config values are set as admin:
      | perform_hide_suspended_users | 1 |
    And I log out
    And I log in as "manager"
    When I navigate to the outstanding perform activities list page
    Then I should see "As Manager"
    And I should see "As Appraiser"

    When I click on "As Manager" "link"
    Then I should see "Test Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Subject Two" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should not see "Subject One"

    When I click on "As Appraiser" "link"
    Then I should see "Test Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Subject Two" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should not see "Subject One"

    # perform_hide_suspended_users setting enabled with two suspended users
    When I log out
    And I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I set the field "user-deleted" to "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I follow "Manage login of Subject Two"
    And I set the "Choose" Totara form field to "Suspend user account"
    And I press "Update"
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Username | User's Email   | User Status |
      | Subject One     | subject1 | s1@example.com | Suspended   |
      | Subject Two     | subject2 | s2@example.com | Suspended   |

    When I log out
    And I log in as "manager"
    When I navigate to the outstanding perform activities list page
    Then I should not see "As Manager"
    And I should not see "As Appraiser"

    # perform_hide_suspended_users setting disabled with two suspended users
    When I log out
    And I log in as "admin"
    And the following config values are set as admin:
      | perform_hide_suspended_users | 0 |
    And I log out
    And I log in as "manager"
    When I navigate to the outstanding perform activities list page
    Then I should see "As Manager"
    And I should see "As Appraiser"

    When I click on "As Manager" "link"
    # First row
    Then I should see "Test Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Subject Two" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    # Second row
    And I should see "Test Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Subject One" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-subject" "css_element"

    When I click on "As Appraiser" "link"
    # First row
    Then I should see "Test Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Subject Two" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    # Second row
    And I should see "Test Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Subject One" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-subject" "css_element"
