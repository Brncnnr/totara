@mod @mod_facetoface @javascript
Feature: Take attendance tracking general
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | course1  | course1   | 0        |
    And the following "users" exist:
      | username  | firstname | lastname | email         | idnumber |
      | kianbomba | kian      | bomba    | k@example.com | bomba    |
      | bolobala  | bolo      | bala     | b@example.com | bolo     |
      | kian      | loc       | nguyen   | l@example.com | loc      |
    And the following "course enrolments" exist:
      | user      | course  | role    |
      | kianbomba | course1 | student |
      | bolobala  | course1 | student |
      | kian      | course1 | student |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name      | course  | attendancetime | sessionattendance | eventgradingmanual |
      | seminar 1 | course1 | 0              | 4                 | 1                  |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | seminar 1  | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                   | finish                  |
      | event 1      | now -2 days -30 minutes | now -2 days +30 minutes |
      | event 1      | now +2 days             | now +2 days +60 minutes |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user      | eventdetails |
      | kianbomba | event 1      |
      | bolobala  | event 1      |
      | kian      | event 1      |
    And I log in as "admin"

  # Expect to not able to take attendance here, because all the sessions are not finished yet,
  # and the mark attendance time is set to end time
  Scenario: Take attendance tracking for event when mark attendance tracking is set for end time
    Given I am on "course1" course homepage
    And I follow "seminar 1"
    And I click on the seminar event action "Attendees" in row "#1"
    When I follow "Take attendance"
    Then I should see "2 session(s) (1 upcoming; 1 over)"
    And the "and mark as" "select" should be disabled
    And the "bolo bala's attendance" "select" should be disabled
    And the "kian bomba's attendance" "select" should be disabled
    And the "loc nguyen's attendance" "select" should be disabled
    And the "bolo bala's event grade" "field" should be disabled
    And the "kian bomba's event grade" "field" should be disabled
    And the "loc nguyen's event grade" "field" should be disabled
    And I should see "2" in the "bolo bala" "table_row"
    And I should see "2" in the "kian bomba" "table_row"
    And I should see "2" in the "loc nguyen" "table_row"

  Scenario: Take attendance tracking for session when mark attendance tracking is set for end time
    Given I am on "course1" course homepage
    And I follow "seminar 1"
    And I click on the seminar event action "Attendees" in row "#1"
    And I follow "Take attendance"
    And I set the field "Take attendance:" to "1"
    And I set the field "bolo bala's attendance" to "Partially attended"
    When I click on "Save attendance" "button"
    Then I should see "Successfully updated attendance"
    And the following fields match these values:
      | bolo bala's attendance  | Partially attended |
      | kian bomba's attendance | Not set            |
      | loc nguyen's attendance | Not set            |
    When I set the field "Take attendance:" to "0"
    Then I should see "1" in the "bolo bala" "table_row"
    And I should see "2" in the "kian bomba" "table_row"
    And I should see "2" in the "loc nguyen" "table_row"
    And the following fields match these values:
      | kian bomba's attendance | Not set |
      | bolo bala's attendance  | Not set |
      | loc nguyen's attendance | Not set |

  Scenario: Take attendance tracking for event when mark attendance tracking is set for any time
    Given I am on "course1" course homepage
    And I follow "seminar 1"
    And I follow "Edit settings"
    And I set the field "Event attendance" to "2"
    And I click on "Save and display" "button"
    And I click on the seminar event action "Attendees" in row "#1"
    And I follow "Take attendance"
    And I set the field "bolo bala's attendance" to "Partially attended"
    When I click on "Save attendance" "button"
    Then I should see "Successfully updated attendance"
    And the following fields match these values:
      | bolo bala's attendance  | Partially attended |
      | kian bomba's attendance | Not set            |
      | loc nguyen's attendance | Not set            |

  Scenario: Take attendance tracking for event, when session attendance is not enabled
    Given I am on "course1" course homepage
    And I follow "seminar 1"
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Event attendance            | 2 |
      | Session attendance tracking | 0 |
    And I click on "Save and display" "button"
    And I click on the seminar event action "Attendees" in row "#1"
    When I follow "Take attendance"
    Then I should not see "2 session(s) (1 upcoming; 1 over)"
    And "Take attendance" "field" should not exist

    And I should not see "2" in the "bolo bala" "table_row"
    And I should not see "2" in the "kian bomba" "table_row"
    And I should not see "2" in the "loc nguyen" "table_row"

    And I set the field "Select learners" to "All"
    And I set the field "and mark as" to "Fully attended"
    When I click on "Save attendance" "button"
    Then I should see "Successfully updated attendance"
    And the following fields match these values:
      | bolo bala's attendance  | Fully attended |
      | kian bomba's attendance | Fully attended |
      | loc nguyen's attendance | Fully attended |
