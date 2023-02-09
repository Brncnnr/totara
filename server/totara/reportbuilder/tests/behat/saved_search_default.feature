@totara @totara_reportbuilder @javascript
Feature: Test that saved search defaults in report builder works correctly
  In order to test saved search defaults in report builder
  I log in as the administrator and create a report

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname        | lastname | email                 |
      | user1    | User1-firstname  | Test     | user1@example.com     |
      | user2    | User2-firstname  | Test     | user2@example.com     |
      | user3    | User3-firstname  | Test     | user3@example.com     |
      | user4    | User4-firstname  | Test     | user4@example.com     |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname             | shortname                   | source |
      | Custom user report 1 | report_custom_user_report_1 | user   |
      | Custom user report 2 | report_custom_user_report_2 | user   |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "Custom user report 1"
    Then I should see "Edit Report 'Custom user report 1'"
    When I switch to "Access" tab
    And I set the following fields to these values:
      | Authenticated User | 1 |
    And I press "Save changes"
    Then I should see "Report Updated"

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "Custom user report 2"
    Then I should see "Edit Report 'Custom user report 2'"
    When I switch to "Access" tab
    And I set the following fields to these values:
      | Authenticated User | 1 |
    And I press "Save changes"
    Then I should see "Report Updated"
    And I log out

  Scenario: Only shared saved search can be set as the report wide default
    Given I log in as "admin"
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    And I set the field "user-fullname" to "User1-firstname Test"
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the field "Search Name" to "Search 1"
    Then the "isdefault" "field" should be disabled
    When I click on "Shared" "radio"
    Then the "isdefault" "field" should be enabled
    When I click on "Private" "radio"
    Then the "isdefault" "field" should be disabled

  Scenario: Report manager can define a report wide default search
    Given I log in as "admin"
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    Then "Save this search" "button" should not exist

    # Create a shared search.
    When I set the field "user-fullname" to "User1-firstname Test"
    And I press "id_submitgroupstandard_addfilter"
    And I should see "User1-firstname Test"
    And I should not see "User2-firstname Test"
    And I should not see "User3-firstname Test"
    And "Save this search" "button" should exist
    And I press "Save this search"
    And I set the field "Search Name" to "Search user1"
    And I click on "Shared" "radio"
    And I press "Save changes"
    Then the "sid" select box should contain "Search user1"

    # Create another shared search and make it the report default.
    When I set the field "user-fullname" to "User2-firstname Test"
    And I press "id_submitgroupstandard_addfilter"
    And I should see "User2-firstname Test"
    And I should not see "User1-firstname Test"
    And I should not see "User3-firstname Test"
    And "Save this search" "button" should exist
    And I press "Save this search"
    And I set the field "Search Name" to "Search user2"
    And I click on "Shared" "radio"
    And the "isdefault" "field" should be enabled
    And I set the field "isdefault" to "1"
    And I press "Save changes"
    Then the "sid" select box should contain "Search user2 (Default view)"

    # Create a private search.
    When I set the field "user-fullname" to "User3-firstname Test"
    And I press "id_submitgroupstandard_addfilter"
    And I should see "User3-firstname Test"
    And I should not see "User1-firstname Test"
    And I should not see "User2-firstname Test"
    And "Save this search" "button" should exist
    And I press "Save this search"
    And I set the field "Search Name" to "Search user3 (private)"
    And I click on "Private" "radio"
    And I press "Save changes"
    Then the "sid" select box should contain "Search user3 (private)"
    And I log out

    # As leaner the default view should be applied.
    Given I log in as "user1"
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    Then I should see "User2-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User1-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User3-firstname Test" in the "report_custom_user_report_1" "table"
    And the "sid" select box should contain "Search user2 (Default view)"
    And the field "sdefault" matches value "1"
    And the "sid" select box should contain "Search user1"
    And the "sid" select box should not contain "Search user3 (private)"

    # Ensure the learner can not change the report wide default.
    When I follow "Manage your saved searches"
    Then I should see "-" in the "Search user1" "table_row"
    And I should not see "Make default" in the "Search user1" "table_row"
    And I should not see "Edit" in the "Search user1" "table_row"
    And I should not see "Delete" in the "Search user1" "table_row"
    And I should see "Default" in the "Search user2" "table_row"
    And I should not see "Make default" in the "Search user2" "table_row"
    And I should not see "Edit" in the "Search user2" "table_row"
    And I should not see "Delete" in the "Search user2" "table_row"
    And I should not see "Search user3 (private)"

    # Report "Custom user report 2" has no saved searches
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 2"
    Then I should not see "Saved searches"
    And I should not see "View a saved search"
    And I should not see "Manage your saved searches"
    And I should see "User2-firstname Test" in the "report_custom_user_report_2" "table"
    And I should see "User1-firstname Test" in the "report_custom_user_report_2" "table"
    And I should see "User3-firstname Test" in the "report_custom_user_report_2" "table"
    And I log out

    # The manager can change the report wide default.
    When I log in as "admin"
    And I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    And I follow "Manage your saved searches"
    Then I should see "Make default" in the "Search user1" "table_row"
    And I should see "Edit" in the "Search user1" "table_row"
    And I should see "Delete" in the "Search user1" "table_row"
    And I should see "Default" in the "Search user2" "table_row"
    And I should see "Edit" in the "Search user2" "table_row"
    And I should see "Delete" in the "Search user2" "table_row"
    And I should see "-" in the "Search user3 (private)" "table_row"
    And I should see "Edit" in the "Search user3 (private)" "table_row"
    And I should see "Delete" in the "Search user3 (private)" "table_row"
    When I click on "Make default" "link"
    Then I should see "Default" in the "Search user1" "table_row"
    And I should see "Make default" in the "Search user2" "table_row"
    And I should see "-" in the "Search user3 (private)" "table_row"
    And I press "Close"
    And I log out

    # As leaner check the new default view is applied.
    Given I log in as "user1"
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    Then I should see "User1-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User2-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User3-firstname Test" in the "report_custom_user_report_1" "table"
    And the "sid" select box should contain "Search user1 (Default view)"
    And the field "sdefault" matches value "1"
    And the "sid" select box should contain "Search user2"
    And the "sid" select box should not contain "Search user3 (private)"

  Scenario: Leaner can not define a report wide default search
    Given I log in as "user1"
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    Then "Save this search" "button" should not exist
    When I set the field "user-fullname" to "User1-firstname Test"
    And I press "id_submitgroupstandard_addfilter"
    Then "Save this search" "button" should exist

    When I press "Save this search"
    Then I should not see "Default view"
    And "isdefault" "checkbox" should not exist

    When I set the field "Search Name" to "Search user1"
    And I click on "Shared" "radio"
    And I press "Save changes"
    Then the "sid" select box should contain "Search user1"

    When I follow "Manage your saved searches"
    Then I should not see "Make default"
    When I follow "Edit"
    Then I should not see "Default view"
    And "isdefault" "checkbox" should not exist

  Scenario: Leaner can remove the report default so they have no default set
    # First create a report wide default.
    Given I log in as "admin"
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    Then "Save this search" "button" should not exist
    # Create a shared search.
    When I set the field "user-fullname" to "User1-firstname Test"
    And I press "id_submitgroupstandard_addfilter"
    And I should see "User1-firstname Test"
    And I should not see "User2-firstname Test"
    And I should not see "User3-firstname Test"
    And "Save this search" "button" should exist
    And I press "Save this search"
    And I set the field "Search Name" to "Search user1"
    And I click on "Shared" "radio"
    And I press "Save changes"
    Then the "sid" select box should contain "Search user1"
    # Create another shared search and make it the report default.
    When I set the field "user-fullname" to "User2-firstname Test"
    And I press "id_submitgroupstandard_addfilter"
    And I should see "User2-firstname Test"
    And I should not see "User1-firstname Test"
    And I should not see "User3-firstname Test"
    And "Save this search" "button" should exist
    And I press "Save this search"
    And I set the field "Search Name" to "Search user2"
    And I click on "Shared" "radio"
    And the "isdefault" "field" should be enabled
    And I set the field "isdefault" to "1"
    And I press "Save changes"
    Then the "sid" select box should contain "Search user2 (Default view)"
    And I log out

    # As leaner check the new default view is applied.
    Given I log in as "user1"
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    Then I should see "User2-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User1-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User3-firstname Test" in the "report_custom_user_report_1" "table"
    And the "sid" select box should contain "Search user2 (Default view)"
    And the field "sdefault" matches value "1"
    And the "sid" select box should contain "Search user1"

    # Remove the default view.
    When I click on "sdefault" "checkbox"
    Then the "sid" select box should contain "Search user1"
    And the "sid" select box should contain "Search user2"
    And I log out

    # Confirm there is no default set when the user signs in again.
    When I log in as "user1"
    And I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    Then I should see "User2-firstname Test" in the "report_custom_user_report_1" "table"
    And I should see "User1-firstname Test" in the "report_custom_user_report_1" "table"
    And I should see "User3-firstname Test" in the "report_custom_user_report_1" "table"
    And the "sid" select box should contain "Search user1"
    And the "sid" select box should contain "Search user2"
    And the field "sdefault" matches value "0"

  Scenario: Leaner can set their own default search
    Given I log in as "user1"
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    Then "Save this search" "button" should not exist

    # Create a saved search.
    When I set the field "user-fullname" to "User1-firstname Test"
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the field "Search Name" to "Search user1"
    And I click on "Private" "radio"
    And I press "Save changes"
    Then the "sid" select box should contain "Search user1"
    And I should see "User1-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User2-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User3-firstname Test" in the "report_custom_user_report_1" "table"
    And the "sdefault" "field" should be disabled

    # Create another saved search.
    When I set the field "user-fullname" to "User2-firstname Test"
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the field "Search Name" to "Search user2"
    And I click on "Private" "radio"
    And I press "Save changes"
    Then the "sid" select box should contain "Search user2"
    And the "sid" select box should contain "Search user1"
    And I should see "User2-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User1-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User3-firstname Test" in the "report_custom_user_report_1" "table"
    And the "sdefault" "field" should be disabled

    # Set the first saved search, Search user1, as the default.
    When I set the field "sid" to "Search user1"
    And I should see "User1-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User2-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User3-firstname Test" in the "report_custom_user_report_1" "table"
    And I click on "sdefault" "checkbox"
    Then the "sid" select box should contain "Search user1 (Default view)"
    And the "sid" select box should contain "Search user2"

    # Ensure the default search is used.
    When I log out
    And I log in as "user1"
    And I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    Then the "sid" select box should contain "Search user1 (Default view)"
    And the "sid" select box should contain "Search user2"
    And the field "sid" matches value "Search user1 (Default view)"
    And the field "sdefault" matches value "1"
    And I should see "User1-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User2-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User3-firstname Test" in the "report_custom_user_report_1" "table"

    # User can change the default.
    When I set the field "sid" to "Search user2"
    And I should see "User2-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User1-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User3-firstname Test" in the "report_custom_user_report_1" "table"
    And I click on "sdefault" "checkbox"
    Then the "sid" select box should contain "Search user2 (Default view)"
    And the "sid" select box should contain "Search user1"
    When I log out
    And I log in as "user1"
    And I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    Then the "sid" select box should contain "Search user2 (Default view)"
    And the "sid" select box should contain "Search user1"
    And the field "sid" matches value "Search user2 (Default view)"
    And the field "sdefault" matches value "1"
    And I should see "User2-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User1-firstname Test" in the "report_custom_user_report_1" "table"
    And I should not see "User3-firstname Test" in the "report_custom_user_report_1" "table"

    # User can remove the default.
    When I click on "sdefault" "checkbox"
    Then the "sid" select box should not contain "Search user1 (Default view)"
    And the "sid" select box should not contain "Search user2 (Default view)"
    And the "sid" select box should contain "Search user1"
    And the "sid" select box should contain "Search user2"
    When I log out
    And I log in as "user1"
    And I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    Then the "sid" select box should not contain "Search user1 (Default view)"
    And the "sid" select box should not contain "Search user2 (Default view)"
    And the "sid" select box should contain "Search user1"
    And the "sid" select box should contain "Search user2"
    And the "sdefault" "field" should be disabled
    And I should see "User1-firstname Test" in the "report_custom_user_report_1" "table"
    And I should see "User2-firstname Test" in the "report_custom_user_report_1" "table"
    And I should see "User3-firstname Test" in the "report_custom_user_report_1" "table"

  Scenario: Editing and deleting a saved search updates the select list of saved searches
    Given I log in as "user1"
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 1"

    # Create a saved search.
    When I set the field "user-fullname" to "User1-firstname Test"
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the field "Search Name" to "Search user1"
    And I press "Save changes"
    Then the "sid" select box should contain "Search user1"

    # Create another saved search.
    When I set the field "user-fullname" to "User2-firstname Test"
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the field "Search Name" to "Search user2"
    And I press "Save changes"
    Then the "sid" select box should contain "Search user2"

    # Edit a search.
    When I follow "Manage your saved searches"
    And I click on "Edit" "link" in the "Search user1" "table_row"
    And I set the field "Search Name" to "Search user1 (Edited)"
    And I press "Save changes"
    And I press "Close"
    Then the "sid" select box should contain "Search user1 (Edited)"
    And the "sid" select box should contain "Search user2"

    # Delete a search.
    When I follow "Manage your saved searches"
    And I click on "Delete" "link" in the "Search user1" "table_row"
    And I press "Continue"
    And I press "Close"
    Then the "sid" select box should not contain "Search user1 (Edited)"
    And the "sid" select box should contain "Search user2"

    # Delete all searches.
    When I follow "Manage your saved searches"
    And I click on "Delete" "link" in the "Search user2" "table_row"
    And I press "Continue"
    And I press "Close"
    Then I should not see "View a saved search"
    And I should not see "Manage your saved searches"

  Scenario: Saved searches should appear alphabetically in the manage search dialog
    Given I log in as "admin"
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    Then I should see "Custom user report 1"

    # Create some saved searches.
    When I set the field "user-fullname" to "Paul"
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the field "Search Name" to "Paul"
    And I press "Save changes"
    Then the "sid" select box should contain "Paul"

    When I set the field "user-fullname" to "Adam"
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the field "Search Name" to "Adam"
    And I press "Save changes"
    Then the "sid" select box should contain "Adam"

    When I set the field "user-fullname" to "Stuart"
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the field "Search Name" to "Stuart"
    And I press "Save changes"
    Then the "sid" select box should contain "Stuart"

    # Ensure the saved search names are ordered alphabetically.
    When I follow "Manage your saved searches"
    Then "Adam" "text" should appear before "Paul" "text"
    And "Paul" "text" should appear before "Stuart" "text"
