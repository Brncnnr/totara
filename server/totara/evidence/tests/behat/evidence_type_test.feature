@totara @perform @totara_evidence @javascript
Feature: Evidence type creation/editing/deletion workflow

  Background:
    When I log in as "admin"
    And I navigate to "Evidence > Manage types" in site administration

  Scenario: Ensure that the default completion system types exist
    Then I should see "Manage evidence types"
    And the following should exist in the "evidence_type" table:
      | Type name                                     | Type ID number                |
      | Course completion import (system type)        | coursecompletionimport        |
      | Certification completion import (system type) | certificationcompletionimport |

  Scenario: Error messages are shown when giving invalid values for name and ID number
    Given I click on "Add evidence type" "link"
    When I set the field "Type name" to "  "
    And I click on "Save and continue" "button"
    Then I should see "Form could not be submitted, validation failed"
    And I should see "Must specify a name"
    When I set the field "Type ID number" to "coursecompletionimport"
    And I click on "Save and continue" "button"
    Then I should see "Form could not be submitted, validation failed"
    And I should see "Must specify a name"
    And I should see "Record with this ID number already exists"
    When I set the field "Type name" to "Valid Name"
    And I set the field "Type ID number" to ""
    And I click on "Save and continue" "button"
    Then I should see "Evidence type \"Valid Name\" was created"

  Scenario: Add an evidence type
    Given I click on "Add evidence type" "link"
    Then I should see "Add an evidence type" in the ".tw-evidence__header_titleBtns_title" "css_element"
    And I should see "General" in the "div.tabtree > ul > li.active > a" "css_element"
    And "div.tabtree > ul > li:not(disabled) > a[href*='fields']" "css_element" should not exist
    And I should see "Type name"
    And I should see "Type ID number"
    And I should see "Type description"
    When I set the following fields to these values:
      | Type name        | EvidenceTest_type_one             |
      | Type ID number   | EvidenceTest_type_one_id          |
      | Type description | EvidenceTest_type_one_description |
    And I click on "Save and continue" "button"
    Then I should see "Evidence type \"EvidenceTest_type_one\" was created"
    And I should see "EvidenceTest_type_one" in the ".tw-evidence__header_titleBtns_title" "css_element"
    And I should see "General" in the "div.tabtree > ul > li:not(active) > a" "css_element"
    And I should see "Custom Fields" in the "div.tabtree > ul > li.active > a" "css_element"
    And I should see "No fields have been defined"
    When I set the field "Create a new custom field" to "Checkbox"
    Then I should see "Editing custom field: Checkbox"
    When I set the following fields to these values:
      | Full name                   | EvidenceTest_type_one_field_one             |
      | Short name (must be unique) | EvidenceTesttypeonefieldoneid               |
      | Description of the field    | EvidenceTest_type_one_field_one_description |
    And I click on "Save changes" "button"
    Then the following should exist in the "customfields_program" table:
      | Custom field                      | Type     |
      | EvidenceTest_type_one_field_one   | Checkbox |
    When I click on "Back to manage evidence types" "link"
    Then the following should exist in the "evidence_type" table:
      | Type name             | Type ID number           |
      | EvidenceTest_type_one | EvidenceTest_type_one_id |

  Scenario: Edit an evidence type
    Given I click on "Add evidence type" "link"
    When I set the following fields to these values:
      | Type name        | EvidenceTest_type_one             |
      | Type ID number   | EvidenceTest_type_one_id          |
      | Type description | EvidenceTest_type_one_description |
    And I click on "Save and continue" "button"
    And I click on "Back to manage evidence types" "link"
    Then I should see "Edit"
    When I click on "Edit" "link"
    And I click on "General" "link"
    Then I should see "EvidenceTest_type_one" in the ".tw-evidence__header_titleBtns_title" "css_element"
    And I should see "General" in the "div.tabtree > ul > li.active > a" "css_element"
    And I should see "Custom Fields" in the "div.tabtree > ul > li:not(active) > a[href*='fields']" "css_element"
    And the following fields match these values:
      | Type name        | EvidenceTest_type_one             |
      | Type ID number   | EvidenceTest_type_one_id          |
      | Type description | EvidenceTest_type_one_description |
    When I set the following fields to these values:
      | Type name        | EvidenceTest_type_two             |
      | Type ID number   | EvidenceTest_type_two_id          |
      | Type description | EvidenceTest_type_two_description |
    And I click on "Save changes" "button"
    Then I should see "Evidence type \"EvidenceTest_type_two\" was updated"
    And I should see "EvidenceTest_type_two" in the ".tw-evidence__header_titleBtns_title" "css_element"
    When I click on "Back to manage evidence types" "link"
    Then the following should exist in the "evidence_type" table:
      | Type name             | Type ID number           |
      | EvidenceTest_type_two | EvidenceTest_type_two_id |
    And the following should not exist in the "evidence_type" table:
      | Type name             | Type ID number           |
      | EvidenceTest_type_one | EvidenceTest_type_one_id |

  Scenario: Delete an evidence type
    Given I click on "Add evidence type" "link"
    When I set the following fields to these values:
      | Type name        | EvidenceTest_type_one    |
      | Type ID number   | EvidenceTest_type_one_id |
    And I click on "Save and continue" "button"
    And I click on "Back to manage evidence types" "link"
    Then I should see "Delete"
    When I click on "Delete" "link"
    Then I should see "Confirm deletion of evidence type"
    And I should see "Do you want to proceed with deletion?"
    When I click on "No" "button"
    Then I should not see "Evidence type \"EvidenceTest_type_one\" was successfully deleted"
    And the following should exist in the "evidence_type" table:
      | Type name             | Type ID number           |
      | EvidenceTest_type_one | EvidenceTest_type_one_id |
    When I reload the page
    And I click on "Delete" "link"
    Then I should see "Confirm deletion of evidence type"
    And I should see "Do you want to proceed with deletion?"
    When I click on "Yes" "button"
    Then I should see "Evidence type \"EvidenceTest_type_one\" was successfully deleted"
    And the following should not exist in the "evidence_type" table:
      | Type name             | Type ID number           |
      | EvidenceTest_type_one | EvidenceTest_type_one_id |
    And the following should exist in the "evidence_type" table:
      | Type name                                     | Type ID number                |
      | Course completion import (system type)        | coursecompletionimport        |
      | Certification completion import (system type) | certificationcompletionimport |