@totara @mod_approval @javascript @vuejs
Feature: Cloning a workflow from the dashboard
  As an admin
  I would like to create a new workflow that is similar to another workflow
  So that I save time

  Background:
    Given I am on a totara site
    And I disable the "engage_resources" advanced feature
    And I disable the "container_workspace" advanced feature
    ## Used for manual testing with from_behat
    Given the following "cohorts" exist:
      | name  | idnumber |
      | aud1  | AUD001   |
      | aud1  | AUD002   |
      | aud2  | AUD003   |
      | aud3  | AUD004   |
      | aud5  | AUD005   |
      | aud6  | AUD006   |
      | aud7  | AUD007   |
      | aud8  | AUD008   |
      | aud9  | AUD009   |
      | aud10 | AUD010   |
      | aud11 | AUD011   |
      | aud12 | AUD012   |
      | aud13 | AUD013   |
      | aud14 | AUD014   |
      | aud15 | AUD015   |
      | aud16 | AUD016   |
      | aud17 | AUD017   |
      | aud18 | AUD018   |
      | aud19 | AUD019   |
      | aud20 | AUD020   |
      | aud21 | AUD021   |
      | aud22 | AUD022   |
      | aud23 | AUD023   |
      | aud24 | AUD024   |
      | aud25 | AUD025   |
      | aud26 | AUD026   |
    ## Used for manual testing with from_behat
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname          | idnumber |
      | Default Framework | ODF      |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname               | shortname    | idnumber     | org_parent |
      | ODF           | Agency                 | org          | org          |            |
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
      | name          | description               | id_number | form      | workflow_type     | type         | identifier |
      | Cool workflow | Mild workflow description | WKF001    | Test form | Hot workflow type | organisation | org        |
    And I publish the "WKF001" workflow

  Scenario: mod_approval_802: Clone a workflow from dashboard
    When I log in as "admin"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    When I open the dropdown menu in the tui datatable row with "Cool workflow" "Workflow name"
    And I click on "Clone" option in the dropdown menu
    When I set the following fields to these values:
      | name | Cool workflow clone |
    And I click on "Next" "button_exact"
    Then I should see "Select assignment" in the ".tui-modalContent__header-title" "css_element"
    Then the "Clone" "button" should be disabled
    And I set the field "Assignment type" to "Organisation"
    And I click on the "Agency" tui radio
    And I click on "Clone" "button_exact"
    Then I should see "Workflow cloned successfully" in the tui success notification toast
    And I should see "Draft" in the ".tui-mod_approval-workflowHeader__status" "css_element"

