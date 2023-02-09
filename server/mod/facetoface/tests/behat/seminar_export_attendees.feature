@javascript @mod @mod_facetoface @totara
Feature: Export seminar attendees
  In order to test exporting of seminar attendees
  As admin
  I need add attendees to a seminar session and ensure export
  doesn't generate any errors

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | idnumber | email                |
      | student1 | Sam1      | Student1 | sid#1    | student1@example.com |
    And the following job assignments exist:
      | user     | fullname | idnumber |
      | student1 | job1     | ja1      |
      | student1 | job2     | ja2      |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | course |
      | Test seminar name | C1     |

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add event"
    And I follow "show-selectdate0-dialog"
    And I set the following fields to these values:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 10               |
      | timestart[month]    | 2                |
      | timestart[year]     | ## next year ## Y ## |
      | timestart[hour]     | 9                |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 10               |
      | timefinish[month]   | 2                |
      | timefinish[year]    | ## next year ## Y ## |
      | timefinish[hour]    | 15               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    When I press "Save changes"
    Then I should see "Pacific/Auckland" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see date "10 February next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I log out


  Scenario: Export seminar without job assignments on signup
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    When I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should not see "Job assignment"

    When I press "Sign-up"
    Then I should see "Your request was accepted"
    And I follow "View all events"

    When I click on "Go to event" "link" in the "(Booked)" "table_row"
    Then I should not see "Job assignment"
    And I log out

    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see date "10 February next year" formatted "%d %B %Y"

    When I click on the seminar event action "Attendees" in row "#1"
    Then I should not see "Job assignment"

    When I follow "View all events"
    And I set the field "Export format" to "Excel"
    And I click on "Export to file" "button"
    Then I should not see "Can not find data record in database"
    And I log out

  Scenario: Export seminar with only global job assignments on signup
    Given I log in as "admin"
    And I set the following administration settings values:
      | facetoface_selectjobassignmentonsignupglobal | 1 |
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    When I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should not see "Select a job assignment"

    When I press "Sign-up"
    Then I should see "Your request was accepted"
    And I follow "View all events"

    When I click on "Go to event" "link" in the "(Booked)" "table_row"
    Then I should not see "Job assignment"
    And I log out

    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see date "10 February next year" formatted "%d %B %Y"

    When I click on the seminar event action "Attendees" in row "#1"
    Then "//th[contains(@class, 'session_positionnameedit')]/a[contains(.,'Job assignment')]" "xpath_element" should exist
    And I should not see "job1" in the "Sam1 Student1" "table_row"

    When I follow "View all events"
    And I set the field "Export format" to "Excel"
    And I click on "Export to file" "button"
    Then I should not see "Can not find data record in database"
    And I log out

  Scenario: Export seminar without global and session job assignments on signup
    Given I log in as "admin"
    And I set the following administration settings values:
      | facetoface_selectjobassignmentonsignupglobal | 1 |
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Select job assignment on signup | 1 |
    And I press "Save and display"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    When I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should see "Select a job assignment"

    When I press "Sign-up"
    Then I should see "Your request was accepted"
    And I follow "View all events"

    When I click on "Go to event" "link" in the "(Booked)" "table_row"
    Then I should see "Job assignment"
    And I should see "job1"
    And I log out

    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see date "10 February next year" formatted "%d %B %Y"

    When I click on the seminar event action "Attendees" in row "#1"
    Then "//th[contains(@class, 'session_positionnameedit')]/a[contains(.,'Job assignment')]" "xpath_element" should exist
    And I should see "job1" in the "Sam1 Student1" "table_row"

    When I follow "View all events"
    And I set the field "Export format" to "Excel"
    And I click on "Export to file" "button"
    Then I should not see "Can not find data record in database"
    And I log out

