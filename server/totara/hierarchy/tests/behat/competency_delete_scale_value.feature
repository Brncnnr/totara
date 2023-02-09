@totara @perform @totara_competency @javascript
Feature: Delete scale values pathways when users haven't achieved the competency.

  Background:
    Given I am on a totara site
    And a competency scale called "Deletable Value scale" exists with the following values:
      | name         | description  | idnumber     | proficient | default | sortorder |
      | Beginner     | Start        | start        | 0          | 1       | 1         |
      | Intermediate | Experienced  | middle       | 0          | 0       | 2         |
      | World-class  | Veteran      | best         | 1          | 0       | 3         |
    And the following "competency" frameworks exist:
      | fullname                        | idnumber | description                    | scale                 |
      | Deletable Scale value framework | dsv1      | Framework for Competencies     | Deletable Value scale |
    And the following "competency" hierarchy exists:
      | framework  | fullname  | idnumber | description  |
      | dsv1       | Comp 1    | comp1    | Lorem        |
      | dsv1       | Comp 2    | comp2    | Ipsum        |
      | dsv1       | Comp 3    | comp3    | Dixon        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | course_1  |
      | Course 2 | course_2  |
      | Course 3 | course_3  |
    And the following "coursecompletion" exist in "totara_criteria" plugin:
      | idnumber          | courses                    | number_required |
      | coursecompletion1 | course_1,course_2,course_3 | 1               |
      | coursecompletion2 | course_1,course_2,course_3 | 1               |
      | coursecompletion3 | course_1,course_2,course_3 | 1               |
    And the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency  | scale_value        | criteria           | sortorder |
      | comp1       | start              | coursecompletion1  | 1         |
      | comp1       | middle             | coursecompletion1  | 1         |
      | comp1       | best               | coursecompletion1  | 1         |
      | comp2       | start              | coursecompletion2  | 1         |
      | comp2       | middle             | coursecompletion2  | 1         |
      | comp2       | best               | coursecompletion2  | 1         |
      | comp3       | start              | coursecompletion3  | 1         |
      | comp3       | middle             | coursecompletion3  | 1         |
      | comp3       | best               | coursecompletion3  | 1         |
  Scenario: Delete Scale Value displays warnings of pathways to be deleted
    Given I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Deletable Value scale" "link"
    And I click on "Delete" "link" in the "Intermediate" "table_row"
    Then I should see "Deleting the 'Intermediate' scale value will:"
    And I should see "Affect competencies that have achievement pathways defined against it."
    And I should see "3 achievement pathway(s) will be deleted."
    When I click on "Continue" "button"
    Then I should see "The competency scale value \"Intermediate\" has been deleted."
    And "Intermediate" "table_row" should not exist
