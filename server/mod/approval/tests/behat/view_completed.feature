@totara @mod_approval @totara_comment @javascript @vuejs
Feature: View completed approval workflow application
  As an applicant
  I would like to view completed applications
  So that I can see what happened to them

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname | lastname |  email            |
      | applicant  | One       | Uno      | user1@example.com |
      | approver   | Two       | Duex     | user2@example.com |
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
      | form      | version  | json_schema |
      | Test form | 20210604 | default     |
    And the following "workflows" exist in "mod_approval" plugin:
      | name          | id_number | form      | workflow_type    | type   | identifier |
      | Test workflow | WKF001    | Test form | Test application | cohort | AUD001     |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name         | type            |
      | WKF001   | Test stage 1 | FORM_SUBMISSION |
      | WKF001   | Test stage 2 | APPROVALS       |
      | WKF001   | Test stage 3 | FINISHED        |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key      | required |
      | Test stage 1   | request_status | true     |
    And the following "approvers" exist in "mod_approval" plugin:
      | assignment | approval_level | type         | identifier       |
      | AUD001     | Level 1        | user         | approver         |
    And I publish the "WKF001" workflow
    And the following "applications" exist in "mod_approval" plugin:
      | title            | user      | workflow | assignment | creator   | workflow_stage |
      | Test application | applicant | WKF001   | AUD001     | applicant | Test stage 1   |
    And the following "application submissions" exist in "mod_approval" plugin:
      | application      | user      | form_data                |
      | Test application | applicant | {"request_status":"Yes"} |
    And the following "application actions" exist in "mod_approval" plugin:
      | application      | user      | action   |
      | Test application | applicant | submit   |
    And the following "comments" exist in "totara_comment" plugin:
      | name             | username  | component    | area    | content                                                                                       | format |
      | Test application | applicant | mod_approval | comment | {"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Kia ora"}]}]}  | 5      |
      | Test application | approver  | mod_approval | comment | {"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Kia kaha"}]}]} | 5      |

  Scenario: mod_approval_410: Applicant and approver see a completed application
    Given the following "application actions" exist in "mod_approval" plugin:
      | application      | user      | action  |
      | Test application | approver  | approve |
    And I change window size to "1280x900"
    And I log in as "applicant"
    And I visit the Applications Dashboard
    And I switch to "Your applications" tui tab
    When I click on "Test application" "link"
    Then I should see "Completed" in the ".tui-mod_approval-header__status" "css_element"
    And I should see date "now" formatted "This application is completed on %d %B %Y"
    And I should see "there are no further actions"
    And the following approval form fields match these values:
      | Request Status | Yes |
    When I switch to "Comments" tui tab
    Then I should see "Kia ora"
    And I should see "Kia kaha"
    When I click on "Menu trigger" "button"
    And I click on "Edit" "link_exact"
    And I activate the weka editor with css ".tui-editCommentReplyForm"
    And I select all text in the weka editor
    And I delete the selected text in the weka editor
    And I type "Whaaat??" in the weka editor
    And I wait for the next second
    And I click on "Done" "button_exact" in the ".tui-editCommentReplyForm" "css_element"
    Then I should see "Whaaat??"
    When I click on "Menu trigger" "button"
    And I click on "Delete" "link_exact"
    And I confirm the tui confirmation modal
    Then I should see "This comment has been deleted" exactly "1" times
    And I log out
    And I log in as "approver"
    And I visit the Applications Dashboard
    When I click on "Test application" "link"
    Then I should see date "now" formatted "This application is completed on %d %B %Y"
    And I should see "there are no further actions"
    And I should see "Completed" in the ".tui-mod_approval-header__status" "css_element"
    When I switch to "Comments" tui tab
    Then I should see "This comment has been deleted"
    And I should see "Kia kaha"
    When I click on "Menu trigger" "button"
    And I click on "Edit" "link_exact"
    And I activate the weka editor with css ".tui-editCommentReplyForm"
    And I select all text in the weka editor
    And I delete the selected text in the weka editor
    And I type "Hooray!" in the weka editor
    And I wait for the next second
    And I click on "Done" "button_exact" in the ".tui-editCommentReplyForm" "css_element"
    Then I should see "Hooray!"
