@totara_cohort @totara @javascript
Feature: Warn about impact when removing cohorts
  As a cohort manager
  I need to be made aware about any impact when removing a cohort
  So I can prevent accidental deletions/unenrolments from synced course/other items.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | First     | User     | user1@example.com |
      | user2    | Second    | User     | user2@example.com |
    And the following "cohorts" exist:
      | name       | idnumber | description         | contextlevel | reference |
      | Audience 1 | AUD1     | About this audience | System       | 0         |
    And the following "courses" exist:
      | fullname | shortname | description       |
      | Course 1 | c1        | About this course |
    And the following "workspaces" exist in "container_workspace" plugin:
      | name | owner | summary            |
      | w1   | user1 | Workspace summary  |
      | w2   | user1 | Workspace2 summary |
    And the following "cohort enrolments" exist in "totara_cohort" plugin:
      | course | cohort | role    |
      | c1     | AUD1   | student |
      | w1     | AUD1   | student |
      | w2     | AUD1   | student |

  Scenario: Show a warning when an audience is deleted
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I click on "Delete" "link" in the "cohort_admin" "table"
    Then the "cohort_delete_changes" table should contain the following:
      | Affected area | Changes to audience members  | Scope |
      | Courses       | Unenrol and delete user data | 1     |
      | Workspaces    | Removed                      | 2     |

    When I press "Cancel"
    Then I disable the "container_workspace" advanced feature
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I click on "Delete" "link" in the "cohort_admin" "table"
    Then the "cohort_delete_changes" table should not contain the following:
      | Affected area | Changes to audience members | Scope |
      | Workspaces    | Removed                     | 2     |
