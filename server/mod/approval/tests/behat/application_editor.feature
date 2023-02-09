@totara @mod_approval @javascript @vuejs
Feature: Application editor fields.
  Background:
    Given I am on a totara site
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
      | form      | version | json_schema   |
      | Test form | MMXXI   | editor_schema |
    And the following "workflows" exist in "mod_approval" plugin:
      | name          | id_number | form      | workflow_type    | type   | identifier |
      | Test workflow | WKF001    | Test form | Test application | cohort | AUD001     |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name         | type            |
      | WKF001   | First stage  | FORM_SUBMISSION |
      | WKF001   | Second stage | APPROVALS       |
      | WKF001   | Last stage   | FINISHED        |
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage | name         |
      | Second stage   | First level  |
    And I publish the "WKF001" workflow

  Scenario: Application editor field can be edited, viewed and  cloned
    # TODO: TL-32083 uploading a file into the weka editor is unstable
    And I skip the scenario until issue "TL-32083" lands
    When I log in as "applicant"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    And I activate the weka editor with css ".tui-mod_approval-applicationEdit__schemaForm"
    And I type "A good description" in the weka editor
    And I upload embedded media to the weka editor using the file "mod/approval/tests/fixtures/logo.png"
    # This is to make sure that the files are all uploaded correctly
    And I wait for pending js
    And I click on "Save as draft" "button"
    And I reload the page
    And I activate the weka editor with css ".tui-mod_approval-applicationEdit__schemaForm"
    Then I should see "A good description" in the weka editor
    # help on testing the file exists in the weka editor.

    # Submitting an editor field.
    When I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "A good description" in the ".tui-mod_approval-applicationView__schemaForm" "css_element"
    And I wait for pending js
    And I should see a weka embedded image with the name "logo.png" in the ".tui-mod_approval-applicationView__schemaForm .tui-imageBlock" "css_element"

    # Cloning an editor field.
    When I click on "More actions" "button"
    And I click on "Clone" option in the dropdown menu
    And I should see "Draft" in the ".tui-mod_approval-header__status" "css_element"
    And I activate the weka editor with css ".tui-mod_approval-applicationEdit__schemaForm"
    Then I should see "A good description" in the weka editor

  Scenario: Application editor field has attachments only when user has the capability
    When I log in as "applicant"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    And I activate the weka editor with css ".tui-mod_approval-applicationEdit__schemaForm"
    Then I should see the "Attachment" toolbar button in the weka editor
    Then I should not see the "Horizontal Rule" toolbar button in the weka editor
    Then I should not see the "Embedded media" toolbar button in the weka editor

    # Disable upload capability.
    Given the following "permission overrides" exist:
      | capability                                        | permission | role | contextlevel | reference |
      | mod/approval:attach_file_to_application_applicant | Prohibit   | user | System       | admin     |
      | mod/approval:attach_file_to_application_owner     | Prohibit   | user | System       | admin     |

    And I reload the page
    And I activate the weka editor with css ".tui-mod_approval-applicationEdit__schemaForm"
    Then I should not see the "Attachment" toolbar button in the weka editor
    Then I should not see the "Horizontal Rule" toolbar button in the weka editor
    Then I should not see the "Embedded media" toolbar button in the weka editor
