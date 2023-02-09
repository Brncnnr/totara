@totara @mod_approval @totara_comment @editor @weka @editor_weka @javascript @vuejs
Feature: Edit or delete comments in an approval workflow application
  As an applicant/approver
  I would like to edit/delete a comment to an application
  So that I can correct my mistake

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname | lastname | email                 |
      | applicant  | Applie    | Kaant    | applicant@example.com |
      | approver   | App Rou   | Vré      | approver@example.com  |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | AUD001   |
    And the following "cohort members" exist:
      | user      | cohort |
      | applicant | AUD001 |
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
      | Test workflow | test workflow description | WKF001    | Test form | Test workflow type | cohort | AUD001     |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name         | type            |
      | WKF001   | Test stage 1 | FORM_SUBMISSION |
      | WKF001   | Test stage 2 | APPROVALS       |
      | WKF001   | Test stage 3 | FINISHED        |
    And the following "approval levels" exist in "mod_approval" plugin:
      | workflow_stage | name         |
      | Test stage 2   | Test level 2 |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key | required |
      | Test stage 1   | food      | true     |
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | approval_level | type | identifier |
      | AUD001     | Level 1        | user | approver   |
      | AUD001     | Test level 2   | user | approver   |
    And I publish the "WKF001" workflow
    And the following "applications" exist in "mod_approval" plugin:
      | title            | user      | workflow | assignment | creator   |
      | Test application | applicant | WKF001   | AUD001     | applicant |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | form_data         |
      | Test application | applicant | {"food":"poison"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user      | action  |
      | Test application | applicant | submit  |

  Scenario Outline: mod_approval_452: Edit or delete comment as applicant or approver
    When I log in as "<user>"
    And I visit the Applications Dashboard
    And I switch to "<tab>" tui tab
    And I click on "<link>" "link"
    And I switch to "Comments" tui tab
    And I activate the weka editor with css ".tui-commentForm"
    And I type "Hooray!" in the weka editor
    And I wait for the next second
    And I click on "Post" "button_exact"
    Then I should see "Hooray!" in the ".tui-commentCard__body" "css_element"
    When I click on "Menu trigger" "button"
    And I click on "Edit" option in the dropdown menu
    And I activate the weka editor with css ".tui-editCommentReplyForm"
    And I select all text in the weka editor
    And I delete the selected text in the weka editor
    And I type "Whaaat??" in the weka editor
    And I wait for the next second
    And I click on "Done" "button_exact" in the ".tui-editCommentReplyForm" "css_element"
    Then I should see "Whaaat??" in the ".tui-commentCard__body" "css_element"
    When I click on "Menu trigger" "button"
    And I click on "Delete" "link_exact"
    And I confirm the tui confirmation modal
    Then I should see "This comment has been deleted"

    Examples:
      | user      | tab                      | link               |
      | applicant | Your applications        | Test workflow type |
      | approver  | Applications from others | Test application   |

  Scenario Outline: mod_approval_453: Applicant cannot edit/delete approver's comment and vice versa
    But I change window size to "1200x1000"
    When I log in as "<victim>"
    And I visit the Applications Dashboard
    And I switch to "<vtab>" tui tab
    And I click on "<application_id>" "link"
    And I switch to "Comments" tui tab
    And I activate the weka editor with css ".tui-commentForm"
    And I type "Hooray!" in the weka editor
    And I wait for the next second
    And I click on "Post" "button_exact"
    Then I should see "Hooray!" in the ".tui-commentCard__body" "css_element"
    And I reload the page
    And I switch to "Comments" tui tab
    When I click on "Menu trigger" "button"
    Then I should see "Edit" option in the dropdown menu
    And I should see "Delete" option in the dropdown menu
    But I should not see "Report" option in the dropdown menu
    And I log out

    When I log in as "<offender>"
    And I visit the Applications Dashboard
    And I switch to "<otab>" tui tab
    And I click on "<application_name>" "link"
    And I switch to "Comments" tui tab
    Then "Menu trigger" "button" should not exist

    Examples:
      | victim    | offender  | vtab                     | otab                     | application_id     | application_name |
      | applicant | approver  | Your applications        | Applications from others | Test workflow type | Test application |
      | approver  | applicant | Applications from others | Your applications        | Test application | Test workflow type |
