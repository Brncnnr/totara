@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation basic
  After seminar events have been created
  As a user
  I need to be able to cancel them.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner1 | C1     | student        |
      | learner2 | C1     | student        |

    And the following "seminars" exist in "mod_facetoface" plugin:
      | name         | intro               | course  |
      | Test Seminar | <p>Test Seminar</p> | C1      |

    Given I log in as "teacher1"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_100: cancel event with single future date, with attendees and confirm booking status.
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity |
      | Test Seminar | event 1 | 39       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish               | starttimezone    | finishtimezone   | sessiontimezone  |
      | event 1      | 10 Feb next year 9am | 10 Feb next year 3pm | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | learner1 | event 1      | booked |
      | learner2 | event 1      | booked |

    When I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    Then I should see date "10 February next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "10 February" "table_row"
    And I should see "2 / 39" in the "10 February" "table_row"
    And I should see "Booking open" in the "10 February" "table_row"

    When I click on the seminar event action "Cancel event" in row "10 February"
    Then I should see "Cancelling event in Test Seminar"
    And I should see date "10 February next year" formatted "%d %B %Y, 9:00 AM - 3:00 PMTimezone: Pacific/Auckland"

    When I press "No"
    Then I should see date "10 February next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "10 February" "table_row"
    And I should see "2 / 39" in the "10 February" "table_row"
    And I should see "Booking open" in the "10 February" "table_row"

    When I click on the seminar event action "Cancel event" in row "10 February"
    And I press "Yes"
    Then I should see date "10 February next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "10 February" "table_row"
    And I should see "2 / 39" in the "10 February" "table_row"
    And I should see "Cancelled" in the "10 February" "table_row"
    And I should not see "Go to event" in the "10 February" "table_row"
    And I should not see the seminar event action "Cancel event" in row "10 February"
    And I should see the seminar event action "Copy event" in row "10 February"
    And I should see the seminar event action "Delete event" in row "10 February"
    And I should not see the seminar event action "Edit event" in row "10 February"

    And I navigate to "Events report" node in "Site administration > Seminars"
    And I should see "N/A" in the ".session_bookingstatus div span" "css_element"
    And I should see "N/A" in the "Test Seminar" "table_row"

    When I log out
    And I log in as "teacher1"
    And I am on "Test Seminar" seminar homepage
    Then I should see date "10 February next year" formatted "%d %B %Y, 9:00 AM - 3:00 PM" in the "Timezone: Pacific/Auckland" "table_row"
    And I should see date "10 February next year" formatted "%d %B %Y" in the "2 / 39" "table_row"
    And I should see date "10 February next year" formatted "%d %B %Y" in the "Cancelled" "table_row"
    And I should not see "Go to event" in the "10 February" "table_row"
    And I should not see the seminar event action "Cancel event" in row "10 February"
    And I should see the seminar event action "Copy event" in row "10 February"
    And I should see the seminar event action "Delete event" in row "10 February"
    And I should not see the seminar event action "Edit event" in row "10 February"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_101: cancel event with multiple future dates, with attendees.
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity |
      | Test Seminar | event 1 | 39       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish               | starttimezone    | finishtimezone   | sessiontimezone  |
      | event 1      | 10 Feb next year 9am | 10 Feb next year 3pm | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |
      | event 1      | 11 Mar +2 years 10am | 11 Mar +2 years 4pm  | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | learner1 | event 1      | booked |
      | learner2 | event 1      | booked |
    And I am on "Test Seminar" seminar homepage

    Then I should see date "10 February next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "10 February" "table_row"
    And I should see date "11 March +2 years" formatted "%d %B %Y" in the "10:00 AM - 4:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "11 March" "table_row"
    And I should see "2 / 39" in the "10 February" "table_row"
    And I should see "Booking open" in the "10 February" "table_row"

    When I click on the seminar event action "Cancel event" in row "10 February"
    And I press "Yes"
    Then I should see date "10 February next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "10 February" "table_row"
    And I should see date "11 March +2 years" formatted "%d %B %Y" in the "10:00 AM - 4:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "11 March" "table_row"
    And I should see "2 / 39" in the "10 February" "table_row"
    And I should see "Cancelled" in the "10 February" "table_row"
    And I should not see "Go to event" in the "10 February" "table_row"
    And I should not see the seminar event action "Cancel event" in row "10 February"
    And I should see the seminar event action "Copy event" in row "10 February"
    And I should see the seminar event action "Delete event" in row "10 February"
    And I should not see the seminar event action "Edit event" in row "10 February"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_102: cancel event with future and past dates, with attendees.
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity |
      | Test Seminar | event 1 | 39       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish               | starttimezone    | finishtimezone   | sessiontimezone  |
      | event 1      | 10 Feb next year 9am | 10 Feb next year 3pm | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |
      | event 1      | -10 days 10am        | -10 days 4pm         | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | learner1 | event 1      | booked |
      | learner2 | event 1      | booked |

    When I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    Then I should see date "10 February next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "10 February" "table_row"
    And I should see date "-10 day Pacific/Auckland" formatted "%d %B %Y"
    And I should see "Timezone: Pacific/Auckland" in the "10:00 AM - 4:00 PM" "table_row"
    And I should see "2 / 39" in the "10:00 AM - 4:00 PM" "table_row"
    And I should see "In progress" in the "10:00 AM - 4:00 PM" "table_row"
    And I should not see the seminar event action "Cancel event" in row "10:00 AM - 4:00 PM"
    And I should see the seminar event action "Edit event" in row "10:00 AM - 4:00 PM"
    And I should see the seminar event action "Copy event" in row "10:00 AM - 4:00 PM"
    And I should see the seminar event action "Delete event" in row "10:00 AM - 4:00 PM"

    When I log out
    And I log in as "teacher1"
    And I am on "Test Seminar" seminar homepage
    Then I should see date "10 February next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "10 February" "table_row"
    And I should see date "-10 day Pacific/Auckland" formatted "%d %B %Y"
    And I should see "Timezone: Pacific/Auckland" in the "10:00 AM - 4:00 PM" "table_row"
    And I should see "2 / 39" in the "10:00 AM - 4:00 PM" "table_row"
    And I should see "In progress" in the "10:00 AM - 4:00 PM" "table_row"
    And I should not see the seminar event action "Cancel event" in row "10:00 AM - 4:00 PM"
    And I should see the seminar event action "Edit event" in row "10:00 AM - 4:00 PM"
    And I should see the seminar event action "Copy event" in row "10:00 AM - 4:00 PM"
    And I should see the seminar event action "Delete event" in row "10:00 AM - 4:00 PM"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_103: cancel event with today and future dates, with attendees.
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity |
      | Test Seminar | event 1 | 39       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish               | starttimezone    | finishtimezone   | sessiontimezone  |
      | event 1      | 10 Feb next year 9am | 10 Feb next year 3pm | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |
      | event 1      | today 12:05am        | today 11:55pm        | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | learner1 | event 1      | booked |
      | learner2 | event 1      | booked |
    And I am on "Test Seminar" seminar homepage

    Then I should see date "10 February next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "10 February" "table_row"
    And I should see date "0 day Pacific/Auckland" formatted "%d %B %Y"
    And I should see "Timezone: Pacific/Auckland" in the "12:05 AM - 11:55 PM" "table_row"
    And I should see "2 / 39" in the "12:05 AM - 11:55 PM" "table_row"
    And I should see "In progress" in the "12:05 AM - 11:55 PM" "table_row"
    And I should not see the seminar event action "Cancel event" in row "12:05 AM - 11:55 PM"
    And I should see the seminar event action "Edit event" in row "12:05 AM - 11:55 PM"
    And I should see the seminar event action "Copy event" in row "12:05 AM - 11:55 PM"
    And I should see the seminar event action "Delete event" in row "12:05 AM - 11:55 PM"

    When I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    Then I should see date "10 February next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "10 February" "table_row"
    And I should see date "0 day Pacific/Auckland" formatted "%d %B %Y"
    And I should see "Timezone: Pacific/Auckland" in the "12:05 AM - 11:55 PM" "table_row"
    And I should see "2 / 39" in the "12:05 AM - 11:55 PM" "table_row"
    And I should see "In progress" in the "12:05 AM - 11:55 PM" "table_row"
    And I should not see the seminar event action "Cancel event" in row "12:05 AM - 11:55 PM"
    And I should see the seminar event action "Edit event" in row "12:05 AM - 11:55 PM"
    And I should see the seminar event action "Copy event" in row "12:05 AM - 11:55 PM"
    And I should see the seminar event action "Delete event" in row "12:05 AM - 11:55 PM"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_104: cancel event with today, in 1 hr, with attendees.
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity |
      | Test Seminar | event 1 | 39       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start   | finish   | starttimezone   | finishtimezone  | sessiontimezone |
      | event 1      | +1 hour | +2 hours | Australia/Perth | Australia/Perth | Australia/Perth |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | learner1 | event 1      | booked |
      | learner2 | event 1      | booked |
    And I am on "Test Seminar" seminar homepage

    Then I should see date "+1 hour Australia/Perth" formatted "%d %B %Y"
    And I should see "Booking open"
    And I should see "2 / 39" in the "Booking open" "table_row"
    And I should see the seminar event action "Cancel event" in row "2 / 39"

    When I click on the seminar event action "Cancel event" in row "2 / 39"
    And I press "Yes"
    Then I should see "2 / 39" in the "Cancelled" "table_row"
    And I should not see "Go to event" in the "Cancelled" "table_row"
    And I should not see the seminar event action "Cancel event" in row "Cancelled"
    And I should not see the seminar event action "Edit event" in row "Cancelled"
    And I should see the seminar event action "Copy event" in row "Cancelled"
    And I should see the seminar event action "Delete event" in row "Cancelled"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_105: cancel event with single past date with no attendees.
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity |
      | Test Seminar | event 1 | 39       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start    | finish   | starttimezone   | finishtimezone  | sessiontimezone |
      | event 1      | -2 hours | +2 hours | Australia/Perth | Australia/Perth | Australia/Perth |
    And I am on "Test Seminar" seminar homepage

    Then I should see date "-2 hours Australia/Perth" formatted "%d %B %Y"
    And I should not see "Event in progress"
    And I should see "In progress"
    And I should see "0 / 39" in the "In progress" "table_row"
    And I should not see the seminar event action "Cancel event" in row "In progress"
    And I should see the seminar event action "Edit event" in row "In progress"
    And I should see the seminar event action "Copy event" in row "In progress"
    And I should see the seminar event action "Delete event" in row "In progress"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_106: cancel event with single future date with no attendees.
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity |
      | Test Seminar | event 1 | 39       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start    | finish   | starttimezone    | finishtimezone   | sessiontimezone  |
      | event 1      | +10 days | +10 days | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |
    And I am on "Test Seminar" seminar homepage

    When I click on the seminar event action "Cancel event" in row "Booking open"
    And I press "Yes"
    Then I should see date "10 day Pacific/Auckland" formatted "%d %B %Y"
    And I should see "Cancelled" in the "0 / 39" "table_row"
    And I should not see "Go to event" in the "Cancelled" "table_row"
    And I should not see the seminar event action "Cancel event" in row "Cancelled"
    And I should see the seminar event action "Copy event" in row "Cancelled"
    And I should see the seminar event action "Delete event" in row "Cancelled"
    And I should not see the seminar event action "Edit event" in row "Cancelled"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_107: cancel and delete the whole seminar event
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity |
      | Test Seminar | event 1 | 20       |
      | Test Seminar | event 2 | 30       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start  | finish         |
      | event 1      | +1 day | +1 day +1 hour |
      | event 2      | +2 day | +2 day +1 hour |
    And I am on "Test Seminar" seminar homepage

    When I click on the seminar event action "Cancel event" in row "0 / 30"
    And I should see "Cancelling event in"
    And I should see "Cancelling this event will remove all of its booking, attendance and grade records. All attendees will be notified."
    And I press "Yes"
    Then I should see "Event cancelled" in the ".alert-success" "css_element"
    And I should see "Cancelled" in the "0 / 30" "table_row"
    And I should not see "Edit event" in the "0 / 30" "table_row"
    And I should see "Booking open" in the "0 / 20" "table_row"

    When I click on the seminar event action "Delete event" in row "0 / 30"
    And I should see "Deleting event in"
    And I press "Delete"
    Then I should not see "0 / 30"
    And I should see "0 / 20"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_108: cancel and clone cancelled event
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity |
      | Test Seminar | event 1 | 20       |
      | Test Seminar | event 2 | 30       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start  | finish         |
      | event 1      | +1 day | +1 day +1 hour |
      | event 2      | +2 day | +2 day +1 hour |
    And I am on "Test Seminar" seminar homepage

    When I click on the seminar event action "Cancel event" in row "0 / 30"
    And I should see "Cancelling event in"
    And I should see "Cancelling this event will remove all of its booking, attendance and grade records. All attendees will be notified."
    And I press "Yes"
    Then I should see "Event cancelled" in the ".alert-success" "css_element"
    And I should see "Cancelled" in the "0 / 30" "table_row"
    And I should not see "Edit event" in the "0 / 30" "table_row"
    And I should see "Booking open" in the "0 / 20" "table_row"

    When I click on the seminar event action "Copy event" in row "0 / 30"
    And I set the field "Maximum bookings" to "99"
    And I press "Save changes"
    Then I should see "Cancelled" in the "0 / 30" "table_row"
    And I should see "Booking open" in the "0 / 99" "table_row"
