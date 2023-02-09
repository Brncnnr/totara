@totara @perform @totara_competency @javascript @vuejs
Feature: View activity logs

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | manager  | Manager   | User     | mngr@example.com  |
    And the following job assignments exist:
      | user  | idnumber | manager |
      | user1 | 1        | manager |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | course1   | 1                |
      | Course 2 | course2   | 1                |
      | Course 3 | course3   | 1                |
    And the following "course enrollments and completions" exist in "totara_competency" plugin:
      | user  | course  |
      | user1 | course1 |
      | user1 | course2 |
    And a competency scale called "scale2" exists with the following values:
      | name                   | description                            | idnumber     | proficient | default | sortorder |
      | Super Competent        | <strong>Is great at doing it.</strong> | super        | 1          | 0       | 1         |
      | Just Barely Competent  | Is okay at doing it.                   | barely       | 0          | 0       | 2         |
      | Incredibly Incompetent | <em>Is rubbish at doing it.</em>       | incompetent  | 0          | 1       | 3         |
    And the following "competency" frameworks exist:
      | fullname                 | idnumber | description                    | scale  |
      | Competency Framework One | fw1      | Framework for Competencies     | scale2 |
    And the following hierarchy types exist:
      | hierarchy  | idnumber | fullname            |
      | competency | type1    | Competency Type One |
    And the following "competency" hierarchy exists:
      | framework | fullname | idnumber | type  | description                    | parent | assignavailability |
      | fw1       | Comp2    | comp2    | type1 | <strong>Lorem ipsum</strong>   |        | any                |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | comp2      | user            | user1      |
    And the following "coursecompletion" exist in "totara_criteria" plugin:
      | idnumber          | courses                 | number_required |
      | coursecompletion1 | course1,course2,course3 | 2               |
    And the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency  | scale_value  | criteria          | sortorder |
      | comp2       | super        | coursecompletion1 | 1         |
    And the following "manual pathways" exist in "totara_competency" plugin:
      | competency | roles   | sortorder |
      | comp2      | manager | 1         |
    And the following "manual ratings" exist in "totara_competency" plugin:
      | competency | subject_user | rater_user | role    | scale_value | comment             | date       |
      | comp2      | user1        | manager    | manager | super       | My staff is alright | 2022-01-01 |

    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    And I wait for the next second
    And I run the scheduled task "totara_competency\task\competency_aggregation_queue"
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group   | type  |
      | comp2      | user            | user1        | self  |
    And I wait for the next second
    And I run the scheduled task "totara_competency\task\expand_assignments_task"

  Scenario: View activity logs in the competency profile
    When I log in as "user1"
    And I navigate to the competency profile details page for the "Comp2" competency
    And I click on "Activity log" "button"

    #Check if each row in the table has a Date
    Then I should see the current date in format "j/m/Y" in the ".tui-dataTableRow:nth-of-type(1) .tui-competencyDetailActivityLogTable__date" "css_element"
    And I should see the current date in format "j/m/Y" in the ".tui-dataTableRow:nth-of-type(2) .tui-competencyDetailActivityLogTable__date" "css_element"
    And I should see the current date in format "j/m/Y" in the ".tui-dataTableRow:nth-of-type(3) .tui-competencyDetailActivityLogTable__date" "css_element"
    And I should see the current date in format "j/m/Y" in the ".tui-dataTableRow:nth-of-type(4) .tui-competencyDetailActivityLogTable__date" "css_element"
    And I should see the current date in format "j/m/Y" in the ".tui-dataTableRow:nth-of-type(5) .tui-competencyDetailActivityLogTable__date" "css_element"

    #Check if date shows when a date is hovered
    When I hover ".tui-dataTableRow:nth-of-type(1) .tui-competencyDetailActivityLogTable__date" "css_element"
    Then I should see the current date in format "j/m/Y, H:" in the ".tui-competencyDetailActivityLogTable .tui-dataTableRow:nth-of-type(1) .tui-popoverFrame__content" "css_element"

    #Check it shows all the items in activity log
    And I should see the tui datatable in the ".tui-competencyDetailActivityLog" "css_element" contains:
      | Description                                                                                                                        | Proficiency status | Assignment        |
      | Assigned: Self-assigned                                                                                                            |                    | Self-assigned     |
      | Rating: Super Competent                                                                                                            |  Proficient        | Directly assigned |
      | Criteria met: Course completion; Rating by Manager User (Manager). Achieved 'Super Competent' rating. Comment: My staff is alright |                    | Directly assigned |
      | COMPETENCY ACTIVE: ACHIEVEMENT TRACKING STARTED                                                                                    |                    |                   |
      | Assigned: Admin User (Admin)                                                                                                       |                    | Directly assigned |

    When I select "Directly assigned by Admin User (Admin)" from the "activity_log_select" singleselect
    Then I should see the tui datatable in the ".tui-competencyDetailActivityLog" "css_element" contains:
      | Description                                                                                                                        | Proficiency status | Assignment        |
      | Rating: Super Competent                                                                                                            | Proficient         | Directly assigned |
      | Criteria met: Course completion; Rating by Manager User (Manager). Achieved 'Super Competent' rating. Comment: My staff is alright |                    | Directly assigned |
      | COMPETENCY ACTIVE: ACHIEVEMENT TRACKING STARTED                                                                                    |                    |                   |
      | Assigned: Admin User (Admin)                                                                                                       |                    | Directly assigned |

    When I select "Self-assigned" from the "activity_log_select" singleselect
    Then I should see the tui datatable in the ".tui-competencyDetailActivityLog" "css_element" contains:
      | Description            | Proficiency status | Assignment        |
      | Assigned: Self-assigned|                    | Self-assigned     |
