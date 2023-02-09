@totara @perform @totara_competency @javascript
Feature: Access control for copy competency achievement paths page

  Background:
    Given I am on a totara site
    And a competency scale called "ggb" exists with the following values:
      | name  | description          | idnumber | proficient | default | sortorder |
      | Great | Is great at doing it | great    | 1          | 0       | 1         |
      | Good  | Is ok at doing it    | good     | 0          | 0       | 2         |
      | Bad   | Has no idea          | bad      | 0          | 1       | 3         |
    And the following "competency" frameworks exist:
      | fullname             | idnumber | description                | scale |
      | Competency Framework | fw1      | Framework for Competencies | ggb   |
    And the following "competency" hierarchy exists:
      | framework | fullname                         | idnumber               | description                       |
      | fw1       | Competency no paths              | no_paths               | Competency no paths               |
      | fw1       | Competency w paths               | w_paths                | Competency w paths                |
      | fw1       | Competency w criteria-based path | w_criteria-based-paths | Competency w criteria-based paths |
    And the following "manual pathways" exist in "totara_competency" plugin:
      | competency | roles   | sortorder |
      | w_paths    | manager | 1         |
    And the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency             | scale_value | criteria      | sortorder |
      | w_criteria-based-paths | great       | linkedcourses | 1         |

  Scenario: Accessing copying page for competency without pathways
    When I log in as "admin"
    And I navigate to the competency pathways copying page for the "Competency no paths" competency
    Then I should see "There are no achievement paths for the selected competency"
    And I should not see "Select target competencies"

  Scenario: Accessing copying page for competency with pathways
    When I log in as "admin"
    And I navigate to the competency pathways copying page for the "Competency w paths" competency
    Then I should see "Copy achievement paths from: ‘Competency w paths’"
    And I should not see "There are no achievement paths for the selected competency"
    And I should see "Select target competencies"

  Scenario: Accessing copying page for competency with criteria group pathways
    When I log in as "admin"
    And I navigate to the competency pathways copying page for the "Competency w criteria-based path" competency
    Then I should see "Copy achievement paths from: ‘Competency w criteria-based path’"
    And I should see "All associated courses and competencies will not be copied over in criteria-based paths."
    And I should see "Select target competencies"