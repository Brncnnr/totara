@totara @perform @mod_perform @javascript @vuejs
Feature: Hide incomplete responses for participants who have not provided any response to any question on a closed instance.

  Background:
    Given the following "users" exist:
      | username          | firstname | lastname | email                              |
      | john              | John      | One      | john.one@example.com               |
      | david             | David     | Two      | david.two@example.com              |
      | harry             | Harry     | Three    | harry.three@example.com            |
      | jerry             | Jerry     | five     | jerry.five@example.com             |
      | manager-appraiser | combined  | Three    | manager-appraiser.four@example.com |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name                 | subject_username | subject_is_participating | other_participant_username |
      | John is participating subject | john             | true                     | david                      |
      | David is subject              | david            | true                     | john                       |
      | John is not participating     | john             | true                     | david                      |
    And the following "subject instances with single user manager-appraiser" exist in "mod_perform" plugin:
      | activity_name                 | subject_username | manager_appraiser_username |
      | single user manager-appraiser | john             | manager-appraiser          |

  Scenario: Test user activities default with "Hide incomplete responses for closed instances" setting off
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link" in the tui datatable row with "single user manager-appraiser" "Name"

    When I click on "Close all instances" "button"
    And I should see "Close all instances" in the tui modal
    And I should see "This will close all the subject instances that are currently open to prevent any further submission of responses from all participants, regardless of their progress." in the tui modal
    Then I confirm the tui confirmation modal
    And I should see "A task has been scheduled to close all instances." in the tui success notification toast

    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link" in the tui datatable row with "David is subject" "Name"
    And I click on "Close all instances" "button"
    And I confirm the tui confirmation modal

    And I run the adhoc scheduled tasks "mod_perform\task\close_activity_subject_instances_task"
    And I log out

    And I log in as "john"
    When I navigate to the outstanding perform activities list page
    # First row
    Then I should see "single user manager-appraiser" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not submitted" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not submitted" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    # Second row
    And I should see "John is not participating" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Third row
    And I should see "John is participating subject" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    Then I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name           | Section progress     |
      | Subject              | You            | Not submitted Closed |
      | Manager              | combined Three | Not submitted Closed |
      | Appraiser            | combined Three | Not submitted Closed |
    And I close the tui modal

    When I click on "As Manager" "link"
    And I click on "View details" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    Then I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name      | Section progress     |
      | Subject              | David Two | Not submitted Closed |
      | Manager              | You       | Not submitted Closed |

  Scenario: Test user activities with "Hide incomplete responses for closed instances" setting on
    Given I log in as "admin"
    And the following config values are set as admin:
      | perform_hide_incomplete_responses_closed_instances | 1 |
    And I navigate to the manage perform activities page

    And I click on "Manage participation" "link" in the tui datatable row with "single user manager-appraiser" "Name"
    When I click on "Close all instances" "button"
    And I should see "Close all instances" in the tui modal
    And I should see "This will close all the subject instances that are currently open to prevent any further submission of responses from all participants, regardless of their progress." in the tui modal
    Then I confirm the tui confirmation modal
    And I should see "A task has been scheduled to close all instances." in the tui success notification toast

    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link" in the tui datatable row with "David is subject" "Name"
    And I click on "Close all instances" "button"
    And I confirm the tui confirmation modal

    And I run the adhoc scheduled tasks "mod_perform\task\close_activity_subject_instances_task"
    And I log out

    And I log in as "john"
    When I navigate to the outstanding perform activities list page

    And I wait "30" seconds

    # First row
    Then I should see "single user manager-appraiser" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not submitted" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not submitted" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "John is not participating" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Third row
    And I should see "John is participating subject" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    Then I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name | Section progress     |
      | Subject              | You  | Not submitted Closed |
    And I should not see "Manager" in the ".tui-performUserActivityListSection:nth-child(1)" "css_element"
    And I should not see "Appraiser" in the ".tui-performUserActivityListSection:nth-child(1)" "css_element"
    And I should not see "combined Three" in the ".tui-performUserActivityListSection:nth-child(1)" "css_element"
    And I close the tui modal

    When I click on "As Manager" "link"
    And I click on "View details" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    Then I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name | Section progress     |
      | Manager              | You  | Not submitted Closed |
    And I should not see "Subject" in the ".tui-performUserActivityListSection:nth-child(1)" "css_element"
    And I should not see "David Two" in the ".tui-performUserActivityListSection:nth-child(1)" "css_element"