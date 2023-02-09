@javascript @mod @mod_facetoface @totara
Feature: Unarchive completions for seminar sessions

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Tea       | Chan     | teacher1@example.com |
      | manager1 | Mana      | Ger      | manager1@example.com |
      | student1 | Student   | One      | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
      | student3 | Student   | Three    | student3@example.com |
      | student4 | Student   | Four     | student4@example.com |
      | student5 | Student   | Five     | student5@example.com |
      | student6 | Student   | Six      | student6@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | manager1 | C1     | manager        |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
      | student5 | C1     | student        |
      | student6 | C1     | student        |
    Given the following "seminars" exist in "mod_facetoface" plugin:
      | name              | course | eventgradingmanual | completionpass | completionstatusrequired |
      | Test seminar name | C1     | 1                  | 1              |                          |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details |
      | Test seminar name | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start       | finish       |
      | event 1      | -1 week 7am | -1 week 10am |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status             |
      | student1 | event 1      | fully_attended     |
      | student2 | event 1      | unable_to_attend   |
      | student3 | event 1      | partially_attended |
      | student4 | event 1      | booked             |
      | student5 | event 1      | no_show            |
    And I log in as "admin"
    And I set the following system permissions of "Site Manager" role:
      | capability | permission |
      | mod/facetoface:managearchivedattendees | Allow |
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Seminars:"
    And I click on "Search" "button_exact" in the ".rb-search.mform" "css_element"
    And I click on "Seminars: Event attendees" "link"
    And I switch to "Columns" tab
    And I add the "Archived" column to the report
    And I add the "Event Grade" column to the report
    And I log out
    And I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Passing grade       | 50                                                |
      | Completion tracking | Show activity as complete when conditions are met |
    And I press "Save and display"
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Seminar - Test seminar name | 1 |
    And I press "Save changes"

  Scenario: No users have been archived
    And I log out
    And I log in as "manager1"
    And I am on "Test seminar name" seminar homepage
    When I click on the seminar event action "Attendees" in row "Over"
    And I set the field "Attendee actions" to "Manage archived users"
    Then I should see "No users have been archived"

  Scenario: Archive completions and unarchive some sign-ups
    Given I am on "Test seminar name" seminar homepage
    And I click on "Take event attendance" "link" in the "Over" "table_row"
    And I set the following fields to these values:
      | Student One's event grade   | 90 |
      | Student Three's event grade | 30 |
    And I press "Save attendance"
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I complete the course via rpl for "Student Two" with text "bribe"
    And I complete the course via rpl for "Student Four" with text "beer"
    And I log out

    And I log in as "manager1"
    And I am on "Course 1" course homepage
    When I navigate to "Reset completions" node in "Course administration"
    Then I should see "This action will affect 4 learner(s)"
    When I press "Continue"
    Then I should see "4 users have had their progress and completion archived and reset in this course."
    And I log out

    And I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    When I click on "Take event attendance" "link" in the "Over" "table_row"
    Then I should see "The disabled attendees can not be updated because they hold archived course completion records"
    And the "Student One's event grade" "field" should be disabled
    And the "Student Two's event grade" "field" should be disabled
    And the "Student Three's event grade" "field" should be disabled
    And the "Student Four's event grade" "field" should be disabled
    But the "Student Five's event grade" "field" should be enabled
    And I log out

    And I log in as "manager1"
    And I am on "Test seminar name" seminar homepage
    When I click on the seminar event action "Attendees" in row "Over"
    Then the "facetoface_sessions" table should contain the following:
      | Name          | Signup status      | Archived | Event Grade |
      | Student One   | Fully attended     | Yes      | 90.00       |
      | Student Two   | Unable to attend   | Yes      |             |
      | Student Three | Partially attended | Yes      | 30.00       |
      | Student Four  | Booked             | Yes      |             |
      | Student Five  | No show            | No       |             |
    When I set the field "Attendee actions" to "Manage archived users"
    Then I should see "Notifications will NOT be sent to restored archived users and their managers"

    # Let's party!
    Then the following fields match these values:
      | Select Student One   | 0 |
      | Select Student Two   | 0 |
      | Select Student Three | 0 |
      | Select Student Four  | 0 |
    And the "Restore selected users" "button" should be disabled
    When I set the field "Select all attendees" to "1"
    Then the following fields match these values:
      | Select Student One   | 1 |
      | Select Student Two   | 1 |
      | Select Student Three | 1 |
      | Select Student Four  | 1 |
    And the "Restore selected users" "button" should be enabled
    And I set the field "Select Student One" to "0"
    Then the field "Select all attendees" matches value "0"
    And the "Restore selected users" "button" should be enabled
    And I set the following fields to these values:
      | Select Student Two   | 0 |
      | Select Student Three | 0 |
      | Select Student Four  | 0 |
    Then the field "Select all attendees" matches value "0"
    And the "Restore selected users" "button" should be disabled
    And I set the following fields to these values:
      | Select Student Four  | 1 |
      | Select Student Three | 1 |
      | Select Student Two   | 1 |
      | Select Student One   | 1 |
    Then the field "Select all attendees" matches value "1"
    And the "Restore selected users" "button" should be enabled

    When I set the following fields to these values:
      | Select Student One   | 0 |
      | Select Student Two   | 0 |
    And I click on "Restore selected users" "button"
    Then the "facetoface_sessions" table should contain the following:
      | Name          | Signup status       | Archived | Event Grade |
      | Student One   | Fully attended     | Yes      | 90.00       |
      | Student Two   | Unable to attend   | Yes      |             |
      | Student Three | Booked             | No       |             |
      | Student Four  | Booked             | No       |             |
      | Student Five  | No show            | No       |             |
    And I should see "Successfully restored 2 user(s)"
    And I log out
