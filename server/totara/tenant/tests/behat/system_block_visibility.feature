@totara @tenant @totara_tenant @javascript @block @block_totara_user_profile
Feature: Tenant member can see system blocks on profile without tenant isolation

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

    And tenant support is enabled without tenant isolation
    When the following "tenants" exist:
      | name          | idnumber | suspended | categoryname      | cohortname      | dashboardname      |
      | First Tenant  | t1       | 0         | First T Category  | First T Cohort  | First T Dashboard  |

    And the following "courses" exist:
      | fullname | shortname   | category |
      | Course 0A | COURSE0A   | t1       |
      | Course 1A | COURSE1A   | t1       |
      | Course 2A | COURSE2A   |          |
      | Course 1B | COURSE1B   |          |

    And the following "users" exist:
      | username          | firstname | lastname    | tenantmember | tenantparticipant | tenantusermanager | tenantdomainmanager |
      | user1             | First     | Member      | t1           |                   |                   |                     |
      | user2             | Second    | NonMember   |              |                   |                   |                     |
      | user3             | Third     | NonMember   |              |                   |                   |                     |

    And the following "course enrolments" exist:
      | user        | course   | role    |
      | user1       | COURSE0A | student |
      | user1       | COURSE1A | student |
      | user2       | COURSE1A | student |
      | user2       | COURSE2A | student |
      | user3       | COURSE1B | student |

  Scenario: Tenant member may see user profile blocks
    When I log in as "user1"
    And I am on profile page for user "user1"
    And I should see "Course 0A"
    And I should see "Course 1A"
    And I should not see "Course 2A"
    And I should see "Preferences"
    And I should see "Reports"
    And I should see "Calendar"

    And I am on profile page for user "user2"
    And I should see "Course 1A"
    And I should see "Course 2A"
    And I should not see "Course 1B"
    And I should not see "Preferences"
    And I should not see "Reports"
    And I should see "Calendar"

    And I am on profile page for user "user3"
    And I should see "Third NonMember"

    When I disable the "engage_resources" advanced feature
    And I am on profile page for user "user3"
    Then I should see "The details of this user are not available to you"

