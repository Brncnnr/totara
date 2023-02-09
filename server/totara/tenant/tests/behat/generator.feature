@totara @tenant @totara_tenant @javascript
Feature: Tenant behat generator functionality

  As a developer
  In order to speed up adding of behat coverage
  I want to use tenant data generators

  Background:
    Given I am on a totara site

  Scenario: Tenant support is disabled by default
    Given I log in as "admin"

    When I navigate to "System information > Configure features > Shared services settings" in site administration
    Then the field "Enable multitenancy support" matches value "0"

    When I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should not see "Tenants" in the "#quickaccess-popover-content" "css_element"

    When I navigate to "Permissions > Define roles" in site administration
    Then I should not see "Tenant User Manager"
    And I should not see "Tenant Domain Manager"

  Scenario: Tenant behat generator turns on tenant support without tenant isolation
    When tenant support is enabled without tenant isolation
    And I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should see "Tenants" in the "#quickaccess-popover-content" "css_element"

    When I navigate to "Development > Experimental > Experimental settings" in site administration
    Then the field "Enable tenant isolation" matches value "0"

    When I navigate to "Permissions > Define roles" in site administration
    Then I should see "Tenant User Manager"
    And I should see "Tenant Domain Manager"

  Scenario: Tenant behat generator may turn on tenant support with full tenant isolation
    When tenant support is enabled with full tenant isolation
    And I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    Then I should see "Tenants" in the "#quickaccess-popover-content" "css_element"

    When I navigate to "Development > Experimental > Experimental settings" in site administration
    Then the field "Enable tenant isolation" matches value "1"

    When I navigate to "Permissions > Define roles" in site administration
    Then I should see "Tenant User Manager"
    And I should see "Tenant Domain Manager"

  Scenario: Tenant behat generator creates tenant instances
    Given tenant support is enabled without tenant isolation
    When the following "tenants" exist:
      | name          | idnumber | description       | suspended | categoryname      | cohortname      | dashboardname      |
      | First Tenant  | t1       |                   |           |                   |                 |                    |
      | Second Tenant | t2       | Some other tenant | 0         | Second T Category | Second T Cohort | Second T Dashboard |
      | Third Tenant  | t3       | Another tenant    | 1         | Third T Category  | Third T Cohort  | Third T Dashboard  |
    And I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    Then "First Tenant" row "Tenant identifier" column of "tenants" table should contain "t1"
    And "First Tenant" row "Suspended" column of "tenants" table should contain "No"
    And "Second Tenant" row "Tenant identifier" column of "tenants" table should contain "t2"
    And "Second Tenant" row "Suspended" column of "tenants" table should contain "No"
    And "Third Tenant" row "Tenant identifier" column of "tenants" table should contain "t3"
    And "Third Tenant" row "Suspended" column of "tenants" table should contain "Yes"

    When I click on "Edit" "link" in the "First Tenant" "table_row"
    Then I should see the following Totara form fields having these values:
      | Name                  | First Tenant              |
      | Tenant identifier     | t1                        |
      | Description           |                           |
      | Suspended             | 0                         |
      | Tenant category name  | First Tenant category     |
      | Tenant participants audience name | First Tenant audience |
    And I press "Cancel"

    When I click on "Edit" "link" in the "Second Tenant" "table_row"
    Then I should see the following Totara form fields having these values:
      | Name                  | Second Tenant             |
      | Tenant identifier     | t2                        |
      | Description           | Some other tenant         |
      | Suspended             | 0                         |
      | Tenant category name  | Second T Category         |
      | Tenant participants audience name | Second T Cohort |
    And I press "Cancel"

    When I click on "Edit" "link" in the "Third Tenant" "table_row"
    Then I should see the following Totara form fields having these values:
      | Name                  | Third Tenant              |
      | Tenant identifier     | t3                        |
      | Description           | Another tenant            |
      | Suspended             | 1                         |
      | Tenant category name  | Third T Category          |
      | Tenant participants audience name | Third T Cohort |
    And I press "Cancel"

    When I am on "Dashboard" page
    And I press "Manage dashboards"
    Then "My Learning" row "Availability" column of "alldashboards" table should contain "Available to all logged in users"
    And "My Learning" row "Tenant" column of "alldashboards" table should not contain "Tenant"
    And "First Tenant dashboard" row "Availability" column of "alldashboards" table should contain "Assigned to 1 audiences"
    And "First Tenant dashboard" row "Tenant" column of "alldashboards" table should contain "First Tenant"
    And "Second T Dashboard" row "Availability" column of "alldashboards" table should contain "Assigned to 1 audiences"
    And "Second T Dashboard" row "Tenant" column of "alldashboards" table should contain "Second Tenant"
    And "Third T Dashboard" row "Availability" column of "alldashboards" table should contain "Assigned to 1 audiences"
    And "Third T Dashboard" row "Tenant" column of "alldashboards" table should contain "Third Tenant"


  Scenario: Tenant behat generator creates tenant member accounts and adds participants
    Given tenant support is enabled without tenant isolation
    When the following "tenants" exist:
      | name          | idnumber | description       | suspended | categoryname      | cohortname      |
      | First Tenant  | t1       |                   |           |                   |                 |
      | Second Tenant | t2       | Some other tenant | 0         | Second T Category | Second T Cohort |
      | Third Tenant  | t3       | Another tenant    | 1         | Third T Category  | Third T Cohort  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 0 | COURSE0   |          |
      | Course 1 | COURSE1   | t1       |
      | Course 2 | COURSE2   | t2       |
      | Course 3 | COURSE3   | t3       |
    And the following "users" exist:
      | username          | firstname | lastname    | tenantmember | tenantparticipant | tenantusermanager | tenantdomainmanager |
      | user1             | First     | Member      | t1           |                   |                   |                     |
      | manager1          | First     | Manager     | t1           |                   | t1                | t1                  |
      | participant       | Tenant    | Participant |              | t1, t3            |                   |                     |
      | boss              | Tenant    | Boss        |              | t1, t2, t3        | t1, t2, t3        | t1, t2, t3          |
    And I log in as "admin"
    And I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    Then "First Tenant" row "Tenant participants" column of "tenants" table should contain "4"
    And  "Second Tenant" row "Tenant participants" column of "tenants" table should contain "1"
    And  "Third Tenant" row "Tenant participants" column of "tenants" table should contain "2"

    When I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    And I click on "4" "link" in the "First Tenant" "table_row"
    Then I should see "Tenant participants: 4 records shown"
    And "user1" row "User's Fullname" column of "tenant_participants" table should contain "First Member"
    And "user1" row "User's Email" column of "tenant_participants" table should contain "user1@example.com"
    And "user1" row "User Status" column of "tenant_participants" table should contain "Active"
    And "user1" row "Tenant member" column of "tenant_participants" table should contain "Yes"
    And "manager1" row "User's Fullname" column of "tenant_participants" table should contain "First Manager"
    And "manager1" row "User's Email" column of "tenant_participants" table should contain "manager1@example.com"
    And "manager1" row "User Status" column of "tenant_participants" table should contain "Active"
    And "manager1" row "Tenant member" column of "tenant_participants" table should contain "Yes"
    And "participant" row "User's Fullname" column of "tenant_participants" table should contain "Tenant Participant"
    And "participant" row "User's Email" column of "tenant_participants" table should contain "participant@example.com"
    And "participant" row "User Status" column of "tenant_participants" table should contain "Active"
    And "participant" row "Tenant member" column of "tenant_participants" table should contain "No"
    And "boss" row "User's Fullname" column of "tenant_participants" table should contain "Tenant Boss"
    And "boss" row "User's Email" column of "tenant_participants" table should contain "boss@example.com"
    And "boss" row "User Status" column of "tenant_participants" table should contain "Active"
    And "boss" row "Tenant member" column of "tenant_participants" table should contain "No"
    And I navigate to "Assign roles" node in "First Tenant"
    And I should see "Tenant Boss"
    And I should see "First Manager"
    And I navigate to "Assign roles" node in "Category: First Tenant category"
    And I should see "Tenant Boss"
    And I should see "First Manager"

    When I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Tenants" "link" in the "#quickaccess-popover-content" "css_element"
    And I click on "1" "link" in the "Second Tenant" "table_row"
    Then I should see "Tenant participants: 1 record shown"
    And "boss" row "User's Fullname" column of "tenant_participants" table should contain "Tenant Boss"
    And "boss" row "User's Email" column of "tenant_participants" table should contain "boss@example.com"
    And "boss" row "User Status" column of "tenant_participants" table should contain "Active"
    And "boss" row "Tenant member" column of "tenant_participants" table should contain "No"
    And I navigate to "Assign roles" node in "Second Tenant"
    And I should see "Tenant Boss"
    And I navigate to "Assign roles" node in "Category: Second T Category"
    And I should see "Tenant Boss"
