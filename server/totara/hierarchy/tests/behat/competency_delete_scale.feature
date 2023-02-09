@totara @perform @totara_competency @javascript
Feature: Delete competency scales.

  Background:
    Given I am on a totara site
    And a competency scale called "Assigned scale" exists with the following values:
      | name         | description  | idnumber     | proficient | default | sortorder |
      | Beginner     | Start        | start        | 0          | 1       | 1         |
      | Intermediate | Experienced  | middle       | 0          | 0       | 2         |
      | World-class  | Veteran      | best         | 1          | 0       | 3         |
    And a competency scale called "Unassigned scale" exists with the following values:
      | name         | description  | idnumber     | proficient | default | sortorder |
      | Beginner     | Start        | start        | 0          | 1       | 1         |
      | Intermediate | Experienced  | middle       | 0          | 0       | 2         |
      | World-class  | Veteran      | best         | 1          | 0       | 3         |
    And the following "competency" frameworks exist:
      | fullname                 | idnumber | description                    | scale          |
      | Assigned Scale Framework | asf1     | Framework for Competencies     | Assigned scale |

  Scenario: Only unused competency scales can be deleted
    Given I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    Then I should see "Assigned but not used" in the "Assigned scale" "table_row"
    And "Delete" "link" should not exist in the "Assigned scale" "table_row"
    And I should see "No" in the "Unassigned scale" "table_row"
    And "Delete" "link" should exist in the "Unassigned scale" "table_row"

    When I click on "Delete" "link" in the "Unassigned scale" "table_row"
    Then I should see "Are you absolutely sure you want to completely delete this scale?"
    When I click on "Continue" "button"
    Then I should see "The competency scale \"Unassigned scale\" has been completely deleted."
    And "Unassigned scale" "table_row" should not exist
