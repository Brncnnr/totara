@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation reporting
  After seminar events have been cancelled
  As an admin
  I need to be able to generate reports.

  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
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
      | name         | intro               | course  |
      | Test Seminar | <p>Test Seminar</p> | C1      |

    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity |
      | Test Seminar | event 1 | 20       |

    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start         | finish       |
      | event 1      | +10 days 10am | +10 days 4pm |

    And I log in as "teacher1"
    And I am on "Test Seminar" seminar homepage

    Given I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Learner One, learner1@example.com,Learner Two, learner2@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "View all events"

    Given I log out
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
  Scenario: mod_facetoface_cancel_700: viewing "seminars: event attendees report"
    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Seminars: Event attendees"
    And I press "id_submitgroupstandard_addfilter"
    And I follow "Seminars: Event attendees"
    And I follow "View This Report"
    And I follow "To view the report, first select an event from the Number of Attendees column in the next report."
    Then I should see "Test Seminar" in the "Course 1" "table_row"
    And I should see "Course 1" in the "Test Seminar" "table_row"
    And I should see "20" in the "Test Seminar" "table_row"
    When I click on "View attendees" "link" in the "Test Seminar" "table_row"

    And I click on "Cancellations" "link"
    And I should see "User Cancelled" in the "Learner One" "table_row"
    And I should see "Event Cancelled" in the "Learner Two" "table_row"
    And I should see "Event Cancelled" in the "Learner Three" "table_row"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_701: using "seminar sign ups" source in custom report
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname                 | shortname                       | source              |
      | Custom test event report | report_custom_test_event_report | facetoface_sessions |
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "Custom test event report"
    And I switch to "Columns" tab
    And I set the field "newcolumns" to "Seminar Name"
    And I press "Add"
    And I set the field "newcolumns" to "Signup status"
    And I press "Add"
    And I press "Save changes"

    When I follow "View This Report"
    Then I should see "Course 1" in the "Learner One" "table_row"
    And I should see date "+10 days 10am Pacific/Auckland" formatted "%d %B %Y"
    And I should see "User Cancelled" in the "Learner One" "table_row"
    And I should see "Test Seminar" in the "Learner One" "table_row"

    And I should see "Course 1" in the "Learner Two" "table_row"
    And I should see date "+10 days 10am Pacific/Auckland" formatted "%d %B %Y"
    And I should see "Event Cancelled" in the "Learner Two" "table_row"
    And I should see "Test Seminar" in the "Learner Two" "table_row"

    And I should see "Course 1" in the "Learner Three" "table_row"
    And I should see date "+10 days 10am Pacific/Auckland" formatted "%d %B %Y"
    And I should see "Event Cancelled" in the "Learner Three" "table_row"
    And I should see "Test Seminar" in the "Learner Three" "table_row"
