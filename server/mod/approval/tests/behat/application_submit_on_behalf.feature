@totara @mod_approval @javascript @vuejs
Feature: Submit application on behalf
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname | lastname | email                 |
      | applicant  | Applie    | Kaant    | applicant@example.com |
      | manager    | Mana      | Djer     |   manager@example.com |
      | boss       | Bossy     | Boss     |     bboss@example.com |
    And the following job assignments exist:
      | user      | manager    | idnumber  | managerjaidnumber |
      | manager   | boss       | jajaja1   |                   |
      | applicant | manager    | jajaja2   | jajaja1           |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |
      | boss    | manager | System       |           |
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
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | approval_level | type         | identifier |
      | AUD001     | Level 1   | relationship | manager    |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key | required |
      | Test stage 1   | food      | true     |
      | Test stage 1   | drink     | true     |
    And I publish the "WKF001" workflow

  Scenario: mod_approval_503: Manager submit application on behalf
    And the following "applications" exist in "mod_approval" plugin:
    | title             | user      | workflow | assignment | creator | job_assignment |
    | Oh My Application | applicant | WKF001   | AUD001     | manager | jajaja2        |
    And the following "application submissions" exist in "mod_approval" plugin:
    | application       | user    | form_data |
    | Oh My Application | manager | {"food":"poison","drink":"водка"} |
    And the following "application actions" exist in "mod_approval" plugin:
    | application       | user    | action |
    | Oh My Application | manager | submit |
    When I log in as "manager"
    And I visit the Applications Dashboard
    Then I should see "Applicant: Applie Kaant" in the ".tui-mod_approval-responseCard" "css_element"
    And I should see date "now" formatted "Submitted on %d/%m/%Y" in the ".tui-mod_approval-responseCard" "css_element"
    And "Respond to application Oh My Application for Applie Kaant" "button" should exist in the ".tui-mod_approval-responseCard" "css_element"
    And I should see date "now" formatted "%d/%m/%Y" in the ".tui-dataTable" "css_element"
