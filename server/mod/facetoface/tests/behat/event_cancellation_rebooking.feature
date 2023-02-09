@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation rebooking
  After seminar events have been cancelled
  As a learner
  I need to be able to rebook events.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner1 | C1     | student        |
      | learner2 | C1     | student        |
      | learner3 | C1     | student        |

    And the following "seminars" exist in "mod_facetoface" plugin:
      | name         | intro               | course | multisignupamount |
      | Test Seminar | <p>Test Seminar</p> | C1     | 1                 |

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

    And I log in as "learner3"
    And I am on "Test Seminar" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I press "Sign-up"

    Given I log out

    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity |
      | Test Seminar | event 2 | 19       |

    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start               | finish              | starttimezone    | finishtimezone   | sessiontimezone  |
      | event 2      | 11 Mar +2 years 9am | 11 Mar +2 years 3pm | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |

    And I log in as "teacher1"
    And I am on "Test Seminar" seminar homepage

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_600: Mass rebooking after a cancelled event
    Given I click on the seminar event action "Attendees" in row "11 March"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Learner One, learner1@example.com,Learner Two, learner2@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"

    When I follow "View results"
    Then I should see "Learner sign-up limit for this seminar was reached" in the "Learner One" "table_row"
    And I should see "Learner sign-up limit for this seminar was reached" in the "Learner Two" "table_row"
    And I press "Cancel"

    Given I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Cancel event" in row "10 February"
    And I press "Yes"

    Given I log out
    And I log in as "teacher1"
    And I am on "Test Seminar" seminar homepage
    Then I should see "Booking open" in the "11 March" "table_row"
    And I should see "Cancelled" in the "10 February" "table_row"

    When I click on the seminar event action "Attendees" in row "11 March"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Learner One, learner1@example.com,Learner Two, learner2@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "View all events"
    Then I should see "2 / 19" in the "11 March" "table_row"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_601: Individual learner rebooking after a cancelled event
    Given I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Cancel event" in row "10 February"
    And I press "Yes"

    Given I log out
    And I log in as "learner1"
    And I am on "Test Seminar" seminar homepage
    Then I should see "Booking open" in the "11 March" "table_row"
    Then I should see "19" in the "11 March" "table_row"
    And I should see "Cancelled" in the "10 February" "table_row"

    When I click on "Go to event" "link" in the "11 March" "table_row"
    And I press "Sign-up"
    Then I should see "Your request was accepted"
    And I follow "View all events"
    Then I should see "Booked" in the "11 March" "table_row"
    And I should see "18" in the "11 March" "table_row"

    Given I log out
    And I log in as "learner2"
    And I am on "Test Seminar" seminar homepage
    Then I should see "Booking open" in the "11 March" "table_row"
    Then I should see "18" in the "11 March" "table_row"
    And I should see "Cancelled" in the "10 February" "table_row"

    When I click on "Go to event" "link" in the "11 March" "table_row"
    And I press "Sign-up"
    Then I should see "Your request was accepted"
    And I follow "View all events"
    Then I should see "Booked" in the "11 March" "table_row"
    And I should see "17" in the "11 March" "table_row"

    Given I log out
    And I log in as "learner3"
    And I am on "Test Seminar" seminar homepage
    Then I should see "Booking open" in the "11 March" "table_row"
    Then I should see "17" in the "11 March" "table_row"
    And I should see "Cancelled" in the "10 February" "table_row"

    When I click on "Go to event" "link" in the "11 March" "table_row"
    And I press "Sign-up"
    Then I should see "Your request was accepted"
    And I follow "View all events"
    Then I should see "Booked" in the "11 March" "table_row"
    And I should see "16" in the "11 March" "table_row"
