@totara @mod_approval @javascript @vuejs
Feature: Edit workflow details
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname | lastname | email               |
      | manager    | Mana      | Geer     | manager@example.com |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | AUD001   |
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
      | name            | description | id_number | form      | workflow_type      | type   | identifier | assignment_id_number |
      | Draft workflow  |             | WKF001    | Test form | Test workflow type | cohort | AUD001     | ASS001               |
      | Active workflow |             | WKF002    | Test form | Test workflow type | cohort | AUD001     | ASS002               |
    And the following "workflow versions" exist in "mod_approval" plugin:
      | workflow | form_version | status |
      | WKF001   | 1            | draft  |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name | type            |
      | WKF001   | Eins | FORM_SUBMISSION |
      | WKF001   | Zwei | APPROVALS       |
      | WKF001   | Drei | FINISHED        |
      | WKF001   | Vier | FINISHED        |
    And I publish the "WKF002" workflow
    And I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I follow "Draft workflow"

  Scenario: mod_approval_831: Check modal validation
    When I click on "More actions" "button"
    And I click on "Edit workflow details" option in the dropdown menu
    Then the "Update" "button_exact" should be disabled
    When I set the field "Workflow name" to ""
    Then the "Update" "button_exact" should be disabled
    And I should see "Required"
    When I set the field "Workflow name" to "Still Draft Workflow"
    And I set the field "Workflow ID" to ""
    Then the "Update" "button_exact" should be disabled
    And I should see "Required"
    When I set the field "Workflow ID" to "WKF002"
    Then the "Update" "button_exact" should be disabled
    And I should see "You should enter a unique ID"
    When I type "A" in the text field "Workflow ID"
    Then the "Update" "button_exact" should be enabled
    And I should not see "You should enter a unique ID"
    And I set the field "Description" to "Lorem Ipsum Is Back"
    And I press "Update"
    Then I should see "Workflow updated successfully" in the tui success notification toast
    And I should see "Still Draft Workflow"
    And I should see "Lorem Ipsum Is Back"
    And I should see "WKF002A"
