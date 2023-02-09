@totara @mod_approval @mod_approval_noengage @javascript @vuejs
Feature: Submit approval workflow application
  Background:
    Given I am on a totara site
    And I disable the "engage_resources" advanced feature
    And I disable the "container_workspace" advanced feature
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | applicant | Applie    | Kaant    | applicant@example.com |
      | approver  | App Rou   | Vré      | approver@example.com  |
      | manager   | Mané      | Jear     | manager@example.com   |
      | peeper    | Pea       | Pawre    | peeper@example.com    |
    And the following job assignments exist:
      | user      | manager | appraiser | idnumber |
      | applicant | manager |           | jaja     |
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
      | workflow_stage | name         |
      | Second stage   | First level  |
      | Fourth stage   | Second level |
      | Sixth stage    | Final level  |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key | required |
      | First stage    | gender    | false    |
      | First stage    | food      | true     |
      | First stage    | shirt     | true     |
      | Third stage    | gender    | false    |
      | Third stage    | food      | true     |
      | Fifth stage    | gender    | true     |
      | Fifth stage    | food      | true     |
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | approval_level | type         | identifier |
      | AUD001     | First level    | user         | approver   |
      | AUD001     | Second level   | relationship | manager    |
      | AUD001     | Final level    | user         | manager    |
    And I publish the "WKF001" workflow

  Scenario: mod_approval_411: View an application form
    # NOTE: Those "should see date" steps may fail around midnight in Perth
    Given the following "applications" exist in "mod_approval" plugin:
      | title            | user      | workflow | assignment | creator |
      | Test application | applicant | WKF001   | AUD001     | manager |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | form_data                                   |
      | Test application | applicant | {"gender":"M","food":"Avocado","shirt":"L"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user      | action | _notes_ |
      | Test application | applicant | submit | S1      |
    When I log in as "approver"
    And I visit the Applications Dashboard
    And I follow "Test application"
    Then I should see "Submitted" in the ".tui-mod_approval-header" "css_element"
    And I should see "Pending First level"
    And I should see date "now" formatted "Submitted by Applie Kaant on %d %B %Y"
    And the following approval form fields match these values:
      | Gender                   | Male    |
      | What food do you want    | Avocado |
      | Which is your shirt size | L       |
    And I should see "App Rou Vré" in the ".tui-mod_approval-applicationView__actions_action" "css_element"
    And I click on "Approve" "button_exact"
    Then I should see "In progress" in the ".tui-mod_approval-header" "css_element"
    And I should see "Stage 3 - Third stage"
    And I should see "Pending Third stage"
    But I should not see "App Rou Vré" in the ".tui-mod_approval-applicationView__actions" "css_element"
    # TODO: TL-31397 fix me!!
    And I reload the page
    And the following approval form fields match these values:
      | Gender                   | Male    |
      | What food do you want    | Avocado |
      | Which is your shirt size | L       |
    And I log out
    And I wait for the next second
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user     | form_data                      |
      | Test application | approver | {"gender":"F","food":"Banana"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user     | action | _notes_ |
      | Test application | approver | submit | S2      |
    When I log in as "applicant"
    And I visit the Applications Dashboard
    And I follow "Test application"
    Then I should see date "now" formatted "Submitted by Applie Kaant on %d %B %Y"
    And I should see date "now" formatted "last updated by App Rou Vré on %d %B %Y"
    And I should see "Pending Second level"
    And the following approval form fields match these values:
      | Gender                   | Female |
      | What food do you want    | Banana |
      | Which is your shirt size | L      |
    But I should not see "Applie Kaant" in the ".tui-mod_approval-applicationView__actions" "css_element"
    And I click on "More actions" "button_exact"
    And I click on "Withdraw" option in the dropdown menu
    And I confirm the tui confirmation modal
    Then I should see "Withdrawn" in the ".tui-mod_approval-header" "css_element"
    And I should see "Withdrawn" in the ".tui-mod_approval-applicationView__actions" "css_element"
    And I wait for the next second
    And I should see "Pending Third stage"
    And I should see "Applie Kaant withdrew the application"
    # keep reloading here
    And I reload the page
    And the following approval form fields match these values:
      | Gender                   | Female |
      | What food do you want    | Banana |
      | Which is your shirt size | L      |
    And I log out
    And I wait for the next second
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user    | form_data       |
      | Test application | manager | {"food":"Kale"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user    | action | _notes_ |
      | Test application | manager | submit | S2      |
    When I log in as "manager"
    And I visit the Applications Dashboard
    And I follow "Test application"
    Then I should see "Submitted" in the ".tui-mod_approval-header" "css_element"
    And I should see date "now" formatted "Submitted by Applie Kaant on %d %B %Y"
    And I should see date "now" formatted "last updated by Mané Jear on %d %B %Y"
    And I should see "Pending Second level"
    And the following approval form fields match these values:
      | Gender                   | N/A  |
      | What food do you want    | Kale |
      | Which is your shirt size | L    |
    And I should see "Mané Jear" in the ".tui-mod_approval-applicationView__actions_action" "css_element"
    And I click on "Approve" "button_exact"
    Then I should see "In progress" in the ".tui-mod_approval-header" "css_element"
    And I should see "Pending Fifth stage"
    And I should see "Stage 5 - Fifth stage"
    But I should not see "Mané Jear" in the ".tui-mod_approval-applicationView__actions" "css_element"
    # TODO: TL-31397 fix me!!
    And I reload the page
    And the following approval form fields match these values:
      | Gender                   | N/A  |
      | What food do you want    | Kale |
      | Which is your shirt size | L    |
    And I log out
    When I log in as "applicant"
    And I visit the Applications Dashboard
    And I follow "Test application"
    And I should see date "now" formatted "Submitted by Applie Kaant on %d %B %Y"
    And I should see date "now" formatted "last updated by Mané Jear on %d %B %Y"
    And I should see "Applie Kaant" in the ".tui-mod_approval-applicationView__actions" "css_element"
    And I should see "Pending Fifth stage"
    And I click on "Complete Fifth stage" "link_or_button"
    And I set the following fields to these values:
      | gender | N/A     |
      | food   | Soufflé |
    And I click on "Submit" "button_exact"
    And I confirm the tui confirmation modal
    Then I should see "Submitted" in the ".tui-mod_approval-header" "css_element"
    And I should see "Stage 6 - Sixth stage"
    And the following approval form fields match these values:
      | Gender                   | N/A     |
      | What food do you want    | Soufflé |
      | Which is your shirt size | L       |
    But I should not see "Applie Kaant" in the ".tui-mod_approval-applicationView__actions" "css_element"
    And I log out
    When I log in as "manager"
    And I visit the Applications Dashboard
    And I follow "Test application"
    Then I should see "Submitted" in the ".tui-mod_approval-header" "css_element"
    And I should see date "now" formatted "Submitted by Applie Kaant on %d %B %Y"
    And I should see date "now" formatted "last updated by Applie Kaant on %d %B %Y"
    And I should see "Pending Final level"
    And the following approval form fields match these values:
      | Gender                   | N/A     |
      | What food do you want    | Soufflé |
      | Which is your shirt size | L       |
    And I should see "Mané Jear" in the ".tui-mod_approval-applicationView__actions_action" "css_element"
    And I click on "Approve" "button_exact"
    Then I should see "Completed" in the ".tui-mod_approval-header" "css_element"
    And I should see date "now" formatted "This application is completed on %d %B %Y"
    And I should see "there are no further actions"
    But I should not see "Mané Jear" in the ".tui-mod_approval-applicationView__actions" "css_element"
    And I should not see "Status:" in the ".tui-mod_approval-applicationView__actions" "css_element"
    And I log out