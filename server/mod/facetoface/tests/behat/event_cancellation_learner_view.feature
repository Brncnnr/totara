@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation learner views
  After seminar events have been cancelled
  As a learner
  I need to see cancellation summaries

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner2@example.com |
      | learner4 | Learner   | Four     | learner2@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner1 | C1     | student        |
      | learner2 | C1     | student        |
      | learner3 | C1     | student        |
      | learner4 | C1     | student        |

    And the following "seminars" exist in "mod_facetoface" plugin:
      | name         | intro               | course |
      | Test Seminar | <p>Test Seminar</p> | C1     |

    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity |
      | Test Seminar | event 1 | 10       |
      | Test Seminar | event 2 | 10       |
      | Test Seminar | event 3 | 29       |

    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish               | sessiontimezone  | starttimezone    | finishtimezone   |
      | event 1      | 10 Feb +1 year 9am   | 10 Feb +1 year 3pm   | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |
      | event 2      | 10 Mar +2 years 10am | 10 Mar +2 years 4pm  | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |
      | event 3      | 10 Apr 2037 5pm      | 10 Apr 2037 6pm      | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |

    Given I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_roles" "css_element"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_rolesnotify" "css_element"
    And I press "Save changes"
    And I log out

    Given I log in as "teacher1"
    And I am on "Test Seminar" seminar homepage

    Given I click on the seminar event action "Edit event" in row "February"
    And I click on "Teacher One" "checkbox"
    And I press "Save changes"

    Given I click on the seminar event action "Edit event" in row "March"
    And I click on "Teacher One" "checkbox"
    And I press "Save changes"

    Given I click on the seminar event action "Edit event" in row "April"
    And I click on "Teacher One" "checkbox"
    And I press "Save changes"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_300: cancelled booking (course view).
    Given I click on the seminar event action "Attendees" in row "10 February"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Learner One, learner1@example.com,Learner Two, learner2@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "View all events"

    Given I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Cancel event" in row "10 February"
    And I press "Yes"

    When I log out
    And I log in as "learner1"
    And I am on "Test Seminar" seminar homepage
    Then I should see "Timezone: Pacific/Auckland" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see date "10 Feb next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Cancelled" in the "9:00 AM - 3:00 PM" "table_row"
    And I should not see "Go to event" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "10:00 AM - 4:00 PM" "table_row"
    And I should see date "10 Mar +2 years" formatted "%d %B %Y" in the "10:00 AM - 4:00 PM" "table_row"
    And I should see "Booking open" in the "10:00 AM - 4:00 PM" "table_row"
    When I click on "Go to event" "link" in the "10:00 AM - 4:00 PM" "table_row"
    Then I should see "Sign-up" in the ".mod_facetoface__eventinfo__sidebar__signup" "css_element"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_301: cancelled booking (future bookings view).
    Given I click on the seminar event action "Attendees" in row "10 February"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Learner One, learner1@example.com,Learner Two, learner2@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "View all events"

    Given I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Cancel event" in row "10 February"
    And I press "Yes"

    When I log out
    And I log in as "learner1"
    And I am on "Dashboard" page
    And I click on "Bookings" "link"
    Then I should see "Course 1" in the "Test Seminar" "table_row"
    And I should see date "10 Feb next year" formatted "%d %B %Y" in the "Test Seminar" "table_row"
    And I should see "9:00 AM" in the "Test Seminar" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "9:00 AM" "table_row"
    And I should see "3:00 PM" in the "Test Seminar" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "3:00 PM" "table_row"
    And I should see "Event Cancelled" in the "Test Seminar" "table_row"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_302: cancelled booking (past bookings view).
    Given I click on the seminar event action "Attendees" in row "10 April 2037"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Learner One, learner1@example.com,Learner Two, learner2@example.com,Learner Three, learner2@example.com,Learner Four, learner2@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "View all events"
    And I click on the seminar event action "Cancel event" in row "10 April 2037"
    And I press "Yes"

    # Magic needed here since only a future event can be cancelled and we don't
    # want to wait until that future time comes.
    Given I use magic to adjust the seminar event "start" from "10/04/2037 17:00" "Pacific/Auckland" to "10/04/2015 09:00"
    And I use magic to adjust the seminar event "end" from "10/04/2037 18:00" "Pacific/Auckland" to "10/04/2015 14:00"
    And I log out

    And I log in as "learner1"
    And I am on "Dashboard" page
    And I click on "Bookings" "link"
    And I click on "Past Bookings" "link"
    Then I should see "Course 1" in the "Test Seminar" "table_row"
    And I should see "10 April 2015" in the "Test Seminar" "table_row"
    And I should see "9:00 AM" in the "Test Seminar" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "9:00 AM" "table_row"
    And I should see "2:00 PM" in the "Test Seminar" "table_row"
    And I should see "Timezone: Pacific/Auckland" in the "2:00 PM" "table_row"
    And I should see "Event Cancelled" in the "Test Seminar" "table_row"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_303: remove cancelled sessions from learner views.
    Given I click on the seminar event action "Attendees" in row "10 February"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Learner One, learner1@example.com,Learner Two, learner2@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "View all events"

    Given I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Cancel event" in row "10 February"
    And I press "Yes"

    When I log out
    And I log in as "learner1"
    And I am on "Dashboard" page
    And I click on "Bookings" "link"

    # --------------------------------------------------------------------------
    # THIS PART WILL FAIL WITH THE CURRENT SEMINAR CANCELLATION CODE. This is
    # because there does not seem to be a way to enable this mechanism, as per
    # v1.6 specs (background section, 2nd para, #7) which says there should be a
    # way.
    # --------------------------------------------------------------------------
    Given I skip the scenario until issue "TL-9482" lands

    Then I should not see "Course 1" in the "Test Seminar" "table_row"
    And I should not see "10 February" in the "Test Seminar" "table_row"
    And I should not see "9:00 AM Pacific/Auckland" in the "Test Seminar" "table_row"
    And I should not see "Timezone: Pacific/Auckland" in the "9:00 AM" "table_row"
    And I should not see "3:00 PM Pacific/Auckland" in the "Test Seminar" "table_row"
    And I should not see "Timezone: Pacific/Auckland" in the "3:00 PM" "table_row"
    And I should not see "Event Cancelled" in the "Test Seminar" "table_row"
