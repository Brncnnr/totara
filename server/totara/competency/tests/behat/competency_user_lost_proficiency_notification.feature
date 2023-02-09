@totara @perform @totara_competency @javascript @vuejs
Feature: User gets a message notification due to a configuration change in an assigned Competency that causes a proficiency lose.
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
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
      | name                   | description                            | idnumber    | proficient | default | sortorder |
      | Super Competent        | <strong>Is great at doing it.</strong> | super       | 1          | 0       | 1         |
      | Just Barely Competent  | Is okay at doing it.                   | barely      | 1          | 0       | 2         |
      | Incredibly Incompetent | <em>Is rubbish at doing it.</em>       | incompetent | 0          | 1       | 3         |
    And the following "competency" frameworks exist:
      | fullname                 | idnumber | description                | scale  |
      | Competency Framework One | fw1      | Framework for Competencies | scale2 |
    And the following hierarchy types exist:
      | hierarchy  | idnumber | fullname            |
      | competency | type1    | Competency Type One |
    And the following "competency" hierarchy exists:
      | framework | fullname | idnumber | type  | description                  | parent | assignavailability |
      | fw1       | Comp2    | comp2    | type1 | <strong>Lorem ipsum</strong> |        | any                |
    And the following "coursecompletion" exist in "totara_criteria" plugin:
      | idnumber            | courses                 | number_required |
      | coursecompletionOne | course1,course2,course3 | 2               |
    And the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency | scale_value | criteria            | sortorder |
      | comp2      | barely      | coursecompletionOne | 1         |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | comp2      | user            | user1      |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    And I wait for the next second
    And I run the scheduled task "totara_competency\task\competency_aggregation_queue"
    When I log in as "user1"
    And I navigate to the competency profile details page for the "Comp2" competency
    Then I should see "Proficient" in the ".tui-competencyDetailAssignment__status-text" "css_element"
    And I log out

  Scenario: User gets notified of proficiency lose due to change in criteria.
    When I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Competency Framework One" "link"
    And I click on "Comp2" "link"
    And I click on "Edit" "link" in the ".tui-competencySummaryAchievementConfiguration" "css_element"
    And I click on "[data-tw-editscalevaluepaths-criterion-action=\"toggle-detail\"]" "css_element"
    And I set the field "Complete all" to "1"
    And I click on "Apply changes" "button"
    And I run the scheduled task "totara_competency\task\competency_aggregation_queue"
    Then I log out

    When I log in as "user1"
    Then I should see "1" in the "#nav-notification-popover-container" "css_element"
    And I open the notification popover
    And I should see "You are no longer proficient in the competency Comp2" in the ".notification-message" "css_element"
    And I click on "View full notification" "link"
    And I click on "competency's activity log" "link"
    Then I should see "Activity log" in the ".tui-competencyDetailActivityLog__title" "css_element"
    And I should see "Rating: None" in the ".tui-dataTableRow:nth-of-type(1) .tui-competencyDetailActivityLogTable__description" "css_element"
    And I should see "Rating value reset" in the ".tui-dataTableRow:nth-of-type(2) .tui-competencyDetailActivityLogTable__description" "css_element"
    And I should see "Criteria change" in the ".tui-dataTableRow:nth-of-type(3) .tui-competencyDetailActivityLogTable__description" "css_element"

  Scenario: User gets notified of proficiency lose due to change in scale value minimum proficiency.
    When I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "scale2" "link"
    And I set the field "minproficiencyid" to "4"
    And I click on "Save and apply changes" "button"
    And I click on "Yes" "button"
    And I am on homepage
    And I run the scheduled task "totara_competency\task\competency_aggregation_queue"
    Then I log out

    When I log in as "user1"
    Then I should see "1" in the "#nav-notification-popover-container" "css_element"
    And I open the notification popover
    And I should see "You are no longer proficient in the competency Comp2" in the ".notification-message" "css_element"
    And I click on "View full notification" "link"
    And I click on "competency's activity log" "link"
    Then I should see "Activity log" in the ".tui-competencyDetailActivityLog__title" "css_element"
    And I should see "Rating: Just Barely Competent" in the ".tui-dataTableRow:nth-of-type(1) .tui-competencyDetailActivityLogTable__description" "css_element"
    And I should see "Criteria met: Course completion. Achieved 'Just Barely Competent' rating" in the ".tui-dataTableRow:nth-of-type(2) .tui-competencyDetailActivityLogTable__description" "css_element"
    And I should see "Minimum required proficiency value changed to 'Super Competent'" in the ".tui-dataTableRow:nth-of-type(3) .tui-competencyDetailActivityLogTable__description" "css_element"
