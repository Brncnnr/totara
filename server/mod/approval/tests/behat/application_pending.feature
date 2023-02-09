@totara @mod_approval @mod_approval_noengage @javascript @vuejs
Feature: View pending approval applications
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
    And the following "workflow types" exist in "mod_approval" plugin:
      | name                  |
      | A very simple request |
    And the following "forms" exist in "mod_approval" plugin:
      | title     |
      | Test form |
    And the following "form versions" exist in "mod_approval" plugin:
      | form      | version | json_schema |
      | Test form | 1       | test1       |
    And the following "workflows" exist in "mod_approval" plugin:
      | name          | description               | id_number | form      | workflow_type         | type   | identifier |
      | Test workflow | test workflow description | WKF001    | Test form | A very simple request | cohort | AUD001     |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name         | type            |
      | WKF001   | Test stage 1 | FORM_SUBMISSION |
      | WKF001   | Test stage 2 | APPROVALS       |
      | WKF001   | Test stage 3 | FINISHED        |
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage | name         |
      | Test stage 2   | Test level 2 |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key | required |
      | Test stage 1   | food      | true     |
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | approval_level | type         | identifier |
      | AUD001     | Level 1        | relationship | manager    |
      | AUD001     | Test level 2   | relationship | manager    |
    And I publish the "WKF001" workflow
    And the following "applications" exist in "mod_approval" plugin:
      | title                    | user      | workflow | assignment | creator   | job_assignment |
      | Test application first   | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application second  | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application third   | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application fourth  | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application fifth   | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application sixth   | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application seventh | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application eighth  | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application ninth   | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application tenth   | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 11      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 12      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 13      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 14      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 15      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 16      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 17      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 18      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 19      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 20      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 21      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 22      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 23      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 24      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
      | Test application 25      | applicant | WKF001   | AUD001     | applicant | jajaja2        |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application              | user      | form_data         |
      | Test application first   | applicant | {"food":"Apple"}  |
      | Test application second  | applicant | {"food":"Banana"} |
      | Test application third   | applicant | {"food":"Cherry"} |
      | Test application fourth  | applicant | {"food":"Feijoa"} |
      | Test application sixth   | applicant | {"food":"Grape"}  |
      | Test application seventh | applicant | {"food":"Kiwi"}   |
      | Test application fifth   | applicant | {"food":"Lemon"}  |
      | Test application eighth  | applicant | {"food":"Melon"}  |
      | Test application ninth   | applicant | {"food":"Orange"} |
      | Test application tenth   | applicant | {"food":"Orange"} |
      | Test application 11      | applicant | {"food":"Orange"} |
      | Test application 12      | applicant | {"food":"Orange"} |
      | Test application 13      | applicant | {"food":"Orange"} |
      | Test application 14      | applicant | {"food":"Orange"} |
      | Test application 15      | applicant | {"food":"Orange"} |
      | Test application 16      | applicant | {"food":"Orange"} |
      | Test application 17      | applicant | {"food":"Orange"} |
      | Test application 18      | applicant | {"food":"Orange"} |
      | Test application 19      | applicant | {"food":"Orange"} |
      | Test application 20      | applicant | {"food":"Orange"} |
      | Test application 21      | applicant | {"food":"Orange"} |
      | Test application 22      | applicant | {"food":"Orange"} |
      | Test application 23      | applicant | {"food":"Orange"} |
      | Test application 24      | applicant | {"food":"Orange"} |
      | Test application 25      | applicant | {"food":"Orange"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application              | user      | action |
      | Test application first   | applicant | submit |
      | Test application second  | applicant | submit |
      | Test application third   | applicant | submit |
      | Test application fourth  | applicant | submit |
      | Test application fifth   | applicant | submit |
      | Test application sixth   | applicant | submit |
      | Test application seventh | applicant | submit |
      | Test application eighth  | applicant | submit |
      | Test application ninth   | applicant | submit |
      | Test application tenth   | applicant | submit |
      | Test application 11      | applicant | submit |
      | Test application 12      | applicant | submit |
      | Test application 13      | applicant | submit |
      | Test application 14      | applicant | submit |
      | Test application 15      | applicant | submit |
      | Test application 16      | applicant | submit |
      | Test application 17      | applicant | submit |
      | Test application 18      | applicant | submit |
      | Test application 19      | applicant | submit |
      | Test application 20      | applicant | submit |
      | Test application 21      | applicant | submit |
      | Test application 22      | applicant | submit |
      | Test application 23      | applicant | submit |
      | Test application 24      | applicant | submit |
      | Test application 25      | applicant | submit |

  Scenario: mod_approval_531: Manager views all pending approval applications
    When I log in as "manager"
    And I visit the Applications Dashboard
    And I click on "View all" "button"
    Then I should see "25 applications"

    When I click on "Load more" "button_exact"
    Then "Test application 25" "link" should exist
    Then "Test application first" "link" should exist


