@totara @totara_reportbuilder @totara_scheduledreports @javascript
Feature: Test the generation of scheduled reports events.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname  | email          |
      | u1       | User      | One       | u1@example.com |
      | srm      | Report    | Manager   | rm@example.com |
    And the following "roles" exist:
      | name                   | shortname              | contextlevel |
      | ScheduledReportManager | ScheduledReportManager | System       |
    And the following "permission overrides" exist:
      | capability                                  | permission | role                   | contextlevel | reference |
      | totara/reportbuilder:managescheduledreports | Allow      | ScheduledReportManager | System       |           |
      | moodle/cohort:view                          | Allow      | ScheduledReportManager | System       |           |
      | moodle/user:viewalldetails                  | Allow      | ScheduledReportManager | System       |           |
    And the following "role assigns" exist:
      | user | role                   | contextlevel | reference |
      | srm  | ScheduledReportManager | System       |           |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname      | shortname | source | accessmode |
      | Test Report#1 | user      | user   | 0          |

    And I log in as "u1"
    And I click on "Reports" in the totara menu
    And I select "Test Report#1" from the "addanewscheduledreport[reportid]" singleselect
    And I press "Add scheduled report"
    And I set the field "schedulegroup[frequency]" to "Daily"
    And I set the field "schedulegroup[daily]" to "06:00"
    And I set the field "Export" to "CSV"
    And I set the field "External email address to add" to "u1@example.com"
    And I press "Add email"
    And I press "Save changes"
    And I log out

  Scenario: scheduled_report_event_00: scheduled report events from owner page
    # Creation event
    When I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Server"
    And I press "Get these logs"
    Then "Scheduled report created" row "Description" column of "reportlog" table should contain "created a scheduled report"
    And "Scheduled report created" row "User full name" column of "reportlog" table should contain "User One"

    # Modification event
    Given I log out
    And I log in as "u1"
    And I click on "Reports" in the totara menu
    And I click on "Edit" "link" in the "Test Report#1" "table_row"
    And I set the field "External email address to add" to "u2@example.com"
    And I press "Add email"
    And I press "Save changes"
    And I log out
    And I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Server"

    When I press "Get these logs"
    Then "Scheduled report updated" row "Description" column of "reportlog" table should contain "updated a scheduled report"
    And "Scheduled report updated" row "User full name" column of "reportlog" table should contain "User One"

    # Deletion event
    Given I log out
    And I log in as "u1"
    And I click on "Reports" in the totara menu
    And I click on "Delete" "link" in the "Test Report#1" "table_row"
    And I press "Continue"
    And I log out
    And I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Server"

    When I press "Get these logs"
    Then "Scheduled report deleted" row "Description" column of "reportlog" table should contain "deleted a scheduled report"
    And "Scheduled report deleted" row "User full name" column of "reportlog" table should contain "User One"

  Scenario: scheduled_report_event_01: scheduled report events from scheduled report source
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname              | shortname                    | source            | accessmode |
      | All scheduled reports | report_all_scheduled_reports | scheduled_reports | 0          |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "All scheduled reports"
    And I switch to "Columns" tab
    And I add the "Scheduler actions" column to the report
    And I press "Save changes"

    # Modification event
    Given I log out
    And I log in as "srm"
    And I navigate to my "All scheduled reports" report
    And I wait until "report_all_scheduled_reports" "table" exists
    And I click on "Settings" "link" in the "Test Report#1" "table_row"
    And I set the field "External email address to add" to "u2@example.com"
    And I press "Save changes"
    And I log out
    And I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Server"

    When I press "Get these logs"
    Then "Scheduled report updated" row "Description" column of "reportlog" table should contain "updated a scheduled report"
    And "Scheduled report updated" row "User full name" column of "reportlog" table should contain "Report Manager"

    # Deletion event
    Given I log out
    And I log in as "srm"
    And I navigate to my "All scheduled reports" report
    And I wait until "report_all_scheduled_reports" "table" exists
    And I click on "Delete" "link" in the "Test Report#1" "table_row"
    Then I should see "Are you sure you would like to delete the 'Test Report#1' scheduled report?"
    And I press "Continue"
    And I log out
    And I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Server"

    When I press "Get these logs"
    Then "Scheduled report deleted" row "Description" column of "reportlog" table should contain "deleted a scheduled report"
    And "Scheduled report deleted" row "User full name" column of "reportlog" table should contain "Report Manager"
