@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation calendar views
  After seminar events have been cancelled
  Calendars should also be updated

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner1 | C1     | student        |

    Given I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_roles" "css_element"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_rolesnotify" "css_element"
    And I press "Save changes"
    And I log out

    And the following "seminars" exist in "mod_facetoface" plugin:
      | name         | course | intro               |
      | Test Seminar | C1     | <p>Test Seminar</p> |

    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details | capacity |
      | Test Seminar | event 1 | 29       |

    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start       | finish     | sessiontimezone | starttimezone   | finishtimezone  |
      | event 1      | +1 day 1am  | +1 day 2am | Australia/Perth | Australia/Perth | Australia/Perth |
      | event 1      | +1 day 10am | +1 day 4pm | Australia/Perth | Australia/Perth | Australia/Perth |

    Given I log in as "teacher1"
    And I am on "Test Seminar" seminar homepage
    Given I click on the seminar event action "Edit event" in row "0 / 29"
    And I click on "Teacher One" "checkbox"
    And I press "Save changes"

    Given I click on the seminar event action "Attendees" in row "0 / 29"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Learner One, learner1@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "View all events"


  Scenario: mod_facetoface_cancel_800: cancelled events removed from learner calendar.
    When I log out
    And I log in as "learner1"
    And I am on "Dashboard" page
    And I click on "Go to calendar" "link"
    Then I should see date "1 day Australia/Perth" formatted "%d %B %Y"
    Then I should see "Course 1"
    And I should see "10:00 AM - 4:00 PMTimezone: Australia/Perth"
    And I should see "Teacher One"

    Given I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Cancel event" in row "1 / 29"
    And I press "Yes"

    When I log out
    And I log in as "learner1"
    And I am on "Test Seminar" seminar homepage
    Then I should see date "1 day Australia/Perth" formatted "%d %B %Y"
    And I should see "Cancelled" in the "10:00 AM - 4:00 PM" "table_row"
    And "Go to event" "link" should not exist in the ".mod_facetoface__event-dashboard" "css_element"
    And "More actions" "button" should not exist in the ".mod_facetoface__event-dashboard" "css_element"

    When I am on "Dashboard" page
    And I click on "Go to calendar" "link"
    Then I should not see "Course 1"
    And I should not see "10:00 AM - 4:00 PM"
    And I should not see "Timezone: Australia/Perth"
    And I should not see "Editing Trainer Teacher One"
    And I should see "There are no upcoming events"


  Scenario: mod_facetoface_cancel_801: cancelled events removed from session role calendar.
    When I log out
    And I log in as "teacher1"
    And I am on "Dashboard" page
    And I click on "Go to calendar" "link"
    Then I should see date "1 day Australia/Perth" formatted "%d %B %Y"
    Then I should see "Course 1"
    And I should see "10:00 AM - 4:00 PMTimezone: Australia/Perth"
    And I should see "Teacher One"

    Given I log out
    And I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Cancel event" in row "1 / 29"
    And I press "Yes"

    When I log out
    And I log in as "teacher1"
    And I am on "Test Seminar" seminar homepage
    Then I should see date "1 day Australia/Perth" formatted "%d %B %Y"
    And I should see "Cancelled" in the "10:00 AM - 4:00 PM" "table_row"
    And "Go to event" "link" should not exist in the ".mod_facetoface__event-dashboard" "css_element"
    But "More actions" "button" should exist in the ".mod_facetoface__event-dashboard" "css_element"

    When I am on "Dashboard" page
    And I click on "Go to calendar" "link"
    Then I should not see "Course 1"
    And I should not see "10:00 AM - 4:00 PM"
    And I should not see "Australia/Perth"
    And I should not see "You are booked for this Seminar event"
    And I should not see "Editing Trainer Teacher One"

  Scenario: mod_facetoface_cancel_802: cancelled events do not re-create in calendar when seminar updated
    Given I am on "Test Seminar" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Description | Test Seminar Lorem ipsum dolor sit amet |
    And I press "Save and display"
    And I click on the seminar event action "Cancel event" in row "1 / 29"
    And I press "Yes"
    And I log out

    When I log in as "teacher1"
    And I am on "Test Seminar" seminar homepage
    Then I should see date "1 day Australia/Perth" formatted "%d %B %Y"
    And I should see "Cancelled" in the "10:00 AM - 4:00 PM" "table_row"
    And "Go to event" "link" should not exist in the ".mod_facetoface__event-dashboard" "css_element"
    But "More actions" "button" should exist in the ".mod_facetoface__event-dashboard" "css_element"

    When I am on "Dashboard" page
    And I click on "Go to calendar" "link"
    Then I should not see "Course 1"
    And I should not see "10:00 AM - 4:00 PM"
    And I should not see "Australia/Perth"
    And I should not see "You are booked for this Seminar event"
    And I should not see "Editing Trainer Teacher One"
