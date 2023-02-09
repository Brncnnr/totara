@totara @mod_approval @javascript @vuejs
Feature: Create an approval workflow
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | manager  | Mana      | Geer     | manager@example.com |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |
    And the following "workflow types" exist in "mod_approval" plugin:
      | name               |
      | Test workflow type |
    And the following "forms" exist in "mod_approval" plugin:
      | title          |
      | Test form uno  |
      | Test form dos  |
      | Test form tres |
      | Test form quat |
      | Test form cinc |
      | Test form seis |
      | Test form siet |
      | Test form ocho |
      | Test form nuev |
      | Test form diez |
      | Test form once |
      | Test form doce |
      | Test form trec |
    And the following "form versions" exist in "mod_approval" plugin:
      | form           | version | json_schema |
      | Test form uno  | 1       | test1       |
      | Test form dos  | 1       | test1       |
      | Test form tres | 1       | test1       |
      | Test form quat | 1       | test1       |
      | Test form cinc | 1       | test1       |
      | Test form seis | 1       | test1       |
      | Test form siet | 1       | test1       |
      | Test form ocho | 1       | test1       |
      | Test form nuev | 1       | test1       |
      | Test form diez | 1       | test1       |
      | Test form once | 1       | test1       |
      | Test form doce | 1       | test1       |
      | Test form trec | 1       | test1       |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname          | idnumber |
      | Default Framework | ODF      |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname               | shortname    | idnumber     | org_parent |
      | ODF           | Agency                 | org          | org          |            |
      | ODF           | Sub-agency A           | org_a        | org_a        | org        |
      | ODF           | Sub-agency A Program A | org_a_prog_a | org_a_prog_a | org_a      |
      | ODF           | Sub-agency A Program B | org_a_prog_b | org_a_prog_b | org_a      |
      | ODF           | Sub-agency B           | org_b        | org_b        | org        |

  Scenario: Create an approval workflow
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    Then I should see "Showing 0 of 0 workflows" in the ".tui-mod_approval-workflowDashboard" "css_element"
    When I click on "New workflow" "button_exact"
    And I set the field "Workflow name" to "My workflow name"
    And I set the field with xpath "//select[@name='workflow_type_id']" to "Test workflow type"
    And I click on "Next" "button_exact"
    Then I should see "Choose a form" in the ".tui-modalContent__header-title" "css_element"
    When I set the field "Search by form name." to "trec"
    Then I should see the tui datatable contains:
      | Form title          |
      | Test form trec      |

    And I click on the "Test form trec" tui radio
    And I click on "Next" "button_exact"
    Then the "Create" "button" should be disabled
    When I click on the "Agency" tui radio
    And I click on "Create" "button_exact"
    Then I should see "Workflow created successfully" in the tui success notification toast
    Then I should see "My workflow name" in the ".tui-mod_approval-workflowHeader" "css_element"
    And I should see "Draft" in the ".tui-mod_approval-workflowHeader__status" "css_element"
