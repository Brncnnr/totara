@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation attendees view
  After seminar events have been cancelled
  As an admin
  I still need to see attendee details

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
      | name         | course | intro               |
      | Test Seminar | C1     | <p>Test Seminar</p> |

    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details |
      | Test Seminar | event 1 |

    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish       | sessiontimezone  | starttimezone    | finishtimezone   |
      | event 1      | +10 days 1am | +10 days 2am | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |
      | event 1      | +1 day 10am  | +1 day 4pm   | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |

    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | learner1 | event 1      | booked |
      | learner2 | event 1      | booked |

    And I log in as "learner3"
    And I am on "Test Seminar" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I press "Sign-up"

    Given I log out
    And I log in as "learner1"
    And I am on "Test Seminar" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I click on "Cancel booking" "link_or_button" in the seminar event sidebar "Booked"
    And I wait "1" seconds
    And I click on "Cancel booking" "button" in the seminar event sidebar "Cancel booking"

    Given I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Cancel event" in row "10:00 AM - 4:00 PM"
    And I press "Yes"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_400: attendees "cancelled" tab view.
    When I click on the seminar event action "Attendees" in row "#1"
    And I should see the "Wait-list" tab is disabled
    And I should see the "Take attendance" tab is disabled
    And I should see "Cancellations" in the "li.active" "css_element"
    And I should see "User Cancelled" in the "Learner One" "table_row"
    And I should see "Event Cancelled" in the "Learner Two" "table_row"
    And I should see "Event Cancelled" in the "Learner Three" "table_row"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_401: attendees "message users" tab view.
    When I click on the seminar event action "Attendees" in row "#1"
    And I click on "Message users" "link"
    And I press "Discard message"
    And I click on "Cancellations" "link"
    Then I should see "User Cancelled" in the "Learner One" "table_row"
    And I should see "Event Cancelled" in the "Learner Two" "table_row"
    And I should see "Event Cancelled" in the "Learner Three" "table_row"

    When I click on "Message users" "link"
    And I set the following fields to these values:
      | User Cancelled - 1 user(s)  | 1                       |
      | Event Cancelled - 2 user(s) | 1                       |
      | Subject                     | It is ON again!!!!      |
      | Body                        | Read the subject line   |
    And I press "Send message"
    Then I should see "3 message(s) successfully sent to attendees"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_402: bulk export of learners in cancelled event
    # --------------------------------------------------------------------------
    # Unfortunately it is impossible to verify the contents of an exported file
    # using a generic Behat step. This is because the location to where the file
    # is downloaded depends on the the test environment eg browser and OS. So
    # the test justs checks for the existence of an "export" UI control and goes
    # no further.
    # --------------------------------------------------------------------------
    When I click on the seminar event action "Attendees" in row "#1"
    And I should see "Cancellations" in the "li.active" "css_element"
    Then I should see "Excel"
    And I should see "ODS"
    And I should see "CSV"
    And I should see "PDF landscape"
    And I should see "PDF portrait"
