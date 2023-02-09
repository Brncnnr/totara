@totara @perform @totara_evidence
Feature: Evidence item creation and editing for another user as a manager

  Background:
    Given the following "users" exist in "totara_evidence" plugin:
      | username | firstname | lastname |
      | manager  | Manager   | User     |
      | user     | Evidence  | User     |
    And the following job assignments exist:
      | user | manager |
      | user | manager |
    When I log in as "manager"
    And I navigate to the evidence bank for user "user"

  @javascript
  Scenario: Create an evidence item for another user and view it
    Given the following "types" exist in "totara_evidence" plugin:
      | name            | user     | fields |
      | Evidence_Type_A | admin    | 3      |
    When I click on "Add evidence" "link"
    And I expand the evidence type selector
    And I select type "Evidence_Type_A" from the evidence type selector
    And I click on "Use this type" "link"
    And I should see "New Evidence_Type_A"
    And the following fields match these values:
      | Evidence name |  |
    When I set the following fields to these values:
      | Evidence name   | Evidence_One |
      | Custom Field #1 | Field_Data_1 |
      | Custom Field #2 | Field_Data_2 |
      | Custom Field #3 | Field_Data_3 |
    And I click on "Save evidence item" "button"
    Then I should see "Evidence bank for Evidence User"
    And I should see "Evidence item \"Evidence_One\" was created"
    And the following should exist in the "evidence_bank_other" table:
      | Name         | Type            | Creator      |
      | Evidence_One | Evidence_Type_A | Manager User |
    When I click on "Evidence_One" "link"
    Then I should see "Evidence_One" in the "h2.tw-evidence__header_titleBtns_title" "css_element"
    And I should see "Edit this item" in the ".tw-evidence__header_titleBtns" "css_element"
    And I should see the evidence item fields contain:
      | Custom Field #1 | Field_Data_1 |
      | Custom Field #2 | Field_Data_2 |
      | Custom Field #3 | Field_Data_3 |
    And I should see the evidence metadata contains:
      | Type             | Evidence_Type_A |
      | Subject user     | Evidence User   |
      | Created by       | Manager User    |
      | Last modified by | Manager User    |
    When I click on "Back to evidence bank for Evidence User" "link"
    Then I should see "Evidence bank for Evidence User"
    When I log out
    And I log in as "admin"
    And I set the following system permissions of "Authenticated User" role:
      | totara/evidence:manageanyevidenceonself | Prohibit |
    And I log out
    And I log in as "user"
    And I navigate to my evidence bank
    Then I should see "Evidence_One"
    And I should see "You cannot edit this evidence because you did not create it"
    And I should see "You cannot delete this evidence because you did not create it"
    When I click on "Evidence_One" "link"
    And I should not see "Edit this item" in the ".tw-evidence__header_titleBtns" "css_element"
    And I should see "Evidence User" in the ".tw-evidence__item_metadata_row:nth-child(3) a" "css_element"
    And I should see "Manager User" in the ".tw-evidence__item_metadata_row:nth-child(4)" "css_element"

  @javascript
  Scenario: Links go back to correct evidence bank
    Given the following "types" exist in "totara_evidence" plugin:
      | name            | user     | fields |
      | Evidence_Type_A | admin    | 3      |
    Then I should see "No evidence items"
    When I click on "Add evidence" "link"
    And I click on "Back to evidence bank for Evidence User" "link"
    And I click on "Add evidence" "link"
    Then I should see "Add an evidence item for Evidence User"
    When I expand the evidence type selector
    And I select type "Evidence_Type_A" from the evidence type selector
    And I click on "Use this type" "link"
    And I click on "Add an evidence item for Evidence User" "link"
    Then I should see "Add an evidence item for Evidence User"
    When I expand the evidence type selector
    And I select type "Evidence_Type_A" from the evidence type selector
    And I click on "Use this type" "link"
    And I click on "Back to evidence bank for Evidence User" "link"
    Then I should see "Evidence bank for Evidence User"
    When I click on "Add evidence" "link"
    And I expand the evidence type selector
    And I select type "Evidence_Type_A" from the evidence type selector
    And I click on "Use this type" "link"
    And I click on "Cancel" "button"
    Then I should see "Evidence bank for Evidence User"

  Scenario: View others evidence bank via link in user profile
    Given the "mylearning" user profile block exists
    When I am on profile page for user "user"
    And I click on "Evidence bank" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Evidence bank for Evidence User" in the ".tw-evidence__header" "css_element"
    And I should see "No evidence items have been added yet."
