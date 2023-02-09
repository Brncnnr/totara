@totara @mod_facetoface @totara_tenant @javascript
Feature: Multi-tenancy seminars
    In order to use seminars with multi-tenancy
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
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
      | Course 2 | C2        | ten1     | 1                |
      | Course 3 | C3        | ten2     | 1                |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | course |
      | No tenant Seminar | C1     |
      | Tenant 1 Seminar  | C2     |
      | Tenant 2 Seminar  | C3     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | No tenant Seminar | event 1 |
      | Tenant 1 Seminar  | event 2 |
      | Tenant 2 Seminar  | event 3 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                   | finish                  |
      | event 1      | now +2 days             | now +2 days +60 minutes |
      | event 2      | now +2 days             | now +2 days +60 minutes |
      | event 3      | now +2 days             | now +2 days +60 minutes |

  Scenario: Add attendees to seminars in different tenants
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    When I set the field "Attendee actions" to "Add users"
    Then I should see "8 potential users"
    And I should see "Learner One"
    And I should see "Learner Three"
    And I should see "Learner Four"
    # Course 2 in Tenant 1
    And I am on "Course 2" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    When I set the field "Attendee actions" to "Add users"
    Then I should see "3 potential users"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should see "User Manager"
    And I should not see "Learner Three"
    And I should not see "Learner Four"
    And I should not see "Learner Six"
    # Course 3 in Tenant 2
    And I am on "Course 3" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    When I set the field "Attendee actions" to "Add users"
    Then I should see "2 potential users"
    And I should see "Learner Three"
    And I should see "Learner Six"
    And I should not see "Learner One"
    And I should not see "Learner Two"
    And I should not see "Learner Four"
    And I should not see "User Manager"
