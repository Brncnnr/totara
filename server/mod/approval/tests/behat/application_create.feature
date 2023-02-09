@totara @mod_approval @javascript @vuejs
Feature: Create new approval workflow application
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname | lastname | email                  |
      | applicant1 | Applicant | One      | applicant1@example.com |
      | applicant2 | Applicant | Two      | applicant2@example.com |
      | applicant3 | Applicant | Three    | applicant3@example.com |
      | applicant4 | Applicant | Four     | applicant3@example.com |
      | manager    | Mana      | Djer     | manager@example.com    |
      | boss       | Bossy     | Boss     | bboss@example.com      |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber |
      | Organisation Framework | ODF      |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname    | shortname | idnumber |
      | ODF           | Cool agency | cool      | org01    |
      | ODF           | Mild agency | mild      | org02    |
    And the following job assignments exist:
      | user       | fullname         | manager | idnumber | managerjaidnumber | organisation |
      | manager    | Job assignment 1 | boss    | jajaja1  |                   | org01        |
      | applicant1 | Job assignment 2 | manager | jajaja2  | jajaja1           | org01        |
      | applicant2 | Job assignment 3 | manager | jajaja3  | jajaja1           | org01        |
      | applicant2 | Job assignment 4 | manager | jajaja4  | jajaja1           | org01        |
      | applicant3 | Job assignment 5 | manager | jajaja5  | jajaja1           | org01        |
      | applicant3 | Job assignment 6 | manager | jajaja7  | jajaja1           | org02        |
      | applicant3 | Job assignment 7 | manager | jajaja8  | jajaja1           | org02        |
      | applicant4 | Job assignment 8 | manager | jajaja9  | jajaja1           |              |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |
      | boss    | manager | System       |           |
    And the following "workflow types" exist in "mod_approval" plugin:
      | name            |
      | Workflow type 1 |
      | Workflow type 2 |
    And the following "forms" exist in "mod_approval" plugin:
      | title    |
      | Uno Form |
      | Dos Form |
    And the following "form versions" exist in "mod_approval" plugin:
      | form     | version | json_schema |
      | Uno Form | Uno     | test1       |
      | Dos Form | Dos     | test1       |
    And the following "workflows" exist in "mod_approval" plugin:
      | name            | description               | id_number | form     | workflow_type   | type         | identifier |
      | Test workflow 1 | test workflow description | WKF001    | Uno Form | Workflow type 1 | organisation | org01      |
      | Test workflow 2 | test workflow description | WKF002    | Dos Form | Workflow type 2 | organisation | org02      |
    And the following "workflow versions" exist in "mod_approval" plugin:
      | workflow | form_version | status |
      | WKF001   | Uno          | draft  |
      | WKF002   | Dos          | draft  |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name         | type            |
      | WKF001   | Test stage 1 | FORM_SUBMISSION |
      | WKF001   | Test stage 3 | FINISHED        |
      | WKF002   | Test stage 4 | FORM_SUBMISSION |
      | WKF002   | Test stage 6 | FINISHED        |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | respondent | field_key | required |
      | Test stage 1   | user       | food      | true     |
      | Test stage 1   | user       | drink     | true     |
      | Test stage 4   | user       | food      | true     |
      | Test stage 4   | user       | drink     | true     |
    And I publish the "WKF001" workflow
    And I publish the "WKF002" workflow

  Scenario: mod_approval_501: Applicant with 1 job assignment creates own application
    When I log in as "applicant1"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    Then I should see "Draft application created successfully" in the tui success notification toast

    # Header buttons.
    And "Submit" "button" should exist in the ".tui-mod_approval-header__actions" "css_element"
    And "Save as draft" "button" should exist in the ".tui-mod_approval-header__actions" "css_element"

    # Schema form buttons.
    And "Submit" "button" should exist in the ".tui-mod_approval-applicationEdit__schemaForm" "css_element"
    And "Save as draft" "button" should exist in the ".tui-mod_approval-applicationEdit__schemaForm" "css_element"
    And I should see "Draft" in the ".tui-mod_approval-header__status" "css_element"

    When I click on "Save as draft" "button" in the ".tui-mod_approval-applicationEdit__schemaForm" "css_element"
    Then I should see "You have not entered any data into the application"
    When I close the tui notification banner
    Then I should not see "You have not made any changes since your last save"
    And the following fields match these values:
      | What beverage do you want | Latte |

    When I set the following fields to these values:
      | What food do you want     | What a great meal! |
      | What beverage do you want | Chocolate drink    |
    And I click on "Save as draft" "button" in the ".tui-mod_approval-header__actions" "css_element"
    Then I should see "Application saved successfully" in the tui success notification toast

    # Test the edit page loads properly with previously saved responses.
    When I reload the page
    Then the following fields match these values:
      | What food do you want     | What a great meal! |
      | What beverage do you want | Chocolate drink    |

    When I click on "Submit" "button_exact" in the ".tui-mod_approval-applicationEdit__schemaForm" "css_element"
    Then I should see "Submit application"
    And I should see "Are you sure you want to submit the application? Once submitted, the application will be visible to others."
    When I click on "Cancel" "button_exact"
    Then "Save as draft" "button" should exist in the ".tui-mod_approval-header__actions" "css_element"

    When I click on "Submit" "button_exact" in the ".tui-mod_approval-header__actions" "css_element"
    And I confirm the tui confirmation modal
    Then I should see "Completed" in the ".tui-mod_approval-header__status" "css_element"
    And the following approval form fields match these values:
      | What food do you want | What a great meal! |

    # Testing the New application button when the dashboard is populated
    When I follow "Back to applications"
    And I click on "New application" "button"
    Then I should see "Draft application created successfully" in the tui success notification toast

  Scenario: mod_approval_502: Applicant with 2 job assignments creates own application
    When I log in as "applicant2"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    Then the field "Select job assignment" matches value "Job assignment 3"
    And the "Select job assignment" select box should contain "Job assignment 4"
    When I click on "Create" "button_exact"
    Then I should see "Draft application created successfully" in the tui success notification toast

    # Testing the New application button when the dashboard is populated
    When I follow "Back to applications"
    And I click on "New application" "button"
    And I set the field "Select job assignment" to "Job assignment 4"
    And I click on "Create" "button_exact"
    Then I should see "Draft application created successfully" in the tui success notification toast

  Scenario: mod_approval_503: Applicant with two available workflows creates own application
    When I log in as "applicant3"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    Then the field "Select an application type" matches value "Workflow type 1"
    When I click on "Create" "button_exact"
    Then I should see "Draft application created successfully" in the tui success notification toast

    # Testing the New application button when the dashboard is populated
    # Workflow with multiple job assignments
    When I follow "Back to applications"
    And I click on "New application" "button"
    And I set the field "Select an application type" to "Workflow type 2"
    And I click on "Next" "button_exact"
    Then the "Select job assignment" select box should not contain "Job assignment 5"
    When I set the field "Select job assignment" to "Job assignment 7"
    And I click on "Back" "button_exact"
    Then the field "Select an application type" matches value "Workflow type 2"
    When I click on "Next" "button_exact"
    Then the field "Select job assignment" matches value "Job assignment 7"
    When I click on "Create" "button_exact"
    Then I should see "Draft application created successfully" in the tui success notification toast

  Scenario: mod_approval_504: Manager create application for applicant with 1 job assignment
    When I log in as "manager"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    And I click on "On behalf" option in the dropdown menu
    Then the field "Select an application type" matches value "Workflow type 1"
    When I click on "Next" "button_exact"
    And I select from the tui taglist in the ".tui-modalContent" "css_element":
      | Bossy Boss |
    Then I should see "It is not possible for you to create an application for this person"
    And the "Next" "button_exact" should be disabled
    When I click on "Remove Bossy Boss" "button"
    And I select from the tui taglist in the ".tui-modalContent" "css_element":
      | Applicant One |
    Then I should not see "It is not possible for you to create an application for this person"
    And "Next" "button_exact" should not exist
    When I click on "Back" "button_exact"
    Then the field "Select an application type" matches value "Workflow type 1"
    When I click on "Next" "button_exact"
    Then I should see "Applicant One" in the "[aria-label='Selected Select a person']" "css_element"
    When I click on "Create" "button_exact"
    Then I should see "Draft application created successfully" in the tui success notification toast
    And I should see "Edit Workflow type 1 for Applicant One" in the page title
    And I should see date "now" formatted "Created by Mana Djer on %d %B %Y"
    When I set the field "What food do you want" to "Pie"
    And I click on "Submit" "button_exact"
    And I confirm the tui confirmation modal
    Then I should see "Workflow type 1 for Applicant One" in the page title
    And I should see "Completed" in the ".tui-mod_approval-header__status" "css_element"
    And I should see date "now" formatted "Submitted by Mana Djer on %d %B %Y"
    But I should not see "Could not submit the application"

  Scenario: mod_approval_505: Manager create application for applicant with 2 job assignments
    When I log in as "manager"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    And I click on "On behalf" option in the dropdown menu
    And I set the field "Select an application type" to "Workflow type 2"
    And I click on "Next" "button_exact"
    Then the "Next" "button_exact" should be disabled
    When I select from the tui taglist in the ".tui-modalContent" "css_element":
      | Applicant Three |
    Then I should not see "It is not possible for you to create an application for this person"
    When I click on "Next" "button_exact"
    Then the field "Select job assignment" matches value "Job assignment 6"
    And the "Select job assignment" select box should not contain "Job assignment 5"
    When I set the field "Select job assignment" to "Job assignment 7"
    And I click on "Back" "button_exact"
    Then I should see "Applicant Three" in the "[aria-label='Selected Select a person']" "css_element"
    And I click on "Back" "button_exact"
    Then the field "Select an application type" matches value "Workflow type 2"
    When I click on "Next" "button_exact"
    Then I should see "Applicant Three" in the "[aria-label='Selected Select a person']" "css_element"
    When I click on "Next" "button_exact"
    Then the field "Select job assignment" matches value "Job assignment 7"
    When I click on "Create" "button_exact"
    Then I should see "Draft application created successfully" in the tui success notification toast

  Scenario: mod_approval_506: Manager with 1 job assignment creates own application
    When I log in as "manager"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    And I click on "For yourself" option in the dropdown menu
    Then I should see "Draft application created successfully" in the tui success notification toast

  Scenario: mod_approval_507: Manager with no job assignments cannot create own application
    When I log in as "boss"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    Then I should see "For yourself" in the ".tui-dropdownItem--disabled" "css_element"

  Scenario: mod_approval_508: Applicant with no audience assignments cannot create an application
    When I log in as "applicant4"
    And I visit the Applications Dashboard
    Then I should not see "New application"

  Scenario: mod_approval_509: Manager cannot create application for applicant with no audience assignments
    When I log in as "manager"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    And I click on "On behalf" option in the dropdown menu
    And I click on "Next" "button_exact"
    And I select from the tui taglist in the ".tui-modalContent" "css_element":
      | Applicant Four |
    Then I should see "It is not possible for you to create an application for this person."

  Scenario: mod_approval_510: Manager can only create on behalf for active workflows
    Given the following "workflows" exist in "mod_approval" plugin:
      | name            | description               | id_number | form     | workflow_type   | type         | identifier |
      | Test workflow 3 | test workflow description | WKF003    | Uno Form | Workflow type 1 | organisation | org01      |
    And I archive the "WKF001" workflow
    When I log in as "manager"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    Then I should see "For yourself" in the ".tui-dropdownItem--disabled" "css_element"
    And I click on "On behalf" option in the dropdown menu
    When I select from the tui taglist in the ".tui-mod_approval-selectUserStep__tagList" "css_element":
      | Applicant Three |
    Then I should not see "It is not possible for you to create an application for this person"
    When I click on "Next" "button_exact"
    Then the field "Select job assignment" matches value "Job assignment 6"
    And the "Select job assignment" select box should not contain "Job assignment 5"
    When I set the field "Select job assignment" to "Job assignment 7"
    When I click on "Create" "button_exact"
    Then I should see "Draft application created successfully" in the tui success notification toast

  Scenario: mod_approval_511: New application button only appears with active workflows
    Given the following "workflows" exist in "mod_approval" plugin:
      | name            | description               | id_number | form     | workflow_type   | type         | identifier |
      | Test workflow 3 | test workflow description | WKF003    | Uno Form | Workflow type 1 | organisation | org01      |
    And I archive the "WKF001" workflow
    And I archive the "WKF002" workflow
    When I log in as "manager"
    And I visit the Applications Dashboard
    Then "New application" "button" should not exist