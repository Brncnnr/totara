@totara @perform @totara_competency @javascript @vuejs
Feature: Competency profile detail page - an overview of their progress (or lack of) towards completing a particular competency

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname |
      | user      | Staff     | User     |
    And a competency scale called "scale2" exists with the following values:
      | name                   | description                            | idnumber     | proficient | default | sortorder |
      | Super Competent        | <strong>Is great at doing it.</strong> | super        | 1          | 0       | 1         |
      | Just Barely Competent  | Is okay at doing it.                   | barely       | 0          | 0       | 2         |
      | Incredibly Incompetent | <em>Is rubbish at doing it.</em>       | incompetent  | 0          | 1       | 3         |
    And the following "competency" frameworks exist:
      | fullname             | idnumber | description                | scale  |
      | Competency Framework | fw       | Framework for Competencies | scale2 |
    And the following hierarchy types exist:
      | hierarchy  | idnumber | fullname            |
      | competency | type     | Competency Type One |
    And the following "competency" hierarchy exists:
      | framework | fullname    | idnumber | type | description                        | assignavailability |
      | fw        | Typing slow | comp     | type | The ability to type <em>slow.</em> | any                |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | comp       | user            | user       |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | course    | 1                |
    And the following "course enrollments and completions" exist in "totara_competency" plugin:
      | user | course |
      | user | course |
    And the following "coursecompletion" exist in "totara_criteria" plugin:
      | idnumber         | courses | number_required |
      | coursecompletion | course  | 1               |
    And the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency  | scale_value  | criteria         | sortorder |
      | comp        | super        | coursecompletion | 1         |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    And I run the scheduled task "totara_competency\task\competency_aggregation_queue"

  Scenario: I can navigate to the details page for a competency I am assigned to
    When I log in as "user"
    And I navigate to the competency profile of user "user"
    And I change the competency profile to list view
    And I click on "Typing slow" "link"

    Then I should see "Competency Details - Typing slow"
    And I should see "Typing slow" in the ".tui-pageHeading__title" "css_element"
    And I should see "The ability to type slow." in the ".tui-competencyDetail__description" "css_element"

  Scenario: I can navigate to the details page for a competency a user I am managing is assigned to
    Given the following "users" exist:
      | username  | firstname | lastname |
      | manager   | Manager   | User     |
    And the following job assignments exist:
      | user | idnumber | manager |
      | user | 1        | manager |

    When I log in as "manager"
    And I navigate to the competency profile of user "user"
    And I change the competency profile to list view
    And I click on "Typing slow" "link"

    Then I should see "Competency Details - Typing slow"
    And I should see "Typing slow" in the ".tui-pageHeading__title" "css_element"
    And I should see "The ability to type slow." in the ".tui-competencyDetail__description" "css_element"

  Scenario: I can navigate to the details page for a competency a user I am appraising is assigned to
    Given the following "users" exist:
      | username  | firstname | lastname |
      | appraiser | Appraiser | User     |
    And the following job assignments exist:
      | user | idnumber | appraiser |
      | user | 1        | appraiser |

    When I log in as "appraiser"
    And I navigate to the competency profile of user "user"
    And I change the competency profile to list view
    And I click on "Typing slow" "link"

    Then I should see "Competency Details - Typing slow"
    And I should see "Typing slow" in the ".tui-pageHeading__title" "css_element"
    And I should see "The ability to type slow." in the ".tui-competencyDetail__description" "css_element"

  Scenario: I can view the achievement progress for the competency on a per-assignment basis
    Given the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group   | type  |
      | comp       | user            | user         | self  |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"

    When I log in as "user"
    And I navigate to the competency profile details page for the "Typing slow" competency

    When I select "Directly assigned by Admin User (Admin)" from the "select_assignment" singleselect
    Then I should see "Super Competent" in the ".tui-competencyDetailAssignment__level-text" "css_element"
    When I select "Self-assigned" from the "select_assignment" singleselect
    Then I should see "No value achieved" in the ".tui-competencyDetailAssignment__level-text" "css_element"

  Scenario: Scale display observes minimum proficiency value overrides on assignment basis
    Given the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group   | type  | min_proficiency_override |
      | comp       | user            | user         | self  | barely                   |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"

    When I log in as "user"
    And I navigate to the competency profile details page for the "Typing slow" competency
    And I select "Directly assigned by Admin User (Admin)" from the "select_assignment" singleselect
    # Proficient scale values have the __target class
    Then I should not see "Achievement target." in the ".tui-progressTrackerNav__items .tui-progressTrackerNav__item:nth-child(2) .tui-progressTrackerNav__itemStatus" "css_element"
    And I should see "Achievement target." in the ".tui-progressTrackerNav__items .tui-progressTrackerNav__item:nth-child(3) .tui-progressTrackerNav__itemStatus" "css_element"

    When I select "Self-assigned" from the "select_assignment" singleselect
    Then I should see "Achievement target." in the ".tui-progressTrackerNav__items .tui-progressTrackerNav__item:nth-child(2) .tui-progressTrackerNav__itemStatus" "css_element"

  Scenario: I can navigate directly to the details page of a competency not assigned to me
    When I log in as "admin"
    And I navigate to the competency profile details page for the "Typing slow" competency

    Then I should see "Typing slow"
    And I should see "The ability to type slow."
    And I should see "There are no active assignments"
