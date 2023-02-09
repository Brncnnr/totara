@mod @mod_facetoface @javascript
Feature: Verify what users on waitlist can be seen in the seminar event dashboard
  Ported mod_facetoface_waitlist_event_testcase

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                 |
      | learner1 | learner   | one      | learner1@example.com  |
      | learner2 | learner   | two      | learner2@example.com  |
      | learner3 | learner   | three    | learner3@example.com  |
      | learner4 | learner   | four     | learner4@example.com  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name      | course | decluttersessiontable |
      | Seminar 1 | C1     | 1                     |

  Scenario: test_rendering_f2f_waitlist_event_with_booked_users
    # Test suite of checking the whether the render is rendering correctly a wait-listed seminar event that has
    # a user as booked along side with the users that have wait-listed status. As a result, the test should only expects
    # one user as waitlisted,not two, even though the event is a wait-listed event
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
      | learner2 | C1     | student |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details | capacity |
      | Seminar 1  | event 1 | 10       |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user      | eventdetails | status     |
      | learner1  | event 1      | booked     |
      | learner2  | event 1      | waitlisted |

    Given I log in as "admin"
    When I am on "Seminar 1" seminar homepage
    Then I should see "1 / 10" in the "mod_facetoface_upcoming_events_table" "table"
    And I should see "1 on waitlist" in the "mod_facetoface_upcoming_events_table" "table"

  Scenario: test_rendering_f2f_waitlist_event
    # Test suite of rendering the event with only wait-listed user
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
      | learner2 | C1     | student |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details | capacity |
      | Seminar 1  | event 1 | 10       |

    Given I log in as "admin"
    When I am on "Seminar 1" seminar homepage
    Then I should see "0 / 10" in the "mod_facetoface_upcoming_events_table" "table"
    And I should not see "on waitlist" in the "mod_facetoface_upcoming_events_table" "table"

    When the following "seminar signups" exist in "mod_facetoface" plugin:
      | user      | eventdetails | status     |
      | learner1  | event 1      | waitlisted |
      | learner2  | event 1      | waitlisted |
    And I reload the page
    Then I should see "0 / 10" in the "mod_facetoface_upcoming_events_table" "table"
    And I should see "2 on waitlist" in the "mod_facetoface_upcoming_events_table" "table"

  Scenario: test_rendering_f2f_overbooked_waitlist_event
    # Test suite of rendering the event with wait-listed user and the event is overbooked
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
      | learner2 | C1     | student |
      | learner3 | C1     | student |
      | learner4 | C1     | student |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details | capacity |
      | Seminar 1  | event 1 | 2        |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user      | eventdetails | status     |
      | learner1  | event 1      | waitlisted |
      | learner2  | event 1      | booked     |
      | learner3  | event 1      | booked     |
      | learner4  | event 1      | booked     |

    Given I log in as "admin"
    When I am on "Seminar 1" seminar homepage
    Then I should see "3 / 2" in the "mod_facetoface_upcoming_events_table" "table"
    And I should see "(Overbooked)" in the "mod_facetoface_upcoming_events_table" "table"
    And I should see "1 on waitlist" in the "mod_facetoface_upcoming_events_table" "table"
