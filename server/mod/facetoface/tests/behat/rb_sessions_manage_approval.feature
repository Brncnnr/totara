@mod @mod_facetoface @totara @totara_reportbuilder @javascript
Feature: Verify that link to approval requests work

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | usertest | user      | test     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1  | c1       | 0        |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname | shortname       | source              |
      | Report 1 | report_report_1 | facetoface_sessions |

  Scenario: Confirm that the link directs to the approval page
    Given I am on a totara site
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name | Seminar1 |
    And I turn editing mode off
    And I follow "Seminar1"
    And I follow "Add event"
    And I click on "Save changes" "button"
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "user test"
    And I click on "Add" "button"
    And I click on "Continue" "button"
    And I click on "Confirm" "button"
    When I navigate to my "Report 1" report
    Then I should see "user test"
    And I should see "Link to approval requests"
    And I follow "Manage approval"
    Then I should see "Seminar1"
    And I should see "No pending approvals"
