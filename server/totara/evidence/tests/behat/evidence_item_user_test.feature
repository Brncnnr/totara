@totara @perform @totara_evidence
Feature: Evidence item creation and editing

  Background:
    Given the following "users" exist in "totara_evidence" plugin:
      | username      | firstname | lastname |
      | evidence_user | Evidence  | User     |
    When I log in as "evidence_user"
    And I navigate to my evidence bank

  Scenario: Show a message when there are no types
    Given I click on "Add evidence" "link"
    Then I should see "Back to evidence bank"
    And I should see "Add an evidence item"
    And I should see "No evidence types"
    And I should not see "Select or search for a type"

  @javascript
  Scenario: Search for a type
    Given the following "types" exist in "totara_evidence" plugin:
      | name            | user     | fields | description |
      | Evidence_Type_B | admin    | 2      | Two         |
      | Evidence_Type_C | admin    | 3      | Three       |
      | Evidence_Type_A | admin    | 1      | One         |
    When I click on "Add evidence" "link"
    Then I should see "Back to evidence bank"
    And I should see "Add an evidence item"
    And I should see "Evidence type" in the ".tw-evidence__select_type_selector" "css_element"
    When I expand the evidence type selector
    Then I should see the evidence type selector contains:
      | Evidence_Type_A |
      | Evidence_Type_B |
      | Evidence_Type_C |
    When I search for "Evidence_Type_" in the evidence type selector
    Then I should see the evidence type selector contains:
      | Evidence_Type_A |
      | Evidence_Type_B |
      | Evidence_Type_C |
    When I search for "Evidence_Type_C" in the evidence type selector
    And I wait "1" seconds
    Then I should see the evidence type selector contains:
      | Evidence_Type_C |

  @javascript
  Scenario: Cancel selection of a type
    Given the following "types" exist in "totara_evidence" plugin:
      | name            | user     | fields | description |
      | Evidence_Type_A | admin    | 1      | One         |
      | Evidence_Type_B | admin    | 2      | Two         |
      | Evidence_Type_C | admin    | 3      | Three       |
    When I click on "Add evidence" "link"
    And I expand the evidence type selector
    And I select type "Evidence_Type_B" from the evidence type selector
    Then I should see "Evidence_Type_B" in the "h3.tw-evidence__select_type_info_metadata_name" "css_element"
    And I should see "Two" in the "div.tw-evidence__select_type_info_metadata_description" "css_element"
    When I click on "Cancel" "link"
    And I wait until the page is ready
    Then "h3.tw-evidence__select_type_info_metadata_name" "css_element" should not exist
    And I should not see "Cancel"
    When I expand the evidence type selector
    And I select type "Evidence_Type_B" from the evidence type selector
    Then I should see "Evidence_Type_B" in the "h3.tw-evidence__select_type_info_metadata_name" "css_element"
    And I should see "Two" in the "div.tw-evidence__select_type_info_metadata_description" "css_element"

  @javascript
  Scenario: Create an evidence item then edit it
    Given the following "types" exist in "totara_evidence" plugin:
      | name            | user     | fields |
      | Evidence_Type_A | admin    | 3      |
    Then I should see "No evidence items"
    When I click on "Add evidence" "link"
    And I expand the evidence type selector
    And I select type "Evidence_Type_A" from the evidence type selector
    And I click on "Use this type" "link"
    Then I should see "Back to evidence bank"
    And I should see "New Evidence_Type_A"
    And the following fields match these values:
      | Evidence name |  |
    When I set the following fields to these values:
      | Evidence name   |              |
      | Custom Field #1 | Field_Data_1 |
      | Custom Field #2 |              |
      | Custom Field #3 | Field_Data_3 |
    And I click on "Save evidence item" "button"
    Then I should see "This field is required"
    When I set the following fields to these values:
      | Custom Field #2 | Field_Data_2 |
    And I click on "Save evidence item" "button"
    Then I should see "Evidence bank"
    And I should see "Evidence item \"Evidence User's Evidence_Type_A\" was created"
    And the following should exist in the "evidence_bank_self" table:
      | Name                            | Type            | Creator       |
      | Evidence User's Evidence_Type_A | Evidence_Type_A | Evidence User |
    When I click on "Edit" "link"
    Then I should see "Edit Evidence User's Evidence_Type_A"
    And the following fields match these values:
      | Custom Field #1 | Field_Data_1 |
      | Custom Field #2 | Field_Data_2 |
      | Custom Field #3 | Field_Data_3 |
    When I set the following fields to these values:
      | Evidence name   | Evidence_Two |
      | Custom Field #1 | Field_Data_4 |
      | Custom Field #2 | Field_Data_5 |
      | Custom Field #3 | Field_Data_6 |
    And I click on "Save changes" "button"
    Then I should see "Evidence bank"
    And I should see "Evidence item \"Evidence_Two\" was updated"
    And the following should exist in the "evidence_bank_self" table:
      | Name         | Type            | Creator       |
      | Evidence_Two | Evidence_Type_A | Evidence User |
    And the following should not exist in the "evidence_bank_self" table:
      | Name         | Type            | Creator       |
      | Evidence_One | Evidence_Type_A | Evidence User |

  @javascript
  Scenario: Cancel evidence form
    Given the following "types" exist in "totara_evidence" plugin:
      | name          | user     | fields | description |
      | Evidence_Type | admin    | 1      | One         |
    When I click on "Add evidence" "link"
    And I expand the evidence type selector
    And I select type "Evidence_Type" from the evidence type selector
    And I click on "Use this type" "link"
    And I click on "Cancel" "button"
    Then I should see "Evidence bank"
    And I should see "No evidence items"

  @javascript
  Scenario: View completed evidence
    Given the following "types" exist in "totara_evidence" plugin:
      | name              | user          | fields |
      | Evidence_Type_One | evidence_user | 1      |
    And the following "evidence" exist in "totara_evidence" plugin:
      | name         | user          | type              |
      | Evidence_One | evidence_user | Evidence_Type_One |
    And I reload the page
    When I click on "Evidence_One" "link"
    Then I should see "Evidence_One" in the "h2.tw-evidence__header_titleBtns_title" "css_element"
    And I should see "Edit this item" in the ".tw-evidence__header_titleBtns" "css_element"
    And I should see "Details" in the ".tw-evidence__item_metadata_title" "css_element"
    And I should see the evidence metadata contains:
      | Type             | Evidence_Type_One |
      | Subject user     | Evidence User     |
      | Created by       | Evidence User     |
      | Last modified by | Evidence User     |
    And I should see "Created on" in the ".tw-evidence__item_metadata_row:nth-child(6)" "css_element"
    And I should see "Last modified on" in the ".tw-evidence__item_metadata_row:nth-child(7)" "css_element"
    And I should not see "Evidence name"
    And I should not see "Save changes"
    And I should see "Evidence_One Dummy Data #0" in the "Custom Field #1" evidence item field
    When I click on "Edit this item" "link"
    Then I should see "Edit Evidence_One" in the "h2.tw-evidence__header_titleBtns_title" "css_element"
    When I click on "Cancel" "button"
    Then I should not see "Edit Evidence_One" in the "h2.tw-evidence__header_titleBtns_title" "css_element"
    When I click on "Edit this item" "link"
    Then I should see "Edit Evidence_One" in the "h2.tw-evidence__header_titleBtns_title" "css_element"
    And the "Custom Field #1" "field" should not be readonly
    And the following fields match these values:
      | Evidence name   | Evidence_One               |
      | Custom Field #1 | Evidence_One Dummy Data #0 |
    When I set the following fields to these values:
      | Evidence name   | Evidence_One_Modified |
      | Custom Field #1 | Foobar                |
    And I click on "Save changes" "button"
    And I should see "Foobar" in the "Custom Field #1" evidence item field

  @javascript
  Scenario: Show message when type has no description
    Given the following "types" exist in "totara_evidence" plugin:
      | name                         | user     | description |
      | Evidence_Type_No_Description | admin    |             |
    When I click on "Add evidence" "link"
    And I expand the evidence type selector
    And I select type "Evidence_Type_No_Description" from the evidence type selector
    Then I should see "Evidence_Type_No_Description" in the "h3.tw-evidence__select_type_info_metadata_name" "css_element"
    And I should see "No description available" in the "div.tw-evidence__select_type_info_metadata_description" "css_element"

  Scenario: View own evidence bank via link in user profile
    Given the "mylearning" user profile block exists
    When I am on profile page for user "evidence_user"
    And I click on "Evidence bank" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Evidence bank" in the ".tw-evidence__header" "css_element"
    And I should see "No evidence items have been added yet."
