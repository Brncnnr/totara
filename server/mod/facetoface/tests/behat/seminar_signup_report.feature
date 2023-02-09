@mod @mod_facetoface @totara
Feature: Users that are in waitlist of seminar's event should be displayed in the report builder
  even though, the event does not have a session date yet

  Background: Given I am at totara site
    And the following "users" exist:
      | username | firstname | lastname |
      | user1    | kian      | bomba    |
      | user2    | bolo      | bala     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1  | c101      | 0        |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname | shortname       | source              |
      | Report 1 | report_report_1 | facetoface_sessions |

  @javascript
  Scenario: I should be able to see the waitlist user within the report
    Given I am on a totara site
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name | Seminar1 |
    And I turn editing mode off
    And I follow "Seminar1"
    And I follow "Add event"
    And I click on "Delete" "link"
    And I click on "Save changes" "button"
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "kian bomba"
    And I click on "Add" "button"
    And I click on "Continue" "button"
    And I click on "Confirm" "button"
    And I navigate to "Reports > Manage user reports" in site administration
    And I follow "Report 1"
    And I switch to "Columns" tab
    And I add the "Signup status" column to the report
    When I navigate to my "Report 1" report
    Then I should see "kian bomba"
    And I should see "On waitlist"
