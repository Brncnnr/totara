@javascript @mod @mod_facetoface @totara
Feature: Take seminar attendance with job assignment on signup
  In order to verify that job assignment on signup is handled correctly
  As admin
  I need to enable global and session job assignment on signup and ensure that
  the assigned jobs are displayed correctly in the Take Attendance page

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | idnumber | email                |
      | student1 | Sam1      | Student1 | sid#1    | student1@example.com |
      | student2 | Bob2      | Student2 | sid#2    | student2@example.com |
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
      | student2 | C1     | student        |

    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
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
    Then I should see date "10 February next year" formatted "%d %B %Y" in the "9:00 AM - 3:00 PM" "table_row"
    And I should see "Pacific/Auckland" in the "9:00 AM - 3:00 PM" "table_row"
    And I log out


  Scenario: Take attendance without job assignments on signup
    # Sign both users up for the future event
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should not see "Job assignment"

    When I press "Sign-up"
    And I follow "View all events"
    And I click on "Go to event" "link" in the "(Booked)" "table_row"
    Then I should not see "Job assignment"
    And I should see "Booked" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I log out

    Given I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should not see "Job assignment"

    When I press "Sign-up"
    And I follow "View all events"
    And I click on "Go to event" "link" in the "(Booked)" "table_row"
    Then I should not see "Job assignment"
    And I should see "Booked" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I log out

    # Now change the event data to be in the past
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the seminar event action "Edit event" in row "10 February"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[year]    | 2016 |
      | timefinish[year]   | 2016 |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    Then I should see "10 February 2016"

    When I press "Save changes"

    When I click on the seminar event action "Attendees" in row "10 February 2016"
    Then I should not see "Job assignment"

    When I follow "Take attendance"
    Then I should not see "Job assignment"
    And I should see "Sam1 Student1"
    And I should see "Bob2 Student2"

    And I log out

  Scenario: Take attendance with only global job assignments on signup
    Given I log in as "admin"
    And I set the following administration settings values:
      | facetoface_selectjobassignmentonsignupglobal | 1 |
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should not see "Job assignment"

    When I press "Sign-up"
    And I follow "View all events"
    And I click on "Go to event" "link" in the "(Booked)" "table_row"
    Then I should not see "Job assignment"
    And I should see "Booked" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I log out

    Given I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should not see "Job assignment"

    When I press "Sign-up"
    And I follow "View all events"
    And I click on "Go to event" "link" in the "(Booked)" "table_row"
    Then I should not see "Job assignment"
    And I should see "Booked" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I log out

    # Now change the event data to be in the past
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the seminar event action "Edit event" in row "10 February"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[year]    | 2016 |
      | timefinish[year]   | 2016 |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    Then I should see "10 February 2016"

    When I press "Save changes"

    When I click on the seminar event action "Attendees" in row "10 February 2016"
    Then "//th[contains(@class, 'session_positionnameedit')]/a[contains(.,'Job assignment')]" "xpath_element" should exist
    And I should not see "job1" in the "Sam1 Student1" "table_row"

    When I follow "Take attendance"
    Then I should not see "Job assignment"
    And I should see "Sam1 Student1"
    And I should see "Bob2 Student2"

    And I log out


  Scenario: Take attendance with global and session job assignments on signup
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

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should see "Select a job assignment"

    When I press "Sign-up"
    And I follow "View all events"
    And I click on "Go to event" "link" in the "(Booked)" "table_row"
    Then I should see "Job assignment"
    And I should see "job1"
    And I should see "Booked" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I log out

    Given I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should not see "Job assignment"

    When I press "Sign-up"
    And I follow "View all events"
    And I click on "Go to event" "link" in the "(Booked)" "table_row"
    Then I should not see "Job assignment"
    And I should see "Booked" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I log out

    # Now change the event data to be in the past
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the seminar event action "Edit event" in row "10 February"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[year]    | 2016 |
      | timefinish[year]   | 2016 |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    Then I should see "10 February 2016"

    When I press "Save changes"

    When I click on the seminar event action "Attendees" in row "10 February 2016"
    Then "//th[contains(@class, 'session_positionnameedit')]/a[contains(.,'Job assignment')]" "xpath_element" should exist
    And I should see "job1" in the "Sam1 Student1" "table_row"
    And "//tr[td[contains(.,'Sam1 Student1')]]//a[contains(@class,'attendee-edit-job-assignment')]" "xpath_element" should exist
    And "//tr[td[contains(.,'Bob2 Student2')]]//a[contains(@class,'attendee-edit-job-assignment')]" "xpath_element" should exist

  Scenario: Show job assignments dropdown when Select job assignment on signup is set
    Given I log in as "admin"
    And the following job assignments exist:
      | user     | fullname | idnumber |
      | student2 | job3     | ja3      |
    And I set the following administration settings values:
      | facetoface_selectjobassignmentonsignupglobal | 1 |
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Select job assignment on signup | 1 |
    And I press "Save and display"
    And I log out

    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on the link "Go to event" in row 1
    Then I should see "Select a job assignment"
    And the "Select a job assignment" select box should contain "job3"

    When I press "Sign-up"
    Then I should see "Your request was accepted"

    And I should see "Job assignment"
    And I should see "job3"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on the link "Go to event" in row 1
    Then I should see "Select a job assignment"
    And the "Select a job assignment" select box should contain "job1"
    And the "Select a job assignment" select box should contain "job2"

    When I select "job2" from the "Select a job assignment" singleselect
    And I press "Sign-up"
    And I should see "Your request was accepted"

    Then I should see "Job assignment"
    And I should see "job2"
    And I log out
