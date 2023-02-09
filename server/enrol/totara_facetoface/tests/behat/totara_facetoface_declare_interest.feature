@javascript @mod @mod_facetoface @totara @enrol_totara_facetoface
Feature: Declare of interest with Seminar direct enrolment
  In order to control seminar attendance
  As a admin
  I need to authorise seminar signups

  Background:
    Given I am using legacy seminar notifications
    And I log in as "admin"
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"

    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
      | student1 | Sam1      | Student1 | student1@moodle.com |
      | student2 | Sam2      | Student2 | student2@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    Then I navigate to "Enrolment methods" node in "Course administration > Users"
    And I set the field "Add method" to "Seminar direct enrolment"
    And I press "Add method"
    And I click on "Disable" "link" in the "Manual enrolments" "table_row"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
    And I log out

  Scenario: Student cannot declare interest where not enabled
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And "Declare interest" "button" should not exist
    And I log out

  Scenario: Student can declare and withdraw interest where enabled
    When I log in as "admin"
    And I am on "Test seminar name" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Users can declare interest | Always |
    And I click on "Save and display" "button"
    And I follow "Add event"
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And "Declare interest" "button" should exist
    And I press "Declare interest"
    And I set the following fields to these values:
      | Reason for interest: | Test reason |
    And I press "Confirm"

    And "Withdraw interest" "button" should exist
    And I press "Withdraw interest"
    And I press "Confirm"
    And "Declare interest" "button" should exist
    And I log out

  Scenario: Student can declare interest even all sessions are fully booked if setting enabled.
    When I log in as "admin"
    And I am on "Test seminar name" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Users can declare interest | When no upcoming events are available for booking |
    And I click on "Save and display" "button"
    And I follow "Add event"
    And I set the following fields to these values:
      | capacity | 1 |
    And I press "Save changes"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And "Declare interest" "button" should not exist
    And I click on the link "Go to event" in row 1
    And I press "Sign-up"
    And I should see "Your request was accepted"
    And I log out

    And I log in as "student2"
    And I am on "Course 1" course homepage
    And "Declare interest" "button" should exist
    And I log out

  Scenario: Student cannot declare interest if overbooking is enabled.
    When I log in as "admin"
    And I am on "Test seminar name" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Users can declare interest | When no upcoming events are available for booking |
    And I click on "Save and display" "button"
    And I follow "Add event"
    And I set the following fields to these values:
      | Enable waitlist | Yes |
      | capacity        | 1   |
    And I press "Save changes"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And "Declare interest" "button" should not exist
    And I click on the link "Go to event" in row 1
    And I press "Sign-up"
    And I should see "Your request was accepted"
    And I log out

    And I log in as "student2"
    And I am on "Course 1" course homepage
    And "Declare interest" "button" should not exist
    And I log out

  Scenario: View report who has expressed interest
    When I log in as "admin"
    And I am on "Test seminar name" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Users can declare interest | Always |
    And I click on "Save and display" "button"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                       | Test f2f 2                      |
      | Description                | Test seminar description        |
      | Users can declare interest | Always                          |
    And I click on "View all events" "link" in the "Test f2f 2" activity
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Declare interest" "button" in the "//*[@id='region-main']/div/div[3]/form/div" "xpath_element"
    And I set the following fields to these values:
      | Reason for interest: | Test reason 1 |
    And I press "Confirm"
    And I click on "Declare interest" "button" in the "//*[@id='region-main']/div/div[5]/form/div" "xpath_element"
    And I set the following fields to these values:
      | Reason for interest: | Test reason 2 |
    And I press "Confirm"
    And I log out

    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Declare interest" "button" in the "//*[@id='region-main']/div/div[3]/form/div" "xpath_element"
    And I set the following fields to these values:
      | Reason for interest: | Test reason 3 |
    And I press "Confirm"
    And I log out

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Declared interest report"
    And I should see "Test reason 1"
    And I should not see "Test reason 2"
    And I should see "Test reason 3"
    And I follow "Course 1"
    And I follow "Test f2f 2"
    And I follow "Declared interest report"
    And I should not see "Test reason 1"
    And I should see "Test reason 2"
    And I should not see "Test reason 3"
    And I log out

  Scenario: Student can declare interest when past sessions are
    When I log in as "admin"
    And I am on "Test seminar name" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Users can declare interest | When no upcoming events are available for booking |
    And I click on "Save and display" "button"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | -1               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | -1               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 0                |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And "Declare interest" "button" should exist
    And I log out
