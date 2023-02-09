@totara @mod_approval @javascript @vuejs
Feature: Edit an approval workflow
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname  | email                 |
      | salesengr | Engr      | Sales     | salesengr@example.com |
      | salesmgr  | Manager   | Tech      | techmgr@example.com   |
      | vp        | Vice      | President | vp@example.com        |
      | manager   | Mana      | Geer      | manager@example.com   |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |
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
      | name              | description          | id_number | form          | workflow_type     | type         | identifier  | assignment_id_number |
      | Draft workflow    | draft description    | WKF001    | Test form uno | Workflow type one | organisation | org         | org1                 |
      | Active workflow   | active description   | WKF002    | Test form uno | Workflow type one | organisation | org         | org2                 |
      | Archived workflow | archived description | WKF003    | Test form uno | Workflow type one | organisation | org         | org3                 |
    And the following "workflow versions" exist in "mod_approval" plugin:
      | workflow | form_version | status |
      | WKF001   | 1            | draft  |
      | WKF002   | 1            | draft  |
      | WKF003   | 1            | draft  |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name     | type            |
      | WKF001   | Stage 1  | FORM_SUBMISSION |
      | WKF001   | Stage 2  | APPROVALS       |
      | WKF001   | Stage 3  | FINISHED        |
      | WKF002   | Stage 11 | FORM_SUBMISSION |
      | WKF002   | Stage 12 | APPROVALS       |
      | WKF002   | Stage 13 | APPROVALS       |
      | WKF002   | Stage 14 | FINISHED        |
      | WKF003   | Stage 31 | FORM_SUBMISSION |
      | WKF003   | Stage 32 | APPROVALS       |
      | WKF003   | Stage 33 | FINISHED        |

    And I delete default approval level for stage "Stage 2"
    And I delete default approval level for stage "Stage 12"
    And I delete default approval level for stage "Stage 13"
    And I delete default approval level for stage "Stage 32"

    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key | required |
      | Stage 1        | food      | true     |
      | Stage 2        | drink     | true     |
    And the following "assignments" exist in "mod_approval" plugin:
      | name                   | id_number    | workflow | type         | identifier   | default |
      | Sub-agency A           | org_a        | WKF002   | organisation | org_a        | false   |
      | Sub-agency A Program A | org_a_prog_a | WKF002   | organisation | org_a_prog_a | false   |
      | Sub-agency A Program B | org_a_prog_b | WKF002   | organisation | org_a_prog_b | false   |
      | Sub-agency B           | org_b        | WKF002   | organisation | org_b        | false   |
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage | name    |
      | Stage 2        | Level 1 |
      | Stage 2        | Level 2 |
      | Stage 2        | Level 3 |
      | Stage 2        | Level 4 |
      | Stage 12       | Level 1 |
      | Stage 12       | Level 2 |
      | Stage 12       | Level 3 |
      | Stage 13       | Level 1 |
      | Stage 13       | Level 2 |
      | Stage 13       | Level 3 |
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | workflow_stage | approval_level | type         | identifier |
      | org1       | Stage 2        | Level 1        | relationship | manager    |
      | org1       | Stage 2        | Level 2        | relationship | manager    |
      | org1       | Stage 2        | Level 3        | relationship | manager    |
      | org2       | Stage 12       | Level 1        | relationship | manager    |
      | org2       | Stage 12       | Level 2        | relationship | manager    |
      | org2       | Stage 12       | Level 3        | relationship | manager    |
    And I publish the "WKF002" workflow
    And I publish the "WKF003" workflow
    And I archive the "WKF003" workflow

  Scenario: Test breadcrumbs are hidden on workflow edit page
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Draft workflow" "link"
    Then ".breadcrumb" "css_element" should not exist

  Scenario: View approval overrides
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Active workflow" "link"
    When I click on "Stage 11" "link"
    And "Configure approval overrides" "button" should not exist
    And "Add approval level" "button" should not exist
    And I click on "Stage 12" "link"
    When I click on "Approvals" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"
    Then I should see "Manager" exactly "3" times
    When I click on "Configure approval overrides" "button"
    Then I should see the tui datatable contains:
      | Organisation           | Level 1   | Level 2   | Level 3   |
      | Sub-agency A           | Inherited | Inherited | Inherited |
      | Sub-agency A Program A | Inherited | Inherited | Inherited |
      | Sub-agency A Program B | Inherited | Inherited | Inherited |
      | Sub-agency B           | Inherited | Inherited | Inherited |
    When I set the field "Search by organisation" to "Sub-agency A Program"
    Then I should see the tui datatable contains:
      | Organisation           | Level 1   | Level 2   | Level 3   |
      | Sub-agency A Program A | Inherited | Inherited | Inherited |
      | Sub-agency A Program B | Inherited | Inherited | Inherited |
    When I set the field "Search by organisation" to ""
    And I set the field "Sort by" to "Organisation (Z-A)"
    Then I should see the tui datatable contains:
      | Organisation           | Level 1   | Level 2   | Level 3   |
      | Sub-agency B           | Inherited | Inherited | Inherited |
      | Sub-agency A Program B | Inherited | Inherited | Inherited |
      | Sub-agency A Program A | Inherited | Inherited | Inherited |
      | Sub-agency A           | Inherited | Inherited | Inherited |
    When I click on "Stage 13" "link"
    And I click on "Approvals" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"
    And I click on "Configure approval overrides" "button"
    Then I should see the tui datatable contains:
      | Organisation           | Level 1   | Level 2   | Level 3   |
      | Sub-agency A           | Inherited | Inherited | Inherited |
      | Sub-agency A Program A | Inherited | Inherited | Inherited |
      | Sub-agency A Program B | Inherited | Inherited | Inherited |
      | Sub-agency B           | Inherited | Inherited | Inherited |

  Scenario: Add and remove approvers on approver level
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Draft workflow" "link"
    And I click on "Stage 2" "link"
    And I click on "Approvals" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"

    Then the field "Level 3 approver type" matches value "Relationship"
    And the field "Level 4 approver type" matches value "Individual"
    # Testing approval level 4

    # Interacting with the individual TagList
    # Open TagList
    When I click on "Tag list Individuals for Level 4" "button"
    # Add individual approvers to TagList
    And I click on "Admin User" option in the dropdown menu
    And I click on "Engr Sales" option in the dropdown menu
    And I click on "Manager Tech" option in the dropdown menu
    And I click on "Vice President" option in the dropdown menu
    Then I should see "Successfully saved" in the tui success notification toast
    # Selected options not shown in dropdown menu.
    And I should not see "Admin User" option in the dropdown menu
    And I should not see "Engr Sales" option in the dropdown menu
    And I should not see "Manager Tech" option in the dropdown menu
    And I should not see "Vice President" option in the dropdown menu

    # Remove individual approvers to TagList
    When I click on "Remove Admin User from Individuals for Level 4" "button"
    When I click on "Remove Vice President from Individuals for Level 4" "button"
    Then I should see "Successfully saved" in the tui success notification toast
    And I should see "Admin User" option in the dropdown menu
    And I should see "Vice President" option in the dropdown menu

    # Switch to Relationship TagList
    When I set the field "Level 4 approver type" to "Relationship"
    Then I should see "Successfully saved" in the tui success notification toast
    # Open TagList
    When I click on "Tag list Relationships for Level 4" "button"
    Then I should not see "Manager" option in the dropdown menu

    # Switching to Individual
    When I set the field "Level 4 approver type" to "Individual"
    Then I should see "Successfully saved" in the tui success notification toast

  Scenario: Expand and collapse notifications
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Draft workflow" "link"
    Then I should not see " - Notifications"
    When I click on "Notifications" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"
    Then I should see "Stage 1 - Notifications"
    And I should not see "Stage fully approved"
    And I should not see "Course set completed"

    When I click on "Expand all" "button_exact"
    Then I should see "Stage fully approved"
    And I should see "Course set completed"

    When I click on "Collapse all" "button_exact"
    Then I should not see "Stage fully approved"
    And I should not see "Course set completed"

    When I toggle the "Approval workflow" tui collapsible
    Then I should see "Stage fully approved"
    But I should not see "Course set completed"

    When I toggle the "Approval workflow" tui collapsible
    And I toggle the "Certification" tui collapsible
    Then I should see "Course set completed"
    But I should not see "Stage fully approved"

    # When I click on "Approvals" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"
    # Then I should not see " - Notifications"
    # When I click on "Notifications" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"
    And I click on "Stage 2" "link"
    Then I should not see " - Notifications"

  Scenario: Edit notification
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Draft workflow" "link"
    And I click on "Notifications" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"
    And I toggle the "Approval workflow" tui collapsible
    And I click on "Stage fully approved details" "button"
    And I click on "Actions for Application has been fully approved" "button"
    Then I should not see "Delete" option in the dropdown menu
    When I click on "Edit" option in the dropdown menu
    Then the "Notification status" "checkbox" should be disabled
    When I click on "Enable customising notification status" tui "toggle_switch"
    And I click on "Notification status" tui "checkbox"
    When I click on "Save" "button_exact"
    Then I should see "Notification updated" in the tui success notification toast
    And I should see "Application has been fully approved"
    And I should see "Enabled"

  Scenario: Add and delete notifications
    # Add notification from button
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Draft workflow" "link"
    And I click on "Notifications" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"
    And I toggle the "Approval workflow" tui collapsible
    And I click on "Comment added details" "button"
    Then I should see "No notifications found."
    When I click on "Create notification" "button"
    And I set the field "Name" to "Hello Hello Totara"
    And I click on the "mod_approval\totara_notification\recipient\applicant" tui checkbox in the "recipients" tui checkbox group
    And I set the weka editor with css ".tui-notificationPreferenceForm__subjectEditor" to "Hello Subject"
    And I set the weka editor with css ".tui-notificationPreferenceForm__bodyEditor" to "Hello Body"
    And I click on "Save" "button_exact"
    Then I should see "Notification saved" in the tui success notification toast
    And I should see "Hello Hello Totara"
    And I should see "Applicant"
    And I should not see "Create notification"

    # Add notification from dropdown
    When I click on "Actions for Comment added event" "button"
    And I click on "Create notification" option in the dropdown menu
    And I set the field "Name" to "Goodbye Goodbye Totara"
    And I click on the "mod_approval\totara_notification\recipient\applicant_manager" tui checkbox in the "recipients" tui checkbox group
    And I set the weka editor with css ".tui-notificationPreferenceForm__subjectEditor" to "Goodbye Subject"
    And I set the weka editor with css ".tui-notificationPreferenceForm__bodyEditor" to "Goodbye Body"
    And I click on "Save" "button_exact"
    Then I should see "Notification saved" in the tui success notification toast
    And I should see "Goodbye Goodbye Totara"
    And I should see "Manager"

    # Delete notification
    When I click on "Actions for Goodbye Goodbye Totara" "button"
    And I click on "Delete" option in the dropdown menu
    And I confirm the tui confirmation modal
    Then I should see "Successfully deleted notification" in the tui success notification toast
    And I should not see "Goodbye Goodbye Totara"

  Scenario: Delete workflow
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Draft workflow" "link"
    And I click on "More actions" "button"
    And I click on "Delete" option in the dropdown menu
    And I confirm the tui confirmation modal
    Then I should not see "Draft workflow" in the ".tui-mod_approval-workflowDashboard" "css_element"

  Scenario: Clone workflow
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Draft workflow" "link"
    And I click on "More actions" "button"
    And I click on "Clone" option in the dropdown menu
    Then the field "Workflow name" matches value "Draft workflow"
    When I set the field "Workflow name" to "Cloned workflow"
    And I click on "Next" "button_exact"
    Then the "Clone" "button" should be disabled
    And the field "Assignment type" matches value "Organisation"
    When I click on the "Agency" tui radio
    And I click on "Clone" "button_exact"
    Then I should see "Workflow cloned successfully" in the tui success notification toast
    And I should see "Cloned workflow" in the ".tui-mod_approval-workflowHeader" "css_element"
    And I should see "Draft" in the ".tui-mod_approval-workflowHeader__status" "css_element"

  Scenario: Publish workflow
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Draft workflow" "link"
    And I press "Publish"
    And I confirm the tui confirmation modal
    Then I should see "Workflow published successfully" in the tui success notification toast
    And I should see "Active" in the ".tui-mod_approval-workflowHeader__status" "css_element"

  Scenario: Archive workflow
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Active workflow" "link"
    And I click on "More actions" "button"
    And I click on "Archive" option in the dropdown menu
    And I confirm the tui confirmation modal
    Then I should see "Workflow archived successfully" in the tui success notification toast
    And I should see "Archived" in the ".tui-mod_approval-workflowHeader__status" "css_element"

  Scenario: Unarchive workflow
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Archived workflow" "link"
    And I click on "More actions" "button"
    And I click on "Unarchive" option in the dropdown menu
    And I confirm the tui confirmation modal
    Then I should see "Workflow unarchived successfully" in the tui success notification toast
    And I should see "Active" in the ".tui-mod_approval-workflowHeader__status" "css_element"

  Scenario: Add approval level to workflow
    When I log in as "manager"

    # Can not add to active workflow
    When I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Active workflow" "link"
    And I follow "Stage 2"
    Then "Add approval level" "button" should not exist

    # Can not add to archived workflow
    And I am on homepage
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Active workflow" "link"
    And I follow "Stage 2"
    Then "Add approval level" "button" should not exist

    And I am on homepage
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Draft workflow" "link"
    And I follow "Stage 2"
    And I click on "Approvals" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"
    And I press "Add approval level"
    And I set the field "Level name" to "Hello Hello Totara"
    And I click on "Add" "button_exact"
    Then I should see "Successfully saved" in the tui success notification toast
    And I should see "Add Hello Hello Totara approver(s):" in the ".tui-mod_approval-approvalsEdit[aria-label*='Level 5']" "css_element"


  Scenario: mod_approval_900: Edit approval level name
    When I log in as "manager"

    # Cannot rename level on an active workflow
    When I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Active workflow" "link"
    And I follow "Stage 2"
    And I click on "Approvals" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"
    Then "More actions" "button" should not exist in the ".tui-mod_approval-approvalsEdit__actions" "css_element"

    # Can rename level on a draft workflow
    And I am on homepage
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Draft workflow" "link"
    And I follow "Stage 2"
    And I click on "Approvals" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"
    And I click on "More actions" "button" in the ".tui-mod_approval-approvalsEdit" "css_element"
    And I click on "Rename" option in the dropdown menu
    And I set the field "name" to "New level name"
    And I click on "Rename" "button"
    Then I should see "New level name"
    And I reload the page
    Then I should see "New level name"

  Scenario: Preview form
    When I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Draft workflow" "link"
    And I click on "Stage 1" "button"
    And I click on "Preview" "button"
    And I switch to "totara_approval_workflow_form_view_preview" window
    Then I should see "What food do you want"
    And I should see "What beverage do you want"

    When I click on "Validate form" "button"
    Then I should not see "Form validated"
    When I set the field "What food do you want" to "Pizza"
    And I click on "Validate form" "button"
    And I should see "Form validated"

    Then I close the current window
    And I switch to the main window

    Then I click on "Stage 2" "button"
    And I click on "Form" "link" in the ".tui-mod_approval-workflowEdit__subSections" "css_element"
    And I click on "Preview" "button"
    And I switch to "totara_approval_workflow_form_view_preview" window
    Then I should see "What beverage do you want"
    And I should see "What food do you want"
