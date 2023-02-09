@totara @perform @totara_competency @pathway_learning_plan @javascript @vuejs
Feature: Test viewing learning plans for a user on their competency details page.

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname |
      | user      | Staff     | User     |
    And a competency scale called "scale" exists with the following values:
      | name                   | description                            | idnumber     | proficient | default | sortorder |
      | Super Competent        | <strong>Is great at doing it.</strong> | super        | 1          | 0       | 1         |
      | Just Barely Competent  | Is okay at doing it.                   | barely       | 1          | 0       | 2         |
      | Incredibly Incompetent | <em>Is rubbish at doing it.</em>       | incompetent  | 0          | 1       | 3         |
    And the following "competency" frameworks exist:
      | fullname                 | idnumber | scale |
      | Competency Framework One | fw       | scale |
    And the following "competency" hierarchy exists:
      | framework | fullname   | idnumber | description                     |
      | fw        | Competency | comp1    | <strong>Competency One</strong> |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | comp1      | user            | user       |
    And the following "learning plan pathways" exist in "totara_competency" plugin:
      | competency | sortorder |
      | comp1      | 1         |
    And the following "plans" exist in "totara_plan" plugin:
      | name       | description                 | user |
      | Plan One   | Plan <strong>One</strong>   | user |
      | Plan Two   | Plan <strong>Two</strong>   | user |
      | Plan Three | Plan <strong>Three</strong> | user |
    And the following "learning plan with competency value" exist in "totara_competency" plugin:
      | plan     | competency | scale_value |
      | Plan One | comp1      |             |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    And I run the scheduled task "totara_competency\task\competency_aggregation_queue"

  Scenario: View learning plans
    When I log in as "user"
    And I navigate to the competency profile details page for the "Competency" competency

    # Just one learning plan shown - no overall rating
    Then I should see "Achievement via learning plan" in the ".tui-pathwayLearningPlanAchievement" "css_element"
    And I should see "No rating set" in the ".tui-pathwayLearningPlanAchievement" "css_element"
    Then I should see "1" rows in the tui datatable
    And I should see "Plan One" under "Name" on row "1" of the tui datatable
    When I toggle expanding row "1" of the tui datatable
    Then I should see "Plan One" under the expanded row of the tui datatable
    And I should see "View plan" under the expanded row of the tui datatable

    # Create more competency plan ratings
    When the following "learning plan with competency value" exist in "totara_competency" plugin:
      | plan       | competency | scale_value | date       |
      | Plan Two   | comp1      | super       | 2020-01-01 |
      | Plan Three | comp1      | incompetent | 2020-01-02 |
    And I reload the page

    # Three learning plans now - overall rating is based on the most recent rating, not the highest.
    Then I should see "Incredibly Incompetent" in the ".tui-pathwayLearningPlanAchievement" "css_element"
    And I should see "3" rows in the tui datatable
    And I should see "Plan One" under "Name" on row "1" of the tui datatable
    And I should see "Plan Two" under "Name" on row "2" of the tui datatable
    And I should see "Plan Three" under "Name" on row "3" of the tui datatable
    When I toggle expanding row "1" of the tui datatable
    Then I should see "Plan One" under the expanded row of the tui datatable
    And I should see "View plan" under the expanded row of the tui datatable
    When I toggle expanding row "2" of the tui datatable
    Then I should see "Plan Two" under the expanded row of the tui datatable
    And I should see "View plan" under the expanded row of the tui datatable
    When I toggle expanding row "3" of the tui datatable
    Then I should see "Plan Three" under the expanded row of the tui datatable
    And I should see "View plan" under the expanded row of the tui datatable

    # Should navigate to learning plan
    When I click on "View plan" "link"
    Then I should see "Plan: Plan Three"
    And I should see "This plan has not yet been approved"
