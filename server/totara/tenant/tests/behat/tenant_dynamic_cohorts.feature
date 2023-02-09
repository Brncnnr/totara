@totara @tenant @totara_tenant @javascript
Feature: Tenant members can create dynamic audiences

  As a tenant domain manager with audience manage rules permissions
  In order to use dynamic tenant audiences
  I want to be able to create dynamic audiences

  Scenario: Tenant domain manager with manage rules permission can manage dynamic audiences
    Given I am on a totara site
    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name          | idnumber | suspended | categoryname      | cohortname      | dashboardname      |
      | First Tenant  | t1       | 0         | First T Category  | First T Cohort  | First T Dashboard  |
      | Second Tenant | t2       | 0         | Second T Category | Second T Cohort | Second T Dashboard |
    And the following "users" exist:
      | username          | firstname | lastname       | tenantmember | tenantparticipant | tenantusermanager | tenantdomainmanager |
      | user1             | First     | Member         | t1           |                   |                   |                     |
      | user2             | Second    | Member         | t2           |                   |                   |                     |
      | manager1          | First     | Manager        | t1           |                   | t1                | t1                  |
      | manager2          | Second    | Manager        | t2           |                   | t2                | t2                  |
      | nonparticipant    | Some      | Nonparticipant |              |                   |                   |                     |
      | participant       | Tenant    | Participant    |              | t1                |                   |                     |
      | boss              | Tenant    | Boss           |              | t1, t2            | t1, t2            | t1, t2              |
    And the following "permission overrides" exist:
      | capability                           | permission | role                | contextlevel | reference |
      | totara/cohort:managerules            | Allow      | tenantdomainmanager | System       |           |

    When I log in as "manager1"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I switch to "Add new audience" tab
    And I set the following fields to these values:
      | Name | Test audience |
      | Type | Dynamic       |
    And I click on "Save changes" "button"
    And I set the field "addrulesetmenu" to "Email address"
    And I set the field "equal" to "contains"
    And I set the field "listofvalues" to "@"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "First Member"
    And I should see "First Manager"
    And I should see "Tenant Participant"
    And I should see "Tenant Boss"
    And I should not see "Some Nonparticipan"
    And I should not see "Second Member"
    And I should not see "Second Manager"
