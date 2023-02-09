@totara @tenant @totara_tenant @javascript @block @block_totara_user_profile
Feature: Tenant member can see system blocks on profile with full tenant isolation

  As a tenant member
  In order to use Totara
  I want to be able to login and use browse around the site

  Background:
    Given I am on a totara site
    And the "coursedetails" user profile block exists
    And the "reports" user profile block exists
    And the "loginactivity" user profile block exists
    And the "mylearning" user profile block exists
    And the "miscellaneous" user profile block exists

    And I log in as "admin"
    And I navigate to "Default profile page" node in "Site administration > Users"
    And I press "Blocks editing on"
    And I add the "Calendar" block
    And I log out

    And tenant support is enabled with full tenant isolation
    When the following "tenants" exist:
      | name          | idnumber | suspended | categoryname      | cohortname      | dashboardname      |
      | First Tenant  | t1       | 0         | First T Category  | First T Cohort  | First T Dashboard  |
      | Second Tenant | t2       | 0         | First T Category  | First T Cohort  | First T Dashboard  |
      | Third Tenant  | t3       | 0         | Second T Category | Second T Cohort | Second T Dashboard |

    And the following "courses" exist:
      | fullname | shortname   | category |
      | Course 0 | COURSE0     | t1       |
      | Course 1 | COURSE1     | t1       |
      | Course 2 | COURSE2     | t2       |
      | Course 4 | COURSE4     |          |

    And the following "users" exist:
      | username          | firstname | lastname    | tenantmember | tenantparticipant | tenantusermanager | tenantdomainmanager |
      | user1             | First     | Member      | t1           |                   |                   |                     |
      | user2             | Second    | Member      | t1           |                   |                   |                     |
      | user3             | Second    | Member      | t2           |                   |                   |                     |
      | user4             | Third     | NonMember   |              |                   |                   |                     |

    And the following "course enrolments" exist:
      | user        | course   | role    |
      | user1       | COURSE0 | student |
      | user1       | COURSE1 | student |
      | user2       | COURSE1 | student |
      | user3       | COURSE1 | student |
      | user3       | COURSE2 | student |
      | user4       | COURSE4 | student |

  Scenario: Tenant member may see user profile blocks on his own profile and cannot see other user's profile
    When I log in as "user1"
    And I am on profile page for user "user1"
    And I should see "Course 0"
    And I should see "Course 1"
    And I should not see "Course 2"
    And I should see "Preferences"
    And I should see "Reports"
    And I should see "Calendar"

    # Same Course, same Tenancy
    And I am on profile page for user "user2"
    And I should see "Course 1"
    And I should not see "Course 2"
    And I should not see "Preferences"
    And I should not see "Reports"
    And I should see "Calendar"

    # Same Course, different Tenancy
    And I am on profile page for user "user3"
    And I should see "The details of this user are not available to you"

    # Different Course, system user
    And I am on profile page for user "user4"
    And I should see "The details of this user are not available to you"

