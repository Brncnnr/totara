@totara @perform @totara_evidence @totara_plan @totara_rol @javascript
Feature: Verify that a manager can not see a staff members personal evidence records if
  they have the evidence:manageownevidenceonothers capability, but do not have viewothers or manageanyevidenceonothers

  Background:
    Given the following "users" exist in "totara_evidence" plugin:
      | username | firstname | lastname |
      | manager  | Manager   | User     |
      | user     | Staff     | Member   |
    And the following job assignments exist:
      | user | manager |
      | user | manager |
    And the following "types" exist in "totara_evidence" plugin:
      | name      | location |
      | Bank_Type | 0        |
      | ROL_Type  | 1        |
    And the following "evidence" exist in "totara_evidence" plugin:
      | name                        | user | created_by | type      |
      | Staff_Created_Evidence_Bank | user | user       | Bank_Type |
      | Staff_Created_Evidence_ROL  | user | user       | ROL_Type  |
    When I log in as "admin"
    And I set the following system permissions of "Staff Manager" role:
      | totara/evidence:viewanyevidenceonothers   | Prohibit |
      | totara/evidence:manageanyevidenceonothers | Prohibit |
      | totara/evidence:manageownevidenceonothers | Allow    |
    And I log out
    And I log in as "manager"

  Scenario: Verify only evidence the manager created is displayed in evidence bank
    When I navigate to the evidence bank for user "user"
    Then I should see "No evidence items"
    When the following "evidence" exist in "totara_evidence" plugin:
      | name                          | user | created_by | type      |
      | Manager_Created_Evidence_Bank | user | manager    | Bank_Type |
    And I reload the page
    Then the following should exist in the "evidence_bank_other" table:
      | Name                          | Creator      |
      | Manager_Created_Evidence_Bank | Manager User |
    And the following should not exist in the "evidence_bank_other" table:
      | Name                        | Creator      |
      | Staff_Created_Evidence_Bank | Staff Member |

  Scenario: Verify only evidence the manager created is displayed in record of learning
    When I am on "Team" page
    And I click on "Records" "link" in the "Staff Member" "table_row"
    Then I should see "There are no records to display"
    When the following "evidence" exist in "totara_evidence" plugin:
      | name                          | user | created_by | type      |
      | Manager_Created_Evidence_ROL  | user | manager    | ROL_Type  |
    And I reload the page
    Then the following should exist in the "evidence_record_of_learning" table:
      | Name                         |
      | Manager_Created_Evidence_ROL |
    And the following should not exist in the "evidence_record_of_learning" table:
      | Name                       |
      | Staff_Created_Evidence_ROL |

  Scenario: Verify only evidence the manager created is displayed when selecting evidence in learning plans
    Given the following "plans" exist in "totara_plan" plugin:
      | user | name            |
      | user | StaffMemberPlan |
    And the following "objectives" exist in "totara_plan" plugin:
      | user | plan            | name                 |
      | user | StaffMemberPlan | StaffMemberObjective |
    And the "mylearning" user profile block exists
    When I am on profile page for user "user"
    And I click on "Learning Plans" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    And I click on "Objectives (1)" "link"
    And I click on "StaffMemberObjective" "link"
    And I click on "Add linked evidence" "button"
    Then I should not see "Staff_Created_Evidence_Bank"
    And I should not see "Staff_Created_Evidence_ROL"
    When I click on "Search" "link"
    And I set the field "query" to "Evidence"
    And I click on "Search" "button"
    Then I should not see "Staff_Created_Evidence_Bank"
    And I should not see "Staff_Created_Evidence_ROL"
    When the following "evidence" exist in "totara_evidence" plugin:
      | name                          | user | created_by | type      |
      | Manager_Created_Evidence_Bank | user | manager    | Bank_Type |
      | Manager_Created_Evidence_ROL  | user | manager    | ROL_Type  |
    And I reload the page
    And I click on "Add linked evidence" "button"
    Then I should not see "Staff_Created_Evidence_Bank"
    And I should not see "Staff_Created_Evidence_ROL"
    And I should see "Manager_Created_Evidence_Bank"
    And I should see "Manager_Created_Evidence_ROL"
    When I click on "Search" "link"
    And I set the field "query" to "Evidence"
    And I click on "Search" "button"
    Then I should not see "Staff_Created_Evidence_Bank"
    And I should not see "Staff_Created_Evidence_ROL"
    And I should see "Manager_Created_Evidence_Bank"
    And I should see "Manager_Created_Evidence_ROL"
    When I reload the page
    And I log out
    And I log in as "admin"
    And I set the following system permissions of "Staff Manager" role:
      | totara/evidence:viewanyevidenceonothers | Allow |
    And I log out
    And I log in as "manager"
    And I am on profile page for user "user"
    And I click on "Learning Plans" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    And I click on "Objectives (1)" "link"
    And I click on "StaffMemberObjective" "link"
    And I click on "Add linked evidence" "button"
    And I click on "Staff_Created_Evidence_Bank" "link"
    And I click on "Manager_Created_Evidence_Bank" "link"
    And I click on "Save" "button" in the "Add linked evidence" "totaradialogue"
    Then I should see "Staff_Created_Evidence_Bank"
    And I should see "Manager_Created_Evidence_Bank"
    When I reload the page
    And I log out
    And I log in as "admin"
    And I set the following system permissions of "Staff Manager" role:
      | totara/evidence:viewanyevidenceonothers | Prohibit |
    And I log out
    And I log in as "manager"
    And I am on profile page for user "user"
    And I click on "Learning Plans" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    And I click on "Objectives (1)" "link"
    And I click on "StaffMemberObjective" "link"
    Then I should not see "Staff_Created_Evidence_Bank"
    And I should see "Manager_Created_Evidence_Bank"
