@mod @mod_facetoface @javascript
Feature: Viewing take attendance page with multiple seminar sessions
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | c101     | c101      | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email         | idnumber |
      | bomba    | kian      | bomba    | a@example.com | loc      |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | bomba | c101   | student |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name      | course | sessionattendance |
      | seminar1  | c101   | 4                 |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details  |
      | seminar1   | event101 |
      | seminar1   | event102 |
    # We need more than two events, so that the session dates are not too following the sequence like
    # 1, 2 and 3 within event101.
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                   | finish                  |
      | event102     | now +2 hours            | now +3 hours            |
      | event102     | now +4 hours            | now +5 hours            |
      | event101     | now -2 days -30 minutes | now -2 days +30 minutes |
      | event101     | now -1 days -30 minutes | now -1 days +30 minutes |
      | event101     | now -2 hours            | now -1 hours            |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
        | user  | eventdetails |
        | bomba | event101     |
    And I log in as "admin"

  Scenario: Viewing session attendance
    Given I am on "c101" course homepage
    And I follow "seminar1"
    And I click on the seminar event action "Attendees" in row "Over"
    And I follow "Take attendance"
    And the field "kian bomba's attendance" matches value "Not set"

    When I set the field "Take attendance:" to "5"
    Then the field "kian bomba's attendance" matches value "Not set"
    And I set the field "kian bomba's attendance" to "Partially attended"
    And I click on "Save attendance" "button"

    When I set the field "Take attendance:" to "4"
    Then the field "kian bomba's attendance" matches value "Not set"
    And I set the field "kian bomba's attendance" to "Fully attended"
    And I click on "Save attendance" "button"

    When I set the field "Take attendance:" to "3"
    Then the field "kian bomba's attendance" matches value "Not set"
    And I set the field "kian bomba's attendance" to "Unable to attend"
    And I click on "Save attendance" "button"

    When I set the field "Take attendance" to "5"
    Then the field "kian bomba's attendance" matches value "Partially attended"

    When I set the field "Take attendance" to "3"
    Then the field "kian bomba's attendance" matches value "Unable to attend"

    When I set the field "Take attendance" to "4"
    Then the field "kian bomba's attendance" matches value "Fully attended"
