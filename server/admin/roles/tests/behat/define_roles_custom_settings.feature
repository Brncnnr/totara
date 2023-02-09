@core @core_admin @core_admin_roles @totara_job @javascript
Feature: Test the custom roles can not be removed when they are assigned
  Background:
    Given I log in as "admin"
    And I navigate to "Define roles" node in "Site administration > Permissions"
    And I press "Add a new role"
    And I set the following fields to these values:
      | Use role or archetype | ARCHETYPE: Staff Manager |
    And I press "Continue"
    And I set the following fields to these values:
      | Short name       | teamlead  |
      | Custom full name | Team Lead |
    And I press "Create this role"
    And I log out

  Scenario: Admin check the custom roles when they are assigned under User Policies
    Given I log in as "admin"
    When I navigate to "Define roles" node in "Site administration > Permissions"
    Then I should see "Team Lead" in the "teamlead" "table_row"
    And I should see "Delete" in the "Team Lead" "table_row"

    And I navigate to "User policies" node in "Site administration > Permissions"
    And I set the following fields to these values:
      | Role for manager | Team Lead (teamlead) |
    And I press "Save changes"

    When I navigate to "Define roles" node in "Site administration > Permissions"
    Then I should not see "Delete" in the "Team Lead" "table_row"
    And I should see "Delete" in the "Staff Manager" "table_row"

  Scenario: Admin check the custom roles when they are assigned under Job Assignment
    Given the following "users" exist:
      | username | firstname | lastname | email                   |
      | user1    | User      | One      | user1@example.com       |
      | manager1 | Manager   | One      | manager1@example.com    |
    And I log in as "admin"
    And I navigate to "User policies" node in "Site administration > Permissions"
    And I set the following fields to these values:
      | Role for manager | Team Lead (teamlead) |
    And I press "Save changes"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "User One" "link" in the "User One" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Developer |
      | ID Number | 1         |
    And I press "Choose manager"
    And I click on "Manager One (manager1@example.com) - create empty job assignment" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I wait "1" seconds
    And I should see "Manager One (manager1@example.com) - create empty job assignment"
    And I click on "Add job assignment" "button"
    When I navigate to "Define roles" node in "Site administration > Permissions"
    Then I should not see "Delete" in the "Team Lead" "table_row"
    And I should see "1" in the "Team Lead" "table_row"

