@totara @totara_appraisal @perform @mod_perform @javascript @vuejs
Feature: Make sure user can see Appraisal in Historic activities under Performance

  Background:
    Given I am on a totara site
    And I enable the "appraisals" advanced feature
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | learner1  | lastname | learner1@example.com |
      | manager  | manager   | lastname | manager@example.com  |
    And the following job assignments exist:
      | user     | fullname     | idnumber | manager |
      | manager  | Manager Job  | ja1      |         |
      | learner1 | Learner1 Job | ja2      | manager |
    And the following "cohorts" exist:
      | name                  | idnumber | description             | contextlevel | reference |
      | Appraisals Audience 1 | AppAud1  | Appraisals Assignments1 | System       | 0         |
    And the following "cohort members" exist:
      | user     | cohort  |
      | learner1 | AppAud1 |
      | manager  | AppAud1 |

    # Set up an appraisal using the data generator.
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name       |
      | Appraisal1 |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal  | name       | timedue                 |
      | Appraisal1 | App1_Stage | 1 January 2038 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal  | stage      | name      |
      | Appraisal1 | App1_Stage | App1_Page |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal  | stage      | page      | name    | type | default | roles   | ExtraInfo |
      | Appraisal1 | App1_Stage | App1_Page | App1-Q1 | text | 2       | manager |           |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal  | type     | id      |
      | Appraisal1 | audience | AppAud1 |

    # Set necessary configuration.
    And the following config values are set as admin:
      | totara_job_allowmultiplejobs | 0 |
      | showhistoricactivities       | 1 |

    # Activate appraisal.
    When I log in as "admin"
    And I navigate to "Manage Appraisals (legacy)" node in "Site administration > Legacy features"
    And I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I press "Activate"
    And I log out

  Scenario: User and manager still can see appraisals
    Given I log in as "learner1"
    And I navigate to the outstanding perform activities list page
    When I click on "Historic activities" "link"
    Then I should see "Your historic activities"
    And I should see the tui datatable contains:
      | Activity title | Type               | Status |
      | Appraisal1     | Appraisal (legacy) | Active |
    And I log out

    Given I log in as "manager"
    And I navigate to the outstanding perform activities list page
    When I click on "Historic activities" "link"
    Then I should see "Your historic activities"
    And I should see the tui datatable contains:
      | Activity title | Type               | Status |
      | Appraisal1     | Appraisal (legacy) | Active |
    And I should see "Activity title" in the ".tui-performOtherHistoricActivityList .tui-dataTableHeaderCell:nth-child(1)" "css_element"
    And I should see "Type" in the ".tui-performOtherHistoricActivityList .tui-dataTableHeaderCell:nth-child(2)" "css_element"
    And I should see "User" in the ".tui-performOtherHistoricActivityList .tui-dataTableHeaderCell:nth-child(3)" "css_element"
    And I should see "Relationship to user" in the ".tui-performOtherHistoricActivityList .tui-dataTableHeaderCell:nth-child(4)" "css_element"
    And I should see "Status" in the ".tui-performOtherHistoricActivityList .tui-dataTableHeaderCell:nth-child(5)" "css_element"

    And I should see "Appraisal1" in the ".tui-performOtherHistoricActivityList .tui-dataTableCell:nth-child(1)" "css_element"
    And I should see "Appraisal (legacy)" in the ".tui-performOtherHistoricActivityList .tui-dataTableCell:nth-child(2)" "css_element"
    And I should see "learner1 lastname" in the ".tui-performOtherHistoricActivityList .tui-dataTableCell:nth-child(3)" "css_element"
    And I should see "Manager" in the ".tui-performOtherHistoricActivityList .tui-dataTableCell:nth-child(4)" "css_element"
    And I should see "Active" in the ".tui-performOtherHistoricActivityList .tui-dataTableCell:nth-child(5)" "css_element"
    And I log out
