@totara @tenant @totara_tenant @totara_dashboard @javascript
Feature: Tenant dashboard management

  As a site admin
  In order to cusotmise tenant sites
  I want to be able to create tenant dashboards

  Scenario: Create new tenant dashboard
    Given I am on a totara site
    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name          | idnumber | dashboardname      |
      | First Tenant  | ten1     | First T Dashboard  |
      | Second Tenant | ten2     | Second T Dashboard |
    And I log in as "admin"
    And I am on "Dashboard" page
    And I press "Manage dashboards"

    When I press "Create dashboard"
    And I set the following fields to these values:
      | Name       | Test dashboard  |
      | Tenant     | First Tenant    |
    And I press "Create dashboard"
    Then "Test dashboard" row "Tenant" column of "alldashboards" table should contain "First Tenant"

  Scenario: Clone tenant dashboard
    Given I am on a totara site
    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name          | idnumber | dashboardname      |
      | First Tenant  | ten1     | First T Dashboard  |
      | Second Tenant | ten2     | Second T Dashboard |
    And I log in as "admin"
    And I am on "Dashboard" page
    And I press "Manage dashboards"

    When I click on "Clone dashboard" "link" in the "First T Dashboard" "table_row"
    And I press "Continue"
    Then "First T Dashboard copy 1" row "Tenant" column of "alldashboards" table should contain "First Tenant"
    And "First T Dashboard copy 1" row "Availability" column of "alldashboards" table should contain "Assigned to 1 audiences"
