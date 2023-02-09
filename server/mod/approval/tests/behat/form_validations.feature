@totara @mod_approval @totara_comment @javascript @vuejs
Feature: Approval workflow form validation
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email             |
      | applicant | One       | Uno      | user1@example.com |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | AUD001   |
    And the following "cohort members" exist:
      | user      | cohort |
      | applicant | AUD001 |
    And the following "workflow types" exist in "mod_approval" plugin:
      | name             |
      | Cool application |
    And the following "forms" exist in "mod_approval" plugin:
      | title     |
      | Cool form |
    And the following "form versions" exist in "mod_approval" plugin:
      | form      | version  | json_schema |
      | Cool form | 20210715 | validations |
    And the following "workflows" exist in "mod_approval" plugin:
      | name          | id_number | form      | workflow_type    | type   | identifier |
      | Cool workflow | WKF001    | Cool form | Cool application | cohort | AUD001     |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name       | type            |
      | WKF001   | Cool stage | FORM_SUBMISSION |
    Given the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | respondent | field_key | required |
      | Cool stage     | user       | forty_two | true     |
    And I publish the "WKF001" workflow
    And the following "applications" exist in "mod_approval" plugin:
      | title            | user      | workflow | assignment | creator   | workflow_stage |
      | Cool application | applicant | WKF001   | AUD001     | applicant | Cool stage     |

  Scenario: mod_approval_701: Required validation
    And I log in as "applicant"
    And I visit the Applications Dashboard
    And I click on "Cool application" "link"
    When I click on "Submit" "button_exact"
    Then I should see "Required" in the ".tui-formField" "css_element"
    When I set the field "Forty Two" to "    "
    And I click on "Submit" "button_exact"
    Then I should see "Required" in the ".tui-formField" "css_element"
    When I set the field "Forty Two" to "?"
    And I click on "Submit" "button_exact"
    Then I should see "Submit application" in the tui modal

  Scenario: mod_approval_702: MaxLength limit
    And I log in as "applicant"
    And I visit the Applications Dashboard
    And I click on "Cool application" "link"
    When I set the field "Forty Two" to "42 Lorem ipsum dolor sit amet, consectetur"
    And I should not see "consectetur"
    When I set the field "Forty Two" to "43 Lorem ipsum dolor sit amet, consectetur?"
    And I should not see "consectetur"
    And I should not see "consectetur?"
