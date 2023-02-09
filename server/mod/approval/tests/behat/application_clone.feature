@totara @mod_approval @javascript @vuejs
Feature: Clone approval workflow application
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname | lastname |  email            |
      | applicant  | One       | Uno      | user1@example.com |
      | approver   | Two       | Duex     | user2@example.com |
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
      | Test form | 1       | {"title":"x","shortname":"x","version":"1","fields":[{"key":"key-a","label":"A","type":"text"},{"key":"key-b","label":"B","type":"text"},{"key":"key-c","label":"C","type":"text"},{"key":"key-d","label":"D","type":"text"}],"sections":[]} |
    And the following "workflows" exist in "mod_approval" plugin:
      | name          | description               | id_number | form      | workflow_type      | type   | identifier |
      | Test workflow | test workflow description | WKF001    | Test form | Test workflow type | cohort | AUD001     |

  Scenario: mod_approval_551: Clone an application
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name         | type            |
      | WKF001   | Test stage 1 | FORM_SUBMISSION |
      | WKF001   | Test stage 2 | APPROVALS       |
      | WKF001   | Test stage 3 | FINISHED        |
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage | name         |
      | Test stage 2   | Test level 1 |
      | Test stage 2   | Test level 2 |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key | required |
      | Test stage 1   | key-a     | true     |
      | Test stage 1   | key-b     | true     |
      | Test stage 1   | key-c     | true     |
      | Test stage 1   | key-d     | true     |
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | approval_level | type | identifier |
      | AUD001     | Test level 1   | user | approver   |
      | AUD001     | Test level 2   | user | approver   |
    And I publish the "WKF001" workflow
    And the following "applications" exist in "mod_approval" plugin:
      | title            | user      | workflow | assignment | creator   |
      | Test application | applicant | WKF001   | AUD001     | applicant |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | id_number        | form_data |
      | Test application | applicant | T-35T-OR191N-A9N | {"key-a":"Test A field","key-b":"Test B field","key-c":"Test C field","key-d":"Test D field"} |
    When I log in as "applicant"
    And I visit the Applications Dashboard
    And I follow "Test workflow type"
    And I click on "More actions" "button"
    And I click on "Clone" option in the dropdown menu
    Then I should see "Application cloned successfully" in the tui success notification toast
    And I should see "Draft" in the ".tui-mod_approval-header__status" "css_element"
    And the following fields match these values:
      | key-a | Test A field |
      | key-b | Test B field |
      | key-c | Test C field |
      | key-d | Test D field |

  Scenario: mod_approval_552: Clone application with a new workflow version
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name         | type            |
      | WKF001   | Test stage 1 | FORM_SUBMISSION |
      | WKF001   | Test stage 2 | APPROVALS       |
      | WKF001   | Test stage 3 | FINISHED        |
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage | name         |
      | Test stage 2   | Test level 1 |
      | Test stage 2   | Test level 2 |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key | required |
      | Test stage 1   | key-a     | true     |
      | Test stage 1   | key-b     | true     |
      | Test stage 1   | key-c     | true     |
      | Test stage 1   | key-d     | true     |
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | approval_level | type | identifier |
      | AUD001     | Test level 1   | user | approver   |
      | AUD001     | Test level 2   | user | approver   |
    And I publish the "WKF001" workflow
    # old version
    And the following "applications" exist in "mod_approval" plugin:
      | title            | user      | workflow | assignment | creator   |
      | Test application | applicant | WKF001   | AUD001     | applicant |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | id_number        | form_data |
      | Test application | applicant | T-35T-OR191N-A9N | {"key-a":"Test A field","key-b":"Test B field","key-c":"Test C field","key-d":"Test D field"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user      | action |
      | Test application | applicant | submit |
    And I archive the "WKF001" workflow
    # new version
    And the following "form versions" exist in "mod_approval" plugin:
      | form      | version | json_schema |
      | Test form | 2       | {"title":"x","shortname":"x","version":"2","fields":[{"key":"key-a","label":"A","type":"text"},{"key":"key-b","label":"Date","type":"date","attrs":{"format":"Y-m-d"}},{"key":"key-c","label":"Choice","type":"select_one","attrs":{"choices":[{"key":null,"label":"Yeah Nah"},{"key":"Y","label":"Yup"},{"key":"N","label":"Nope"}]}}],"sections":[]} |
    And the following "workflow versions" exist in "mod_approval" plugin:
      | workflow | form_version | status |
      | WKF001   | 2            | draft  |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name             | type            |
      | WKF001   | New test stage 1 | FORM_SUBMISSION |
      | WKF001   | New test stage 2 | APPROVALS       |
      | WKF001   | New test stage 3 | FINISHED        |
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage   | name             |
      | New test stage 2 | New test level 1 |
      | New test stage 2 | New test level 2 |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage   | field_key | required |
      | New test stage 1 | key-a     | false    |
      | New test stage 1 | key-b     | false    |
      | New test stage 1 | key-c     | false    |
    And I publish the "WKF001" workflow

    When I log in as "applicant"
    And I visit the Applications Dashboard
    And I press "More actions"
    And I click on "Clone" option in the dropdown menu
    Then I should see "Application cloned successfully" in the tui success notification toast
    And I should see "Draft" in the ".tui-mod_approval-header__status" "css_element"
    But I should not see "T-35T-OR191N-A9N"
    And the field "key-a" matches value "Test A field"
    And the "key-b" tui date selector should not be set
    And the field "key-c" matches value ""
