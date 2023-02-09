@totara @perform @totara_evidence @javascript
Feature: Evidence type visibility
  Show list of evidence types
  With option to change their visibility

  Background:
    Given the following "types" exist in "totara_evidence" plugin:
      | name            | status |
      | Evidence_Type_A | 0      |
      | Evidence_Type_B | 1      |
    When I log in as "admin"

  Scenario: Hide an evidence type
    When I navigate to "Evidence > Manage types" in site administration
    And I click on "Show" "link" in the "Evidence_Type_A" "table_row"
    Then I should see "Evidence type \"Evidence_Type_A\" is now visible"
    And I should see "Hide" in the "Evidence_Type_A" "table_row"
    And I should not see "Show" in the "Evidence_Type_A" "table_row"
    When I click on "Hide" "link" in the "Evidence_Type_B" "table_row"
    Then I should see "Evidence type \"Evidence_Type_B\" is now hidden"
    And I should see "Show" in the "Evidence_Type_B" "table_row"
    And I should not see "Hide" in the "Evidence_Type_B" "table_row"
    When I click on "Hide" "link" in the "Evidence_Type_A" "table_row"
    Then I should see "Evidence type \"Evidence_Type_A\" is now hidden"
    And I should see "Show" in the "Evidence_Type_A" "table_row"
    And I should not see "Hide" in the "Evidence_Type_A" "table_row"

  Scenario: Hidden evidence in type search
    When I navigate to my evidence bank
    And I click on "Add evidence item" "link"
    And I expand the evidence type selector
    Then I should see the evidence type selector contains:
      | Evidence_Type_B |
    When I navigate to "Evidence > Manage types" in site administration
    When I click on "Show" "link" in the "Evidence_Type_A" "table_row"
    And I click on "Hide" "link" in the "Evidence_Type_B" "table_row"
    When I navigate to my evidence bank
    And I click on "Add evidence item" "link"
    And I expand the evidence type selector
    Then I should see the evidence type selector contains:
      | Evidence_Type_A |