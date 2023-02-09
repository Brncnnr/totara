@totara @mod_approval @mod_approval_noengage @javascript @vuejs
Feature: Approval workflow application approvers
  Background:
    Given I am on a totara site
    And I disable the "engage_resources" advanced feature
    And I disable the "container_workspace" advanced feature
    And the following "users" exist:
      | username   | firstname | lastname |    email             |
      | applicant  | App Li    | Kant     |   applicant@example.com |
      | approver   | App Rou   | Vee      | approver@example.com |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | AUD001   |
    And the following "cohort members" exist:
      | user     | cohort |
      | applicant| AUD001 |
      | approver | AUD001 |
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
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key | required |
      | Test stage 1   | food      | true     |
      | Test stage 2   | food      | true     |
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | approval_level | type | identifier |
      | AUD001     | Level 1   | user | approver   |
    And I publish the "WKF001" workflow

  Scenario: mod_approval_521: Approver cannot approve application where they are the applicant when assigned as a trainer
    Given the following "applications" exist in "mod_approval" plugin:
      | title            | user     | workflow | assignment | creator  |
      | Test application | approver | WKF001   | AUD001     | approver |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user     | form_data         |
      | Test application | approver | {"food":"pasta"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user     | action |
      | Test application | approver | submit |
    When I log in as "approver"
    And I visit the Applications Dashboard
    And I switch to "Your applications" tui tab
    And I click on "Test workflow type" "link"
    Then "Approve" "button" should not exist
    Then I should see "There are no Level 1 approvers."
    And "View" "link_or_button" should not exist in the ".tui-mod_approval-sidePanel" "css_element"

  Scenario: Approver can approve an application
    Given the following "applications" exist in "mod_approval" plugin:
      | title            | user      | workflow | assignment | creator  |
      | Test application | applicant | WKF001   | AUD001     | approver |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | form_data         |
      | Test application | applicant | {"food":"pasta"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user      | action |
      | Test application | applicant | submit |
    When I log in as "approver"
    And I visit the Applications Dashboard
    And I click on "Test workflow type" "link"
    Then "Approve" "button" should exist
    And I should not see "Completed" in the ".tui-mod_approval-header__status" "css_element"
    When I click on "Approve" "button"
    Then I should see "Completed" in the ".tui-mod_approval-header__status" "css_element"
    And I should see "This application is completed" in the ".tui-mod_approval-applicationView__actions" "css_element"

  @editor @weka @editor_weka
  Scenario: Approver can reject an application
    Given the following "applications" exist in "mod_approval" plugin:
      | title            | user      | workflow | assignment | creator  |
      | Test application | applicant | WKF001   | AUD001     | approver |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | form_data         |
      | Test application | applicant | {"food":"pasta"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user      | action |
      | Test application | applicant | submit |
    When I log in as "approver"
    And I visit the Applications Dashboard
    And I click on "Test workflow type" "link"
    Then "Reject" "button" should exist
    And I should not see "Rejected" in the ".tui-mod_approval-header__status" "css_element"
    When I click on "Reject" "button"
    And I activate the weka editor with css ".tui-weka"
    And I type "No pasta, sorry" in the weka editor
    And I click on "Confirm" "button"
    Then I should see "Rejected" in the ".tui-mod_approval-header__status" "css_element"
    And I should see "Rejected" in the ".tui-mod_approval-applicationView__actions_last-action" "css_element"
    And I should see "App Rou Vee rejected the application"

  Scenario: Approver can edit an application with capability
    Given the following "applications" exist in "mod_approval" plugin:
      | title            | user      | workflow | assignment | creator  |
      | Test application | applicant | WKF001   | AUD001     | approver |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | form_data         |
      | Test application | applicant | {"food":"pasta"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user      | action |
      | Test application | applicant | submit |
    And the following "permission overrides" exist:
      | capability                                             | permission | role                     | contextlevel  | reference |
      | mod/approval:edit_in_approvals_pending_application_any | Prevent    | approvalworkflowapprover | System        | approver  |
    When I log in as "approver"
    And I visit the Applications Dashboard
    And I click on "Test workflow type" "link"
    And I click on "More actions" "button"
    Then I should not see "Edit"
    And I should see "Submitted by App Li Kant on"
    Given the following "permission overrides" exist:
      | capability                                             | permission | role                     | contextlevel  | reference |
      | mod/approval:edit_in_approvals_pending_application_any | Allow      | approvalworkflowapprover | System        | approver  |
    And I visit the Applications Dashboard
    And I click on "Test workflow type" "link"
    And I click on "More actions" "button"
    And I click on "Edit" option in the dropdown menu
    Then I should see "Form sections" in the ".tui-mod_approval-applicationEdit__sectionNav" "css_element"
    And I should see "Section A - Food & drinks" in the ".tui-mod_approval-applicationEdit__sectionNav" "css_element"
    When I set the following fields to these values:
      | food | Fish |
    And I click on "Save" "button" in the ".tui-mod_approval-header__actions" "css_element"
    And I confirm the tui confirmation modal
    Then I should not see "Could not save the application"
    And the following approval form fields match these values:
      | food | Fish |
    And I should see "Submitted by App Li Kant on"
    And I should see "last updated by App Rou Vee on"
