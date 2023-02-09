@totara @mod_approval @mod_approval_noengage @javascript @vuejs
Feature: Save application form when in approvals
  Background:
    Given I am on a totara site
    And I disable the "engage_resources" advanced feature
    And I disable the "container_workspace" advanced feature
    And the following "users" exist:
      | username   | firstname | lastname |  email                |
      | applicant  | Applie    | Kaant    | applicant@example.com |
      | approver   | App Rou   | Vré      |  approver@example.com |
      | manager    | Mané      | Jear     |   manager@example.com |
      | peeper     | Pea       | Pawre    |    peeper@example.com |
    And the following job assignments exist:
      | user      | manager    | appraiser | idnumber  |
      | applicant | manager    |           | jaja      |
    And the following "role assigns" exist:
      | user   | role                     | contextlevel | reference |
      | peeper | approvalworkflowapprover | System       |           |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | AUD001   |
    And the following "cohort members" exist:
      | user      | cohort |
      | applicant | AUD001 |
    And the following "workflow types" exist in "mod_approval" plugin:
      | name             |
      | Test application |
    And the following "forms" exist in "mod_approval" plugin:
      | title     |
      | Test form |
    And the following "form versions" exist in "mod_approval" plugin:
      | form      | version | json_schema |
      | Test form | MMXXI   | test1       |
    And the following "workflows" exist in "mod_approval" plugin:
      | name          | id_number | form      | workflow_type    | type   | identifier |
      | Test workflow | WKF001    | Test form | Test application | cohort | AUD001     |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name         | type            |
      | WKF001   | First stage  | FORM_SUBMISSION |
      | WKF001   | Second stage | APPROVALS       |
      | WKF001   | Final stage  | FINISHED        |
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage | name         |
      | Second stage   | Second level |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key   | required |
      | First stage    | gender      | false    |
      | First stage    | food        | true     |
      | First stage    | shirt       | true     |
      | Second stage   | gender      | false    |
      | Second stage   | food        | true     |
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | approval_level | type         | identifier       |
      | AUD001     | Level 1        | user         | approver         |
      | AUD001     | Second level   | user         | approver         |
    And I publish the "WKF001" workflow

  Scenario: mod_approval_412: Save with and without invalidating approvals
    When I log in as "applicant"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    And I set the following fields to these values:
      | Gender                   | Male    |
      | What food do you want    | Avocado |
      | Which is your shirt size | L       |
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I log out

    Then I log in as "approver"
    And I visit the Applications Dashboard
    And I follow "Test application"
    And I click on "Approve" "button"

    # Resetting Approval
    Given the following "permission overrides" exist:
      | capability                                           | permission | role                     | contextlevel  | reference |
      | mod/approval:edit_without_invalidating_approvals_any | Prevent    | approvalworkflowapprover | System        | approver  |
    And I click on "More actions" "button"
    And I click on "Edit" option in the dropdown menu
    And I set the field "Gender" to "Female"
    And I click on "Save" "button"
    Then "Save the changes without resetting approval" "checkbox" should not exist
    And I confirm the tui confirmation modal
    # Approval level is reset.
    Then I should see "Pending Level 1"

    # Re-approve the application.
    Given I click on "Approve" "button"

    # Without resetting approval
    And the following "permission overrides" exist:
      | capability                                           | permission | role                     | contextlevel  | reference |
      | mod/approval:edit_without_invalidating_approvals_any | Allow      | approvalworkflowapprover | System        | approver  |
    Then I should see "Pending Second level"
    And I click on "More actions" "button"
    And I click on "Edit" option in the dropdown menu
    And I set the field "Gender" to "Male"
    And I click on "Save" "button"
    Then "Save the changes without resetting approval" "checkbox" should exist
    And I click on "Save the changes without resetting approval" tui "checkbox"
    And I confirm the tui confirmation modal

    # Approval level still remains.
    Then I should see "Pending Second level"