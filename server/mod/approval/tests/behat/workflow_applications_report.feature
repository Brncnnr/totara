@totara @mod_approval @reportbuilder @javascript @vuejs
Feature: Workflow application report
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname | lastname |  email                |
      | applicant  | Applie    | Kaant    | applicant@example.com |
      | approver   | App Rou   | Vre      |  approver@example.com |
      | manager    | Mane      | Jear     |   manager@example.com |
      | peeper     | Pea       | Pawre    |    peeper@example.com |
    And the following job assignments exist:
      | user      | manager    | appraiser | idnumber  |
      | applicant | manager    |           | jaja      |
    And the following "role assigns" exist:
      | user   | role           | contextlevel | reference |
      | peeper | teacher        | System       |           |
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
      | form      | version | json_schema                  |
      | Test form | MMXXI   | workflow_applications_report |
    And the following "workflows" exist in "mod_approval" plugin:
      | name          | id_number | form      | workflow_type    | type   | identifier | assignment_id_number |
      | Test workflow | WKF001    | Test form | Test application | cohort | AUD001     | AUD001               |
    And the following "workflow versions" exist in "mod_approval" plugin:
      | workflow | form_version | status |
      | WKF001   | MMXXI        | draft  |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name         | type            |
      | WKF001   | First stage  | FORM_SUBMISSION |
      | WKF001   | Second stage | APPROVALS       |
      | WKF001   | Last stage   | FINISHED        |
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage | name         |
      | Second stage   | First level  |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key   | required |
      | First stage    | gender      | false    |
      | First stage    | food        | false    |
      | First stage    | shirt       | false    |
      | First stage    | genre       | false    |
      | First stage    | color       | false    |
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | approval_level | type         | identifier       |
      | AUD001     | First level    | user         | approver         |
    And I publish the "Test workflow" workflow

  Scenario: View workflow applications report
    Given I log in as "applicant"
    And I visit the Applications Dashboard
    And I click on "New application" "button"
    Then I set the following fields to these values:
      | gender | Male          |
      | food   | Spicy chicken |
      | shirt  | S             |
      | genre  | Thriller      |
    And I activate the weka editor with css ".tui-mod_approval-applicationEdit__schemaForm"
    And I type "colorful images" in the weka editor
    And I wait for pending js
    Then I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I log out
    Given I log in as "admin"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I follow "Test workflow"
    When I click on "More actions" "button"
    And I click on "View report" option in the dropdown menu
    And I set the field "application-status" to "SUBMITTED"
    And I click on "submitgroupstandard[addfilter]" "button"
    Then the following should exist in the "application_form_responses" table:
      | Workflow name | Application title | Applicant name | Gender | Share images of your favourite color | What food do you want | What movie genre do you like | Which is your shirt size |
      | Test workflow | Test application  | Applie Kaant   | M      | colorful images                      | Spicy chicken         | Thriller                     | S                        |