@totara @javascript @block_totara_report_graph
Feature: Test the basic functionality of the Totara report graph block
  In order to test the Totara report graph block
  As an admin I add an instance
  And I configure it to display as I want

  Scenario: Test I can add and configure a Totara report graph block instance
    Given the following "users" exist:
      | username | firstname | country |
      | trainer1 | Trainer   | NZ      |
      | learner1 | Learner1  | NZ      |
      | learner2 | Learner2  | NZ      |
      | learner3 | Learner3  | US      |
      | learner4 | Learner4  | US      |
      | learner5 | Learner5  | AU      |
      | learner6 | Learner6  | CZ      |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname       | shortname             | source |
      | My user report | report_my_user_report | user   |

    When I log in as "admin"
    And I navigate to "General settings" node in "Site administration > Reports"
#    Can't run this on ChartJS, since behat can't look into the resulting chart elements
    And I set the field "Graph Library" to "SVGGraph"
    And I press "Save changes"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "My user report"
    Then I should see "Edit Report 'My user report'"

    When I switch to "Columns" tab
    And I delete the "User's Fullname (linked to profile with icon)" column from the report
    And I delete the "User Last Login" column from the report
    And I add the "User's Country" column to the report
    And I set aggregation for the "Username" column to "Count unique" in the report
    And I press "Save changes"
    Then I should see "Columns updated"

    When I switch to "Graph" tab
    And I set the following fields to these values:
      | Graph type | Pie |
      | Category   | User's Country |
    And I press "Save changes"
    Then I should see "Graph updated"

    When I click on "View This Report" "link"
    Then I should see "5 records shown" in the ".rb-record-count" "css_element"
    And I should see "Search by"
    And I should see "User's Fullname"
    And I should see "New Zealand"
    And I should see "United States"
    And I should see "Australia"
    And I should see "Czechia"
    And I should see "33.33%"
    And I should see "22.22%"
    And I should see "11.11%"

    When I click on "Dashboard" "link"
    And I press "Customise this page"
    And I add the "Report graph" block
    And I configure the "Report graph" block
    And I set the following fields to these values:
     | Override default block title | Yes                        |
     | Block title                  | My user report graph block |
     | Report                       | My user report             |
     | Graph height                 | 600                        |
    And I press "Save changes"
    Then I should see "My user report graph block"
    And I should see "View full report" in the "My user report graph block" "block"

  Scenario: Add Totara report graph block instance with creator user data
    Given the following "users" exist:
      | username | firstname | lastname |country |
      | trainer1 | Trainer1  | User     |NZ      |
      | trainer2 | Trainer2  | User     |DE      |
      | learner1 | Learner1  | User     |NZ      |
      | learner2 | Learner2  | User     |EN      |
      | learner3 | Learner3  | User     |US      |
      | learner4 | Learner4  | User     |US      |
      | learner5 | Learner5  | User     |AU      |
      | learner6 | Learner6  | User     |CZ      |
    And the following "system role assigns" exist:
      | user     | role    |
      | trainer1 | manager |
      | trainer2 | manager |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname       | shortname             | source |
      | My user report | report_my_user_report | user   |
    And I log in as "trainer1"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "Settings" "link" in the "My user report" "table_row"
    And I switch to "Columns" tab
    And I delete the "User's Fullname (linked to profile with icon)" column from the report
    And I delete the "User Last Login" column from the report
    And I add the "User's Country" column to the report
    And I set aggregation for the "Username" column to "Count unique" in the report
    And I press "Save changes"
    And I switch to "Graph" tab
    And I set the following fields to these values:
      | Graph type   | Column                          |
      | Category     | User's Country                  |
      | Data sources | Count unique values of Username |
    And I press "Save changes"
    And I switch to "Access" tab
    And I set the field "Only certain users can view this report (see below)" to "1"
    And I set the field "Manager" to "1"
    And I press "Save changes"
    And I switch to "Content" tab
    And I set the field "Show records matching all of the checked criteria below" to "1"
    And I set the field "id_user_enable" to "1"
    And I set the field "A user's own records" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    And I click on "Dashboard" "link"
    And I press "Manage dashboards"
    And I click on "My Learning" "link" in the "My Learning" "table_row"
    And I press "Blocks editing on"

    When I add the "Report graph" block
    And I configure the "Report graph" block
    And I set the following fields to these values:
      | Report                       | My user report      |
      | Show report data for user    | Me (Trainer1 User)  |
    And I press "Save changes"
    # Now there should be one NZ column with value 1, we cannot test it
    And I log out

    When I log in as "trainer2"
    # Now there should be one NZ column with value 1, we cannot test it
    And I press "Manage dashboards"
    And I click on "My Learning" "link" in the "My Learning" "table_row"
    And I press "Blocks editing on"
    And I configure the "My user report" block
    And the following fields match these values:
      | Report                       | My user report                 |
      | Show report data for user    | Previous user (Trainer1 User)  |
    And I press "Save changes"
    # Now there should be one NZ column with value 1, we cannot test it
    And I log out

    And I log in as "learner3"
    And I should see "My user report"
    # Now there should be one NZ column with value 1, we cannot test it
    And I log out

  Scenario: Add Totara report graph block instance with current user data
    Given the following "users" exist:
      | username | firstname | lastname |country |
      | trainer1 | Trainer1  | User     |NZ      |
      | trainer2 | Trainer2  | User     |DE      |
      | learner1 | Learner1  | User     |NZ      |
      | learner2 | Learner2  | User     |EN      |
      | learner3 | Learner3  | User     |US      |
      | learner4 | Learner4  | User     |US      |
      | learner5 | Learner5  | User     |AU      |
      | learner6 | Learner6  | User     |CZ      |
    And the following "system role assigns" exist:
      | user     | role    |
      | trainer1 | manager |
      | trainer2 | manager |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname       | shortname             | source |
      | My user report | report_my_user_report | user   |
    And I log in as "trainer1"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "Settings" "link" in the "My user report" "table_row"
    And I switch to "Columns" tab
    And I delete the "User's Fullname (linked to profile with icon)" column from the report
    And I delete the "User Last Login" column from the report
    And I add the "User's Country" column to the report
    And I set aggregation for the "Username" column to "Count unique" in the report
    And I press "Save changes"
    And I switch to "Graph" tab
    And I set the following fields to these values:
      | Graph type   | Column                          |
      | Category     | User's Country                  |
      | Data sources | Count unique values of Username |
    And I press "Save changes"
    And I switch to "Access" tab
    And I set the field "Only certain users can view this report (see below)" to "1"
    And I set the field "Manager" to "1"
    And I press "Save changes"
    And I switch to "Content" tab
    And I set the field "Show records matching all of the checked criteria below" to "1"
    And I set the field "id_user_enable" to "1"
    And I set the field "A user's own records" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    And I click on "Dashboard" "link"
    And I press "Manage dashboards"
    And I click on "My Learning" "link" in the "My Learning" "table_row"
    And I press "Blocks editing on"

    When I add the "Report graph" block
    And I configure the "Report graph" block
    And I set the following fields to these values:
      | Report                       | My user report             |
      | Show report data for user    | Current user viewing block |
    And I press "Save changes"
    # Now there should be one NZ column with value 1, we cannot test it
    And I log out

    When I log in as "trainer2"
    # Now there should be one NZ column with value 1, we cannot test it
    And I press "Manage dashboards"
    And I click on "My Learning" "link" in the "My Learning" "table_row"
    And I press "Blocks editing on"
    And I configure the "My user report" block
    And the following fields match these values:
      | Report                       | My user report             |
      | Show report data for user    | Current user viewing block |
    And I press "Save changes"
    # Now there should be one DE column with value 1, we cannot test it
    And I log out

    And I log in as "learner3"
    And I should not see "My user report"
    And I log out

  Scenario: Add Totara report graph block instance with guest user data
    Given the following "users" exist:
      | username | firstname | lastname |country |
      | trainer1 | Trainer1  | User     |NZ      |
      | trainer2 | Trainer2  | User     |DE      |
      | learner1 | Learner1  | User     |NZ      |
      | learner2 | Learner2  | User     |EN      |
      | learner3 | Learner3  | User     |US      |
      | learner4 | Learner4  | User     |US      |
      | learner5 | Learner5  | User     |AU      |
      | learner6 | Learner6  | User     |CZ      |
    And the following "system role assigns" exist:
      | user     | role    |
      | trainer1 | manager |
      | trainer2 | manager |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname       | shortname             | source |
      | My user report | report_my_user_report | user   |
    And I log in as "trainer1"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "Settings" "link" in the "My user report" "table_row"
    And I switch to "Columns" tab
    And I delete the "User's Fullname (linked to profile with icon)" column from the report
    And I delete the "User Last Login" column from the report
    And I add the "User's Country" column to the report
    And I set aggregation for the "Username" column to "Count unique" in the report
    And I press "Save changes"
    And I switch to "Graph" tab
    And I set the following fields to these values:
      | Graph type   | Column                          |
      | Category     | User's Country                  |
      | Data sources | Count unique values of Username |
    And I press "Save changes"
    And I switch to "Access" tab
    And I set the field "Only certain users can view this report (see below)" to "1"
    And I set the field "Manager" to "1"
    And I press "Save changes"
    And I switch to "Content" tab
    And I set the field "Show records matching all of the checked criteria below" to "1"
    And I set the field "id_user_enable" to "1"
    And I set the field "A user's own records" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"
    And I click on "Dashboard" "link"
    And I press "Manage dashboards"
    And I click on "My Learning" "link" in the "My Learning" "table_row"
    And I press "Blocks editing on"

    When I add the "Report graph" block
    And I configure the "Report graph" block
    And I set the following fields to these values:
      | Report                       | My user report     |
      | Show report data for user    | The guest account  |
    And I press "Save changes"
    # Now there should not be any chart data

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "Settings" "link" in the "My user report" "table_row"
    And I switch to "Access" tab
    And I set the field "All users can view this report" to "1"
    And I set the field "Manager" to "1"
    And I press "Save changes"
    And I click on "Dashboard" "link"
    And I should see "My user report"
    # Now there should be one no country column with value 1, we cannot test it
    And I log out

    And I log in as "learner3"
    And I should see "My user report"
    # Now there should be one no country column with value 1, we cannot test it
    And I log out
