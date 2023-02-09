@totara @totara_api @javascript
Feature: View different service account status on API client page.

  Background:
    Given I am on a totara site
    And I enable the "api" advanced feature
    And the following "users" exist:
      | username | firstname | lastname |
      | user1    | api       | user1    |
    And the following "api clients" exist in "totara_api" plugin:
      | name                 | username |
      | API client1          | user1    |

  Scenario: view service account on API clients page
    Given I log in as "admin"
    And I enable the "api" advanced feature
    And I navigate to "Development > API > API clients" in site administration
    When I click on "API client1" "button"
    Then I should see "API client1"
    And I should see "api user1"
    And I should not see "Invalid user"

  Scenario: view suspended service account on API clients page
    Given I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    When I follow "Manage login of api user1"
    And I set the "Choose" Totara form field to "Suspend user account"
    And I press "Update"

    And I navigate to "Development > API > API clients" in site administration
    When I click on "API client1" "button"
    Then I should see "API client1"
    And I should see "api user1"
    And I should see "Invalid user"

  Scenario: view deleted service account on API clients page
    Given I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    When I follow "Delete api user1"
    And I press "Delete"

    And I navigate to "Development > API > API clients" in site administration
    When I click on "API client1" "button"
    Then I should see "API client1"
    And I should not see "api user1"
    And I should see "Invalid user"

  Scenario: view tenant service account on API clients page
    Given I am on a totara site
    And tenant support is enabled with full tenant isolation
    And the following "tenants" exist:
      | name    | idnumber | categoryname |
      | Tenant1 | ten1     | T1           |
      | Tenant2 | ten2     | T1           |
    And the following "users" exist:
      | username | firstname | lastname | tenantmember | tenantdomainmanager |
      | t1user   | T1        | User     | ten1         | ten1                |
      | t2user   | T2        | User     | ten1         |                     |
    And the following "api clients" exist in "totara_api" plugin:
      | name                 | username | tenant_id_number |
      | API tenant client1   | t1user   | ten1             |
      | API tenant client2   | t2user   | ten1             |

    And I log in as "t1user"
    And I navigate to "Courses > Courses and categories" in site administration
    Then I should see "API" in the "Administration" "block"
    When I expand "API" node
    Then I should see "API clients" in the "Administration" "block"
    When I navigate to "API > API clients" in current page administration
    When I click on "API tenant client1" "button"
    Then I should see "API tenant client1"
    And I should see "T1 User"
    And I should not see "Invalid user"
    When I click on "API tenant client2" "button"
    Then I should see "API tenant client2"
    And I should see "T2 User"
    And I should not see "Invalid user"
    And I move user "t2user" to tenant "ten2"
    When I navigate to "API > API clients" in current page administration
    Then I click on "API tenant client2" "button"
    And I should not see "T2 user"
    And I should see "Invalid user"
    And I log out

    # Site manager can view service account name under wrong tenant.
    And I log in as "admin"
    And I enable the "api" advanced feature
    And I click on "[aria-label='Show admin menu window']" "css_element"
    Then I follow "Tenants"
    And I click on "Tenant1" "link"
    Then I should see "API" in the "Administration" "block"
    And I expand "API" node
    When I navigate to "API > API clients" in current page administration
    Then I should see "API tenant client2"
    When I click on "API tenant client2" "button"
    Then I should see "T2 User"
    And I should see "Invalid user"