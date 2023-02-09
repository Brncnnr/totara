@totara @mod_approval @mod_approval_noengage @javascript @vuejs
Feature: Application dashboard config.
  Background:
    Given I am on a totara site
    And I disable the "engage_resources" advanced feature
    And I disable the "container_workspace" advanced feature
    And the following "users" exist:
      | username   | firstname | lastname | email                 |
      | applicant  | Applie    | Kaant    | applicant@example.com |
      | manager    | Mana      | Djer     |   manager@example.com |
    And the following job assignments exist:
      | user      | manager    | idnumber  |
      | applicant | manager    | jajaja2   |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |
    And the following "cohorts" exist:
      | name          | idnumber |
      | Test audience | AUD001   |
    And the following "cohort members" exist:
      | user      | cohort |
      | applicant | AUD001 |
      | manager   | AUD001 |
    And the following "workflow types" exist in "mod_approval" plugin:
      | name                  |
      | simple request |
    And the following "forms" exist in "mod_approval" plugin:
      | title     |
      | Test form |
    And the following "form versions" exist in "mod_approval" plugin:
      | form      | version | json_schema |
      | Test form | 1       | test1       |
    And the following "workflows" exist in "mod_approval" plugin:
      | name          | description               | id_number | form      | workflow_type  | type   | identifier |
      | Test workflow | test workflow description | WKF001    | Test form | simple request | cohort | AUD001     |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name         | type            |
      | WKF001   | Test stage   | FORM_SUBMISSION |
      | WKF001   | Test stage 2 | APPROVALS       |
      | WKF001   | Test stage 3 | FINISHED        |
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | approval_level | type         | identifier |
      | AUD001     | Level 1   | relationship | manager    |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key | required |
      | Test stage     | food      | true     |
      | Test stage     | drink     | true     |
    And I publish the "WKF001" workflow

    # Setup some applications.
    Given the following "applications" exist in "mod_approval" plugin:
      | title               | user      | workflow | assignment | creator   | id_number |
      | Other application 1 | applicant | WKF001   | AUD001     | manager   | 123       |
      | Other application 2 | applicant | WKF001   | AUD001     | manager   | 456       |
      | Other application 3 | applicant | WKF001   | AUD001     | manager   | 789       |
      | Other application 4 | applicant | WKF001   | AUD001     | manager   | 101       |
      | My application 1    | manager   | WKF001   | AUD001     | manager   | 112       |
      | My application 2    | manager   | WKF001   | AUD001     | manager   | 131       |
      | My application 3    | manager   | WKF001   | AUD001     | manager   | 415       |
      | My application 4    | manager   | WKF001   | AUD001     | manager   | 161       |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application         | user    | form_data |
      | Other application 1 | manager | {"food":"apple", "drink": "juice"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application         | user    | action |
      | Other application 1 | manager | submit |

  Scenario: Filter the table and navigate away and back
    When I log in as "manager"
    And I visit the Applications Dashboard
    When I follow "Applications from others"
    When I set the field "Overall progress" to "In progress"
    Then I should see "Showing 1 of 1 applications"
    Then I should see the tui datatable contains:
      | Application title           |
      | Other application 1         |

    When I follow "123"
    Then I should see "In progress" in the ".tui-mod_approval-header" "css_element"

    When I follow "Back to applications"
    Then I should see "Showing 1 of 1 applications"
    Then I should see the tui datatable contains:
      | Application title           |
      | Other application 1         |
    Then the field "Overall progress" matches value "In progress"











