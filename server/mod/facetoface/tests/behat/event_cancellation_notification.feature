@mod @mod_facetoface @mod_facetoface_notification @totara @javascript
Feature: Seminar event cancellation notifications
  After seminar events have been cancelled
  As an learner
  I need to be notified of the cancellations

  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |
      | learner4 | Learner   | Four     | learner4@example.com |
      | manager4 | Manager   | Four     | manager4@example.com |

    And the following job assignments exist:
      | user     | manager  |
      | learner4 | manager4 |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "roles" exist:
      | name              | shortname         | archetype | contextlevel |
      | ReservationRole   | ReservationRole   |           | System       |

    And the following "permission overrides" exist:
      | capability                         | permission | role            | contextlevel | reference |
      | mod/facetoface:reservespace        | Allow      | ReservationRole | System       |           |
      | mod/facetoface:view                | Allow      | ReservationRole | System       |           |
      | mod/facetoface:viewcancellations   | Allow      | ReservationRole | System       |           |
      | mod/facetoface:viewemptyactivities | Allow      | ReservationRole | System       |           |
      | mod/facetoface:signup              | Allow      | ReservationRole | System       |           |

    Given the following "course enrolments" exist:
      | user     | course | role            |
      | teacher1 | C1     | editingteacher  |
      | learner1 | C1     | student         |
      | learner2 | C1     | student         |
      | learner3 | C1     | student         |
      | manager4 | C1     | ReservationRole |

    And the following "seminars" exist in "mod_facetoface" plugin:
      | name         | intro               | course | managerreserve | maxmanagerreserves | reservecanceldays |
      | Test Seminar | <p>Test Seminar</p> | C1     | 1              | 1                  | 0                 |

    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity | allowoverbook |
      | Test Seminar | event 1 | 2        | 1             |

    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish               | starttimezone    | finishtimezone   | sessiontimezone  |
      | event 1      | 10 Feb next year 9am | 10 Feb next year 3pm | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |

    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status     |
      | learner1 | event 1      | booked     |
      | learner2 | event 1      | booked     |
      | learner3 | event 1      | waitlisted |

    Given I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_roles" "css_element"
    And I click on "ReservationRole" "text" in the "#admin-facetoface_session_roles" "css_element"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_rolesnotify" "css_element"
    And I click on "ReservationRole" "text" in the "#admin-facetoface_session_rolesnotify" "css_element"
    And I press "Save changes"
    And I log out

    Given I log in as "teacher1"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Teacher One" "checkbox"
    And I click on "Manager Four" "checkbox"
    And I press "Save changes"
    And I log out

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_200: people notified of cancelled event with single future date.
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    Then I should see "Timezone: Pacific/Auckland" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see date "10 Feb next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "2 / 2" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Booking full" in the "2 / 2" "table_row"
    And I should see the seminar event action "Cancel event" in row "2 / 2"

    When I click on the seminar event action "Cancel event" in row "2 / 2"
    Then I should see "Cancelling event in Test Seminar"
    And I should see date "10 Feb next year" formatted "%d %B %Y, 9:00 AM - 3:00 PMTimezone: Pacific/Auckland"
    And I press "Yes"
    Then I should see "Timezone: Pacific/Auckland" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see date "10 Feb next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "3 / 2 (Overbooked)" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Cancelled" in the "9:00 AM - 3:00 PM" "table_row"
    And I should not see the seminar event action "Cancel event" in row "9:00 AM - 3:00 PM"
    And I run all adhoc tasks

    When I log out
    And I log in as "learner1"
    And I am on "Dashboard" page
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see date "10 Feb next year" formatted "%d %B %Y, 9:00 AM - %d %B %Y, 3:00 PM Pacific/Auckland"

    When I log out
    And I log in as "learner2"
    And I am on "Dashboard" page
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see date "10 Feb next year" formatted "%d %B %Y, 9:00 AM - %d %B %Y, 3:00 PM Pacific/Auckland"

    When I log out
    And I log in as "learner3"
    And I am on "Dashboard" page
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see date "10 Feb next year" formatted "%d %B %Y, 9:00 AM - %d %B %Y, 3:00 PM Pacific/Auckland"

    When I log out
    And I log in as "teacher1"
    And I am on "Dashboard" page
    Then I should see "Seminar event trainer cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see date "10 Feb next year" formatted "%d %B %Y, 9:00 AM - %d %B %Y, 3:00 PM Pacific/Auckland"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_201: people notified of cancelled event with multiple future dates.
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish              | starttimezone    | finishtimezone   | sessiontimezone  |
      | event 1      | 11 Mar +2 years 10am | 11 Mar +2 years 4pm | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |

    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    Then I should see "Timezone: Pacific/Auckland" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see date "10 Feb next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "10:00 AM - 4:00 PM" "table_row"
    And I should see date "11 Mar +2 years" formatted "%d %B %Y" in the "10:00 AM - 4:00 PM" "table_row"
    And I should see "Booking full" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see the seminar event action "Cancel event" in row "9:00 AM - 3:00 PM"

    When I click on the seminar event action "Cancel event" in row "Booking full"
    And I press "Yes"
    Then I should see "Timezone: Pacific/Auckland" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see date "10 Feb next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "10:00 AM - 4:00 PM" "table_row"
    And I should see date "11 Mar +2 years" formatted "%d %B %Y" in the "10:00 AM - 4:00 PM" "table_row"
    And I should see "3 / 2 (Overbooked)" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Cancelled" in the "9:00 AM - 3:00 PM" "table_row"
    And I should not see the seminar event action "Cancel event" in row "9:00 AM - 3:00 PM"
    And I run all adhoc tasks

    When I log out
    And I log in as "learner1"
    And I am on "Dashboard" page
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see date "10 Feb next year" formatted "%d %B %Y, 9:00 AM - %d %B %Y, 3:00 PM Pacific/Auckland"
    And I should see date "11 Mar +2 years" formatted "%d %B %Y, 10:00 AM - %d %B %Y, 4:00 PM Pacific/Auckland"

    When I log out
    And I log in as "learner2"
    And I am on "Dashboard" page
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see date "10 Feb next year" formatted "%d %B %Y, 9:00 AM - %d %B %Y, 3:00 PM Pacific/Auckland"
    And I should see date "11 Mar +2 years" formatted "%d %B %Y, 10:00 AM - %d %B %Y, 4:00 PM Pacific/Auckland"

    When I log out
    And I log in as "learner3"
    And I am on "Dashboard" page
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see date "10 Feb next year" formatted "%d %B %Y, 9:00 AM - %d %B %Y, 3:00 PM Pacific/Auckland"
    And I should see date "11 Mar +2 years" formatted "%d %B %Y, 10:00 AM - %d %B %Y, 4:00 PM Pacific/Auckland"

    When I log out
    And I log in as "teacher1"
    And I am on "Dashboard" page
    Then I should see "Seminar event trainer cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see date "10 Feb next year" formatted "%d %B %Y, 9:00 AM - %d %B %Y, 3:00 PM Pacific/Auckland"
    And I should see date "11 Mar +2 years" formatted "%d %B %Y, 10:00 AM - %d %B %Y, 4:00 PM Pacific/Auckland"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_202: deleting a cancelled event does not resend cancellation messages.
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    Then I should see "Timezone: Pacific/Auckland" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see date "10 Feb next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "2 / 2" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Booking full" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see the seminar event action "Cancel event" in row "9:00 AM - 3:00 PM"
    And I click on the seminar event action "Cancel event" in row "9:00 AM - 3:00 PM"
    And I should see "Cancelling event in Test Seminar"
    And I should see date "10 Feb next year" formatted "%d %B %Y, 9:00 AM - 3:00 PMTimezone: Pacific/Auckland"
    And I press "Yes"
    And I run all adhoc tasks

    When I log out
    And I log in as "learner1"
    And I am on "Dashboard" page

    When I click on "View all alerts" "link"
    And I click on "All" "link"
    And I press "Dismiss"
    And I click on "Dismiss" "button" in the "Dismiss" "totaradialogue"
    Then I should see "0 records shown"

    Given I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Delete event" in row "10 February"
    And I press "Delete"

    When I log out
    And I log in as "learner1"
    And I am on "Dashboard" page
    Then I should not see "Seminar event cancellation"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_203: manager with reservations notified of cancelled event.
    And I log in as "manager4"
    And I am on "Test Seminar" seminar homepage
    Then I should see "Timezone: Pacific/Auckland" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see date "10 Feb next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Booking full" in the "9:00 AM - 3:00 PM" "table_row"
    When I click on "Go to event" "link" in the "9:00 AM - 3:00 PM" "table_row"
    Then I should see "Reserve spaces for team (0/1)"

    When I follow "Reserve spaces for team"
    And I set the field "reserve" to "1*"
    And I press "Update"
    Then I should see "Reserve spaces for team (1/1)"

    Given I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Cancel event" in row "10 February"
    And I press "Yes"
    And I run all adhoc tasks

    When I log out
    And I log in as "manager4"
    And I am on "Test Seminar" seminar homepage
    Then I should see "Timezone: Pacific/Auckland" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see date "10 Feb next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Cancelled" in the "9:00 AM - 3:00 PM" "table_row"
    And I should not see "Go to event" in the "9:00 AM - 3:00 PM" "table_row"

    When I am on "Dashboard" page
    Then I should see "Seminar event trainer cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see date "10 Feb next year" formatted "%d %B %Y, 9:00 AM - %d %B %Y, 3:00 PM Pacific/Auckland"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_204: manager with allocations notified of cancelled event.
    And I log in as "manager4"
    And I am on "Test Seminar" seminar homepage
    Then I should see "Timezone: Pacific/Auckland" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see date "10 Feb next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Booking full" in the "9:00 AM - 3:00 PM" "table_row"
    When I click on "Go to event" "link" in the "9:00 AM - 3:00 PM" "table_row"
    Then I should see "Allocate spaces for team (0/1)"

    When I follow "Allocate spaces for team"
    And I set the field "Available team members" to "Learner Four"
    And I press "Add"
    Then I should see "Allocate spaces for team (1/1)"

    Given I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Cancel event" in row "10 February"
    And I press "Yes"
    And I run all adhoc tasks

    When I log out
    And I log in as "learner4"
    And I am on "Dashboard" page
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see date "10 Feb next year" formatted "%d %B %Y, 9:00 AM - %d %B %Y, 3:00 PM Pacific/Auckland"

    When I log out
    And I log in as "manager4"
    And I am on "Test Seminar" seminar homepage
    Then I should see "Timezone: Pacific/Auckland" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see date "10 Feb next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Cancelled" in the "9:00 AM - 3:00 PM" "table_row"
    And I should not see "Go to event" in the "9:00 AM - 3:00 PM" "table_row"

    When I am on "Dashboard" page
    Then I should see "Seminar event trainer cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see date "10 Feb next year" formatted "%d %B %Y, 9:00 AM - %d %B %Y, 3:00 PM Pacific/Auckland"
