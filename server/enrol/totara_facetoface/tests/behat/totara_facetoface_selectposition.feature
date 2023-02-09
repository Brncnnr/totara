@enrol @javascript @totara @enrol_totara_facetoface @mod_facetoface
Feature: Users can enrol on courses that have position signup enabled and get signed for appropriate sessions
  In order to participate in courses with seminars
  As a user
  I need to sign up to seminars when enrolling on the course

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@asd.com |
      | student1 | Student | 1 | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
      | Course 2 | C2 | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | teacher1 | C2 | editingteacher |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name                | intro                             | course |
      | Test seminar name 1 | <p>Test seminar description 1</p> | C1     |
      | Test seminar name 2 | <p>Test seminar description 2</p> | C2     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface          | details |
      | Test seminar name 1 | event 1 |
      | Test seminar name 2 | event 2 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish        |
      | event 1      | tomorrow 9am | tomorrow 10am |
      | event 2      | tomorrow 9am | tomorrow 10am |

    And I log in as "admin"
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"
    And I set the following administration settings values:
      | facetoface_selectjobassignmentonsignupglobal | 1 |
    And I log out
    And I log in as "teacher1"
    And I am on "Test seminar name 1" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Select job assignment on signup                                 | 1                          |
      | Prevent signup if no job assignment is selected or can be found | 0                          |
    And I press "Save and display"
    And I am on "Test seminar name 2" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Select job assignment on signup                                 | 1                          |
      | Prevent signup if no job assignment is selected or can be found | 1                          |
    And I press "Save and display"
    And I log out

  Scenario: Enrol using seminar direct where position asked for but not required
    Given I log in as "teacher1"
    And I am on "Test seminar name 1" seminar homepage
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name                          | Test student enrolment |
      | Automatically sign users up to seminar events | 0                      |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on the link "Go to event" in row 1
    And I press "Sign-up"
    Then I should see "Your request was accepted"

  Scenario: Enrol using seminar direct where position asked for and required
    Given I log in as "teacher1"
    And I am on "Test seminar name 2" seminar homepage
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name                          | Test student enrolment |
      | Automatically sign users up to seminar events | 0                      |
    And I log out
    And I log in as "student1"
    And I am on "Course 2" course homepage
    And I click on the link "Go to event" in row 1
    Then I should see "You must have a suitable job assignment to sign up for this seminar activity."
