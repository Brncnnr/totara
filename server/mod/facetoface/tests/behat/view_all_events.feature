@mod @mod_facetoface @totara @javascript
Feature: Check previous and upcomings sections are right populated
  In order to see if all events are in their right section (previous and upcomings)
  As admin
  I need to create sessions with different status

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                 | Test seminar in progress |
      | Description                          | Test seminar in progress |
      | How many times the user can sign-up? | Unlimited                |
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | -2               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | -2               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 0                |
    And I click on "OK" "button" in the "Select date" "totaradialogue"

    And I press "Add a new session"
    And I follow "show-selectdate1-dialog"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | +1               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | +1               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 0                |
    And I click on "OK" "button" in the "Select date" "totaradialogue"

    And I press "Add a new session"
    And I follow "show-selectdate2-dialog"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | +2               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | +2               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 0                |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"

    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 1999 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 1999 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"

    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2037 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2037 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I log out

  Scenario: Check upcoming and previous events are displayed accordingly
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see "In progress" in the "mod_facetoface_upcoming_events_table" "table"
    And I should see "1 January 2037" in the "mod_facetoface_upcoming_events_table" "table"
    And I should see "1 January 1999" in the "mod_facetoface_past_events_table" "table"

    When I follow "C1"
    Then I should see "In progress"
    And I should see "1 January 2037"
    And I should not see "1 January 1999"

    # Sign up for a session and make sure it is displayed in the course page.
    And I click on "Go to event" "link" in the "1 January 2037" "table_row"
    And I press "Sign-up"
    When I follow "C1"
    Then I should see "Booked"
    And I should not see "In progress"
    And I should not see "Over"
    And I follow "View all events"
    Then I should see "Booked"
    And I should see "In progress"
    And I should see "Over"
    And I log out

    # Change sign up for multiple events setting.
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I set the field "How many times the user can sign-up?" to "1"
    And I press "Save and return to course"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "1 January 2037"
    And I should not see "1 January 1999"
    And I should not see "In progress"
    And I log out

  Scenario: Check Event details are displayed accordingly for editingteacher role
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the seminar event action "Attendees" in row "#1"
    When I follow "Event details"
    Then I should see "In progress"
