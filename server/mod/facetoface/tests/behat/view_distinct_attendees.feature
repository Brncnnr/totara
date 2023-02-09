@mod @mod_facetoface @javascript
Feature: Viewing distinct attendees
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | course1  | course1   | 0        |
    And the following "course enrolments" exist:
     | user     | course   | role    |
     | user1    | course1  | student |
     | user2    | course1  | student |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name      | course  |
      | seminar 1 | course1 |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | seminar 1  | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start      | finish             |
      | event 1      | 2 days ago | 2 days ago +1 hour |
      | event 1      | yesterday  | yesterday +1 hour  |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user  | eventdetails |
      | user1 | event 1      |
      | user2 | event 1      |

    And I log in as "admin"
    And I am on "course1" course homepage
    And I follow "seminar 1"
    When I click on the seminar event action "Attendees" in row "#1"
    # Scenario: VDA: Attendees tab
    Then I should see "User One" exactly "1" times
    And I should see "User Two" exactly "1" times

  Scenario: VDA: Cancellations tab
    When I set the field "Attendee actions" to "Remove users"
    And I set the field "Current attendees" to "User One, user1@example.com,User Two, user2@example.com"
    And I press "Remove"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Bulk remove users success - Successfully removed 2 attendees"

    When I switch to "Cancellations" tab
    Then I should see "User One" exactly "1" times
    And I should see "User Two" exactly "1" times
