@totara @perform @totara_evidence
Feature: Evidence report with global report restriction
  Shows a list of evidence items for a user

  Background:
    Given the following config values are set as admin:
      | enableglobalrestrictions | 1 |
    And the following "users" exist in "totara_evidence" plugin:
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
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname        | shortname                   | source        |
      | Evidence report | report_evidence_item_report | evidence_item |
    And the following "cohorts" exist:
      | name               | idnumber | cohorttype |
      | No Suspended users | D1       | 2          |

  @javascript
  Scenario: Test global report restriction apply for Evidence report
    Given I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "Evidence report"
    And I switch to "Columns" tab
    And I add the "User's Fullname" column to the report
    And I add the "User Status" column to the report
    When I follow "View This Report"
   # Make sure all users are active
    Then I should see "Active" in the "Totara_User One" "table_row"
    And I should see "Active" in the "Totara_User Two" "table_row"
    And I should see "Active" in the "Totara_User Three" "table_row"
   # Suspend Totara_user One"
    And I click on "Home" in the totara menu
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Manage login of Totara_User One" "link" in the "Totara_User One" "table_row"
    And I set the "Choose" Totara form field to "Suspend user account"
    And I press "Update"
    # this is replacing the cron trigger as it fails in the jenkins behat test
    And I am on homepage
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "No Suspended users"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "User is suspended"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I wait "1" seconds
    And I press "Approve changes"
   # Test we still see the suspended user
    When I click on "Reports" in the totara menu
    And I follow "Evidence report"
    Then I should see "Suspended" in the "Totara_User One" "table_row"
    And I should see "Active" in the "Totara_User Two" "table_row"
    And I should see "Active" in the "Totara_User Three" "table_row"
   # Create the global report restriction rule to hide any suspended users
    And I am on homepage
    And I navigate to "Global report restrictions" node in "Site administration > Reports"
    And I press "New restriction"
    And I set the following fields to these values:
      | Name   | No suspended users |
      | Active | 1                  |
    And I press "Save changes"
    And I set the field "menugroupselector" to "Audience"
    And I wait "1" seconds
    And I click on "No Suspended users" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"
    And I wait "1" seconds
    And I switch to "Users allowed to select restriction" tab
    And I press "Make this restriction available to all users"
   # Test we don't see suspended users
    When I click on "Reports" in the totara menu
    And I follow "Evidence report"
    Then I should not see "Totara_User One" in the ".reportbuilder-table" "css_element"
    And I should see "Totara_User Two" in the ".reportbuilder-table" "css_element"
    And I should see "Totara_User Three" in the ".reportbuilder-table" "css_element"
    And I should not see "Suspended"
    And I should see "Active" in the "Totara_User Two" "table_row"
    And I should see "Active" in the "Totara_User Three" "table_row"
