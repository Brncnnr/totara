@totara @mod_approval @javascript @vuejs
Feature: Edit approval workflow stages
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | manager  | Mana      | Geer     | manager@example.com |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname          | idnumber |
      | Default Framework | ODF      |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname | shortname | idnumber | org_parent |
      | ODF           | Agency   | org       | org      |            |
    And the following "workflow types" exist in "mod_approval" plugin:
      | name              |
      | Workflow type one |
    And the following "forms" exist in "mod_approval" plugin:
      | title         |
      | Test form uno |
    And the following "form versions" exist in "mod_approval" plugin:
      | form          | version | json_schema |
      | Test form uno | 1       | test1       |
    And the following "workflows" exist in "mod_approval" plugin:
      | name           | description       | id_number | form          | workflow_type     | type         | identifier |
      | Draft workflow | draft description | WKF001    | Test form uno | Workflow type one | organisation | org        |
    And the following "workflow versions" exist in "mod_approval" plugin:
      | workflow | form_version | status |
      | WKF001   | 1            | draft  |

  Scenario: Delete workflow stage
    Given the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name | type            |
      | WKF001   | Eins | FORM_SUBMISSION |
      | WKF001   | Zwei | APPROVALS       |
      | WKF001   | Drei | FINISHED        |
      | WKF001   | Vier | FINISHED        |
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Draft workflow" "link"

    # First stage (always a form submission) is not deletable
    When I click on "More actions for stage 'Eins'" "button"
    Then I should see "Delete" option disabled in the dropdown menu
    And I close any visible tui dropdowns

    # Delete one of the end stages
    When I click on "More actions for stage 'Drei'" "button"
    Then I should see "Delete" option in the dropdown menu
    When I click on "Delete" option in the dropdown menu
    And I click on "Delete" "button"
    Then I should not see "Drei" in the ".tui-mod_approval-workflowEdit__workflowStages" "css_element"

    # Delete current stage
    And I follow "Zwei"
    When I click on "More actions for stage 'Zwei'" "button"
    Then I should see "Delete" option in the dropdown menu
    When I click on "Delete" option in the dropdown menu
    And I click on "Delete" "button"
    Then I should not see "Zwei" in the ".tui-mod_approval-workflowEdit__workflowStages" "css_element"

    # Single end stage is not deletable
    When I click on "More actions for stage 'Vier'" "button"
    Then I should see "Delete" option disabled in the dropdown menu
    And I close any visible tui dropdowns

