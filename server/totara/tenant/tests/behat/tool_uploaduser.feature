@totara @tenant @totara_tenant @tool_uploaduser @javascript
Feature: Tenant user CSV upload

  As an administrator
  In order to quickly add new tenant members
  I want to use CSV user uploads

  Background:
    Given I am on a totara site
    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name          | idnumber |
      | First Tenant  | ten1     |
      | Second Tenant | ten2     |
    And the following "users" exist:
      | username          | firstname | lastname    | tenantmember |
      | user0             | Regular   | User        |              |
      | user2             | Second    | User        | ten2         |

  Scenario: Administrator may upload tenant accounts via CSV
    Given I log in as "admin"

    When I navigate to "Upload users" node in "Site administration > Users"
    And I upload "totara/tenant/tests/fixtures/upload_users.csv" file to "File" filemanager
    And I press "Upload users"
    And I set the following fields to these values:
      | Upload type | Add new and update existing users |
      | Tenant      | First Tenant                      |
    And I press "Upload users"
    Then I should not see "First Tenant" in the "user0@example.com" "table_row"
    And I should not see "Second Tenant" in the "user0@example.com" "table_row"
    And I should see "First Tenant" in the "user1@example.com" "table_row"
    And I should see "Second Tenant" in the "user2@example.com" "table_row"
    And I should see "First Tenant" in the "user3@example.com" "table_row"
