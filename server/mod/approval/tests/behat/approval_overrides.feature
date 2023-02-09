@totara @mod_approval @mod_approval_noengage @javascript @vuejs
Feature: Add and edit approval overrides
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | approver | Appro     | Ver      | user4@example.com   |
      | manager  | Mana      | Geer     | manager@example.com |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |
    And the following "cohorts" exist:
      | name             | idnumber |
      | Test audience 01 | AUD011   |
      | Test audience 02 | AUD002   |
      | Test audience 03 | AUD003   |
      | Test audience 04 | AUD004   |
      | Test audience 05 | AUD005   |
      | Test audience 06 | AUD006   |
      | Test audience 07 | AUD007   |
      | Test audience 08 | AUD111   |
      | Test audience 09 | AUD012   |
      | Test audience 10 | AUD013   |
      | Test audience 11 | AUD014   |
      | Test audience 12 | AUD015   |
      | Test audience 13 | AUD016   |
      | Test audience 14 | AUD017   |
      | Test audience 15 | AUD115   |
      | Test audience 16 | AUD112   |
      | Test audience 17 | AUD113   |
      | Test audience 18 | AUD018   |
      | Test audience 19 | AUD019   |
      | Test audience 20 | AUD020   |
      | Test audience 21 | AUD021   |
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
      | Test workflow | test workflow description | WKF001    | Test form | Test workflow type | cohort | AUD011     |
    And the following "workflow versions" exist in "mod_approval" plugin:
      | workflow | form_version | status |
      | WKF001   | 1            | draft  |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name        | type             |
      | WKF001   | First stage | FORM_SUBMISSION  |
      | WKF001   | Test stage  | APPROVALS        |
      | WKF001   | Final stage | FINISHED         |
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage | name         |
      | Test stage     | Second level |
      | Test stage     | Third level  |

  Scenario: mod_approval_591: Add and edit approval overrides
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Test workflow" "link"
    And I follow "Test stage"
    And I click on "Approvals" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"
    And I click on "Configure approval overrides" "button"
    Then I should see "There are no approver levels and approvers in this stage."

    # Add approval override
    When I click on "Add override" "button_exact"
    And I set the field "Assignment type" to "Audience"
    Then the "Test audience 01" "radio" should be disabled
    And I should not see "Test audience 21"
    And the "Add" "button_exact" should be disabled
    When I click on "Load more" "button_exact"
    And I click on the "Test audience 21" tui radio
    And I click on "Add" "button_exact"
    Then I should see "Override created successfully. You may edit the approvers." in the tui success notification toast

    # Edit approval override from add override flow
    And the field "Level 1 approver type" matches value "Individual"
    And the field "Filter Individuals for Level 1" matches value ""
    When I click on "Override Level 1" tui "checkbox"
    Then "Level 1 approver type" "field" should not be visible
    And "Filter Individuals for Level 1" "field" should not be visible
    When I click on "Tag list Individuals for Second level" "button"
    And I click on "Appro Ver" option in the dropdown menu
    And I set the field "Third level approver type" to "Relationship"
    Then I should see "Manager" in the "[aria-label='Selected Relationships for Third level']" "css_element"
    When I click on "Save" "button_exact"
    Then I should see "Overrides saved successfully" in the tui success notification toast
    And I should see the tui datatable contains:
      | Organisation      | Level 1     | Second level | Third level |
      | Test audience 21  | Inherited   | 1 User       | 1 User      |
    And I should not see "There are no approver levels and approvers in this stage."

    # Edit approval override from edit button
    When I open the dropdown menu in the tui datatable row with "Test audience 21" "Organisation"
    When I click on "Edit" option in the dropdown menu
    And I click on "Tag list Individuals for Second level" "button"
    And I click on "Mana Geer" option in the dropdown menu
    And I click on "Save" "button_exact"
    Then I should see "Overrides saved successfully" in the tui success notification toast
    And I should see the tui datatable contains:
      | Organisation      | Level 1     | Second level | Third level |
      | Test audience 21  | Inherited   | 2 Users      | 1 User      |
