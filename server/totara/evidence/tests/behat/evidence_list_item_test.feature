@totara @perform @totara_evidence
Feature: Evidence bank page
  Shows a list of evidence items for a user

  Background:
    Given the following "users" exist in "totara_evidence" plugin:
      | username              | firstname   | lastname |
      | evidence_user_manager | Totara_User | Manager  |
      | evidence_user_one     | Totara_User | One      |
      | evidence_user_two     | Totara_User | Two      |
      | evidence_user_three   | Totara_User | Three    |
      | evidence_user_four    | Totara_User | Four     |
    And the following job assignments exist:
      | user                | manager               |
      | evidence_user_one   | evidence_user_manager |
      | evidence_user_two   | evidence_user_manager |
      | evidence_user_three | evidence_user_manager |
    And the following "types" exist in "totara_evidence" plugin:
      | name                | user     | fields | description |
      | Evidence_Type_One   | admin    | 1      | DESC_ONE    |
      | Evidence_Type_Two   | admin    | 2      | DESC_TWO    |
      | Evidence_Type_Three | admin    | 3      | DESC_THREE  |
    And the following "evidence" exist in "totara_evidence" plugin:
      | name           | user                | type                |
      | Evidence_One   | evidence_user_one   | Evidence_Type_One   |
      | Evidence_Two   | evidence_user_two   | Evidence_Type_Two   |
      | Evidence_Three | evidence_user_three | Evidence_Type_Three |

  Scenario: View evidence bank
    When I log in as "evidence_user_one"
    And I navigate to my evidence bank
    Then the following should exist in the "evidence_bank_self" table:
      | Name           | Type              | Creator         |
      | Evidence_One   | Evidence_Type_One | Totara_User One |
    And the following should not exist in the "evidence_bank_self" table:
      | Name           | Type                | Creator           |
      | Evidence_Two   | Evidence_Type_Two   | Totara_User Two   |
      | Evidence_Three | Evidence_Type_Three | Totara_User Three |
    When I log out
    And I log in as "evidence_user_two"
    And I navigate to my evidence bank
    Then the following should exist in the "evidence_bank_self" table:
      | Name         | Type              | Creator         |
      | Evidence_Two | Evidence_Type_Two | Totara_User Two |
    And the following should not exist in the "totara_evidence" table:
      | Name           | Type                | Creator           |
      | Evidence_One   | Evidence_Type_One   | Totara_User One   |
      | Evidence_Three | Evidence_Type_Three | Totara_User Three |
    When I log out
    And I log in as "evidence_user_three"
    And I navigate to my evidence bank
    Then the following should exist in the "evidence_bank_self" table:
      | Name           | Type                | Creator           |
      | Evidence_Three | Evidence_Type_Three | Totara_User Three |
    And the following should not exist in the "evidence_bank_self" table:
      | Name         | Type              | Creator         |
      | Evidence_One | Evidence_Type_One | Totara_User One |
      | Evidence_Two | Evidence_Type_Two | Totara_User Two |

  Scenario: View another user's evidence bank as a manager via their profile
    Given I log in as "evidence_user_manager"
    When I navigate to the evidence bank for user "evidence_user_one"
    Then I should see "Evidence bank for Totara_User One"
    And the following should exist in the "evidence_bank_other" table:
      | Name         | Type              | Creator         |
      | Evidence_One | Evidence_Type_One | Totara_User One |
    And the following should not exist in the "evidence_bank_other" table:
      | Name           | Type                | Creator           |
      | Evidence_Two   | Evidence_Type_Two   | Totara_User Two   |
      | Evidence_Three | Evidence_Type_Three | Totara_User Three |
    When I navigate to the evidence bank for user "evidence_user_two"
    Then I should see "Evidence bank for Totara_User Two"
    And the following should exist in the "evidence_bank_other" table:
      | Name         | Type              | Creator         |
      | Evidence_Two | Evidence_Type_Two | Totara_User Two |
    And the following should not exist in the "evidence_bank_other" table:
      | Name           | Type                | Creator           |
      | Evidence_One   | Evidence_Type_One   | Totara_User One   |
      | Evidence_Three | Evidence_Type_Three | Totara_User Three |
    When I navigate to the evidence bank for user "evidence_user_three"
    Then I should see "Evidence bank for Totara_User Three"
    And the following should exist in the "evidence_bank_other" table:
      | Name           | Type                | Creator           |
      | Evidence_Three | Evidence_Type_Three | Totara_User Three |
    And the following should not exist in the "evidence_bank_other" table:
      | Name         | Type              | Creator         |
      | Evidence_One | Evidence_Type_One | Totara_User One |
      | Evidence_Two | Evidence_Type_Two | Totara_User Two |

  Scenario: View another user's evidence bank as a manager via my team
    Given I log in as "evidence_user_manager"
    When I am on "Team" page
    Then "Totara_User One" "link" should exist in the "team_members" "table"
    And "Totara_User Two" "link" should exist in the "team_members" "table"
    And "Totara_User Three" "link" should exist in the "team_members" "table"
    And "Totara_User Four" "link" should not exist in the "team_members" "table"
    When I click on "Evidence" "link" in the "Totara_User One" "table_row"
    Then I should see "Evidence bank for Totara_User One"
    When I am on "Team" page
    And I click on "Evidence" "link" in the "Totara_User Two" "table_row"
    Then I should see "Evidence bank for Totara_User Two"
    When I am on "Team" page
    And I click on "Evidence" "link" in the "Totara_User Three" "table_row"
    Then I should see "Evidence bank for Totara_User Three"

  @javascript
  Scenario: Delete an evidence item
    Given I log in as "evidence_user_one"
    And the following "evidence" exist in "totara_evidence" plugin:
      | name          | user              | type                |
      | Evidence_Four | evidence_user_one | Evidence_Type_Two   |
      | Evidence_Five | evidence_user_one | Evidence_Type_Three |
    When I navigate to my evidence bank
    Then the following should exist in the "evidence_bank_self" table:
      | Name          | Type                | Creator         |
      | Evidence_One  | Evidence_Type_One   | Totara_User One |
      | Evidence_Four | Evidence_Type_Two   | Totara_User One |
      | Evidence_Five | Evidence_Type_Three | Totara_User One |
    When I click on "Delete" "link" in the "Evidence_Five" "table_row"
    Then I should see "Confirm deletion of evidence item \"Evidence_Five\""
    And I should see "The evidence item and any attached files will be deleted permanently."
    And I should see "Do you want to proceed with deletion?"
    When I click on "No" "button"
    Then the following should exist in the "evidence_bank_self" table:
      | Name          | Type                | Creator         |
      | Evidence_One  | Evidence_Type_One   | Totara_User One |
      | Evidence_Four | Evidence_Type_Two   | Totara_User One |
      | Evidence_Five | Evidence_Type_Three | Totara_User One |
    And I should not see "Evidence item \"Evidence_Five\" was successfully deleted"
    When I reload the page
    And I click on "Delete" "link" in the "Evidence_Four" "table_row"
    Then I should see "Confirm deletion of evidence item \"Evidence_Four\""
    When I click on "Yes" "button"
    Then the following should exist in the "evidence_bank_self" table:
      | Name          | Type                | Creator         |
      | Evidence_One  | Evidence_Type_One   | Totara_User One |
      | Evidence_Five | Evidence_Type_Three | Totara_User One |
    And the following should not exist in the "evidence_bank_self" table:
      | Name          | Type                | Creator         |
      | Evidence_Four | Evidence_Type_Two   | Totara_User One |
    And I should see "Evidence item \"Evidence_Four\" was successfully deleted"

  @javascript
  Scenario: Default report filters
    Given the following "evidence" exist in "totara_evidence" plugin:
      | name           | user              | type                |
      | Evidence_One   | evidence_user_one | Evidence_Type_One   |
      | Evidence_Two   | evidence_user_one | Evidence_Type_Two   |
      | Evidence_Three | evidence_user_one | Evidence_Type_Three |
    When I log in as "evidence_user_one"
    And I navigate to my evidence bank
    Then the following should exist in the "evidence_bank_self" table:
      | Name           |
      | Evidence_One   |
      | Evidence_Two   |
      | Evidence_Three |
    And I should see "Name" in the ".rb-search" "css_element"
    And I should see "Type" in the ".rb-search" "css_element"
    And I should not see "Creator" in the ".rb-search" "css_element"
    And I should not see "Creation date" in the ".rb-search" "css_element"
    When I click on "Show more" "link" in the ".rb-search" "css_element"
    Then I should see "Creator" in the ".rb-search" "css_element"
    And I should see "Creation date" in the ".rb-search" "css_element"
    When I set the field with xpath "//input[@name='base-name']" to "One"
    And I click on "Search" "button" in the ".rb-search" "css_element"
    Then the following should exist in the "evidence_bank_self" table:
      | Name         |
      | Evidence_One |
    And the following should not exist in the "evidence_bank_self" table:
      | Name           |
      | Evidence_Two   |
      | Evidence_Three |
    When I log out
    And I log in as "admin"
    And I navigate to the evidence bank for user "evidence_user_one"
    Then the following should exist in the "evidence_bank_other" table:
      | Name           |
      | Evidence_One   |
      | Evidence_Two   |
      | Evidence_Three |
    And I should see "Name" in the ".rb-search" "css_element"
    And I should see "Type" in the ".rb-search" "css_element"
    And I should not see "Creator" in the ".rb-search" "css_element"
    And I should not see "Creation date" in the ".rb-search" "css_element"
    When I click on "Show more" "link" in the ".rb-search" "css_element"
    Then I should see "Creator" in the ".rb-search" "css_element"
    And I should see "Creation date" in the ".rb-search" "css_element"
    When I set the field with xpath "//input[@name='base-name']" to "One"
    And I click on "Search" "button" in the ".rb-search" "css_element"
    Then the following should exist in the "evidence_bank_other" table:
      | Name         |
      | Evidence_One |
    And the following should not exist in the "evidence_bank_self" table:
      | Name           |
      | Evidence_Two   |
      | Evidence_Three |

  @javascript
  Scenario: Type details modal
    Given the following "types" exist in "totara_evidence" plugin:
      | name                         | user     | fields | description |
      | Evidence_Type_No_Description | admin    | 1      |             |
    And the following "evidence" exist in "totara_evidence" plugin:
      | name                    | user              | type                         |
      | Evidence_No_Description | evidence_user_one | Evidence_Type_No_Description |
    When I log in as "evidence_user_one"
    And I navigate to my evidence bank
    And I click on "Evidence_Type_One" "link" in the "Evidence_One" "table_row"
    Then I should see "Evidence_Type_One" in the ".modal-header" "css_element"
    And I should see "DESC_ONE" in the ".modal-body" "css_element"
    When I click on "Cancel" "button"
    Then I should not see "Evidence_Type_One" in the ".modal-header" "css_element"
    And I should not see "DESC_ONE" in the ".modal-body" "css_element"
    When I click on "Evidence_No_Description" "link" in the "Evidence_No_Description" "table_row"
    And I click on "Evidence_Type_No_Description" "link" in the ".tw-evidence__item_metadata_row:nth-child(2)" "css_element"
    Then I should see "Evidence_Type_No_Description" in the ".modal-header" "css_element"
    And I should see "No description available" in the ".modal-body" "css_element"
    When I click on "Cancel" "button"
    And I log out
    And I log in as "admin"
    And I navigate to the evidence bank for user "evidence_user_one"
    And I click on "Evidence_Type_One" "link" in the "Evidence_One" "table_row"
    Then I should see "Evidence_Type_One" in the ".tw-evidence__header" "css_element"
    And I should see "manage evidence types"

  @javascript
  Scenario: Edit and delete an item that is in use
    Given I log in as "evidence_user_one"
    When I navigate to my evidence bank
    Then the following should exist in the "evidence_bank_self" table:
      | Name           | Type              | Creator         |
      | Evidence_One   | Evidence_Type_One | Totara_User One |
    Given the following "plan relations" exist in "totara_evidence" plugin:
      | evidence       |
      | Evidence_One   |
    When I click on "Delete" "link" in the "Evidence_One" "table_row"
    And I click on "Yes" "button"
    Then I should see "There was an error while trying to delete evidence item \"Evidence_One\""
    And I should see "Edit" in the "Evidence_One" "table_row"
    When I reload the page
    Then I should not see "Edit" in the "Evidence_One" "table_row"
    And I should not see "Delete" in the "Evidence_One" "table_row"
    And I should see "cannot delete" in the "Evidence_One" "table_row"
    And I should see "cannot edit" in the "Evidence_One" "table_row"
