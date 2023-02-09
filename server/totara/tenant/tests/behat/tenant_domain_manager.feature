@totara @tenant @totara_tenant @javascript
Feature: Tenant domain manager without tenant isolation

  As a tenant domain manager
  In order to manage tenant category
  I want to be able to create, update and delete courses and categories

  Background:
    Given I am on a totara site
    And tenant support is enabled without tenant isolation
    And the following config values are set as admin:
      | passwordpolicy | 0 |

  Scenario: Administrator may assign tenant domain management to non-member with tenant isolation
    Given the following "users" exist:
      | username            | firstname       | lastname |
      | tenantdomainmanager | Tenant Domain   | Manager  |
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
    And I set the field "addselect" to "Tenant Domain Manager"
    And I press "Add"
    And I press "Back"
    And I should see "Tenant participants: 1 record shown"
    And "tenantdomainmanager" row "User's Fullname" column of "tenant_participants" table should contain "Tenant Domain Manager"
    And "tenantdomainmanager" row "User's Email" column of "tenant_participants" table should contain "tenantdomainmanager@example.com"
    And "tenantdomainmanager" row "User Status" column of "tenant_participants" table should contain "Active"
    And "tenantdomainmanager" row "Tenant member" column of "tenant_participants" table should contain "No"
    And "tenantdomainmanager" row "Actions" column of "tenant_participants" table should contain "Edit Tenant Domain Manager"
    And "tenantdomainmanager" row "Actions" column of "tenant_participants" table should contain "Manage login of Tenant Domain Manager"
    And "tenantdomainmanager" row "Actions" column of "tenant_participants" table should contain "User data"
    And "tenantdomainmanager" row "Actions" column of "tenant_participants" table should contain "Delete Tenant Domain Manager"
    And I navigate to "Assign roles" node in "Category: First T Category"
    And I click on "Tenant Domain Manager" "link"
    And I set the field "addselect" to "Tenant Domain Manager"
    And I press "Add"
    And I log out

    When I log in as "tenantdomainmanager"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Courses and categories" "link" in the "#quickaccess-popover-content" "css_element"
    Then I should see "Course categories"
    And I should see "First T Category"
    And I follow "First T Category"

    When I navigate to "Tenant participants" node in "First tenant"
    Then I should see "Tenant participants: 1 record shown"

    When I navigate to "Manage this category" node in "Category: First T Category"
    Then I should see "Course and category management"

    When I follow "Create new course"
    And I set the following fields to these values:
      | Course full name  | First course |
      | Course short name | FC1          |
    And I press "Save and return"
    Then I should see "Course and category management"
    And I should see "First course"

  Scenario: Administrator may assign tenant domain management to member with tenant isolation
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
      | Username     | tenantdomainmanager             |
      | New password | tenantdomainmanager             |
      | First name   | Tenant Domain                   |
      | Surname      | Manager                         |
      | Email        | tenantdomainmanager@example.com |
    And I press "Create user"
    And I should see "Tenant participants: 1 record shown"
    And "tenantdomainmanager" row "User's Fullname" column of "tenant_participants" table should contain "Tenant Domain Manager"
    And "tenantdomainmanager" row "User's Email" column of "tenant_participants" table should contain "tenantdomainmanager@example.com"
    And "tenantdomainmanager" row "User Status" column of "tenant_participants" table should contain "Active"
    And "tenantdomainmanager" row "Tenant member" column of "tenant_participants" table should contain "Yes"
    And "tenantdomainmanager" row "Actions" column of "tenant_participants" table should contain "Edit Tenant Domain Manager"
    And "tenantdomainmanager" row "Actions" column of "tenant_participants" table should contain "Manage login of Tenant Domain Manager"
    And "tenantdomainmanager" row "Actions" column of "tenant_participants" table should contain "User data"
    And "tenantdomainmanager" row "Actions" column of "tenant_participants" table should contain "Delete Tenant Domain Manager"
    And I navigate to "Assign roles" node in "Category: First T Category"
    And I click on "Tenant Domain Manager" "link"
    And I set the field "addselect" to "Tenant Domain Manager"
    And I press "Add"
    And I log out

    When I log in as "tenantdomainmanager"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Users" "link" in the "#quickaccess-popover-content" "css_element"
    Then I should see "Users: 1 record shown"

    When I navigate to "Users" node in "User management"
    Then I should see "Users: 1 record shown"

    When I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Courses and categories" "link" in the "#quickaccess-popover-content" "css_element"
    Then I should see "Course and category management"

    When I follow "Create new course"
    And I set the following fields to these values:
      | Course full name  | First course |
      | Course short name | FC1          |
    And I press "Save and return"
    Then I should see "Course and category management"
    And I should see "First course"
