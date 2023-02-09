@totara @totara_program @totara_tenant @javascript
Feature: Multi-tenancy programs
    In order to use a program with multi-tenancy
    As an admin
    I need to have user selection restricted by tenant

  Background:
    Given I am on a totara site
    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name          | idnumber |
      | First Tenant  | ten1     |
      | Second Tenant | ten2     |
    And the following "users" exist:
      | username          | firstname | lastname    | tenantmember | tenantparticipant | tenantusermanager |
      | user1             | Learner   | One         | ten1         |                   |                   |
      | user2             | Learner   | Two         |              | ten1              |                   |
      | user3             | Learner   | Three       | ten2         |                   |                   |
      | user4             | Learner   | Four        |              |                   |                   |
      | user5             | Learner   | Five        |              |                   |                   |
      | user6             | Learner   | Six         |              | ten2              |                   |
      | usermanager       | User      | Manager     | ten1         |                   | ten1              |

    And the following "cohorts" exist:
      | name                 | idnumber | contextlevel | reference |
      | Audience 1           | aud1     | System       |           |
      | Audience 2           | aud2     | System       |           |
      | Audience in tenant 1 | aud3     | Category     | ten1      |
      | Audience in tenant 2 | aud4     | Category     | ten2      |

  Scenario: Test program assignments for standard program
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Add a new program" "button"
    And I set the field "Full name" to "Standard program 1"
    And I click on "Save changes" "button"
    And I click on "Assignments" "link"
    When I set the field "Add a new" to "Individuals"
    Then I should see "Learner One"
    And I should see "Learner Five"
    And I should see "Learner Three"
    And I click on "Cancel" "button"
    When I set the field "Add a new" to "Audiences"
    Then I should see "Audience 1"
    And I should see "Audience 2"
    And I should not see "First Tenant audience"
    And I should not see "Audience in tenant 1"

  Scenario: Test program assignments for tenant one program
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "First Tenant category" "link"
    And I click on "Add a new program" "button"
    And I set the field "Full name" to "Tenant 1 program"
    And I click on "Save changes" "button"
    And I click on "Assignments" "link"
    When I set the field "Add a new" to "Individuals"
    Then I should see "Learner Two"
    And I should see "Learner One"
    And I should not see "Learner Five"
    And I should not see "Learner Six"
    And I click on "Cancel" "button"
    When I set the field "Add a new" to "Audiences"
    Then I should see "First Tenant audience"
    And I should see "Audience in tenant 1"
    And I should not see "Audience 1"
    And I should not see "Audience in tenant 2"

  Scenario: Test program assignments for tenant two program
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Second Tenant category" "link"
    And I click on "Add a new program" "button"
    And I set the field "Full name" to "Tenant 2 program"
    And I click on "Save changes" "button"
    And I click on "Assignments" "link"
    When I set the field "Add a new" to "Individuals"
    Then I should see "Learner Three"
    And I should see "Learner Six"
    And I should not see "Learner One"
    And I should not see "Learner Two"
    And I should not see "Learner Four"
    And I click on "Cancel" "button"
    When I set the field "Add a new" to "Audiences"
    Then I should see "Second Tenant audience"
    And I should see "Audience in tenant 2"
    And I should not see "Audience 1"
    And I should not see "Audience in tenant 1"

