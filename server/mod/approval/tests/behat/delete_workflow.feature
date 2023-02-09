@totara @mod_approval @javascript @vuejs
Feature: Delete an approval workflow
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | approver | Approux   | Vea      | approver@example.com |
      | manager  | Mana      | Gear     | manager@example.com  |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname | idnumber |
      | orgfw    | orgfw    |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | idnumber | fullname | shortname | org_parent |
      | orgfw         | ORG001   | Test org | org1      |            |
      | orgfw         | ORG002   | Sub org  | org2      | ORG001     |
    And the following "cohorts" exist:
      | name          | idnumber |
      | Test audience | AUD001   |
    And the following "workflow types" exist in "mod_approval" plugin:
      | name          |
      | Workflow type |
    And the following "forms" exist in "mod_approval" plugin:
      | title     |
      | Test form |
    And the following "form versions" exist in "mod_approval" plugin:
      | form      | version | json_schema |
      | Test form | 1       | test1       |
    And the following "workflows" exist in "mod_approval" plugin:
      | name           | id_number | form      | workflow_type | type         | identifier |
      | Here comes the | WKF001    | Test form | Workflow type | organisation | ORG001     |
    And the following "workflow versions" exist in "mod_approval" plugin:
      | workflow | form_version | status |
      | WKF001   | 1            | draft  |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name       | type            |
      | WKF001   | Only stage | FORM_SUBMISSION |
      | WKF001   | Stage 2    | APPROVALS       |
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage | name       |
      | Stage 2        | Only level |
    And the following "assignments" exist in "mod_approval" plugin:
      | name           | id_number | workflow | type         | identifier | default |
      | Two assignment | ASS002    | WKF001   | organisation | ORG002     | false   |
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I open the dropdown menu in the tui datatable row with "Here comes the" "Workflow name"
    And I click on "Clone" option in the dropdown menu
    And I set the field "Workflow name" to "I am a brand new"
    And I press "Next"
    # FIXME: why behat can't change the assignment type select menu???
    # And I set the field "Assignment type" to "Audience"
    # And I click on the "Test audience" tui radio
    And I click on the "Test org" tui radio
    And I press "Clone"
    And I follow "Stage 2"
    And I click on "Approvals" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"
    And I select from the tui taglist in the ".tui-mod_approval-approvalsEdit__approvers" "css_element":
      | Mana Gear |

  Scenario: mod_approval_841: Delete a workflow from the edit page
    # todo: fix in TL-33060
    And I click on "More actions" "button"
    And I click on "Delete" option in the dropdown menu
    And I confirm the tui confirmation modal
    # no notifications???
    # Then I should see "Workflow deleted successfully" in the tui success notification toast
    And I should see "Showing 1 of 1 workflows"
    And I should not see "I am a brand new"

  Scenario: mod_approval_842: Delete a workflow from the dashboard
    # todo: fix in TL-33060
    And I follow "Back to all approval workflows"
    And I open the dropdown menu in the tui datatable row with "I am a brand new" "Workflow name"
    And I click on "Delete" option in the dropdown menu
    And I click on "Delete" "button"
    Then I should see "Workflow deleted successfully" in the tui success notification toast
    # oops, the number of workflows is not updated
    And I should see "Showing 1 of 2 workflows"
    And I should not see "I am a brand new"
