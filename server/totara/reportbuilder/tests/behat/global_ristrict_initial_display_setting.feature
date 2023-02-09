@totara @totara_reportbuilder @javascript
Feature: Global restriction initial display setting
  In order to use Global restriction initial display
  As a user
  I need to be able to enable Global restriction initial display

  Scenario: Enable global restriction initial display
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname  | shortname        | source        |
      | Site Logs | report_site_logs | site_logstore |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "Site Logs"
    And I switch to "Columns" tab
    And I add the "Event Class Name" column to the report
    When I switch to "Performance" tab
    Then I should not see "'Restrict initial display in all report builder reports' setting has been enabled."
    And I should not see "Please apply a filter to view the results of this report, or hit search without adding any filters to view all entries"
    When I click on "View This Report" "link"
    Then I should see "\core\event\course_viewed"
    And I should see "\core\event\user_loggedin"
    And I should see "\totara_reportbuilder\event\report_updated"

    And I set the following administration settings values:
      | Restrict initial display in all report builder reports | 1 |

    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "Site Logs"
    When I switch to "Performance" tab
    Then I should see "'Restrict initial display in all report builder reports' setting has been enabled."
    When I click on "View This Report" "link"
    Then I should see "Please apply a filter to view the results of this report, or hit search without adding any filters to view all entries"

    When I set the field "logstore_standard_log-eventname_op" to "2"
    And I set the field "logstore_standard_log-eventname" to "\core\event\course_viewed"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "\core\event\course_viewed"
    And I should not see "\core\event\user_loggedin"
    And I should not see "\totara_reportbuilder\event\report_updated"
