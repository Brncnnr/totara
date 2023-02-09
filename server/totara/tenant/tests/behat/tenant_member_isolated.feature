@totara @tenant @totara_tenant @javascript
Feature: Tenant member access with full tenant isolation

  As a tenant member
  In order to use Totara
  I want to be able to login and use browse around the site

  Background:
    Given I am on a totara site
    And tenant support is enabled with full tenant isolation
    When the following "tenants" exist:
      | name          | idnumber | suspended | categoryname      | cohortname      | dashboardname      |
      | First Tenant  | t1       | 0         | First T Category  | First T Cohort  | First T Dashboard  |
      | Second Tenant | t2       | 0         | Second T Category | Second T Cohort | Second T Dashboard |
      | Third Tenant  | t3       | 1         | Third T Category  | Third T Cohort  | Third T Dashboard  |
    And the following "courses" exist:
      | fullname | shortname   | category |
      | Course 0A | COURSE0A   |          |
      | Course 0B | COURSE0B   |          |
      | Course 1A | COURSE1A   | t1       |
      | Course 1B | COURSE1B   | t1       |
      | Course 2A | COURSE2A   | t2       |
      | Course 2B | COURSE2B   | t2       |
      | Course 3A | COURSE3A   | t3       |
      | Course 3B | COURSE3B   | t3       |
    And the following "users" exist:
      | username          | firstname | lastname    | tenantmember | tenantparticipant | tenantusermanager | tenantdomainmanager |
      | user1             | First     | Member      | t1           |                   |                   |                     |
      | user2             | Second    | Member      | t2           |                   |                   |                     |
      | user3             | Third     | Member      | t3           |                   |                   |                     |
      | manager1          | First     | Manager     | t1           |                   | t1                | t1                  |
      | manager2          | First     | Manager     | t2           |                   | t2                | t2                  |
      | manager3          | First     | Manager     | t3           |                   | t3                | t3                  |
      | participant       | Tenant    | Participant |              | t1, t3            |                   |                     |
      | boss              | Tenant    | Boss        |              | t1, t2, t3        | t1, t2, t3        | t1, t2, t3          |
    And the following "course enrolments" exist:
      | user        | course   | role    |
      | user1       | COURSE0A | student |
      | user1       | COURSE1A | student |
      | user1       | COURSE3A | student |
      | user2       | COURSE2A | student |
      | participant | COURSE0B | student |
      | participant | COURSE1B | student |
      | participant | COURSE2B | student |
      | participant | COURSE3B | student |

  Scenario: Tenant member may log in and access their courses with full tenant isolation
    When I log in as "user1"
    Then I should not see "Home" in the totara menu

    When I click on "Find Learning" in the totara menu
    Then I should not see "Course 0A"
    And I should not see "Course 0B"
    And I should see "Course 1A"
    And I should see "Course 1B"
    And I should not see "Course 2A"
    And I should not see "Course 2B"
    And I should not see "Course 3A"
    And I should not see "Course 3B"

    When I click on "Find Learning" in the totara menu
    And I click on "Course 1A" "text"
    Then I should see "Topic 1"

    When I click on "Find Learning" in the totara menu
    And I click on "Course 1B" "text"
    Then I should see "You can not enrol yourself in this course."
