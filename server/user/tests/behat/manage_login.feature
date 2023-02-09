@totara @core @core_user @javascript
Feature: Account login management

  As an account login manager
  In order to help users with login issues
  I need to be able to unlock, suspend, activate accounts and change or reset passwords

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username     | firstname | lastname | email                    | auth       | suspended |
      | user1        | First     | User     | user1@example.com        | manual     | 0         |
      | user2        | Second    | User     | user2@example.com        | manual     | 1         |
      | ws           | Service   | User     | ws@example.com           | webservice | 0         |
      | loginmanager | Login     | Manager  | loginmanager@example.com | manual     | 0         |
      | minimanager  | Mini      | Manager  | mini@example.com         | manual     | 0         |
    And the following "roles" exist:
      | shortname    |
      | loginmanager |
      | userviewer   |
    And the following "role assigns" exist:
      | user         | role         | contextlevel | reference |
      | loginmanager | userviewer   | System       |           |
      | loginmanager | loginmanager | System       |           |
      | minimanager  | userviewer   | System       |           |
      | minimanager  | loginmanager | User         | user1     |

    And the following "permission overrides" exist:
      | capability                        | permission | role         | contextlevel | reference |
      | moodle/user:managelogin           | Allow      | loginmanager | System       |           |
      | moodle/user:viewalldetails        | Allow      | userviewer   | System       |           |

  Scenario: Login manager unlocks locked out user account
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

    When I log in as "loginmanager"
    And I navigate to "Manage users" node in "Site administration > Users"
    Then "user1" row "Actions" column of "system_browse_users" table should contain "Unlock First User"
    And "user1" row "Actions" column of "system_browse_users" table should not contain "Manage login of First User"

    When I click on "Unlock First User" "link" in the "First User" "table_row"
    And I should see "Account can be unlocked by user, administrator or automatically when resetting or changing password."
    And I set the "Choose" Totara form field to "Unlock user account"
    And I press "Update"
    Then "user1" row "Actions" column of "system_browse_users" table should not contain "Unlock First User"
    And "user1" row "Actions" column of "system_browse_users" table should contain "Manage login of First User"
    And I log out

    When I set the following fields to these values:
      | Username | user1    |
      | Password | user1    |
    And I press "Log in"
    Then I should see "You do not have any current learning. For previously completed learning see your Record of Learning."

  Scenario: Login manager suspends user account
    Given I log in as "loginmanager"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I set the field "user-deleted" to "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And "user1" row "User Status" column of "system_browse_users" table should contain "Active"

    When I click on "Manage login of First User" "link" in the "First User" "table_row"
    And I set the "Choose" Totara form field to "Suspend user account"
    And I press "Update"
    Then "user1" row "User Status" column of "system_browse_users" table should contain "Suspended"

  Scenario: Login manager unsuspends user account
    Given I log in as "loginmanager"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I set the field "user-deleted" to "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And "user2" row "User Status" column of "system_browse_users" table should contain "Suspended"

    When I click on "Manage login of Second User" "link" in the "Second User" "table_row"
    And I set the "Choose" Totara form field to "Activate user account"
    And I press "Update"
    Then "user1" row "User Status" column of "system_browse_users" table should contain "Active"

  Scenario: Login manager changes user password without forced change
    Given I log in as "loginmanager"
    And I navigate to "Manage users" node in "Site administration > Users"

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

  Scenario: Login manager changes user password with forced change
    Given I log in as "loginmanager"
    And I navigate to "Manage users" node in "Site administration > Users"

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

  Scenario: Login manager cannot change password if auth plugin does not support it
    Given I log in as "loginmanager"
    And I navigate to "Manage users" node in "Site administration > Users"

    When I click on "Manage login of Service User" "link" in the "Service User" "table_row"
    Then I should not see "Change password"
    And I should not see "Generate password and notify user"
    And I press "Cancel"
    And I should see "Manage users: 6 records shown"

  Scenario: Login manager resets user password
    Given I log in as "loginmanager"
    And I navigate to "Manage users" node in "Site administration > Users"

    When I click on "Manage login of First User" "link" in the "First User" "table_row"
    And I set the "Choose" Totara form field to "Generate password and notify user"
    And I press "Update"
    Then I should see "Manage users: 6 records shown"

  Scenario: Access login management via profile
    Given I log in as "loginmanager"
    And I navigate to "Manage users" node in "Site administration > Users"

    When I follow "First User"
    Then I should see "Manage user login"
    And I should see "User details"

    When I follow "Manage user login"
    And I press "Cancel"
    Then I should see "Manage user login"
    And I should see "User details"

  Scenario: Manage login of individual users
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
      | Password | 12345678 |
    And I press "Log in"
    And I should see "Invalid login, please try again"

    When I log in as "minimanager"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I set the field "user-deleted" to "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then "user2" row "Actions" column of "system_browse_users" table should not contain "Manage login of Second User"

    When I click on "Unlock First User" "link" in the "First User" "table_row"
    And I should see "Account can be unlocked by user, administrator or automatically when resetting or changing password."
    And I set the "Choose" Totara form field to "Unlock user account"
    And I press "Update"
    Then "user1" row "Actions" column of "system_browse_users" table should not contain "Unlock First User"
    And "user1" row "Actions" column of "system_browse_users" table should contain "Manage login of First User"
    And I log out
    And I set the following fields to these values:
      | Username | user1 |
      | Password | user1 |
    And I press "Log in"
    And I should see "You do not have any current learning. For previously completed learning see your Record of Learning."
    And I log out

    When I log in as "minimanager"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I set the field "user-deleted" to "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I click on "Manage login of First User" "link" in the "user1" "table_row"
    And I set the "Choose" Totara form field to "Suspend user account"
    And I press "Update"
    Then "user1" row "User Status" column of "system_browse_users" table should contain "Suspended"

    When I click on "Manage login of First User" "link" in the "user1" "table_row"
    And I set the "Choose" Totara form field to "Activate user account"
    And I press "Update"
    Then "user1" row "User Status" column of "system_browse_users" table should contain "Active"

    When I click on "Manage login of First User" "link" in the "user1" "table_row"
    And I set the "Choose" Totara form field to "Change password"
    And I set the "New password" Totara form field to "Grr!!666"
    And I press "Update"
    And I log out
    And I set the following fields to these values:
      | Username | user1    |
      | Password | Grr!!666 |
    And I press "Log in"
    Then I should see "You do not have any current learning. For previously completed learning see your Record of Learning."

  Scenario: Admin/manager can change password for others but not for yourself
    Given I log in as "admin"
    And I click on "Admin User" "link"
    When I follow "Profile"
    And I follow "Manage user login"
    Then I should not see "Change password"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Manage login of First User" "link" in the "First User" "table_row"
    Then I should see "Change password"

  Scenario: Admin/manager can change password for other admin but not for yourself
    Given I log in as "admin"
    And I click on "Admin User" "link"
    When I follow "Profile"
    And I follow "Manage user login"
    Then I should not see "Change password"

    And I navigate to "Permissions > Site administrators" in site administration

    And I set the field "Users" to "First User (user1, user1@example.com)"
    And I click on "Add" "button"
    And I click on "Continue" "button"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Manage login of First User" "link" in the "First User" "table_row"
    Then I should see "Change password"