@totara @perform @totara_competency @totara_criteria @pathway_criteria_group @javascript @vuejs
Feature: Test viewing criteria fulfilment for a user on their competency details page.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | user     | Staff     | User     |
    And a competency scale called "scale" exists with the following values:
      | name                   | description                            | idnumber     | proficient | default | sortorder |
      | Super Competent        | <strong>Is great at doing it.</strong> | super        | 1          | 0       | 1         |
      | Just Barely Competent  | Is okay at doing it.                   | barely       | 1          | 0       | 2         |
      | Incredibly Incompetent | <em>Is rubbish at doing it.</em>       | incompetent  | 0          | 1       | 3         |
    And the following "competency" frameworks exist:
      | fullname                 | idnumber | scale |
      | Competency Framework One | fw       | scale |
    And the following "competency" hierarchy exists:
      | framework | fullname | idnumber | description                     |
      | fw        | Comp1    | comp1    | <strong>Competency One</strong> |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | comp1      | user            | user       |

  Scenario: View course criteria
    Given the following "courses" exist:
      | fullname | shortname | enablecompletion  | summary                               |
      | Course 1 | course1   | 1                 | Course <strong>1</strong> Description |
      | Course 2 | course2   | 1                 | Course <strong>2</strong> Description |
      | Course 3 | course3   | 1                 | Course <strong>3</strong> Description |
    And the following "course enrollments and completions" exist in "totara_competency" plugin:
      | user | course  |
      | user | course1 |
      | user | course3 |
    And the following "linked courses" exist in "totara_competency" plugin:
      | competency | course  | mandatory |
      | comp1      | course3 | 1         |
    And the following "coursecompletion" exist in "totara_criteria" plugin:
      | idnumber         | courses         | number_required |
      | coursecompletion | course1,course2 | all             |
    And the following "linkedcourses" exist in "totara_criteria" plugin:
      | idnumber      | competency | number_required |
      | linkedcourses | comp1      | 1               |
    And the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency  | scale_value        | criteria         | sortorder |
      | comp1       | super              | coursecompletion | 1         |
      | comp1       | barely             | linkedcourses    | 1         |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    And I run the scheduled task "totara_competency\task\competency_aggregation_queue"
    And I log in as "user"
    And I navigate to the competency profile details page for the "Comp1" competency
    And I wait for pending js

    # Course completion (aka flexible courses) criteria (Course 1 & 2)
    And I wait until ".tui-competencyAchievementsScale" "css_element" exists
    # Make sure we expand existing headers
    And I ensure the "Work towards level Super Competent" tui collapsible is expanded
    Then I should see "1 / 2" "courses" completed towards achieving "Super Competent" in the competency profile

    # Course 1 - 100% Completed
    Then I should see "Course 1" under "Courses" on row "1" of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Super Competent" tui collapsible
    And I should see "100%" under "Progress" on row "1" of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Super Competent" tui collapsible
    And I should see "Complete" under "Completion" on row "1" of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Super Competent" tui collapsible
    When I toggle expanding row "1" of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Super Competent" tui collapsible
    Then I should see "Course 1 Description" under the expanded row of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Super Competent" tui collapsible

    # Course 2 - No completion data
    Then I should see "Course 2" under "Courses" on row "2" of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Super Competent" tui collapsible
    And I should see "Not available" under "Progress" on row "2" of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Super Competent" tui collapsible
    And I should see "Not complete" under "Completion" on row "2" of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Super Competent" tui collapsible
    When I toggle expanding row "2" of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Super Competent" tui collapsible
    And I wait for pending js
    Then I should see "Course 2 Description" under the expanded row of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Super Competent" tui collapsible

    # View Course 2
    When I click on "Go to course" "link" in the expanded row of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Super Competent" tui collapsible
    And I wait for pending js
    Then I should see "Course 2 Description"
    And I should see "You can not enrol yourself in this course."
    When I click on "Continue" "button"
    And I wait for pending js
    Then I should see "Competency profile"

    # Linked courses criteria (Course 3)
    And I wait until ".tui-competencyAchievementsScale" "css_element" exists
    When I ensure the "Work towards level Just Barely Competent" tui collapsible is expanded
    And I wait for pending js
    Then I should see "1 / 1" "courses" completed towards achieving "Just Barely Competent" in the competency profile

    # Course 3 - 100% Completed
    Then I should see "Course 3" under "Courses" on row "1" of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Just Barely Competent" tui collapsible
    And I should see "100%" under "Progress" on row "1" of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Just Barely Competent" tui collapsible
    And I should see "Complete" under "Completion" on row "1" of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Just Barely Competent" tui collapsible
    When I toggle expanding row "1" of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Just Barely Competent" tui collapsible
    And I wait for pending js
    Then I should see "Course 3 Description" under the expanded row of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Just Barely Competent" tui collapsible

    # View Course 3
    When I click on "Go to course" "link" in the expanded row of the tui datatable in the ".tui-criteriaCourseAchievement" "css_element" in the "Work towards level Just Barely Competent" tui collapsible
    And I wait for pending js
    Then I should see "Course 3"
    And I should see "Topic 1"

  Scenario: View competency criteria
    Given the following "competency" hierarchy exists:
      | framework | fullname | idnumber | description                       | parent | assignavailability |
      | fw        | Comp2    | comp2    | Competency <strong>Two</strong>   | comp1  | any                |
      | fw        | Comp3    | comp3    | Competency <strong>Three</strong> |        | any                |
      | fw        | Comp4    | comp4    | Competency <strong>Four</strong>  |        | none               |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | comp2      | user            | user       |
    And the following "childcompetency" exist in "totara_criteria" plugin:
      | idnumber        | competency | number_required |
      | childcompetency | comp1      | all             |
    And the following "othercompetency" exist in "totara_criteria" plugin:
      | idnumber        | competency | number_required | competencies |
      | othercompetency | comp1      | all             | comp3,comp4  |
    And the following "onactivate" exist in "totara_criteria" plugin:
      | idnumber   | competency |
      | onactivate | comp2      |
    And the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency  | scale_value        | criteria         | sortorder |
      | comp1       | super              | othercompetency  | 1         |
      | comp2       | super              | onactivate       | 1         |
      | comp1       | barely             | childcompetency  | 1         |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    And I run the scheduled task "totara_competency\task\competency_aggregation_queue"

    When I log in as "user"
    And I navigate to the competency profile details page for the "Comp1" competency
    And I wait for pending js

    # Other competencies
    # Make sure we expand existing headers
    And I wait until ".tui-competencyAchievementsScale" "css_element" exists
    And I ensure the "Work towards level Super Competent" tui collapsible is expanded
    And I wait for pending js
    Then I should see "0 / 2" "other competencies" completed towards achieving "Super Competent" in the competency profile

    # Other competency - Comp 4 - is not assigned and can not be self assigned.
    Then I should see "Comp4" under "Competencies" on row "2" of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Super Competent" tui collapsible
    And I should see "Not available" under "Achievement level" on row "2" of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Super Competent" tui collapsible
    And I should see "Not complete" under "Completion" on row "2" of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Super Competent" tui collapsible
    When I toggle expanding row "2" of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Super Competent" tui collapsible
    Then I should see "Competency Four" under the expanded row of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Super Competent" tui collapsible
    And I should not see "View competency"
    And I should not see "Self assign competency"

    # Other competency - Comp3 - is not assigned but we self assign it now.
    Then I should see "Comp3" under "Competencies" on row "1" of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Super Competent" tui collapsible
    And I should see "Not available" under "Achievement level" on row "1" of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Super Competent" tui collapsible
    And I should see "Not complete" under "Completion" on row "1" of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Super Competent" tui collapsible
    When I toggle expanding row "1" of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Super Competent" tui collapsible
    Then I should see "Competency Three" under the expanded row of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Super Competent" tui collapsible
    And I should see "Self assign competency" under the expanded row of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Super Competent" tui collapsible
    When I click on "Self assign competency" "button"
    Then I should see "Are you sure you would like to assign this competency" in the tui modal
    When I confirm the tui confirmation modal
    Then I should see "View competency" under the expanded row of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Super Competent" tui collapsible
    When I click on "View competency" "link"
    Then I should see "Competency Details - Comp3"
    When I navigate to the competency profile details page for the "Comp1" competency

    # Child competencies
    And I wait until ".tui-competencyAchievementsScale" "css_element" exists
    And I ensure the "Work towards level Just Barely Competent" tui collapsible is expanded
    And I wait for pending js
    And I should see "1 / 1" "child competencies" completed towards achieving "Just Barely Competent" in the competency profile

    # Child competency - Comp2 - is already assigned and completed.
    Then I should see "Comp2" under "Competencies" on row "1" of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Just Barely Competent" tui collapsible
    And I should see "Super Competent" under "Achievement level" on row "1" of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Just Barely Competent" tui collapsible
    And I should see "Complete" under "Completion" on row "1" of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Just Barely Competent" tui collapsible
    When I toggle expanding row "1" of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Just Barely Competent" tui collapsible
    Then I should see "Competency Two" under the expanded row of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Just Barely Competent" tui collapsible
    And I should see "View competency" under the expanded row of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Just Barely Competent" tui collapsible
    When I click on "View competency" "link" in the expanded row of the tui datatable in the ".tui-competencyAchievementsScale__item" "css_element" in the "Work towards level Just Barely Competent" tui collapsible
    And I wait for pending js
    Then I should see "Competency Details - Comp2"

  Scenario: View competency assignment activation criteria
    Given the following "onactivate" exist in "totara_criteria" plugin:
      | idnumber   | competency |
      | onactivate | comp1      |
    And the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency  | scale_value        | criteria         | sortorder |
      | comp1       | super              | onactivate       | 1         |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"

    When I log in as "user"
    And I navigate to the competency profile details page for the "Comp1" competency
    # On activate criteria
    And I wait until ".tui-competencyAchievementsScale" "css_element" exists
    Then I should see "Value achieved when the competency is assigned" in the ".tui-criteriaOnActiveAchievement" "css_element"
    And I should see "Achieved on" in the ".tui-criteriaOnActiveAchievement" "css_element"

  Scenario: View invalid course criteria
    Given the following "linkedcourses" exist in "totara_criteria" plugin:
      | idnumber      | competency | number_required |
      | linkedcourses | comp1      | all             |
    Given the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency  | scale_value        | criteria         | sortorder |
      | comp1       | barely             | linkedcourses    | 1         |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    And I run the scheduled task "totara_competency\task\competency_aggregation_queue"
    And I log in as "user"
    And I navigate to the competency profile details page for the "Comp1" competency

    # Course completion (aka flexible courses) criteria (Course 1 & 2)
    And I wait until ".tui-competencyAchievementsScale" "css_element" exists
    # Make sure we expand existing headers
    And I ensure the "Work towards level Just Barely Competent" tui collapsible is expanded
    Then I should see "0 / 0" "courses" completed towards achieving "Just Barely Competent" in the competency profile
    And "div.tui-progressCircle--complete" "css_element" should not exist
