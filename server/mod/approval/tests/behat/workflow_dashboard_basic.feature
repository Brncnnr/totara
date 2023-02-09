@totara @mod_approval @javascript @vuejs
Feature: Simple test of approval workflow dashboard
  Background:
    Given I am on a totara site

  Scenario: mod_approval_800: Simple test of workflow dashboard when no workflows
    When I log in as "admin"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    Then I should see "No workflows match your search"

  Scenario: mod_approval_801: Simple test of workflow dashboard when some workflows
    Given the following "cohorts" exist:
      | name | idnumber |
      | aud1 | AUD001   |
    And the following "workflow types" exist in "mod_approval" plugin:
      | name              |
      | Hot workflow type |
    And the following "forms" exist in "mod_approval" plugin:
      | title     |
      | Test form |
    And the following "form versions" exist in "mod_approval" plugin:
      | form      | version | json_schema |
      | Test form | 1       | test1       |
    And the following "workflows" exist in "mod_approval" plugin:
      | name          | description               | id_number | form      | workflow_type     | type   | identifier |
      | Cool workflow | Mild workflow description | WKF001    | Test form | Hot workflow type | cohort | AUD001     |
    And the following "workflow versions" exist in "mod_approval" plugin:
      | workflow | form_version | status |
      | WKF001   | 1            | draft  |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name | type            |
      | WKF001   | Eins | FORM_SUBMISSION |
      | WKF001   | Zwei | APPROVALS       |
      | WKF001   | Drei | FINISHED        |
      | WKF001   | Vier | FINISHED        |
    And I publish the "WKF001" workflow
    When I log in as "admin"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    Then I should see the tui datatable contains:
      | Workflow name | Type              | Assignment type | Assigned to | Status |
      | Cool workflow | Hot workflow type | Audience        | aud1        | Active |
    When I follow "Cool workflow"
    Then I should see "Cool workflow"
    And I should see "Mild workflow description"
    And I should see "Hot workflow type"
