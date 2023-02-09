@totara @tenant @totara_tenant @enrol @enrol_cohort @javascript
Feature: Tenant cohort enrolment

  As a person responsible for enrolments
  In order to configure enrolments
  I want to be able to set up audience sync

  Background:
    Given I am on a totara site
    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name          | idnumber |
      | First Tenant  | ten1     |
      | Second Tenant | ten2     |
    And the following "users" exist:
      | username          | firstname | lastname    | tenantmember | tenantparticipant |
      | teacher0          | Regular   | Teacher     |              |                   |
      | teacher1          | First     | Teacher     | ten1         |                   |
      | teacher2          | Second    | Teacher     |              | ten2              |
      | manager1          | First     | Manager     |              |                   |
    And the following "cohorts" exist:
      | name            | idnumber |
      | System cohort A | CHSA     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 0 | COURSE0   |          |
      | Course 1 | COURSE1   | ten1     |
      | Course 2 | COURSE2   | ten2     |
    And the following "course enrolments" exist:
      | user     | course   | role           |
      | teacher0 | COURSE0  | editingteacher |
      | teacher1 | COURSE1  | editingteacher |
      | teacher2 | COURSE2  | editingteacher |
    And the following "system role assigns" exist:
      | user              | role                |
      | manager1          | tenantdomainmanager |

  Scenario: Tenant manager adding audience sync without tenant isolation
    Given I log in as "manager1"
    And I click on "Find Learning" in the totara menu
    And I click on "Course 1" "text"

    When I navigate to "Enrolment methods" node in "Course administration > Users"
    And I set the field "Add method" to "Audience sync"
    And I set the field "Audience" to "First Tenant audience"
    And I press "Add method"
    Then I should see "Audience sync (First Tenant audience - Learner)"

    When I click on "Delete" "link" in the "Audience sync (First Tenant audience - Learner)" "table_row"
    And I press "Continue"
    Then I should not see "Audience sync (First Tenant audience - Learner)"

    When I navigate to "Edit settings" node in "Course administration"
    And I press "Add enrolled audiences"
    Then I should see "First Tenant audience"
    And I should not see "Second Tenant audience"
    And I should not see "System cohort A"

  Scenario: Tenant manager adding audience sync with full tenant isolation
    Given tenant support is enabled with full tenant isolation
    And I log in as "manager1"
    And I click on "Find Learning" in the totara menu
    And I click on "Course 1" "text"

    When I navigate to "Enrolment methods" node in "Course administration > Users"
    And I set the field "Add method" to "Audience sync"
    And I set the field "Audience" to "First Tenant audience"
    And I press "Add method"
    Then I should see "Audience sync (First Tenant audience - Learner)"

    When I click on "Delete" "link" in the "Audience sync (First Tenant audience - Learner)" "table_row"
    And I press "Continue"
    Then I should not see "Audience sync (First Tenant audience - Learner)"

    When I navigate to "Edit settings" node in "Course administration"
    And I press "Add enrolled audiences"
    Then I should see "First Tenant audience"
    And I should not see "Second Tenant audience"
    And I should not see "System cohort A"
