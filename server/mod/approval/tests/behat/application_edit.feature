@totara @mod_approval @javascript @vuejs
Feature: Edit approval workflow application
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname | lastname | email                 |
      | applicant  | Applie    | Kaant    | applicant@example.com |
      | editor     | Eddie     | Tuar     |    editor@example.com |
    And the following "roles" exist:
      | shortname     |
      | historyeditor |
    And the following "role assigns" exist:
      | user   | role          | contextlevel | reference |
      | editor | historyeditor | User         | applicant |
    And the following "permission overrides" exist:
      | capability                                              | permission | role          | contextlevel | reference |
      | mod/approval:view_in_dashboard_application_user         | Allow      | historyeditor | User         | applicant |
      | mod/approval:view_application_user                      | Allow      | historyeditor | User         | applicant |
      | mod/approval:edit_first_approval_level_application_user | Allow      | historyeditor | User         | applicant |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | AUD001   |
    And the following "workflow types" exist in "mod_approval" plugin:
      | name                  |
      | A very simple request |
    And the following "forms" exist in "mod_approval" plugin:
      | title     |
      | Test form |
    And the following "form versions" exist in "mod_approval" plugin:
      | form      | version | json_schema |
      | Test form | 1       | test1       |
    And the following "workflows" exist in "mod_approval" plugin:
      | name          | description               | id_number | form      | workflow_type         | type   | identifier |
      | Test workflow | test workflow description | WKF001    | Test form | A very simple request | cohort | AUD001     |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name         | type            |
      | WKF001   | Test stage 1 | FORM_SUBMISSION |
      | WKF001   | Test stage 2 | APPROVALS       |
      | WKF001   | Test stage 3 | FINISHED        |
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage | name         |
      | Test stage 2   | Test level 1 |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key | required |
      | Test stage 1   | food      | true     |
      | Test stage 2   | food      | true     |
    And I publish the "WKF001" workflow

  Scenario: mod_approval_511: Editor edit applicant's application
    Given the following "applications" exist in "mod_approval" plugin:
      | title            | user      | workflow | assignment | creator   |
      | Test application | applicant | WKF001   | AUD001     | applicant |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | form_data                     |
      | Test application | applicant | {"food":"What a great meal!"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user      | action |
      | Test application | applicant | submit |

    When I log in as "editor"
    And I visit the Applications Dashboard
    And I follow "A very simple request"

    Then "Save" "button" should not exist in the ".tui-mod_approval-header__actions" "css_element"
    And "Cancel" "button" should not exist in the ".tui-mod_approval-header__actions" "css_element"
    And "Submit" "button" should not exist in the ".tui-mod_approval-header__actions" "css_element"
    And "Save as draft" "button" should not exist in the ".tui-mod_approval-header__actions" "css_element"
    And "Applie Kaant" "link" should not exist in the ".tui-mod_approval-applicationView__actions" "css_element"
    But "Eddie Tuar" "link" should exist in the ".tui-mod_approval-applicationView__actions" "css_element"

    And I click on "More actions" "button"
    And I click on "Edit" option in the dropdown menu
    Then I should see "Form sections" in the ".tui-mod_approval-applicationEdit__sectionNav" "css_element"
    And I should see "Section A - Food & drinks" in the ".tui-mod_approval-applicationEdit__sectionNav" "css_element"

    # Header buttons.
    And "Save" "button" should exist in the ".tui-mod_approval-header__actions" "css_element"
    And "Cancel" "button" should exist in the ".tui-mod_approval-header__actions" "css_element"
    And "Submit" "button" should not exist in the ".tui-mod_approval-header__actions" "css_element"
    And "Save as draft" "button" should not exist in the ".tui-mod_approval-header__actions" "css_element"

    # Schema form buttons.
    And "Save" "button" should exist in the ".tui-mod_approval-applicationEdit__schemaForm" "css_element"
    And "Cancel" "button" should exist in the ".tui-mod_approval-applicationEdit__schemaForm" "css_element"
    And "Submit" "button" should not exist in the ".tui-mod_approval-applicationEdit__schemaForm" "css_element"
    And "Save as draft" "button" should not exist in the ".tui-mod_approval-applicationEdit__schemaForm" "css_element"
    And I should see "In progress" in the ".tui-mod_approval-header__status" "css_element"

    And the following fields match these values:
      | food | What a great meal! |

    When I click on "Cancel" "button"
    Then I should not see "Form sections"

    # Go back to edit page
    And I click on "More actions" "button"
    And I click on "Edit" option in the dropdown menu
    When I set the following fields to these values:
      | food | Have to try this again |
    And I click on "Save" "button" in the ".tui-mod_approval-header__actions" "css_element"
    And I confirm the tui confirmation modal
    Then I should not see "Could not save the application"
    And the following approval form fields match these values:
      | food | Have to try this again |
