@totara @totara_tenant @javascript
Feature: Tenant account login management without tenant isolation

  As an account login manager
  In order to help users with login issues
  I need to be able to unlock, suspend, activate accounts and change or reset passwords

  Background:
    Given I am on a totara site
    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name          | idnumber |
      | First Tenant  | ten1     |
      | Second Tenant | ten2     |
    And the following "users" exist:
      | username          | firstname | lastname    | tenantmember | tenantparticipant | tenantusermanager |
      | user1             | First     | User        | ten1         |                   |                   |
      | user2             | Second    | User        |              | ten1              |                   |
      | usermanager       | User      | Manager     | ten1         |                   | ten1              |

  Scenario: Tenant login manager unlocks locked out user account
    Given I log in as "admin"
    And I set the following administration settings values:
      | Account lockout threshold | 3 |
    And I log out

    And I set the following fields to these values:
      | Username | user1    |
      | Password | 12345678 |
    And I press "Log in"
    And I should see "Invalid login, please try again"
    And I set the following fields to these values:
      | Username | user1    |
      | Password | 12345678 |
    And I press "Log in"
    And I should see "Invalid login, please try again"
    And I set the following fields to these values:
      | Username | user1    |
      | Password | 12345678 |
    And I press "Log in"
    And I set the following fields to these values:
      | Username | user1    |
      | Password | user1    |
    And I press "Log in"
    And I should see "Invalid login, please try again"

    When I log in as "usermanager"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Users" "link" in the "#quickaccess-popover-content" "css_element"
    Then "user1" row "Actions" column of "tenant_users" table should contain "Unlock First User"
    And "user1" row "Actions" column of "tenant_users" table should not contain "Manage login of First User"
    And "user2" row "Actions" column of "tenant_users" table should not contain "Manage login of Second User"

    When I click on "Unlock First User" "link" in the "First User" "table_row"
    And I should see "Account can be unlocked by user, administrator or automatically when resetting or changing password."
    And I set the "Choose" Totara form field to "Unlock user account"
    And I press "Update"
    Then "user1" row "Actions" column of "tenant_users" table should not contain "Unlock First User"
    And "user1" row "Actions" column of "tenant_users" table should contain "Manage login of First User"
    And I log out

    When I set the following fields to these values:
      | Username | user1    |
      | Password | user1    |
    And I press "Log in"
    Then I should see "You do not have any current learning. For previously completed learning see your Record of Learning."

  Scenario: Tenant login manager suspends and unsuspends user account without tenant isolation
    Given I log in as "usermanager"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Users" "link" in the "#quickaccess-popover-content" "css_element"

    When I click on "Manage login of First User" "link" in the "First User" "table_row"
    And I set the "Choose" Totara form field to "Suspend user account"
    And I press "Update"
    Then "user1" row "User Status" column of "tenant_users" table should contain "Suspended"

    When I click on "Manage login of First User" "link" in the "First User" "table_row"
    And I set the "Choose" Totara form field to "Activate user account"
    And I press "Update"
    Then "user1" row "User Status" column of "tenant_users" table should contain "Active"

  Scenario: Tenant login manager changes user password without forced change without tenant isolation
    Given I log in as "usermanager"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Users" "link" in the "#quickaccess-popover-content" "css_element"

    When I click on "Manage login of First User" "link" in the "First User" "table_row"
    And I set the "Choose" Totara form field to "Change password"
    And I set the "New password" Totara form field to "Grr!!666"
    And I press "Update"
    And I log out
    And I set the following fields to these values:
      | Username | user1    |
      | Password | Grr!!666 |
    And I press "Log in"
    Then I should see "You do not have any current learning. For previously completed learning see your Record of Learning."

  Scenario: Tenant login manager changes user password with forced change without tenant isolation
    Given I log in as "usermanager"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Users" "link" in the "#quickaccess-popover-content" "css_element"

    When I click on "Manage login of First User" "link" in the "First User" "table_row"
    And I set the "Choose" Totara form field to "Change password"
    And I set the "New password" Totara form field to "Grr!!666"
    And I set the "Force password change" Totara form field to "1"
    And I press "Update"
    And I log out
    And I set the following fields to these values:
      | Username | user1    |
      | Password | Grr!!666 |
    And I press "Log in"
    Then I should see "You must change your password to proceed."

    When I set the following fields to these values:
      | Current password     | Grr!!666  |
      | New password         | Argh!!666 |
      | New password (again) | Argh!!666 |
    And I press "Save changes"
    Then I should see "You do not have any current learning. For previously completed learning see your Record of Learning."

  Scenario: Tenant login manager resets user password without tenant isolation
    Given I log in as "usermanager"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Users" "link" in the "#quickaccess-popover-content" "css_element"

    When I click on "Manage login of First User" "link" in the "First User" "table_row"
    And I set the "Choose" Totara form field to "Generate password and notify user"
    And I press "Update"
    Then I should see "Users: 3 records shown"
