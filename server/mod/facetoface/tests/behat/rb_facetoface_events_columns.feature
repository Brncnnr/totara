@javascript @mod_facetoface @totara @totara_reportbuilder
Feature: Test the seminar events report columns
In order to test the columns
As an admin
I need to create a course, create seminar with event, create seminar event report

  Background:
    Given I am on a totara site
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname | shortname       | source            |
      | Report 1 | report_report_1 | facetoface_events |
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
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                           | course  |
      | Test seminar name | <p>Test seminar description</p> | C1      |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details |
      | Test seminar name | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start  | finish      | sessiontimezone  |
      | event 1      | -1 day | -30 minutes | Pacific/Auckland |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | student1 | event 1      | booked |
      | student2 | event 1      | booked |
      | student3 | event 1      | booked |
      | student4 | event 1      | booked |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable restricted access | 1 |
    And I log out
    # Leaving it if we need to test something else
    And I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 1                                                 |
    And I click on "Save and display" "button"
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Seminar - Test seminar name | 1 |
    And I press "Save changes"
    And I log out

  Scenario: Test Viewer's status seminar events report column
    Given I log in as "admin"
    And I navigate to "Reports > Manage user reports" in site administration
    And I follow "Report 1"
    And I switch to "Columns" tab
    And I add the "Viewer's status" column to the report
    When I navigate to my "Report 1" report
    And I should see "Test seminar name" in the "Course 1" "table_row"
    And I should see "Course 1" in the "Test seminar name" "table_row"
    And I should see "Not set" in the "Test seminar name" "table_row"
