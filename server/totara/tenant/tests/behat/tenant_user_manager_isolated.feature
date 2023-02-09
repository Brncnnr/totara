@totara @tenant @totara_tenant @javascript
Feature: Tenant user manager with isolation enabled

  As a tenant user manager
  In order to manage tenant users
  I want to be able to create, update and delete member accounts

  Background:
    Given I am on a totara site
    And tenant support is enabled with full tenant isolation
    And the following config values are set as admin:
      | passwordpolicy | 0 |

  Scenario: Administrator may assign tenant user management to non-member with tenant isolation
    Given the following "users" exist:
      | username          | firstname     | lastname |
      | tenantusermanager | Tenant User   | Manager  |
    And the following "custom profile fields" exist in "totara_core" plugin:
      | datatype | shortname | name              | param1     | defaultdata |
      | text     | textcf    | Text Custom Field |            |             |
# Note: the non-member managing tenant does not have any UI that leads them to the tenant category,
#       workaround is to add totara/tenant:view capability in the system context, they can see
#       what tenants are there just by looking at course categories anyway.
    And the following "roles" exist:
      | name          | shortname    | archetype |
      | Tenant viewer | tenantviewer |           |
    And the following "permission overrides" exist:
      | capability                           | permission | role         | contextlevel | reference |
      | totara/tenant:view                   | Allow      | tenantviewer | System       |           |
    And the following "system role assigns" exist:
      | user              | role         |
      | tenantusermanager | tenantviewer |
    And I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | First tenant              |
      | Tenant identifier     | t1                        |
      | Description           | Details about this tenant |
      | Suspended             | 0                         |
      | Tenant category name  | First T Category          |
      | Tenant participants audience name | First T Audience |
      | Tenant dashboard name | First T Dashboard         |
    And I press "Add tenant"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | Second tenant             |
      | Tenant identifier     | t2                        |
      | Description           | More details              |
      | Suspended             | 0                         |
      | Tenant category name  | Second T Category         |
      | Tenant participants audience name | Second T Audience |
      | Tenant dashboard name | Second T Dashboard        |
    And I press "Add tenant"
    And I click on "0" "link" in the "First tenant" "table_row"
    And I press "Non-member participants"
    And I set the field "addselect" to "Tenant User Manager"
    And I press "Add"
    And I press "Back"
    And I should see "Tenant participants: 1 record shown"
    And "tenantusermanager" row "User's Fullname" column of "tenant_participants" table should contain "Tenant User Manager"
    And "tenantusermanager" row "User's Email" column of "tenant_participants" table should contain "tenantusermanager@example.com"
    And "tenantusermanager" row "User Status" column of "tenant_participants" table should contain "Active"
    And "tenantusermanager" row "Tenant member" column of "tenant_participants" table should contain "No"
    And "tenantusermanager" row "Actions" column of "tenant_participants" table should contain "Edit Tenant User Manager"
    And "tenantusermanager" row "Actions" column of "tenant_participants" table should contain "Manage login of Tenant User Manager"
    And "tenantusermanager" row "Actions" column of "tenant_participants" table should contain "User data"
    And "tenantusermanager" row "Actions" column of "tenant_participants" table should contain "Delete Tenant User Manager"
    And I navigate to "Assign roles" node in "First tenant"
    And I click on "Tenant User Manager" "link"
    And I set the field "addselect" to "Tenant User Manager"
    And I press "Add"
    And I navigate to "Tenant participants" node in "First tenant"
    And I log out

    When I log in as "tenantusermanager"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    And I click on "1" "link" in the "First tenant" "table_row"
    And I press "Create user"
    And I set the following fields to these values:
      | Username     | member1              |
      | New password | member1              |
      | First name   | Test                 |
      | Surname      | User                 |
      | Email        | member1@example.com  |
    And I press "Create user"
    Then I should see "Tenant participants: 2 records shown"
    And "member1" row "User's Fullname" column of "tenant_participants" table should contain "Test User"
    And "member1" row "User Status" column of "tenant_participants" table should contain "Active"
    And "member1" row "Tenant member" column of "tenant_participants" table should contain "Yes"
    And "member1" row "Actions" column of "tenant_participants" table should contain "Edit Test User"
    And "member1" row "Actions" column of "tenant_participants" table should contain "Manage login of Test User"
    And "member1" row "Actions" column of "tenant_participants" table should not contain "User data"
    And "member1" row "Actions" column of "tenant_participants" table should contain "Delete Test User"

    When I click on "Edit Test User" "link" in the "Test User" "table_row"
    And I set the following fields to these values:
      | First name   | Testovaci            |
      | Surname      | Uzivatel             |
      | Email        | test@example.com     |
    And I press "Update profile"
    Then "member1" row "User's Fullname" column of "tenant_participants" table should contain "Testovaci Uzivatel"
    And "member1" row "User Status" column of "tenant_participants" table should contain "Active"
    And "member1" row "Tenant member" column of "tenant_participants" table should contain "Yes"
    And "member1" row "Actions" column of "tenant_participants" table should contain "Edit Testovaci Uzivatel"
    And "member1" row "Actions" column of "tenant_participants" table should contain "Delete Testovaci Uzivatel"

    When I click on "Delete Testovaci Uzivatel" "link" in the "Testovaci Uzivatel" "table_row"
    And I press "Delete"
    Then I should see "Tenant participants: 1 record shown"
    And I should not see "Testovaci Uzivatel"

  Scenario: Administrator may assign tenant user management to member with tenant isolation
    And I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | First tenant              |
      | Tenant identifier     | t1                        |
      | Description           | Details about this tenant |
      | Suspended             | 0                         |
      | Tenant category name  | First T Category          |
      | Tenant participants audience name | First T Audience |
      | Tenant dashboard name | First T Dashboard         |
    And I press "Add tenant"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | Second tenant             |
      | Tenant identifier     | t2                        |
      | Description           | More details              |
      | Suspended             | 0                         |
      | Tenant category name  | Second T Category         |
      | Tenant participants audience name | Second T Audience |
      | Tenant dashboard name | Second T Dashboard        |
    And I press "Add tenant"
    And I click on "0" "link" in the "First tenant" "table_row"
    And I press "Create user"
    And I set the following fields to these values:
      | Username     | tenantusermanager             |
      | New password | tenantusermanager             |
      | First name   | Tenant User                   |
      | Surname      | Manager                       |
      | Email        | tenantusermanager@example.com |
    And I press "Create user"
    And I should see "Tenant participants: 1 record shown"
    And "tenantusermanager" row "User's Fullname" column of "tenant_participants" table should contain "Tenant User Manager"
    And "tenantusermanager" row "User's Email" column of "tenant_participants" table should contain "tenantusermanager@example.com"
    And "tenantusermanager" row "User Status" column of "tenant_participants" table should contain "Active"
    And "tenantusermanager" row "Tenant member" column of "tenant_participants" table should contain "Yes"
    And "tenantusermanager" row "Actions" column of "tenant_participants" table should contain "Edit Tenant User Manager"
    And "tenantusermanager" row "Actions" column of "tenant_participants" table should contain "Manage login of Tenant User Manager"
    And "tenantusermanager" row "Actions" column of "tenant_participants" table should contain "User data"
    And "tenantusermanager" row "Actions" column of "tenant_participants" table should contain "Delete Tenant User Manager"
    And I navigate to "Assign roles" node in "First tenant"
    And I click on "Tenant User Manager" "link" in the "Tenant User Manager" "table_row"
    And I set the field "addselect" to "Tenant User Manager"
    And I press "Add"
    And I navigate to "Tenant participants" node in "First tenant"
    And I log out

    When I log in as "tenantusermanager"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Users" "link" in the "#quickaccess-popover-content" "css_element"
    And I should see "Users: 1 record shown"
    And I press "Create user"
    And I set the following fields to these values:
      | Username     | member1              |
      | New password | member1              |
      | First name   | Test                 |
      | Surname      | User                 |
      | Email        | member1@example.com  |
    And I press "Create user"
    Then I should see "Users: 2 records shown"
    And "member1" row "User's Fullname" column of "tenant_users" table should contain "Test User"
    And "member1" row "User Status" column of "tenant_users" table should contain "Active"
    And "member1" row "Actions" column of "tenant_users" table should contain "Edit Test User"
    And "member1" row "Actions" column of "tenant_users" table should contain "Manage login of Test User"
    And "member1" row "Actions" column of "tenant_users" table should not contain "User data"
    And "member1" row "Actions" column of "tenant_users" table should contain "Delete Test User"

    When I click on "Edit Test User" "link" in the "Test User" "table_row"
    And I set the following fields to these values:
      | First name   | Testovaci            |
      | Surname      | Uzivatel             |
      | Email        | test@example.com     |
    And I press "Update profile"
    Then "member1" row "User's Fullname" column of "tenant_users" table should contain "Testovaci Uzivatel"
    And "member1" row "User Status" column of "tenant_users" table should contain "Active"
    And "member1" row "Actions" column of "tenant_users" table should contain "Edit Testovaci Uzivatel"
    And "member1" row "Actions" column of "tenant_users" table should contain "Delete Testovaci Uzivatel"

    When I click on "Delete Testovaci Uzivatel" "link" in the "Testovaci Uzivatel" "table_row"
    And I press "Delete"
    Then I should see "Users: 1 record shown"
    And I should not see "Testovaci Uzivatel"

