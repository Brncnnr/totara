@mod @mod_facetoface @totara
Feature: Seminar Manager signup approval changes
  The system should react gracefully when seminar approval type changes occur

  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username    | firstname | lastname | email              |
      | teacher     | Freddy    | Fred     | freddy@example.com |
      | manager     | Cassy     | Cas      | cassy@example.com  |
      | jimmy       | Jimmy     | Jim      | jimmy@example.com  |
      | timmy       | Timmy     | Tim      | timmy@example.com  |
      | sammy       | Sammy     | Sam      | sammy@example.com  |
    And the following "courses" exist:
      | fullname                 | shortname | category |
      | Classroom Connect Course | CCC       | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | CCC    | editingteacher |
      | jimmy   | CCC    | student        |
      | timmy   | CCC    | student        |
      | sammy   | CCC    | student        |
    And the following job assignments exist:
      | user  | manager |
      | jimmy | manager |
      | timmy | manager |
      | sammy | manager |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                          | course  |
      | Classroom Connect | <p>Classroom Connect Tests</p> | CCC     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details | capacity | allowoverbook |
      | Classroom Connect | event 1 | 1        | 1             |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish        |
      | event 1      | tomorrow 9am | tomorrow 10am |

  @javascript
  Scenario: The waitlisted report should be correct when the approval type changes
    When I log in as "jimmy"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"

    When I press "Sign-up"
    Then I should see "Your request was accepted"

    When I log out
    And I log in as "timmy"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should not see "Manager Approval"
    And I should see "This event is currently full. Upon successful sign-up, you will be placed on the event's waitlist."

    Given I press "Join waitlist"
    And I log out
    And I log in as "teacher"
    And I am on "Classroom Connect" seminar homepage
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I click on "#id_approvaloptions_approval_manager" "css_element"
    And I press "Save and display"
    And I log out

    When I log in as "sammy"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should see "Manager Approval"
    And I should see "This event is currently full. Upon successful sign-up, you will be placed on the event's waitlist."
    When I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks

    Given I log out
    And I log in as "manager"
    And I am on "Dashboard" page
    Then I should see "Seminar booking request"
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Sammy Sam has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "Attendees" "link" in the "Sammy Sam" "table_row"
    Then I should see "Sammy Sam" in the ".lastrow" "css_element"

    Given I click on "requests[7]" "radio" in the ".lastrow .lastcol" "css_element"
    And I click on "Update requests" "button"
    And I log out

    And I log in as "admin"
    And I am on "Classroom Connect" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I follow "Wait-list"
    And I press "Edit this report"
    And I switch to "Columns" tab
    And I set the field "newcolumns" to "Approver name"
    And I press "Add"
    And I press "Save changes"
    And I log out

    When I log in as "teacher"
    And I am on "Classroom Connect" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    Then I should see "Booked" in the "Jimmy Jim" "table_row"

    When I follow "Wait-list"
    And I should see "On waitlist" in the "Timmy Tim" "table_row"
    And I should not see "Thursday, 1 January 1970, 1:00 AM" in the "Timmy Tim" "table_row"
    And I should see "On waitlist" in the "Sammy Sam" "table_row"
    And I should see "Cassy Cas" in the "Sammy Sam" "table_row"

