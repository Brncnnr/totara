@totara @totara_generator @mod_approval @javascript @vuejs
Feature: Filter test of approval workflow dashboard
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | approver | Approver  | Zero     | approver@example.com |
      | user1    | Applicant | One      |    user1@example.com |
      | user2    | Applicant | Two      |    user2@example.com |
      | user3    | Applicant | Three    |    user3@example.com |
      | user4    | Applicant | Four     |    user4@example.com |
      | user5    | Applicant | Five     |    user5@example.com |
      | user6    | Applicant | Six      |    user6@example.com |
      | user7    | Applicant | Seven    |    user7@example.com |
      | user8    | Applicant | Eight    |    user8@example.com |
      | user9    | Applicant | Nine     |    user9@example.com |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber |
      | Organisation Framework | ODF      |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname    | shortname | idnumber |
      | ODF           | Cool agency | cool      | org01    |
      | ODF           | Mild agency | mild      | org02    |
      | ODF           | Hot agency  | hot       | org03    |
    And the following "position" frameworks exist:
      | fullname           | idnumber |
      | Position Framework | PDF      |
    And the following "position" hierarchy exists:
      | framework | fullname        | idnumber |
      | PDF       | High position   | pos01    |
      | PDF       | Medium position | pos02    |
      | PDF       | Low position    | pos03    |
    And the following "cohorts" exist:
      | name          | idnumber |
      | Fast cohort   | aud01    |
      | Normal cohort | aud02    |
      | Slow cohort   | aud03    |
    And the following job assignments exist:
      | user  | fullname              | idnumber | organisation | position |
      | user1 | Cool job assignment   | org01ja  | org01        |          |
      | user2 | Mild job assignment   | org02ja  | org02        |          |
      | user3 | Hot job assignment    | org03ja  | org03        |          |
      | user4 | High job assignment   | pos01ja  |              | pos01    |
      | user5 | Medium job assignment | pos02ja  |              | pos02    |
      | user6 | Low job assignment    | pos03ja  |              | pos03    |
    And the following "cohort members" exist:
      | user  | cohort |
      | user7 | aud01  |
      | user8 | aud02  |
      | user9 | aud03  |
    And the following "workflow types" exist in "mod_approval" plugin:
      | name            |
      | First Workflow  |
      | Second Workflow |
      | Third Workflow  |
    And the following "forms" exist in "mod_approval" plugin:
      | title     |
      | Uno Form  |
      | Dos Form  |
      | Tres Form |
    And the following "form versions" exist in "mod_approval" plugin:
      | form      | version | json_schema |
      | Uno Form  | Uno     | test1       |
      | Dos Form  | Dos     | test1       |
      | Tres Form | Tres    | test1       |
    And the following "workflows" exist in "mod_approval" plugin:
      | name                   | id_number | form      | workflow_type   | type         | identifier | assignment_id_number |
      | 11 Elven Workflow      | WKF11     | Uno Form  | First Workflow  | organisation | org01      | org01ass             |
      | 12 Towelve Workflow    | WKF12     | Dos Form  | First Workflow  | organisation | org02      | org02ass             |
      | 13 Thirstyn Workflow   | WKF13     | Tres Form | First Workflow  | organisation | org03      | org03ass             |
      | 21 Twin One Workflow   | WKF21     | Uno Form  | Second Workflow | position     | pos01      | pos01ass             |
      | 22 Twen Two Workflow   | WKF22     | Dos Form  | Second Workflow | position     | pos02      | pos02ass             |
      | 23 Twon Three Workflow | WKF23     | Tres Form | Second Workflow | position     | pos03      | pos03ass             |
      | 31 Thyn One Workflow   | WKF31     | Uno Form  | Third Workflow  | cohort       | aud01      | aud01ass             |
      | 32 Than Two Workflow   | WKF32     | Dos Form  | Third Workflow  | cohort       | aud02      | aud02ass             |
      | 33 Thun Three Workflow | WKF33     | Tres Form | Third Workflow  | cohort       | aud03      | aud03ass             |
      | 41 Quad One Workflow   | WKF41     | Uno Form  | Second Workflow | organisation | org01      | org01ass             |
      | 42 Quar Two Workflow   | WKF42     | Dos Form  | Second Workflow | organisation | org02      | org02ass             |
      | 43 Quid Three Workflow | WKF43     | Tres Form | Second Workflow | organisation | org03      | org03ass             |
    And the following "workflow versions" exist in "mod_approval" plugin:
      | workflow | form_version | status   | memo        |
      | WKF11    | Uno          | draft    | 11 draft    |
      | WKF12    | Dos          | draft    | -           |
      | WKF12    | Dos          | active   | 12 active   |
      | WKF13    | Tres         | draft    | -           |
      | WKF13    | Tres         | active   | -           |
      | WKF13    | Tres         | archived | 13 archived |
      | WKF21    | Uno          | draft    | 21 draft    |
      | WKF22    | Dos          | draft    | -           |
      | WKF22    | Dos          | active   | 22 active   |
      | WKF23    | Tres         | draft    | -           |
      | WKF23    | Tres         | archived | 23 archived |
      | WKF31    | Uno          | draft    | -           |
      | WKF31    | Uno          | active   | -           |
      | WKF31    | Uno          | draft    | 31 draft    |
      | WKF32    | Dos          | archived | -           |
      | WKF32    | Dos          | active   | 32 active   |
      | WKF33    | Tres         | archived | 33 archived |
      | WKF41    | Uno          | draft    | -           |
      | WKF41    | Uno          | active   | -           |
      | WKF41    | Uno          | draft    | 41 draft    |
      | WKF42    | Dos          | archived | -           |
      | WKF42    | Dos          | active   | 42 active   |
      | WKF43    | Tres         | archived | 43 archived |
    And I log in as "admin"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"

  Scenario: mod_approval_811: Filter by Status
    When I set the field "Status" to "Draft"
    Then I should see "Showing 4 of 4 workflows"
    And I should see the tui datatable contains:
      | Workflow name          | Type            | Assignment type | Assigned to     | Status   |
      | 41 Quad One Workflow   | Second Workflow | Organisation    | Cool agency     | Draft    |
      | 31 Thyn One Workflow   | Third Workflow  | Audience        | Fast cohort     | Draft    |
      | 21 Twin One Workflow   | Second Workflow | Position        | High position   | Draft    |
      | 11 Elven Workflow      | First Workflow  | Organisation    | Cool agency     | Draft    |
    When I set the field "Status" to "Active"
    Then I should see "Showing 4 of 4 workflows"
    And I should see the tui datatable contains:
      | Workflow name          | Type            | Assignment type | Assigned to     | Status   |
      | 42 Quar Two Workflow   | Second Workflow | Organisation    | Mild agency     | Active   |
      | 32 Than Two Workflow   | Third Workflow  | Audience        | Normal cohort   | Active   |
      | 22 Twen Two Workflow   | Second Workflow | Position        | Medium position | Active   |
      | 12 Towelve Workflow    | First Workflow  | Organisation    | Mild agency     | Active   |
    When I set the field "Status" to "Archived"
    Then I should see "Showing 4 of 4 workflows"
    And I should see the tui datatable contains:
      | Workflow name          | Type            | Assignment type | Assigned to     | Status   |
      | 43 Quid Three Workflow | Second Workflow | Organisation    | Hot agency      | Archived |
      | 33 Thun Three Workflow | Third Workflow  | Audience        | Slow cohort     | Archived |
      | 23 Twon Three Workflow | Second Workflow | Position        | Low position    | Archived |
      | 13 Thirstyn Workflow   | First Workflow  | Organisation    | Hot agency      | Archived |

  Scenario: mod_approval_812: Filter by Type
    When I set the field "Type" to "First"
    Then I should see "Showing 3 of 3 workflows"
    And I should see the tui datatable contains:
      | Workflow name          | Type            | Assignment type | Assigned to     | Status   |
      | 13 Thirstyn Workflow   | First Workflow  | Organisation    | Hot agency      | Archived |
      | 12 Towelve Workflow    | First Workflow  | Organisation    | Mild agency     | Active   |
      | 11 Elven Workflow      | First Workflow  | Organisation    | Cool agency     | Draft    |
    When I set the field "Type" to "Second"
    Then I should see "Showing 6 of 6 workflows"
    And I should see the tui datatable contains:
      | Workflow name          | Type            | Assignment type | Assigned to     | Status   |
      | 43 Quid Three Workflow | Second Workflow | Organisation    | Hot agency      | Archived |
      | 42 Quar Two Workflow   | Second Workflow | Organisation    | Mild agency     | Active   |
      | 41 Quad One Workflow   | Second Workflow | Organisation    | Cool agency     | Draft    |
      | 23 Twon Three Workflow | Second Workflow | Position        | Low position    | Archived |
      | 22 Twen Two Workflow   | Second Workflow | Position        | Medium position | Active   |
      | 21 Twin One Workflow   | Second Workflow | Position        | High position   | Draft    |
    When I set the field "Type" to "Third"
    Then I should see "Showing 3 of 3 workflows"
    And I should see the tui datatable contains:
      | Workflow name          | Type            | Assignment type | Assigned to     | Status   |
      | 33 Thun Three Workflow | Third Workflow  | Audience        | Slow cohort     | Archived |
      | 32 Than Two Workflow   | Third Workflow  | Audience        | Normal cohort   | Active   |
      | 31 Thyn One Workflow   | Third Workflow  | Audience        | Fast cohort     | Draft    |

  Scenario: mod_approval_813: Filter by Assignment type
    When I set the field "Assignment type" to "Organisation"
    Then I should see "Showing 6 of 6 workflows"
    And I should see the tui datatable contains:
      | Workflow name          | Type            | Assignment type | Assigned to     | Status   |
      | 43 Quid Three Workflow | Second Workflow | Organisation    | Hot agency      | Archived |
      | 42 Quar Two Workflow   | Second Workflow | Organisation    | Mild agency     | Active   |
      | 41 Quad One Workflow   | Second Workflow | Organisation    | Cool agency     | Draft    |
      | 13 Thirstyn Workflow   | First Workflow  | Organisation    | Hot agency      | Archived |
      | 12 Towelve Workflow    | First Workflow  | Organisation    | Mild agency     | Active   |
      | 11 Elven Workflow      | First Workflow  | Organisation    | Cool agency     | Draft    |
    When I set the field "Assignment type" to "Position"
    Then I should see "Showing 3 of 3 workflows"
    And I should see the tui datatable contains:
      | Workflow name          | Type            | Assignment type | Assigned to     | Status   |
      | 23 Twon Three Workflow | Second Workflow | Position        | Low position    | Archived |
      | 22 Twen Two Workflow   | Second Workflow | Position        | Medium position | Active   |
      | 21 Twin One Workflow   | Second Workflow | Position        | High position   | Draft    |
    When I set the field "Assignment type" to "Audience"
    Then I should see "Showing 3 of 3 workflows"
    And I should see the tui datatable contains:
      | Workflow name          | Type            | Assignment type | Assigned to     | Status   |
      | 33 Thun Three Workflow | Third Workflow  | Audience        | Slow cohort     | Archived |
      | 32 Than Two Workflow   | Third Workflow  | Audience        | Normal cohort   | Active   |
      | 31 Thyn One Workflow   | Third Workflow  | Audience        | Fast cohort     | Draft    |

  Scenario: mod_approval_814: Filter resets pagination
    When I set the field "Items per page" to "10"
    Then I should not see "11 Elven Workflow"
    And I should see "13 Thirstyn Workflow"
    When I click on "Next page" "button"
    Then I should see "11 Elven Workflow"
    And I should not see "13 Thirstyn Workflow"
    When I set the field "Type" to "Second"
    Then I should see "Showing 6 of 6 workflows"
    And I should see the tui datatable contains:
      | Workflow name          | Type            | Assignment type | Assigned to     | Status   |
      | 43 Quid Three Workflow | Second Workflow | Organisation    | Hot agency      | Archived |
      | 42 Quar Two Workflow   | Second Workflow | Organisation    | Mild agency     | Active   |
      | 41 Quad One Workflow   | Second Workflow | Organisation    | Cool agency     | Draft    |
      | 23 Twon Three Workflow | Second Workflow | Position        | Low position    | Archived |
      | 22 Twen Two Workflow   | Second Workflow | Position        | Medium position | Active   |
      | 21 Twin One Workflow   | Second Workflow | Position        | High position   | Draft    |
