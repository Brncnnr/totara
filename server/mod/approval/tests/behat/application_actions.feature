@totara @mod_approval @javascript @vuejs
Feature: Approval workflow application actions
  As an applicant I can save as draft, preview, clone, delete and submit an application.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | applicant | Applie    | Kaant    | applicant@example.com |
      | approver  | App Rou   | Var      | approver@example.com  |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | AUD001   |
    And the following "cohort members" exist:
      | user      | cohort |
      | applicant | AUD001 |
    And the following "workflow types" exist in "mod_approval" plugin:
      | name               |
      | Test workflow type |
    And the following "forms" exist in "mod_approval" plugin:
      | title     |
      | Test form |
    And the following "form versions" exist in "mod_approval" plugin:
      | form      | version | json_schema |
      | Test form | 1       | test1       |
    And the following "workflows" exist in "mod_approval" plugin:
      | name          | description               | id_number | form      | workflow_type      | type   | identifier |
      | Test workflow | test workflow description | WKF001    | Test form | Test workflow type | cohort | AUD001     |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name         | type            |
      | WKF001   | Test stage 1 | FORM_SUBMISSION |
      | WKF001   | Test stage 2 | APPROVALS       |
      | WKF001   | Test stage 3 | FINISHED        |
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage | name         |
      | Test stage 2   | Test level 2 |
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | approval_level | type | identifier |
      | AUD001     | Level 1   | user | approver   |
      | AUD001     | Test level 2   | user | approver   |
    And I publish the "WKF001" workflow

  Scenario: Preview an application
    When I log in as "applicant"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    And I click on "More actions" "button"
    And I click on "Print preview" option in the dropdown menu
    When I switch to "totara_approval_workflow_application_preview" window
    Then I should see "Test Form" in the ".tui-mod_approval-printView__document" "css_element"
    And "Print" "button_exact" should exist

  Scenario: Withdraw an application
    Given the following "applications" exist in "mod_approval" plugin:
      | title            | user      | workflow | assignment | creator   |
      | Test application | applicant | WKF001   | aud1       | applicant |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | form_data                     |
      | Test application | applicant | {"food":"What a great meal!"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user      | action |
      | Test application | applicant | submit |
    When I log in as "applicant"
    And I visit the Applications Dashboard
    And I follow "Test workflow type"
    And I click on "More actions" "button"
    And I click on "Withdraw" option in the dropdown menu
    And I confirm the tui confirmation modal

    Then I should see "Withdrawn" in the ".tui-mod_approval-header__status" "css_element"
    And I should see "Application withdrawn successfully" in the tui success notification toast

  Scenario: Delete an application
    When I log in as "applicant"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    And I click on "More actions" "button"
    And I click on "Delete" option in the dropdown menu

    Then I should see "Delete application"
    And I should see "Are you sure you want to delete the application 'Test workflow type'?"
    And I click on "Cancel" "button"

    Then "Submit" "button" should exist in the ".tui-mod_approval-header__actions" "css_element"
    And "Save as draft" "button" should exist in the ".tui-mod_approval-header__actions" "css_element"
    And "Submit" "button_exact" should exist in the ".tui-mod_approval-applicationEdit__schemaForm" "css_element"
    And "Save as draft" "button_exact" should exist in the ".tui-mod_approval-applicationEdit__schemaForm" "css_element"

    Then I click on "More actions" "button"
    And I click on "Delete" option in the dropdown menu
    And I confirm the tui confirmation modal
    And I should see "Application deleted successfully" in the tui success notification toast
    And I should see "You don't currently have any applications"
    Then I should see "New application"
