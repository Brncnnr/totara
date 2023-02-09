@totara @totara_reportbuilder @javascript
Feature: Caching works as expected when adding search columns
  In order to check cache report builder is working when adding search columns
  As a admin
  I need to be able set up caching and add search columns as filters

  Background:
    Given this test is skipped if tables cannot be created from select
    And I am on a totara site
    And the following "cohorts" exist:
      | name      | idnumber | contextlevel | reference |
      | Audience1 | aud1     | System       |           |
    And the following "courses" exist:
      | fullname  | shortname | category |
      | Course #1 | C1        | 0        |
      | Course #2 | C2        | 0        |
    And the following "cohort enrolments" exist in "totara_cohort" plugin:
      | course | cohort |
      | C1     | aud1   |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable report caching | 1 |

  Scenario: Report Builder caching works with search-columns when there is no data for "Custom Audience Report"
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname               | shortname                     | source              |
      | Custom Audience Report | report_custom_audience_report | cohort_associations |
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "Custom Audience Report"
    And I switch to "Filters" tab
    And I select "Name" from the "newsearchcolumn" singleselect
    And I press "Add"
    And I switch to "Performance" tab
    And I click on "Enable Report Caching" "text"
    And I click on "Generate Now" "text"
    And I click on "Save changes" "button"
    And I should see "Last cached"
    And I should not see "Not cached yet"

    When I navigate to my "Custom Audience Report" report
    And I run the scheduled task "totara_reportbuilder\task\refresh_cache_task"
    Then I should see "Report data last updated"
    And I should see "Course #1"
