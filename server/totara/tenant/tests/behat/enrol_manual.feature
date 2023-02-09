@totara @tenant @totara_tenant @enrol @enrol_manual @javascript
Feature: Tenant manual enrolment

  As a person responsible for enrolments
  In order to keep tenants separate
  I want to be able to see only tenant participants when enrolling users in tenant course

  Background:
    Given I am on a totara site
    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name          | idnumber |
      | First Tenant  | ten1     |
      | Second Tenant | ten2     |
    And the following "users" exist:
      | username          | firstname | lastname    | tenantmember | tenantparticipant |
      | user0             | Regular   | User        |              |                   |
      | teacher0          | Regular   | Teacher     |              |                   |
      | teacher1          | First     | Teacher     | ten1         |                   |
      | teacher2          | Second    | Teacher     |              | ten2              |
      | member1           | First     | Member      | ten1         |                   |
      | member2           | Second    | Member      | ten2         |                   |
      | participant1      | First     | Participant |              | ten1              |
      | participant2      | Second    | Participant |              | ten1, ten2        |
      | manager1          | First     | Manager     |              |                   |
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

  Scenario: Regular teacher manually enrolling without tenant isolation
    Given I log in as "teacher0"
    And I click on "Find Learning" in the totara menu
    And I click on "Course 0" "text"

    When I navigate to "Enrolled users" node in "Course administration > Users"
    And I press "Enrol users"
    Then I should see "First Manager"
    And I should see "First Member"
    And I should see "Second Member"
    And I should see "First Participant"
    And I should see "Second Participant"
    And I should see "First Teacher"
    And I should see "Second Teacher"
    And I should see "Admin User"
    And I should see "Regular User"
    And I press "Finish enrolling users"

    When I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Enrol users" "link" in the "Manual enrolment" "table_row"
    Then I should see "First Manager"
    And I should see "First Member"
    And I should see "Second Member"
    And I should see "First Participant"
    And I should see "Second Participant"
    And I should see "First Teacher"
    And I should see "Second Teacher"
    And I should see "Admin User"
    And I should see "Regular User"
    And I log out

  Scenario: Regular teacher manually enrolling with full tenant isolation
    Given tenant support is enabled with full tenant isolation
    And I log in as "teacher0"
    And I click on "Find Learning" in the totara menu
    And I click on "Course 0" "text"

    When I navigate to "Enrolled users" node in "Course administration > Users"
    And I press "Enrol users"
    Then I should see "First Manager"
    And I should not see "First Member"
    And I should not see "Second Member"
    And I should see "First Participant"
    And I should see "Second Participant"
    And I should not see "First Teacher"
    And I should see "Second Teacher"
    And I should see "Admin User"
    And I should see "Regular User"
    And I press "Finish enrolling users"

    When I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Enrol users" "link" in the "Manual enrolment" "table_row"
    Then I should see "First Manager"
    And I should not see "First Member"
    And I should not see "Second Member"
    And I should see "First Participant"
    And I should see "Second Participant"
    And I should not see "First Teacher"
    And I should see "Second Teacher"
    And I should see "Admin User"
    And I should see "Regular User"
    And I log out

  Scenario: Tenant member teacher manually enrolling without tenant isolation
    Given I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I click on "Course 1" "text"

    When I navigate to "Enrolled users" node in "Course administration > Users"
    And I press "Enrol users"
    Then I should not see "First Manager"
    And I should see "First Member"
    And I should not see "Second Member"
    And I should see "First Participant"
    And I should see "Second Participant"
    And I should not see "Regular Teacher"
    And I should not see "Second Teacher"
    And I should not see "Admin User"
    And I should not see "Regular User"
    And I press "Finish enrolling users"

    When I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Enrol users" "link" in the "Manual enrolment" "table_row"
    Then I should not see "First Manager"
    And I should see "First Member"
    And I should not see "Second Member"
    And I should see "First Participant"
    And I should see "Second Participant"
    And I should not see "Regular Teacher"
    And I should not see "Second Teacher"
    And I should not see "Admin User"
    And I should not see "Regular User"
    And I log out

  Scenario: Tenant member teacher manually enrolling with full tenant isolation
    Given tenant support is enabled with full tenant isolation
    And I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I click on "Course 1" "text"

    When I navigate to "Enrolled users" node in "Course administration > Users"
    And I press "Enrol users"
    Then I should not see "First Manager"
    And I should see "First Member"
    And I should not see "Second Member"
    And I should see "First Participant"
    And I should see "Second Participant"
    And I should not see "Regular Teacher"
    And I should not see "Second Teacher"
    And I should not see "Admin User"
    And I should not see "Regular User"
    And I press "Finish enrolling users"

    When I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Enrol users" "link" in the "Manual enrolment" "table_row"
    Then I should not see "First Manager"
    And I should see "First Member"
    And I should not see "Second Member"
    And I should see "First Participant"
    And I should see "Second Participant"
    And I should not see "Regular Teacher"
    And I should not see "Second Teacher"
    And I should not see "Admin User"
    And I should not see "Regular User"
    And I log out

  Scenario: Tenant manager manually enrolling without tenant isolation
    Given I log in as "manager1"
    And I click on "Find Learning" in the totara menu
    And I click on "Course 1" "text"

    When I navigate to "Enrolled users" node in "Course administration > Users"
    And I press "Enrol users"
    Then I should see "First Member"
    And I should not see "Second Member"
    And I should see "First Participant"
    And I should see "Second Participant"
    And I should not see "Regular Teacher"
    And I should not see "Second Teacher"
    And I should not see "Admin User"
    And I should not see "Regular User"
    And I press "Finish enrolling users"

    When I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Enrol users" "link" in the "Manual enrolment" "table_row"
    Then I should see "First Member"
    And I should not see "Second Member"
    And I should see "First Participant"
    And I should see "Second Participant"
    And I should not see "Regular Teacher"
    And I should not see "Second Teacher"
    And I should not see "Admin User"
    And I should not see "Regular User"
    And I log out

  Scenario: Tenant manager manually enrolling with full tenant isolation
    Given tenant support is enabled with full tenant isolation
    And I log in as "manager1"
    And I click on "Find Learning" in the totara menu
    And I click on "Course 1" "text"

    When I navigate to "Enrolled users" node in "Course administration > Users"
    And I press "Enrol users"
    Then I should see "First Member"
    And I should not see "Second Member"
    And I should see "First Participant"
    And I should see "Second Participant"
    And I should not see "Regular Teacher"
    And I should not see "Second Teacher"
    And I should not see "Admin User"
    And I should not see "Regular User"
    And I press "Finish enrolling users"

    When I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Enrol users" "link" in the "Manual enrolment" "table_row"
    Then I should see "First Member"
    And I should not see "Second Member"
    And I should see "First Participant"
    And I should see "Second Participant"
    And I should not see "Regular Teacher"
    And I should not see "Second Teacher"
    And I should not see "Admin User"
    And I should not see "Regular User"
    And I log out
