@totara @perform @totara_competency @javascript @vuejs
Feature: Archive user assignments on competency details page and view archived assignments.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher  | Lesson    | Teacher  | teacher@example.com |
      | student  | Learn     | Student  | student@example.com |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname       | idnumber |
      | High Framework | HSCH1    |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname      | shortname | idnumber |
      | HSCH1         | High School 1 | org1      | org1     |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber |
      | Position Root 1 | PFW001   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname   | shortname | idnumber |
      | PFW001        | Position 1 | pos1      | pos1     |
    And the following job assignments exist:
      | user    | idnumber | manager | organisation | position |
      | student | 1        | teacher | org1         | pos1     |

    And a competency scale called "Sample scale" exists with the following values:
      | name         | description | idnumber | proficient | default | sortorder |
      | Beginner     | Start       | start    | 0          | 1       | 1         |
      | Intermediate | Experienced | middle   | 0          | 0       | 2         |
      | World-class  | Veteran     | best     | 1          | 0       | 3         |
    And the following "competency" frameworks exist:
      | fullname         | idnumber | description                | scale        |
      | Sample framework | sam1     | Framework for Competencies | Sample scale |
    And the following hierarchy types exist:
      | hierarchy  | idnumber | fullname            |
      | competency | type1    | Competency Type One |
      | competency | type2    | Competency Type Two |
    And the following "competency" hierarchy exists:
      | framework | fullname | idnumber | type  | description | assignavailability |
      | sam1      | Comp 1   | comp1    | type1 | Lorem       | any                |
      | sam1      | Comp 2   | comp2    | type1 | Ipsum       | any                |
      | sam1      | Comp 3   | comp3    | type2 | Dixon       | any                |

    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | course1   | 1                |
      | Course 2 | course2   | 1                |
      | Course 3 | course3   | 1                |
    And the following "course enrollments and completions" exist in "totara_competency" plugin:
      | user    | course  |
      | student | course1 |
      | student | course2 |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group | type   |
      | comp2      | user            | student    | admin  |
      | comp2      | user            | student    | legacy |
    And the following "coursecompletion" exist in "totara_criteria" plugin:
      | idnumber          | courses                 | number_required |
      | coursecompletion1 | course1,course2,course3 | 2               |
    And the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency | scale_value | criteria          | sortorder |
      | comp2      | best        | coursecompletion1 | 1         |

    # Expand the assignments - needed for them to be activated
    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    And I run the scheduled task "totara_competency\task\competency_aggregation_queue"

    #Assign competency to student. Self and Other assigned.
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group | type  |
      | comp1      | user            | student    | other |
      | comp1      | user            | student    | self  |
      | comp2      | user            | student    | self  |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"

  Scenario: Archive self-assigned and other-assigned competency as manager
    Given I log in as "teacher"
    When I navigate to the competency profile of user "student"
    And I change the competency profile to list view
    And I click on "Comp 1" "link"
    And I select "Self-assigned" from the "select_assignment" singleselect
    Then I should see "Archive this assignment"
    And I click on "Archive this assignment" "button"
    Then I should see "Confirm archiving of assignment"
    And I click on "OK" "button"
    Then I should not see "Self-assigned"

    When I select "Directly assigned by Admin User (Manager)" from the "select_assignment" singleselect
    Then I should see "Archive this assignment"
    And I click on "Archive this assignment" "button"
    Then I should see "Confirm archiving of assignment"
    And I click on "OK" "button"
    Then I should not see "Directly assigned by Admin User (Manager)"

  Scenario: Archiving is possible even with archived assignments
    Given I log in as "student"

    When I navigate to the competency profile of user "student"
    And I change the competency profile to list view
    And I click on "Comp 1" "link"
    And I select "Self-assigned" from the "select_assignment" singleselect
    Then I should see "Archive this assignment"
    When I select "Directly assigned by Admin User (Manager)" from the "select_assignment" singleselect
    Then I should not see "Archive this assignment"

    Given I log out
    And I log in as "teacher"
    When I navigate to the competency profile of user "student"
    And I change the competency profile to list view
    And I click on "Comp 1" "link"
    And I select "Self-assigned" from the "select_assignment" singleselect
    Then I should see "Archive this assignment"
    And I click on "Archive this assignment" "button"
    Then I should see "Confirm archiving of assignment"
    And I click on "OK" "button"
    Then I should not see "Self-assigned"
    When I select "Directly assigned by Admin User (Manager)" from the "select_assignment" singleselect
    Then I should see "Archive this assignment"
    And I click on "Archive this assignment" "button"
    Then I should see "Confirm archiving of assignment"
    And I click on "OK" "button"
    Then I should not see "Directly assigned by Admin User (Manager)"

    Given the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group | type  |
      | comp1      | user            | student    | other |
      | comp1      | user            | student    | self  |
      | comp2      | user            | student    | self  |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"

    Given I log out
    And I log in as "student"
    When I navigate to the competency profile of user "student"
    And I change the competency profile to list view
    And I click on "Comp 1" "link"
    And I click on "Archived assignments" "button"
    Then I should see the tui datatable contains:
      | Assignment        | Date archived      | Proficiency status |
      | Directly assigned | ## today ##j F Y## |                    |
      | Self-assigned     | ## today ##j F Y## |                    |
    When I close the tui modal
    And I select "Self-assigned" from the "select_assignment" singleselect
    Then I should see "Archive this assignment"
    When I select "Directly assigned by Admin User (Manager)" from the "select_assignment" singleselect
    Then I should not see "Archive this assignment"

    Given I log out
    And I log in as "teacher"
    When I navigate to the competency profile of user "student"
    And I change the competency profile to list view
    And I click on "Comp 1" "link"
    And I select "Self-assigned" from the "select_assignment" singleselect
    Then I should see "Archive this assignment"
    When I select "Directly assigned by Admin User (Manager)" from the "select_assignment" singleselect
    Then I should see "Archive this assignment"

  Scenario: Hide Archive directly-assigned competency button for User profile
    Given I log in as "student"
    When I navigate to the competency profile of user "student"
    And I change the competency profile to list view
    And I click on "Comp 1" "link"
    And I select "Directly assigned by Admin User (Manager)" from the "select_assignment" singleselect
    Then I should not see "Archive this assignment"

    When I select "Self-assigned" from the "select_assignment" singleselect
    Then I should see "Archive this assignment"
    And I click on "Archive this assignment" "button"
    Then I should see "Confirm archiving of assignment"
    And I click on "OK" "button"
    Then I should not see "Self-assigned"

  Scenario: View archived assignments
    Given all assignments for the "comp2" competency are archived at "yesterday"
    When I log in as "student"
    And I navigate to the competency profile details page for the "Comp 2" competency
    When I click on "Archived assignments" "button"
    Then I should see the tui datatable contains:
      | Assignment        | Date archived        | Proficiency status |
      | Directly assigned | ## -1 days ##j F Y## | Proficient         |
      | Legacy Assignment | ## -1 days ##j F Y## | Proficient         |
      | Self-assigned     | ## -1 days ##j F Y## |                    |
    When I click on "Show help for Proficiency status" "button"
    Then I should see "This rating was determined through methods which have been discontinued." in the tui popover
    And I should see "These include learning plans, course completion, or proficiency in child competencies, in previous versions of the system." in the tui popover
