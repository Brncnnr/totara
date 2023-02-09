@totara @tenant @totara_tenant @javascript
Feature: Tenant role assignments

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
      | username          | firstname | lastname    | tenantmember | tenantparticipant | tenantusermanager | tenantdomainmanager |
      | user0             | Regular   | User        |              |                   |                   |                     |
      | user1             | First     | User        | ten1         |                   |                   |                     |
      | user2             | Second    | User        | ten2         |                   |                   |                     |
      | teacher0          | Regular   | Teacher     |              |                   |                   |                     |
      | teacher1          | First     | Teacher     | ten1         |                   |                   |                     |
      | teacher2          | Second    | Teacher     |              | ten2              |                   |                     |
      | manager0          | Main      | Manager     |              |                   |                   |                     |
      | manager1          | First     | Manager     |              | ten1              | ten1              | ten1                |
      | manager2          | Second    | Manager     |              |                   | ten2              | ten2                |
    Given the following "categories" exist:
      | name            | category | idnumber |
      | Normal category |          | CAT0     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 0 | COURSE0   | CAT0     |
      | Course 1 | COURSE1   | ten1     |
      | Course 2 | COURSE2   | ten2     |
    And the following "course enrolments" exist:
      | user     | course   | role           |
      | teacher0 | COURSE0  | editingteacher |
      | teacher1 | COURSE1  | editingteacher |
      | teacher2 | COURSE2  | editingteacher |
    And the following "system role assigns" exist:
      | user              | role    |
      | manager0          | manager |

  Scenario: Administrator assigning roles without tenant isolation
    Given I log in as "admin"

    When I navigate to "Assign system roles" node in "Site administration > Permissions"
    And I follow "Site Manager"
    Then I should see "manager0@example.com"
    And I should see "manager1@example.com"
    And I should see "manager2@example.com"
    And I should see "teacher0@example.com"
    And I should see "teacher1@example.com"
    And I should see "teacher2@example.com"
    And I should see "user0@example.com"
    And I should see "user1@example.com"
    And I should see "user2@example.com"
    And I should see "moodle@example.com"

    When I navigate to "Courses and categories" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I navigate to "Assign roles" node in "Category: Miscellaneous"
    And I follow "Site Manager"
    Then I should see "manager0@example.com"
    And I should see "manager1@example.com"
    And I should see "manager2@example.com"
    And I should see "teacher0@example.com"
    And I should see "teacher1@example.com"
    And I should see "teacher2@example.com"
    And I should see "user0@example.com"
    And I should see "user1@example.com"
    And I should see "user2@example.com"
    And I should see "moodle@example.com"

    When I navigate to "Manage tenants" node in "Site administration > Tenants"
    And I follow "First Tenant"
    And I navigate to "Assign roles" node in "Category: First Tenant category"
    And I follow "Tenant Domain Manager"
    Then I should not see "manager0@example.com"
    And I should see "manager1@example.com"
    And I should not see "manager2@example.com"
    And I should not see "teacher0@example.com"
    And I should see "teacher1@example.com"
    And I should not see "teacher2@example.com"
    And I should not see "user0@example.com"
    And I should see "user1@example.com"
    And I should not see "user2@example.com"
    And I should not see "moodle@example.com"

    When I navigate to "Manage tenants" node in "Site administration > Tenants"
    And I follow "First Tenant"
    And I navigate to "Assign roles" node in "First Tenant"
    And I follow "Tenant User Manager"
    Then I should not see "manager0@example.com"
    And I should see "manager1@example.com"
    And I should not see "manager2@example.com"
    And I should not see "teacher0@example.com"
    And I should see "teacher1@example.com"
    And I should not see "teacher2@example.com"
    And I should not see "user0@example.com"
    And I should see "user1@example.com"
    And I should not see "user2@example.com"
    And I should not see "moodle@example.com"

    When I navigate to "Manage tenants" node in "Site administration > Tenants"
    And I follow "Second Tenant"
    And I navigate to "Assign roles" node in "Category: Second Tenant category"
    And I follow "Tenant Domain Manager"
    Then I should not see "manager0@example.com"
    And I should not see "manager1@example.com"
    And I should see "manager2@example.com"
    And I should not see "teacher0@example.com"
    And I should not see "teacher1@example.com"
    And I should see "teacher2@example.com"
    And I should not see "user0@example.com"
    And I should not see "user1@example.com"
    And I should see "user2@example.com"
    And I should not see "moodle@example.com"

    When I navigate to "Manage tenants" node in "Site administration > Tenants"
    And I follow "Second Tenant"
    And I navigate to "Assign roles" node in "Second Tenant"
    And I follow "Tenant User Manager"
    Then I should not see "manager0@example.com"
    And I should not see "manager1@example.com"
    And I should see "manager2@example.com"
    And I should not see "teacher0@example.com"
    And I should not see "teacher1@example.com"
    And I should see "teacher2@example.com"
    And I should not see "user0@example.com"
    And I should not see "user1@example.com"
    And I should see "user2@example.com"
    And I should not see "moodle@example.com"

    When I click on "Find Learning" in the totara menu
    And I click on "Course 0" "text"
    And I navigate to "Other users" node in "Course administration > Users"
    And I press "Assign roles"
    Then I should see "manager0@example.com"
    And I should see "manager1@example.com"
    And I should see "manager2@example.com"
    And I should not see "teacher0@example.com"
    And I should see "teacher1@example.com"
    And I should see "teacher2@example.com"
    And I should see "user0@example.com"
    And I should see "user1@example.com"
    And I should see "user2@example.com"
    And I should see "moodle@example.com"

    When I click on "Find Learning" in the totara menu
    And I click on "Course 1" "text"
    And I navigate to "Other users" node in "Course administration > Users"
    And I press "Assign roles"
    Then I should not see "manager2@example.com"
    And I should not see "teacher0@example.com"
    And I should not see "teacher1@example.com"
    And I should not see "teacher2@example.com"
    And I should not see "user0@example.com"
    And I should see "user1@example.com"
    And I should not see "user2@example.com"
    And I should not see "moodle@example.com"

    When I click on "Find Learning" in the totara menu
    And I click on "Course 2" "text"
    And I navigate to "Other users" node in "Course administration > Users"
    And I press "Assign roles"
    Then I should not see "teacher0@example.com"
    And I should not see "teacher1@example.com"
    And I should not see "teacher2@example.com"
    And I should not see "user0@example.com"
    And I should not see "user1@example.com"
    And I should see "user2@example.com"
    And I should not see "moodle@example.com"

  Scenario: Administrator assigning roles with full tenant isolation
    Given I log in as "admin"
    And tenant support is enabled with full tenant isolation

    When I navigate to "Assign system roles" node in "Site administration > Permissions"
    And I follow "Site Manager"
    Then I should see "manager0@example.com"
    And I should see "manager1@example.com"
    And I should see "manager2@example.com"
    And I should see "teacher0@example.com"
    And I should not see "teacher1@example.com"
    And I should see "teacher2@example.com"
    And I should see "user0@example.com"
    And I should not see "user1@example.com"
    And I should not see "user2@example.com"
    And I should see "moodle@example.com"

    When I navigate to "Courses and categories" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I navigate to "Assign roles" node in "Category: Miscellaneous"
    And I follow "Site Manager"
    Then I should see "manager0@example.com"
    And I should see "manager1@example.com"
    And I should see "manager2@example.com"
    And I should see "teacher0@example.com"
    And I should not see "teacher1@example.com"
    And I should see "teacher2@example.com"
    And I should see "user0@example.com"
    And I should not see "user1@example.com"
    And I should not see "user2@example.com"
    And I should see "moodle@example.com"

    When I navigate to "Manage tenants" node in "Site administration > Tenants"
    And I follow "First Tenant"
    And I navigate to "Assign roles" node in "Category: First Tenant category"
    And I follow "Tenant Domain Manager"
    Then I should not see "manager0@example.com"
    And I should see "manager1@example.com"
    And I should not see "manager2@example.com"
    And I should not see "teacher0@example.com"
    And I should see "teacher1@example.com"
    And I should not see "teacher2@example.com"
    And I should not see "user0@example.com"
    And I should see "user1@example.com"
    And I should not see "user2@example.com"
    And I should not see "moodle@example.com"

    When I navigate to "Manage tenants" node in "Site administration > Tenants"
    And I follow "First Tenant"
    And I navigate to "Assign roles" node in "First Tenant"
    And I follow "Tenant User Manager"
    Then I should not see "manager0@example.com"
    And I should see "manager1@example.com"
    And I should not see "manager2@example.com"
    And I should not see "teacher0@example.com"
    And I should see "teacher1@example.com"
    And I should not see "teacher2@example.com"
    And I should not see "user0@example.com"
    And I should see "user1@example.com"
    And I should not see "user2@example.com"
    And I should not see "moodle@example.com"

    When I navigate to "Manage tenants" node in "Site administration > Tenants"
    And I follow "Second Tenant"
    And I navigate to "Assign roles" node in "Category: Second Tenant category"
    And I follow "Tenant Domain Manager"
    Then I should not see "manager0@example.com"
    And I should not see "manager1@example.com"
    And I should see "manager2@example.com"
    And I should not see "teacher0@example.com"
    And I should not see "teacher1@example.com"
    And I should see "teacher2@example.com"
    And I should not see "user0@example.com"
    And I should not see "user1@example.com"
    And I should see "user2@example.com"
    And I should not see "moodle@example.com"

    When I navigate to "Manage tenants" node in "Site administration > Tenants"
    And I follow "Second Tenant"
    And I navigate to "Assign roles" node in "Second Tenant"
    And I follow "Tenant User Manager"
    Then I should not see "manager0@example.com"
    And I should not see "manager1@example.com"
    And I should see "manager2@example.com"
    And I should not see "teacher0@example.com"
    And I should not see "teacher1@example.com"
    And I should see "teacher2@example.com"
    And I should not see "user0@example.com"
    And I should not see "user1@example.com"
    And I should see "user2@example.com"
    And I should not see "moodle@example.com"

    When I click on "Find Learning" in the totara menu
    And I click on "Course 0" "text"
    And I navigate to "Other users" node in "Course administration > Users"
    And I press "Assign roles"
    Then I should see "manager0@example.com"
    And I should see "manager1@example.com"
    And I should see "manager2@example.com"
    And I should not see "teacher0@example.com"
    And I should not see "teacher1@example.com"
    And I should see "teacher2@example.com"
    And I should see "user0@example.com"
    And I should not see "user1@example.com"
    And I should not see "user2@example.com"
    And I should see "moodle@example.com"

    When I click on "Find Learning" in the totara menu
    And I click on "Course 1" "text"
    And I navigate to "Other users" node in "Course administration > Users"
    And I press "Assign roles"
    Then I should not see "manager2@example.com"
    And I should not see "teacher0@example.com"
    And I should not see "teacher1@example.com"
    And I should not see "teacher2@example.com"
    And I should not see "user0@example.com"
    And I should see "user1@example.com"
    And I should not see "user2@example.com"
    And I should not see "moodle@example.com"

    When I click on "Find Learning" in the totara menu
    And I click on "Course 2" "text"
    And I navigate to "Other users" node in "Course administration > Users"
    And I press "Assign roles"
    Then I should not see "teacher0@example.com"
    And I should not see "teacher1@example.com"
    And I should not see "teacher2@example.com"
    And I should not see "user0@example.com"
    And I should not see "user1@example.com"
    And I should see "user2@example.com"
    And I should not see "moodle@example.com"
