@javascript @mod @mod_facetoface @totara
Feature: Course archive completions for seminar sessions can not be changed
  In order to test file upload
  As an admin
  I need to archive completions in a seminar session

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                |
      | teacher1  | Terry3    | Teacher  | teacher@example.com  |
      | student1  | Sam1      | Student1 | student1@example.com |
      | student2  | Sam2      | Student2 | student2@example.com |
      | student3  | Sam3      | Student3 | student3@example.com |
      | student4  | Sam4      | Student4 | student4@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable restricted access | 1 |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 1                                                 |
    And I turn editing mode off
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Seminar - Test seminar name | 1 |
    And I press "Save changes"
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | -1               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | 0                |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | 0                |
      | timefinish[minute] | -30              |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam1 Student1, student1@example.com,Sam2 Student2, student2@example.com,Sam3 Student3, student3@example.com, Sam4 Student4, student4@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I should see "Sam3 Student3"
    And I should see "Sam4 Student4"
    And I log out

  Scenario: Test upload attendance can not change the eventattendance and eventgrade values for user with the archived course completion record
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "View all events" "link"
    And I click on the seminar event action "Attendees" in row "#1"
    And I switch to "Take attendance" tab
    And "input[name='check_submissionid_1']" "css_element" should exist
    And I set the field "Sam1 Student1's attendance" to "Fully attended"
    And I set the field "Sam2 Student2's attendance" to "Partially attended"
    And I press "Save attendance"
    And I switch to "Attendees" tab
    And I should see "Fully attended" in the "Sam1 Student1" "table_row"
    And I should see "Partially attended" in the "Sam2 Student2" "table_row"
    And I should see "Booked" in the "Sam3 Student3" "table_row"
    And I should see "Booked" in the "Sam4 Student4" "table_row"
    And I log out

    And I log in as "admin"
    And I am on "Course 1" course homepage
    When I navigate to "Reset completions" node in "Course administration"
    And I should see "This action will affect 1 learner(s)"
    And I press "Continue"
    Then I should see "1 users have had their progress and completion archived and reset in this course."
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "View all events" "link"
    And I click on the seminar event action "Attendees" in row "#1"
    When I switch to "Take attendance" tab
    Then I should see "The disabled attendees can not be updated because they hold archived course completion records"
    And "input[name='check_submissionid_1']" "css_element" should not exist
    And the "menusubmissionid_1" "select" should be disabled
    And I follow "Upload event attendance"
    And I upload "mod/facetoface/tests/fixtures/grade2.csv" file to "CSV text file" filemanager
    When I press "Continue"
      # Sam1 Student1 with the archived course completion record and can not be updated the eventattendance and eventgrade values
    Then I should see "(invalid)" in the "Sam1 Student1" "table_row"
    And I should see "Fully attended" in the "Sam2 Student2" "table_row"
    And I should see "No show" in the "Sam3 Student3" "table_row"
    And I should see "Partially attended" in the "Sam4 Student4" "table_row"
    When I press "Confirm"
    Then I should see "The disabled attendees can not be updated because they hold archived course completion records"
    And "input[name='check_submissionid_1']" "css_element" should not exist
    And the "menusubmissionid_1" "select" should be disabled
    And I switch to "Attendees" tab
      # Sam1 Student1 left with the archived course completion record
    And I should see "Fully attended" in the "Sam1 Student1" "table_row"
    And I should see "Fully attended" in the "Sam2 Student2" "table_row"
    And I should see "No show" in the "Sam3 Student3" "table_row"
    And I should see "Partially attended" in the "Sam4 Student4" "table_row"
    And I log out
