@totara @perform @totara_evidence
Feature: Verify evidence type multi-lang names, descriptions and custom field names resolve correctly

  Background:
    Given the following "types" exist in "totara_evidence" plugin:
      | name                        | description                 |
      | multilang:completion_legacy | multilang:completion_legacy |
    When I log in as "admin"
    And I navigate to "Evidence > Manage types" in site administration
    And I click on "Edit this report" "button"
    And I click on "Columns" "link"
    And I set the field "newcolumns" to "Description"
    And I click on "Save changes" "button"
    And I click on "Filters" "link"
    And I set the field "newstandardfilter" to "Type name"
    And I click on "Save changes" "button"
    And I set the field "newstandardfilter" to "Description"
    And I click on "Save changes" "button"
    And I click on "View This Report" "link"

  @javascript
  Scenario: Verify language strings are correct
    Then I should see "This is a system type" in the "Legacy course/certification completion import (system type)" "table_row"
    When I click on "Legacy course/certification completion import (system type)" "link"
    Then I should see "Legacy course/certification completion import (system type)" in the ".tw-evidence__header_titleBtns_title" "css_element"
    And I should see "This is a system type"
    When I click on "Edit this type" "link"
    And I set the field "Create a new custom field" to "Checkbox"
    And I set the following fields to these values:
      | Full name                   | multilang:old_type |
      | Short name (must be unique) | multilangoldtype   |
    And I click on "Save changes" "button"
    And I click on "Back to manage evidence types" "link"
    And I click on "Legacy course/certification completion import (system type)" "link"
    And the following should exist in the "table-evidence-type-fields" table:
      | Custom field name |
      | Old Type Name     |
    When I click on "Old Type Name" "link"
    Then I should see "Checkbox: Old Type Name" in the ".tw-evidence__header_titleBtns_title" "css_element"
    When I navigate to my evidence bank
    And I click on "Add evidence item" "link"
    And I expand the evidence type selector
    And I select type "Legacy course/certification completion import (system type)" from the evidence type selector
    Then I should see "Legacy course/certification completion import (system type)"
    And I should see "This is a system type"
    And I click on "Use this type" "link"
    And I should see "New Legacy course/certification completion import (system type)"
    And I should see "Old Type Name"
    When I click on "Save evidence item" "button"
    And I click on "Legacy course/certification completion import (system type)" "link"
    Then I should see "Old Type Name"
