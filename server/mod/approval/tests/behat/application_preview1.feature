@totara @mod_approval @mod_approval_noengage @javascript @vuejs
Feature: Preview approval workflow application 1 (basic)
  Background:
    Given I am on a totara site
    And I disable the "engage_resources" advanced feature
    And I disable the "container_workspace" advanced feature
    And the following "users" exist:
      | username   | firstname | lastname | email                  |
      | supervisor | Sue Pa    | Weiz     | supervisor@example.com |
      | applicant  | Applie    | Kaant    |  applicant@example.com |
      | approver   | App Rou   | Vré      |   approver@example.com |
      | manager    | Mané      | Jear     |    manager@example.com |
    And the following job assignments exist:
      | user      | manager    | appraiser | idnumber | managerjaidnumber |
      | manager   | supervisor |           | jajaja   |                   |
      | applicant | manager    |           | hahaha   | jajaja            |
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
      | form      | version | json_schema |
      | Test form | MMXXI   | test1       |
    And the following "workflows" exist in "mod_approval" plugin:
      | name          | id_number | form      | workflow_type    | type   | identifier |
      | Test workflow | WKF001    | Test form | Test application | cohort | AUD001     |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name         | type            |
      | WKF001   | First stage  | FORM_SUBMISSION |
      | WKF001   | Second stage | APPROVALS       |
      | WKF001   | Third stage  | FORM_SUBMISSION |
      | WKF001   | Fourth stage | APPROVALS       |
      | WKF001   | Fifth stage  | FORM_SUBMISSION |
      | WKF001   | Sixth stage  | APPROVALS       |
      | WKF001   | Final stage  | FINISHED        |
    And I delete default approval level for stage "Second stage"
    And I delete default approval level for stage "Fourth stage"
    And I delete default approval level for stage "Sixth stage"
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage | name                                                                                                          |
      | Second stage   | <span lang="en" class="multilang">First level</span><span lang="fr" class="multilang">Premier niveau</span>   |
      | Fourth stage   | <span lang="en" class="multilang">Second level</span><span lang="fr" class="multilang">Deuxième niveau</span> |
      | Sixth stage    | <span lang="en" class="multilang">Final level</span><span lang="fr" class="multilang">Niveau final</span>     |

    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key    | required |
      | First stage    |  food        | true     |
      | Third stage    |  food        | true     |
      | Fifth stage    |  agency_code | true     |
      | Fifth stage    |  gender      | true     |
      | Fifth stage    |  food        | true     |
      | Fifth stage    |  drink       | true     |
      | Fifth stage    |  genre       | true     |
      | Fifth stage    |  tomato      | false    |
      | Fifth stage    |  shirt       | true     |
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | approval_level                                                                                                | type         | identifier       |
      | AUD001     | <span lang="en" class="multilang">First level</span><span lang="fr" class="multilang">Premier niveau</span>   | relationship | manager          |
      | AUD001     | <span lang="en" class="multilang">First level</span><span lang="fr" class="multilang">Premier niveau</span>   | user         | approver         |
      | AUD001     | <span lang="en" class="multilang">Second level</span><span lang="fr" class="multilang">Deuxième niveau</span> | user         | manager          |
      | AUD001     | <span lang="en" class="multilang">Second level</span><span lang="fr" class="multilang">Deuxième niveau</span> | user         | supervisor       |
      | AUD001     | <span lang="en" class="multilang">Final level</span><span lang="fr" class="multilang">Niveau final</span>     | user         | supervisor       |
    And I publish the "WKF001" workflow

  Scenario: mod_approval_441: Check approvers in application preview (basic)
    Given the following "applications" exist in "mod_approval" plugin:
      | title            | user      | workflow | assignment | creator |
      | Test application | applicant | WKF001   | AUD001     | manager |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | form_data          |
      | Test application | applicant | {"food":"poison"}  |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user      | action  |
      | Test application | applicant | submit  |
      | Test application | approver  | approve |
    And I wait for the next second
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | form_data          |
      | Test application | applicant | {"food":"allergy"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user       | action  |
      | Test application | applicant  | submit  |
      | Test application | supervisor | reject  |
    And I wait for the next second
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | form_data          |
      | Test application | applicant | {"food":"storage"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user       | action  |
      | Test application | applicant  | submit  |
      | Test application | manager    | approve |
    And I wait for the next second
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | form_data          |
      | Test application | applicant | data1              |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user       | action  |
      | Test application | applicant  | submit  |
      | Test application | supervisor | approve |
    And the multi-language content filter is enabled

    When I log in as "applicant"
    And I visit the Applications Dashboard
    And I click on "Test application" "link"
    And I click on "More actions" "button"
    And I click on "Print preview" option in the dropdown menu
    When I switch to "totara_approval_workflow_application_preview" window
    Then I should see "Test Form" in the ".tui-mod_approval-basicPrintView" "css_element"
    And I should see "A93NC1E-CO0DE" in the ".tui-mod_approval-basicPrintView__field" "css_element"
    And I should see "First level" in the ".tui-mod_approval-basicPrintView__approvals-table > tbody > tr:nth-of-type(1)" "css_element"
    And I should see "App Rou Vré" in the ".tui-mod_approval-basicPrintView__approvals-table > tbody > tr:nth-of-type(1)" "css_element"
    And I should see "Second level" in the ".tui-mod_approval-basicPrintView__approvals-table > tbody > tr:nth-of-type(2)" "css_element"
    And I should see "Mané Jear" in the ".tui-mod_approval-basicPrintView__approvals-table > tbody > tr:nth-of-type(2)" "css_element"
    And I should see "Final level" in the ".tui-mod_approval-basicPrintView__approvals-table > tbody > tr:nth-of-type(3)" "css_element"
    And I should see "Sue Pa Weiz" in the ".tui-mod_approval-basicPrintView__approvals-table > tbody > tr:nth-of-type(3)" "css_element"
    But I should not see "Premier niveau"
