@mod @totara @mod_facetoface
Feature: Add a seminar with select position
  In order to run a seminar
  As a teacher
  I need to create a seminar activity

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
      | student1 | Sam1      | Student1 | student1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I set the following fields to these values:
      | Select job assignment on signup | 1 |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Add and configure a seminar activity with a single session and position asked for but not mandated then sign up as user with no pos
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
      | Select job assignment on signup | 1    |
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 1    |
      | timestart[month]     | 1    |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 11   |
      | timestart[minute]    | 0    |
      | timefinish[day]      | 1    |
      | timefinish[month]    | 1    |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 12   |
      | timefinish[minute]   | 0    |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I should see date "1 January next year" formatted "%d %B %Y"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I press "Sign-up"
    Then I should see "Your request was accepted"

  @javascript
  Scenario: Add and configure a seminar activity with a single session and position asked for but not mandated then sign in as user with two positions and check attendee list reflects this and the selected position can be updated
    Given the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
      | FW001     | POS002   | Position2  |
    And the following job assignments exist:
      | user     | position |
      | student1 | POS001   |
      | student1 | POS002   |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
      | Select job assignment on signup | 1    |
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 1    |
      | timestart[month]     | 1    |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 11   |
      | timestart[minute]    | 0    |
      | timefinish[day]      | 1    |
      | timefinish[month]    | 1    |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 12   |
      | timefinish[minute]   | 0    |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I should see date "1 January next year" formatted "%d %B %Y"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I set the following fields to these values:
      | Select a job assignment | Unnamed job assignment (ID: 2) (Position2) |
    And I press "Sign-up"
    Then I should see "Your request was accepted"
    And I follow "View all events"
    When I click on "Go to event" "link" in the "(Booked)" "table_row"
    Then I should see "Job assignment"
    And I should see "Unnamed job assignment (ID: 2)"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I should see "Position2"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I should see "Position2"
    And I click on ".attendee-edit-job-assignment" "css_element"
    And I set the following fields to these values:
      | Select a job assignment | Unnamed job assignment (ID: 1) (Position1) |
    And I press "Update job assignment"
    And I should see "Position1"

  @javascript
  Scenario: Add and configure a seminar activity with a single session and position asked for and mandated then try to sign up as user with no pos
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
      | Select job assignment on signup | 1    |
      | Prevent signup if no job assignment is selected or can be found | 1 |
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 1    |
      | timestart[month]     | 1    |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 11   |
      | timestart[minute]    | 0    |
      | timefinish[day]      | 1    |
      | timefinish[month]    | 1    |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 12   |
      | timefinish[minute]   | 0    |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I should see date "1 January next year" formatted "%d %B %Y"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should see "You must have a suitable job assignment to sign up for this seminar activity"

  @javascript
  Scenario: Add and configure a seminar activity with a single session and position asked for then sign in as user with two positions and check user shown to correct manager.
    Given the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
      | FW001     | POS002   | Position2  |
    And the following job assignments exist:
      | user     | position |
      | student1 | POS001   |
      | student1 | POS002   |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
      | Select job assignment on signup | 1    |
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 1    |
      | timestart[month]     | 1    |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 11   |
      | timestart[minute]    | 0    |
      | timefinish[day]      | 1    |
      | timefinish[month]    | 1    |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 12   |
      | timefinish[minute]   | 0    |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I should see date "1 January next year" formatted "%d %B %Y"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I set the following fields to these values:
      | Select a job assignment | Unnamed job assignment (ID: 2) (Position2) |
    And I press "Sign-up"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    Then I should see "Position2"
    And I log out

    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname     | shortname           | source              |
      | F2F sessions | report_f2f_sessions | facetoface_sessions |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "F2F sessions"
    And I switch to "Columns" tab
    And I add the "Position on sign up" column to the report

    When I navigate to my "F2F sessions" report
    Then I should see "Position2"
