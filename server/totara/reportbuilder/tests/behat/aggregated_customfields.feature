@totara @totara_reportbuilder @javascript
Feature: Test aggregated columns with custom fields
  As an admin
  I create a report using the user report source
  I test that I can add the aggregated columns
  I test that the aggregated columns display correctly

  Background:
    Given I am on a totara site
    And the following "custom profile fields" exist in "totara_core" plugin:
      | datatype | shortname | name              | param1                     | defaultdata |
      | menu     | menucf    | Menu Custom Field | Option 1/Option 2/Option 3 |             |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname           | shortname    | source  |
      | Test User Report   | test         | user    |

    And I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Edit" "link" in the "User One" "table_row"
    And I expand all fieldsets
    And I set the field "Menu Custom Field" to "Option 1"
    And I press "Save and go back"
    And I click on "Edit" "link" in the "User Two" "table_row"
    And I expand all fieldsets
    And I set the field "Menu Custom Field" to "Option 1"
    And I press "Save and go back"
    And I click on "Edit" "link" in the "User Three" "table_row"
    And I expand all fieldsets
    And I set the field "Menu Custom Field" to "Option 3"
    And I press "Save and go back"
    And I navigate to "Manage user reports" node in "Site administration > Reports"

  Scenario: View aggregated fields in the program overview report
    Given I click on "Test User Report" "link"
    And I switch to "Columns" tab
    And I delete the "User's Fullname (linked to profile with icon)" column from the report
    And I delete the "User Last Login" column from the report
    And I add the "Menu Custom Field" column to the report
    And I set aggregation for the "Username" column to "Count" in the report
    And I press "Save changes"
    When I follow "View This Report"
    Then I should not see "Using aggregation in a report with a restricted visibility custom field is unsupported and may cause unexpected results."
    And I should see "3 records shown"

    And I am on site homepage
    Then I navigate to "User profile fields" node in "Site administration > Users"
    And I click on "Edit" "link" in the "Menu Custom Field" "table_row"
    And I set the field "Who is this field visible to?" to "Restricted visibility"
    And I press "Save changes"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "View" "link" in the "Test User Report" "table_row"
    And I should see "Using aggregation in a report with a restricted visibility custom field is unsupported and may cause unexpected results."
    And I should see "5 records shown"
