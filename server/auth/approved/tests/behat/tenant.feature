@totara @auth @auth_approved @tenant
Feature: Test confirming tenant member signup
  As system administrator
  In order to allow tenant members to sign up
  I need to be able to sepcify tenant during account confirmation

  @javascript
  Scenario: Test account sign up tenant assignment
    Given I am on a totara site
    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name          | idnumber |
      | First Tenant  | ten1     |
      | Second Tenant | ten2     |

    And I log in as "admin"
    And I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I click on "Enable" "link" in the "Self-registration with approval" "table_row"
    And I set the following administration settings values:
      | registerauth | Self-registration with approval |
    And I log out

    And I press "Create new account"
    And I set the following fields to these values:
      | Username      | test1             |
      | Password      | Password_1        |
      | Email address | test1@example.com |
      | First name    | Test              |
      | Surname       | Account           |
    And I press "Request account"
    And I should see "An email should have been sent to your address at test1@example.com"
    When I confirm self-registration request from email "test1@example.com"
    Then I should see "an email should have been sent to your address at test1@example.com with information describing the account approval process"

    When I log in as "admin"
    And I navigate to "Pending requests" node in "Site administration > Plugins > Authentication > Self-registration with approval"
    And I click on "Approve" "link" in the "test1@example.com" "table_row"
    And I set the following Totara form fields to these values:
      | Tenant | First Tenant |
    And I press "Approve"
    Then I should see "Account request \"test1@example.com\" was approved"
    And I navigate to "Manage tenants" node in "Site administration > Tenants"
    And I click on "1" "link" in the "First Tenant" "table_row"
    And "test1" row "Tenant member" column of "tenant_participants" table should contain "Yes"
    And I should see "test1@example.com"
    And I log out

    When I set the following fields to these values:
      | Username      | test1             |
      | Password      | Password_1        |
    And I press "Log in"
    Then I should see "Test Account"
    And I should see "Current Learning"
    And I should see "First Tenant dashboard"

