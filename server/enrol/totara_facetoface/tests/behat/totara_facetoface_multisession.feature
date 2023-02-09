@enrol @javascript @totara @enrol_totara_facetoface @mod_facetoface
Feature: Users can enrol on courses that have several seminar activities and signup to several sessions
  In order to participate in courses with seminars
  As a user
  I need to sign up to seminars when enrolling on the course

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name                | course | approvaltype |
      | Test seminar name 1 | C1     | 0            |
      | Test seminar name 2 | C1     | 0            |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface          | details              |
      | Test seminar name 1 | Test seminar event 1 |
      | Test seminar name 2 | Test seminar event 2 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails         | start                                     | finish                                    |
      | Test seminar event 1 | first day of January next year +11 hours  | first day of January next year +12 hours  |
      | Test seminar event 2 | first day of February next year +11 hours | first day of February next year +12 hours |

    And I log in as "admin"
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name                          | Test student enrolment |
      | Automatically sign users up to seminar events | 0                      |
    And I log out

  Scenario: Enrol using seminar direct to a multisession
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Go to event" "link" in the "1 February" "table_row"
    And I press "Sign-up"
    Then I should see "Your request was accepted"
    And I am on "Course 1" course homepage
    And I click on "Go to event" "link" in the "1 January" "table_row"
    And I press "Sign-up"
    And I should see "Your request was accepted"
