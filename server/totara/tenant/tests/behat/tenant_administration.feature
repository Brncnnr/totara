@totara @tenant @totara_tenant @javascript
Feature: Tenant administration

  As an administrator
  In order to make new tenant site available
  I want to be able to create and manager tenants and deletegate prigileges for administration

  Background:
    Given I am on a totara site
    And the following "roles" exist:
      | name          | shortname    | archetype |
      | Tenant admin  | tenantadmin  |           |
      | Tenant viewer | tenantviewer |           |
    And the following "permission overrides" exist:
      | capability                           | permission | role         | contextlevel | reference |
      | totara/tenant:view                   | Allow      | tenantadmin  | System       |           |
      | totara/tenant:config                 | Allow      | tenantadmin  | System       |           |
      | moodle/category:viewhiddencategories | Allow      | tenantadmin  | System       |           |
      | moodle/user:viewalldetails           | Allow      | tenantadmin  | System       |           |
      | totara/tenant:usercreate             | Allow      | tenantadmin  | System       |           |
      | totara/tenant:view                   | Allow      | tenantviewer | System       |           |
    And the following "users" exist:
      | username     |
      | tenantadmin  |
      | tenantviewer |
    And the following "system role assigns" exist:
      | user         | role         |
      | tenantadmin  | tenantadmin  |
      | tenantviewer | tenantviewer |

  Scenario: Site administrator may enable tenant support
    Given I log in as "admin"

    When I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should not see "Tenants" in the "#quickaccess-popover-content" "css_element"

    When I set the following administration settings values:
      | tenantsenabled | 1 |
    And I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should see "Tenants" in the "#quickaccess-popover-content" "css_element"

  Scenario: Tenant administrator may create a new tenant
    Given I log in as "admin"
    And I set the following administration settings values:
      | tenantsenabled | 1 |
    And I log out
    And I log in as "tenantadmin"

    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"

    # First as little info as possible
    When I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | First tenant              |
      | Tenant identifier     | t1                        |
    And I press "Add tenant"
    Then "First tenant" row "Tenant identifier" column of "tenants" table should contain "t1"
    And "First tenant" row "Suspended" column of "tenants" table should contain "No"
    And "First tenant" row "Tenant participants" column of "tenants" table should contain "0"

    # Invalid names
    When I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | <script>                  |
      | Tenant identifier     | A B                       |
    And I press "Add tenant"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Required"
    And I should see "Invalid tenant identifier, use only lower case letters (a-z) and numbers"
    And I press "Cancel"

    # Then try adding all details and check duplicate identifiers are prevented
    When I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | First tenant              |
      | Tenant identifier     | t1                        |
      | Description           | Details about this tenant |
      | Suspended             | 1                         |
      | Tenant category name  | Second T Category         |
      | Tenant participants audience name | Second T Audience |
      | Tenant dashboard name | Second T Dashboard        |
      | Clone dashboard       | First tenant              |
    And I press "Add tenant"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Tenant with the same name already exists"
    And I should see "Tenant with the same identifier already exists"
    And I set the following Totara form fields to these values:
      | Name                  | Second tenant             |
      | Tenant identifier     | t2                        |
    And I press "Add tenant"
    Then "Second tenant" row "Tenant identifier" column of "tenants" table should contain "t2"
    And "Second tenant" row "Suspended" column of "tenants" table should contain "Yes"
    And "Second tenant" row "Tenant participants" column of "tenants" table should contain "0"

  Scenario: Tenant administrator may update an existing tenant
    Given I log in as "admin"
    And I set the following administration settings values:
      | tenantsenabled | 1 |
    And I log out
    And I log in as "tenantadmin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | First tenant              |
      | Tenant identifier     | t1                        |
      | Description           | Details about this tenant |
      | Suspended             | 1                         |
      | Tenant category name  | First T Category          |
      | Tenant participants audience name | First T Audience |
      | Tenant dashboard name | First T Dashboard         |
      | Clone dashboard       | My Learning               |
    And I press "Add tenant"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | Second tenant             |
      | Tenant identifier     | t2                        |
      | Description           | Details about this tenant |
      | Suspended             | 0                         |
      | Tenant category name  | Second T Category         |
      | Tenant participants audience name | Second T Audience |
      | Tenant dashboard name | Second T Dashboard        |
      | Clone dashboard       | My Learning               |
    And I press "Add tenant"

    When I click on "Edit" "link" in the "First tenant" "table_row"
    And I should see the following Totara form fields having these values:
      | Name                  | First tenant              |
      | Tenant identifier     | t1                        |
      | Description           | Details about this tenant |
      | Suspended             | 1                         |
      | Tenant category name  | First T Category          |
      | Tenant participants audience name | First T Audience |
    And I press "Update tenant"
    Then "First tenant" row "Tenant identifier" column of "tenants" table should contain "t1"
    And "First tenant" row "Suspended" column of "tenants" table should contain "Yes"
    And "First tenant" row "Tenant participants" column of "tenants" table should contain "0"

    When I click on "Edit" "link" in the "First tenant" "table_row"
    And I should see the following Totara form fields having these values:
      | Name                  | First tenant              |
      | Tenant identifier     | t1                        |
      | Description           | Details about this tenant |
      | Suspended             | 1                         |
      | Tenant category name  | First T Category          |
      | Tenant participants audience name | First T Audience |
    And I set the following Totara form fields to these values:
      | Name                  | Prvni tenant              |
      | Tenant identifier     | p1                        |
      | Description           | Detaily tenanta           |
      | Suspended             | 0                         |
      | Tenant category name  | Prvni kategorie           |
      | Tenant participants audience name | Prvni kohorta |
    And I press "Update tenant"
    Then "Prvni tenant" row "Tenant identifier" column of "tenants" table should contain "p1"
    And "Prvni tenant" row "Suspended" column of "tenants" table should contain "No"
    And "Prvni tenant" row "Tenant participants" column of "tenants" table should contain "0"

    When I click on "Edit" "link" in the "Prvni tenant" "table_row"
    And I should see the following Totara form fields having these values:
      | Name                  | Prvni tenant              |
      | Tenant identifier     | p1                        |
      | Description           | Detaily tenanta           |
      | Suspended             | 0                         |
      | Tenant category name  | Prvni kategorie           |
      | Tenant participants audience name | Prvni kohorta |
    And I set the following Totara form fields to these values:
      | Name                  | Second tenant              |
      | Tenant identifier     | t2                        |
    And I press "Update tenant"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Tenant with the same name already exists"
    And I should see "Tenant with the same identifier already exists"
    And I press "Cancel"
    Then "Prvni tenant" row "Tenant identifier" column of "tenants" table should contain "p1"

  Scenario: Tenant viewer may see all tenants
    Given I log in as "admin"
    And I set the following administration settings values:
      | tenantsenabled | 1 |
    And I log out
    And I log in as "tenantadmin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | First tenant              |
      | Tenant identifier     | t1                        |
      | Description           | Details about this tenant |
      | Suspended             | 1                         |
      | Tenant category name  | First T Category          |
      | Tenant participants audience name | First T Audience |
      | Tenant dashboard name | First T Dashboard         |
      | Clone dashboard       | My Learning               |
    And I press "Add tenant"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | Second tenant             |
      | Tenant identifier     | t2                        |
      | Description           | Details about this tenant |
      | Suspended             | 0                         |
      | Tenant category name  | Second T Category         |
      | Tenant participants audience name | Second T Audience |
      | Tenant dashboard name | Second T Dashboard        |
      | Clone dashboard       | My Learning               |
    And I press "Add tenant"
    And I log out

    When I log in as "tenantviewer"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    Then "First tenant" row "Tenant identifier" column of "tenants" table should contain "t1"
    Then "Second tenant" row "Tenant identifier" column of "tenants" table should contain "t2"
    And I should not see "Add tenant"

  Scenario: Delete tenant with members suspended
    Given I log in as "admin"
    And I set the following administration settings values:
      | tenantsenabled | 1 |
    And I log out
    And I log in as "tenantadmin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | First tenant              |
      | Tenant identifier     | t1                        |
      | Description           | Details about this tenant |
      | Suspended             | 1                         |
      | Tenant category name  | First T Category          |
      | Tenant participants audience name | First T Audience |
      | Tenant dashboard name | First T Dashboard         |
      | Clone dashboard       | My Learning               |
    And I press "Add tenant"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | Second tenant             |
      | Tenant identifier     | t2                        |
      | Description           | Details about this tenant |
      | Suspended             | 0                         |
      | Tenant category name  | Second T Category         |
      | Tenant participants audience name | Second T Audience |
      | Tenant dashboard name | Second T Dashboard        |
      | Clone dashboard       | My Learning               |
    And I press "Add tenant"
    And I click on "0" "link" in the "First tenant" "table_row"
    And I press "Create user"
    And I set the following fields to these values:
      | Username     | member1              |
      | New password | Member1+             |
      | First name   | Test                 |
      | Surname      | User                 |
      | Email        | member1@example.com  |
    And I press "Create user"

    When I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    And I click on "Delete" "link" in the "First tenant" "table_row"
    And I press "Delete tenant"
    And I should see "Form could not be submitted, validation failed"
    And I set the following Totara form fields to these values:
      | Tenant member status change | Suspend all members |
    And I press "Delete tenant"
    Then I should not see "First tenant"
    And I log out
    And I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I set the field "User Status" to "Suspended"
    And I press "id_submitgroupstandard_addfilter"
    And I should see "Test User"

  Scenario: Delete tenant with members deleted
    Given I log in as "admin"
    And I set the following administration settings values:
      | tenantsenabled | 1 |
    And I log out
    And I log in as "tenantadmin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | First tenant              |
      | Tenant identifier     | t1                        |
      | Description           | Details about this tenant |
      | Suspended             | 1                         |
      | Tenant category name  | First T Category          |
      | Tenant participants audience name | First T Audience |
      | Tenant dashboard name | First T Dashboard         |
      | Clone dashboard       | My Learning               |
    And I press "Add tenant"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | Second tenant             |
      | Tenant identifier     | t2                        |
      | Description           | Details about this tenant |
      | Suspended             | 0                         |
      | Tenant category name  | Second T Category         |
      | Tenant participants audience name | Second T Audience |
      | Tenant dashboard name | Second T Dashboard        |
      | Clone dashboard       | My Learning               |
    And I press "Add tenant"
    And I click on "0" "link" in the "First tenant" "table_row"
    And I press "Create user"
    And I set the following fields to these values:
      | Username     | member1              |
      | New password | Member1+             |
      | First name   | Test                 |
      | Surname      | User                 |
      | Email        | member1@example.com  |
    And I press "Create user"

    When I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    And I click on "Delete" "link" in the "First tenant" "table_row"
    And I press "Delete tenant"
    And I should see "Form could not be submitted, validation failed"
    And I set the following Totara form fields to these values:
      | Tenant member status change | Delete all members |
    And I press "Delete tenant"
    Then I should not see "First tenant"
    And I log out
    And I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I set the field "User Status" to "any value"
    And I press "id_submitgroupstandard_addfilter"
    And I should not see "Test User"

  Scenario: Delete tenant with members migrated
    Given I log in as "admin"
    And I set the following administration settings values:
      | tenantsenabled | 1 |
    And I log out
    And I log in as "tenantadmin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | First tenant              |
      | Tenant identifier     | t1                        |
      | Description           | Details about this tenant |
      | Suspended             | 1                         |
      | Tenant category name  | First T Category          |
      | Tenant participants audience name | First T Audience |
      | Tenant dashboard name | First T Dashboard         |
      | Clone dashboard       | My Learning               |
    And I press "Add tenant"
    And I press "Add tenant"
    And I set the following Totara form fields to these values:
      | Name                  | Second tenant             |
      | Tenant identifier     | t2                        |
      | Description           | Details about this tenant |
      | Suspended             | 0                         |
      | Tenant category name  | Second T Category         |
      | Tenant participants audience name | Second T Audience |
      | Tenant dashboard name | Second T Dashboard        |
      | Clone dashboard       | My Learning               |
    And I press "Add tenant"
    And I click on "0" "link" in the "First tenant" "table_row"
    And I press "Create user"
    And I set the following fields to these values:
      | Username     | member1              |
      | New password | Member1+             |
      | First name   | Test                 |
      | Surname      | User                 |
      | Email        | member1@example.com  |
    And I press "Create user"

    When I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    And I click on "Delete" "link" in the "First tenant" "table_row"
    And I press "Delete tenant"
    And I should see "Form could not be submitted, validation failed"
    And I set the following Totara form fields to these values:
      | Tenant member status change | Keep as users without tenant |
    And I press "Delete tenant"
    Then I should not see "First tenant"
    And I log out
    And I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I set the field "User Status" to "Active"
    And I press "id_submitgroupstandard_addfilter"
    And I should see "Test User"
