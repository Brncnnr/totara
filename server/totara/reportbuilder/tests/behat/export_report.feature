@totara @totara_reportbuilder @tabexport @javascript
Feature: Test that report builder can export reports
  In order to use my reportbuilder data elsewhere
  As a admin
  I need to be able to export data to file

  Background: Set up a user report
    Given I am on a totara site
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname       | shortname  | source |
      | User report 1  | user       | user   |
    And I log in as "admin"

  Scenario: Export report to CVS
    # NOTE: the CSV export is hacked to not force download in behat which makes it testable
    Given I navigate to my "User report 1" report
    And I set the field "id_format" to "CSV"
    And I click on "Export" "button"
    And I should see "\"User's Fullname\",Username,\"User Last Login\""
    And I should see "Guest user"
    And I should see "Admin User"

  Scenario: I can define report export options at a general level
    # Default export options.
    Given I navigate to "General settings" node in "Site administration > Reports"
    Then the field "s_reportbuilder_exportoptions[csv]" matches value "1"
    And the field "s_reportbuilder_exportoptions[csv_excel]" matches value "0"
    And the field "s_reportbuilder_exportoptions[excel]" matches value "1"
    And the field "s_reportbuilder_exportoptions[ods]" matches value "1"
    And the field "s_reportbuilder_exportoptions[pdflandscape]" matches value "1"
    And the field "s_reportbuilder_exportoptions[pdfportrait]" matches value "1"
    And the field "s_reportbuilder_exportoptions[wkpdflandscape]" matches value "0"
    And the field "s_reportbuilder_exportoptions[wkpdfportrait]" matches value "0"

    When I navigate to my "User report 1" report
    Then the "format" select box should contain "CSV"
    And the "format" select box should contain "Excel"
    And the "format" select box should contain "ODS"
    And the "format" select box should contain "PDF landscape"
    And the "format" select box should not contain "csv_excel"
    And the "format" select box should not contain "wkpdflandscape"
    And the "format" select box should not contain "wkpdfportrait"

    When I click on "Reports" in the totara menu
    And I press "Add scheduled report"
    Then the "format" select box should contain "CSV"
    And the "format" select box should contain "Excel"
    And the "format" select box should contain "ODS"
    And the "format" select box should contain "PDF landscape"
    And the "format" select box should not contain "csv_excel"
    And the "format" select box should not contain "wkpdflandscape"
    And the "format" select box should not contain "wkpdfportrait"

    # Now change the export options.
    And I navigate to "General settings" node in "Site administration > Reports"
    And I set the following fields to these values:
      | s_reportbuilder_exportoptions[csv]            | 1 |
      | s_reportbuilder_exportoptions[csv_excel]      | 1 |
      | s_reportbuilder_exportoptions[excel]          | 0 |
      | s_reportbuilder_exportoptions[ods]            | 0 |
      | s_reportbuilder_exportoptions[pdflandscape]   | 0 |
      | s_reportbuilder_exportoptions[pdfportrait]    | 0 |
      | s_reportbuilder_exportoptions[wkpdflandscape] | 0 |
      | s_reportbuilder_exportoptions[wkpdfportrait]  | 0 |
    And I press "Save changes"
    Then I should see "Changes saved"
    When I navigate to my "User report 1" report
    Then the "format" select box should contain "CSV"
    And the "format" select box should contain "csv_excel"
    And the "format" select box should not contain "Excel"
    And the "format" select box should not contain "ODS"
    And the "format" select box should not contain "PDF landscape"
    And the "format" select box should not contain "PDF portrait"

    When I click on "Reports" in the totara menu
    And I press "Add scheduled report"
    Then the "format" select box should contain "CSV"
    And the "format" select box should contain "csv_excel"
    And the "format" select box should not contain "Excel"
    And the "format" select box should not contain "ODS"
    And the "format" select box should not contain "PDF landscape"
    And the "format" select box should not contain "PDF portrait"

  Scenario: I can define report export options at a report level
    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    When I follow "User report 1"
    And I switch to "Performance" tab
    Then the "overrideexportoptions" "checkbox" should be enabled
    And the "exportoptions[csv]" "checkbox" should be disabled
    And the "exportoptions[csv_excel]" "checkbox" should be disabled
    And the "exportoptions[excel]" "checkbox" should be disabled
    And the "exportoptions[ods]" "checkbox" should be disabled
    And the "exportoptions[pdflandscape]" "checkbox" should be disabled
    And the "exportoptions[pdfportrait]" "checkbox" should be disabled

    When I click on "overrideexportoptions" "checkbox"
    And the "exportoptions[csv]" "checkbox" should be enabled
    And the "exportoptions[csv_excel]" "checkbox" should be enabled
    And the "exportoptions[excel]" "checkbox" should be enabled
    And the "exportoptions[ods]" "checkbox" should be enabled
    And the "exportoptions[pdflandscape]" "checkbox" should be enabled
    And the "exportoptions[pdfportrait]" "checkbox" should be enabled

    When I set the following fields to these values:
      | exportoptions[csv]           | 1 |
      | exportoptions[csv_excel]     | 1 |
      | exportoptions[excel]         | 0 |
      | exportoptions[ods]           | 0 |
      | exportoptions[pdflandscape]  | 0 |
      | exportoptions[pdfportrait]   | 0 |
    And I press "Save changes"
    Then I should see "Report Updated"
    And the field "exportoptions[csv]" matches value "1"
    And the field "exportoptions[csv_excel]" matches value "1"
    And the field "exportoptions[excel]" matches value "0"
    And the field "exportoptions[ods]" matches value "0"
    And the field "exportoptions[pdflandscape]" matches value "0"
    And the field "exportoptions[pdfportrait]" matches value "0"

    When I navigate to my "User report 1" report
    Then the "format" select box should contain "CSV"
    And the "format" select box should contain "csv_excel"
    And the "format" select box should not contain "Excel"
    And the "format" select box should not contain "ODS"
    And the "format" select box should not contain "PDF landscape"
    And the "format" select box should not contain "PDF portrait"

    When I click on "Reports" in the totara menu
    And I press "Add scheduled report"
    Then the "format" select box should contain "CSV"
    And the "format" select box should contain "csv_excel"
    And the "format" select box should not contain "Excel"
    And the "format" select box should not contain "ODS"
    And the "format" select box should not contain "PDF landscape"
    And the "format" select box should not contain "PDF portrait"

  Scenario: I can only define report export options at a report level with the correct capability
    Given I log out
    When the following "users" exist:
      | username       | firstname | lastname  | email                |
      | reportmanager1 | manager1  | manager1  | manager1@example.com |
      | reportmanager2 | manager2  | manager2  | manager1@example.com |
    And the following "roles" exist:
      | name           | shortname      | contextlevel |
      | ReportManager1 | ReportManager1 | System       |
      | ReportManager2 | ReportManager2 | System       |
    And the following "permission overrides" exist:
      | capability                                  | permission | role           | contextlevel | reference |
      | totara/reportbuilder:createscheduledreports | Allow      | ReportManager1 | System       |           |
      | totara/reportbuilder:manageembeddedreports  | Allow      | ReportManager1 | System       |           |
      | totara/reportbuilder:managereports          | Allow      | ReportManager1 | System       |           |
      | totara/reportbuilder:managescheduledreports | Allow      | ReportManager1 | System       |           |
      | totara/reportbuilder:createscheduledreports | Allow      | ReportManager2 | System       |           |
      | totara/reportbuilder:manageembeddedreports  | Allow      | ReportManager2 | System       |           |
      | totara/reportbuilder:managereports          | Allow      | ReportManager2 | System       |           |
      | totara/reportbuilder:overrideexportoptions  | Allow      | ReportManager2 | System       |           |
    And the following "role assigns" exist:
      | user            | role                   | contextlevel | reference |
      | reportmanager1  | ReportManager1         | System       |           |
      | reportmanager2  | ReportManager2         | System       |           |

    And I log in as "reportmanager1"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "User report 1"
    And I switch to "Performance" tab
    Then the "id_overrideexportoptions" "checkbox" should be disabled
    And the "id_exportoptions_csv" "checkbox" should be disabled
    And the "id_exportoptions_csv_excel" "checkbox" should be disabled
    And the "id_exportoptions_excel" "checkbox" should be disabled
    And the "id_exportoptions_ods" "checkbox" should be disabled
    And the "id_exportoptions_pdflandscape" "checkbox" should be disabled
    And the "id_exportoptions_pdfportrait" "checkbox" should be disabled
    And I log out

    When I log in as "reportmanager2"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "User report 1"
    And I switch to "Performance" tab
    Then the "overrideexportoptions" "checkbox" should be enabled
    When I click on "overrideexportoptions" "checkbox"
    Then the "exportoptions[csv]" "checkbox" should be enabled
    And the "exportoptions[csv_excel]" "checkbox" should be enabled
    And the "exportoptions[excel]" "checkbox" should be enabled
    And the "exportoptions[ods]" "checkbox" should be enabled
    And the "exportoptions[pdflandscape]" "checkbox" should be enabled
    And the "exportoptions[pdfportrait]" "checkbox" should be enabled
    And I log out
