@totara @perform @totara_evidence @javascript
Feature: Evidence type index page
  Shows list of evidence types
  With options to add, edit and delete evidence type

  Background:
    Given the following "types" exist in "totara_evidence" plugin:
      | name                    | idnumber                   | description                         | fields |
      | EvidenceTest_type_one   | EvidenceTest_type_one_id   | EvidenceTest_type_one_description   | 1      |
      | EvidenceTest_type_two   | EvidenceTest_type_two_id   | EvidenceTest_type_two_description   | 2      |
      | EvidenceTest_type_three | EvidenceTest_type_three_id | EvidenceTest_type_three_description | 3      |
      | EvidenceTest_type_four  | EvidenceTest_type_four_id  | EvidenceTest_type_four_description  | 4      |
      | EvidenceTest_type_five  | EvidenceTest_type_five_id  | EvidenceTest_type_five_description  | 5      |
    And the following "types" exist in "totara_evidence" plugin:
      | name                 | location |
      | Evidence_Type_System | 1        |
    When I log in as "admin"
    And I navigate to "Evidence > Manage types" in site administration

  @javascript
  Scenario: Add an evidence type
    Given I click on "Add evidence type" "link"
    Then I should see "Add an evidence type" in the ".tw-evidence__header_titleBtns_title" "css_element"
    And I should see "Type name"
    And I should see "Type ID number"
    And I should see "Type description"
    When I set the following fields to these values:
      | Type name        | EvidenceTest_type_six             |
      | Type ID number   | EvidenceTest_type_six_id          |
      | Type description | EvidenceTest_type_six_description |
    And I click on "Save and continue" "button"
    Then I should see "Evidence type \"EvidenceTest_type_six\" was created"
    And I should see "No fields have been defined"
    When I set the field "Create a new custom field" to "Checkbox"
    Then I should see "Editing custom field: Checkbox"
    When I set the following fields to these values:
      | Full name                   | EvidenceTest_type_six_field_one             |
      | Short name (must be unique) | EvidenceTesttypesixfieldoneid               |
      | Description of the field    | EvidenceTest_type_six_field_one_description |
    And I click on "Save changes" "button"
    Then the following should exist in the "customfields_program" table:
      | Custom field                      | Type     |
      | EvidenceTest_type_six_field_one   | Checkbox |
    When I click on "Back to manage evidence types" "link"
    Then the following should exist in the "evidence_type" table:
      | Type name               | Type ID number             |
      | EvidenceTest_type_one   | EvidenceTest_type_one_id   |
      | EvidenceTest_type_two   | EvidenceTest_type_two_id   |
      | EvidenceTest_type_three | EvidenceTest_type_three_id |
      | EvidenceTest_type_four  | EvidenceTest_type_four_id  |
      | EvidenceTest_type_five  | EvidenceTest_type_five_id  |
      | EvidenceTest_type_six   | EvidenceTest_type_six_id   |

  Scenario: Edit evidence types
    Given I should see "EvidenceTest_type_three"
    When I click on "Edit" "link" in the "EvidenceTest_type_three" "table_row"
    And I click on "General" "link"
    Then I should see "EvidenceTest_type_three" in the ".tw-evidence__header_titleBtns_title" "css_element"
    And the following fields match these values:
      | Type name        | EvidenceTest_type_three             |
      | Type ID number   | EvidenceTest_type_three_id          |
      | Type description | EvidenceTest_type_three_description |
    When I set the following fields to these values:
      | Type name        | EvidenceTest_type_seven             |
      | Type ID number   | EvidenceTest_type_seven_id          |
      | Type description | EvidenceTest_type_seven_description |
    And I click on "Save changes" "button"
    Then I should see "Evidence type \"EvidenceTest_type_seven\" was updated"
    And the following should exist in the "customfields_program" table:
      | Custom field     |
      | Evidence field 4 |
      | Evidence field 5 |
      | Evidence field 6 |
    When I click on "Back to manage evidence types" "link"
    Then the following should exist in the "evidence_type" table:
      | Type name               | Type ID number             |
      | EvidenceTest_type_seven | EvidenceTest_type_seven_id |
    And the following should not exist in the "evidence_type" table:
      | Type name               | Type ID number             |
      | EvidenceTest_type_three | EvidenceTest_type_three_id |

  Scenario: Delete all evidence types
    Given I should see "Delete"
    When I click on "Delete" "link"
    And I click on "Yes" "button"
    Then the following should not exist in the "evidence_type" table:
      | Type name               | Type ID number             |
      | EvidenceTest_type_one   | EvidenceTest_type_one_id   |

  @javascript
  Scenario: Edit a type that is in use
    Given the following "evidence" exist in "totara_evidence" plugin:
      | name           | type                    |
      | Evidence_One   | EvidenceTest_type_three |
    When I click on "Delete" "link" in the "EvidenceTest_type_three" "table_row"
    And I click on "Yes" "button"
    Then I should see "There was an error while trying to delete evidence type \"EvidenceTest_type_three\""
    And I should see "Edit" in the "EvidenceTest_type_three" "table_row"
    When I reload the page
    Then I should not see "Edit" in the "EvidenceTest_type_three" "table_row"
    And I should not see "Delete" in the "EvidenceTest_type_three" "table_row"
    And I should see "cannot delete" in the "EvidenceTest_type_three" "table_row"
    And I should see "cannot edit" in the "EvidenceTest_type_three" "table_row"

  @javascript
  Scenario: View an evidence type
    Given I click on "EvidenceTest_type_three" "link"
    Then I should see "Back to manage evidence types" in the ".tw-evidence__header" "css_element"
    And I should see "EvidenceTest_type_three" in the ".tw-evidence__header" "css_element"
    And I should see "Edit this type" in the ".tw-evidence__header" "css_element"
    And I should see the evidence metadata contains:
      | ID number   | EvidenceTest_type_three_id          |
      | Description | EvidenceTest_type_three_description |
    And the following should exist in the "table-evidence-type-fields" table:
      | Custom field name | Type       |
      | Custom Field #1   | Text input |
      | Custom Field #2   | Text input |
      | Custom Field #3   | Text input |
    When I click on "Custom Field #2" "link"
    Then I should see "Back to EvidenceTest_type_three" in the ".tw-evidence__header" "css_element"
    And I should see "Text input" in the ".tw-evidence__header_titleBtns_title" "css_element"
    And I should see "Custom Field #2" in the ".tw-evidence__header_titleBtns_title" "css_element"
    And the following fields match these values:
      | Full name                   | Custom Field #2 |
      | Short name (must be unique) | FIELD2          |
    And the "Full name" "field" should be readonly
    And the "Short name (must be unique)" "field" should be readonly
    When I click on "EvidenceTest_type_three" "link" in the ".breadcrumb-nav" "css_element"
    Then I should see "EvidenceTest_type_three" in the ".tw-evidence__header" "css_element"
    When I click on "Custom Field #2" "link"
    And I click on "Back to EvidenceTest_type_three" "link" in the ".tw-evidence__header" "css_element"
    Then I should see "EvidenceTest_type_three" in the ".tw-evidence__header" "css_element"
    When the following "evidence" exist in "totara_evidence" plugin:
      | name         | type                    |
      | Evidence_One | EvidenceTest_type_three |
    And I click on "Custom Field #2" "link"
    And I click on "Go back" "button"
    Then I should see "EvidenceTest_type_three" in the ".tw-evidence__header" "css_element"
    And I should not see "Edit this type" in the ".tw-evidence__header" "css_element"

  Scenario: Record of learning type can be modified
    Given I navigate to "Evidence > Manage types" in site administration
    Then I should see "Edit" in the "Evidence_Type_System" "table_row"
    And I should see "Delete" in the "Evidence_Type_System" "table_row"
    And I should see "Hide" in the "Evidence_Type_System" "table_row"
    When I click on "Evidence_Type_System" "link" in the "Evidence_Type_System" "table_row"
    Then I should see "Edit this type"