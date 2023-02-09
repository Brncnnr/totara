@totara @tenant @totara_tenant @javascript
Feature: Tenant members must not be admins

  As an administrator
  In order to not create nightmares
  I want to be prevented from adding tenant members as system administrators

  Background:
    Given I am on a totara site
    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name          | idnumber |
      | First Tenant  | ten1     |
    And the following "users" exist:
      | username          | firstname | lastname    | tenantmember | tenantparticipant |
      | user0             | Regular   | User        |              |                   |
      | user1             | First     | User        | ten1         |                   |

  Scenario: Tenant member cannot be assigned as site administrator
    Given I log in as "admin"

    When I navigate to "Site administrators" node in "Site administration > Permissions"
    Then I should see "user0@example.com"
    And I should not see "user1@example.com"
