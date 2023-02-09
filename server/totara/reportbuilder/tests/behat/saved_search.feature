@totara @totara_reportbuilder @javascript
Feature: Test report builder saved search

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname        | lastname | email                 |
      | user1    | User1-firstname  | Test     | user1@example.com     |
      | user2    | User2-firstname  | Test     | user2@example.com     |
      | user3    | User3-firstname  | Test     | user3@example.com     |
      | user4    | User4-firstname  | Test     | user4@example.com     |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname             | shortname    | source |
      | Custom user report 1 | report_user1 | user   |
      | Custom user report 2 | report_user2 | user   |
    And I log in as "admin"
    When I navigate to my "Custom user report 1" report
    And I press "Edit this report"
    Then I should see "Edit Report 'Custom user report 1'"
    When I switch to "Access" tab
    And I set the following fields to these values:
      | Authenticated User | 1 |
    And I press "Save changes"
    Then I should see "Report Updated"

    When I navigate to my "Custom user report 2" report
    And I press "Edit this report"
    Then I should see "Edit Report 'Custom user report 2'"
    When I switch to "Access" tab
    And I set the following fields to these values:
      | Authenticated User | 1 |
    And I press "Save changes"
    Then I should see "Report Updated"
    And I log out

  Scenario: I can delete a saved search
    Given I log in as "admin"
    When I navigate to my "Custom user report 1" report
    And I set the field "user-fullname" to "Search 1"
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the field "Search Name" to "My search 1"
    And I press "Save changes"
    Then the "sid" select box should contain "My search 1"

    When I follow "Manage your saved searches"
    And I click on "Delete" "link" in the "My search 1" "table_row"
    Then I should see "Are you sure you want to delete this saved search 'My search 1'?"
    And I press "Continue"
    Then I should not see "My search 1"
    And I should see "This report does not have any saved searches."

  Scenario: I can delete a saved search that is being used for a scheduled report
    # Create a saved search.
    Given I log in as "admin"
    When I navigate to my "Custom user report 1" report
    And I set the field "user-fullname" to "Search 1"
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the field "Search Name" to "My search 1"
    And I click on "Shared" "radio"
    And I press "Save changes"
    Then the "sid" select box should contain "My search 1"

    # Create a scheduled report that doesn't use the saved search.
    When I click on "Reports" in the totara menu
    And I select "Custom user report 1" from the "addanewscheduledreport[reportid]" singleselect
    And I press "Add scheduled report"
    And I set the field "Data" to "All data"
    And I set the field "schedulegroup[frequency]" to "Daily"
    And I set the field "schedulegroup[daily]" to "01:00"
    And I set the field "Export" to "CSV"
    And I press "Save changes"
    Then I should see "All data" in the "Daily at 01:00 AM" "table_row"

    # Create a couple of scheduled reports that use the saved search.
    When I click on "Reports" in the totara menu
    And I select "Custom user report 1" from the "addanewscheduledreport[reportid]" singleselect
    And I press "Add scheduled report"
    And I set the field "Data" to "My search 1"
    And I set the field "schedulegroup[frequency]" to "Daily"
    And I set the field "schedulegroup[daily]" to "02:00"
    And I set the field "Export" to "CSV"
    And I press "Save changes"
    Then I should see "Custom user report 1" in the "Daily at 02:00 AM" "table_row"

    When I select "Custom user report 1" from the "addanewscheduledreport[reportid]" singleselect
    And I press "Add scheduled report"
    And I set the field "Data" to "My search 1"
    And I set the field "schedulegroup[frequency]" to "Daily"
    And I set the field "schedulegroup[daily]" to "03:00"
    And I set the field "Export" to "Excel"
    And I press "Save changes"
    Then I should see "Custom user report 1" in the "Daily at 03:00 AM" "table_row"
    And I log out

    # Create a scheduled report as another user using the same saved search.
    When I log in as "user1"
    And I click on "Reports" in the totara menu
    And I select "Custom user report 1" from the "addanewscheduledreport[reportid]" singleselect
    And I press "Add scheduled report"
    And I set the field "Data" to "My search 1"
    And I set the field "schedulegroup[frequency]" to "Daily"
    And I set the field "schedulegroup[daily]" to "04:00"
    And I set the field "Export" to "ODS"
    And I press "Save changes"
    Then I should see "Custom user report 1" in the "Daily at 04:00 AM" "table_row"
    And I log out

    # Delete the search.
    When I log in as "admin"
    And I navigate to my "Custom user report 1" report
    And I follow "Manage your saved searches"
    And I click on "Delete" "link" in the "My search 1" "table_row"
    Then I should see "This saved search is currently being used by 3 scheduled reports. Deleting it will also delete these scheduled reports. Are you sure?"
    And I should see "Report: Custom user report 1"
    And I should not see "Daily at 01:00 AM"
    And I should see "Saved search: My search 1"
    And I should see "You" in the "Daily at 02:00 AM" "table_row"
    And I should see "You" in the "Daily at 03:00 AM" "table_row"
    And I should not see "You" in the "Daily at 04:00 AM" "table_row"
    When I press "Continue"
    Then I should not see "My search 1"
    And I should see "This report does not have any saved searches."

    When I click on "Reports" in the totara menu
    Then I should not see "My search 1"

  Scenario: I can save a toolbar search via standard filter
    Given I log in as "admin"
    When I navigate to my "Custom user report 1" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I select "User's Fullname" from the "newsearchcolumn" singleselect
    And I press "Save changes"
    And I follow "View This Report"
    And I set the field "toolbarsearchtext" to "user1"
    And I press "toolbarsearchbutton"
    And I press "Save this search"
    And I set the field "Search Name" to "My search 1"
    And I press "Save changes"
    And I press "cleartoolbarsearchtext"
    Then I should see "User2-firstname Test"

    When I set the field "sid" to "My search 1"
    Then the field "toolbarsearchtext" matches value "user1"
    And I should not see "User2-firstname Test"

  Scenario: I can save a toolbar search with only toolbar filter showing
    Given I log in as "admin"
    When I navigate to my "Custom user report 1" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I click on "Delete" "link" in the "User's Fullname" "table_row" confirming the dialogue
    And I select "User's Fullname" from the "newsearchcolumn" singleselect
    And I press "Save changes"
    And I follow "View This Report"
    And I set the field "toolbarsearchtext" to "user1"
    And I press "toolbarsearchbutton"
    And I press "Save this search"
    And I set the field "Search Name" to "My search 1"
    And I press "Save changes"
    And I press "cleartoolbarsearchtext"
    Then I should see "User2-firstname Test"

    When I set the field "sid" to "My search 1"
    Then the field "toolbarsearchtext" matches value "user1"
    And I should not see "User2-firstname Test"