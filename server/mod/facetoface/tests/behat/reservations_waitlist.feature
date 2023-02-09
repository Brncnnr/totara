@javascript @mod @mod_facetoface @totara
Feature: Reserve spaces in waitlist in Seminar
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
      | student3 | Sam3      | Student3 | student3@example.com |
      | manager  | Max       | Manager  | manager@example.com  |
      | teamlead | Torry     | Teamlead | teamlead@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | summary |
      | Course 1 | C1        | 0        |         |
    And the following "course enrolments" exist:
      | user | course | role           |
      | student1 | C1 | student        |
      | student2 | C1 | student        |
      | manager  | C1 | editingteacher |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |
    And the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
    And the following job assignments exist:
      | user     | position | manager  |
      | student1 | POS001   | manager  |
      | student2 | POS001   | manager  |
      | student3 | POS001   | teamlead |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                           | course  | managerreserve | maxmanagerreserves |
      | Test Seminar name | <p>Test Seminar description</p> | C1      | 1              | 2                  |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details | capacity | allowoverbook |
      | Test Seminar name | event 1 | 1        | 1             |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                 | finish                |
      | event 1      | 1 Feb next year 11:00 | 1 Feb next year 12:00 |

  Scenario: Confirm manager reservations are on waitlist when overbooked
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Test Seminar name" "link"
    And I click on the seminar event action "Attendees" in row "1 February"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam3 Student3, student3@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Bulk add attendees success"
    And I should see "Booked" in the "Sam3 Student3" "table_row"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Test Seminar name" "link"
    And I click on "Go to event" "link" in the "1 February" "table_row"
    And I press "Join waitlist"
    Then I should see "You have been placed on the waitlist"
    And I log out
    And I log in as "manager"
    And I am on "Course 1" course homepage
    And I click on "Test Seminar name" "link"
    And I click on "Go to event" "link" in the "1 February" "table_row"
    When I click on "Reserve spaces for team" "link"
    And I set the field "Reserve spaces for team" to "1"
    And I press "Update"
    Then I should see "Reserve spaces for team (1/2)"
    When I click on "All events" "link"
    Then the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Session times | Booked | Booked        |
      | 1 February    | 1 / 1  | 2 on waitlist |
    But the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Session times | Booked     |
      | 1 February    | Overbooked |
    And I click on the seminar event action "Attendees" in row "1 February"
    Then "table#facetoface_sessions > tbody > tr:nth-child(2)" "css_element" should not exist
    When I switch to "Wait-list" tab
    Then I should see "On waitlist" in the "Sam1 Student1" "table_row"
    # The following steps must be fixed in TL-23420 as such:
    # And I should see "On waitlist" in the "Reserved (Max Manager)" "table_row"
    # And "Cancel reservation" "link" should exist in the "Reserved (Max Manager)" "table_row"
    And I should see "On waitlist" in the "table#facetoface_waitlist > tbody > tr:nth-child(2)" "css_element"

    And I switch to "Attendees" tab
    When I set the field "Attendee actions" to "Remove users"
    And I set the field "Current attendees" to "Sam3 Student3, student3@example.com"
    And I press "Remove"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Bulk remove users success"
    And I should not see "Sam3 Student3"
    But I should see "Booked" in the "Sam1 Student1" "table_row"

    When I switch to "Wait-list" tab
    And I should see "On waitlist" in the "table#facetoface_waitlist > tbody > tr:first-child" "css_element"
    When I switch to "Cancellations" tab
    Then I should see "User Cancelled" in the "Sam3 Student3" "table_row"

    When I follow "View all events"
    Then the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Session times | Booked | Booked        |
      | 1 February    | 1 / 1  | 1 on waitlist |
    But the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Session times | Booked     |
      | 1 February    | Overbooked |
    And I click on the seminar event action "Attendees" in row "1 February"

    When I set the field "Attendee actions" to "Remove users"
    And I set the field "Current attendees" to "Sam1 Student1, student1@example.com"
    And I press "Remove"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Bulk remove users success"
    And I should not see "Sam1 Student1"
    # The following step must be fixed in TL-23420
    But I should see "Booked" in the "table#facetoface_sessions > tbody > tr:first-child" "css_element"

    When I follow "View all events"
    Then the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Session times | Booked |
      | 1 February    | 1 / 1  |
    But the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Session times | Booked     | Booked      |
      | 1 February    | Overbooked | on waitlist |
    And I click on the seminar event action "Attendees" in row "1 February"

    When I switch to "Cancellations" tab
    Then I should see "User Cancelled" in the "Sam1 Student1" "table_row"
    And I should see "User Cancelled" in the "Sam3 Student3" "table_row"
