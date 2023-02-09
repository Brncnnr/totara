@totara @mod_approval @totara_comment @editor @weka @editor_weka @javascript @vuejs
Feature: Post comments to an approval workflow application
  As an applicant/approver
  I would like to post a comment to an application
  So that I can communicate with others

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

  Scenario: mod_approval_451: Conversation between applicant and approver
    When I log in as "applicant"
    And I visit the Applications Dashboard
    And I switch to "Your applications" tui tab
    And I click on "Test workflow type" "link"
    When I switch to "Comments" tui tab
    And I activate the weka editor with css ".tui-commentForm"
    And I type "Hooray!" in the weka editor
    And I wait for the next second
    And I click on "Post" "button_exact"
    Then I should see "Hooray!" in the "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-commentCard__body ') and contains(., 'Applie Kaant')]" "xpath_element"
    And I log out
    When I log in as "approver"
    And I visit the Applications Dashboard
    And I click on "Respond to application Test application for Applie Kaant" "button"
    And I switch to "Comments" tui tab
    And I click on "Reply" "button_exact"
    And I activate the weka editor with css ".tui-commentReplyForm__form"
    And I type "Whaaat??" in the weka editor
    And I wait for the next second
    And I click on "Reply" "button_exact" in the ".tui-commentReplyForm__form" "css_element"
    Then I should see "Whaaat??" in the "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-commentReplyCard__body ') and contains(., 'App Rou Vré')]" "xpath_element"
    And I log out
    When I log in as "applicant"
    And I visit the Applications Dashboard
    And I switch to "Your applications" tui tab
    And I click on "Test workflow type" "link"
    And I switch to "Comments" tui tab
    Then I should see "Hooray!" in the "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-commentCard__body ') and contains(., 'Applie Kaant')]" "xpath_element"
    When I click on "View replies" "link"
    Then I should see "Whaaat??" in the "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-commentReplyCard__body ') and contains(., 'App Rou Vré')]" "xpath_element"

  Scenario: mod_approval_454: Admin cannot see the report command
    Given the following "comments" exist in "totara_comment" plugin:
      | name             | username  | component    | area    | content                                                      | format |
      | Test application | applicant | mod_approval | comment | {"type":"doc","content":[{"type":"text","text":"Kia ora"}]}  | 5      |
    # Extend the browser window or the tui tab becomes a dropdown menu and the "switch to tui tab" step fails
    And I change window size to "1400x900"
    When I log in as "admin"
    And I visit the Applications Dashboard
    And I click on "Test workflow" "link"
    And I switch to "Comments" tui tab
    And I click on "Menu trigger" "button"
    Then I should not see "Report" option in the dropdown menu
