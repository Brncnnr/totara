@enrol @javascript @totara @enrol_totara_facetoface @mod_facetoface
Feature: Users can auto-enrol themself in courses where seminar direct enrolment is allowed
  In order to participate in courses
  As a user
  I need to auto enrol me in courses

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
      | name              | intro                           | course | approvaltype |
      | Test seminar name | <p>Test seminar description</p> | C1     | 0            |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details |
      | Test seminar name | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish        |
      | event 1      | tomorrow 9am | tomorrow 10am |

    And I log in as "admin"
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"
    And I log out

  Scenario: Enrol using seminar direct enrolment
    Given I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Test student enrolment |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on the link "Go to event" in row 1
    And I set the following fields to these values:
      | Requests for session organiser | Lorem ipsum dolor sit amet |
    And I press "Sign-up"
    Then I should see "Test seminar name: Your request was accepted"
    And I log out
    # Check signup note
    And I log in as "admin"
    And I am on "Test seminar name" seminar homepage
    When I click on the seminar event action "Attendees" in row "#1"
    Then I should see "Lorem ipsum dolor sit amet" in the "Student 1" "table_row"

  Scenario: Seminar direct enrolment disabled
    Given I log in as "student1"
    When I am on "Course 1" course homepage
    Then I should see "You can not enrol yourself in this course"

  Scenario: Enrol through course catalogue
    Given I log in as "admin"
    And I set the following administration settings values:
      | catalogtype | enhanced |
    And I log out
    Given I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Test student enrolment |
    And I log out
    And I log in as "student1"
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    And I click on the link "Go to event" in row 1
    And I press "Sign-up"
    Then I should see "Your request was accepted"

  Scenario: Enrol using seminar direct enrolment with customfields
    # Setup customfields
    Given I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "Sign-up" "link"

    And I click on "Edit" "link" in the "Requests for session organiser" "table_row"
    And I set the following fields to these values:
      | fullname            | Signup text input |
      | shortname           | signupnote1 |
    And I press "Save changes"

    And I set the field "datatype" to "Text area"
    And I set the following fields to these values:
      | fullname           | Signup textarea |
      | shortname          | signuptextarea |
    And I press "Save changes"
    And I log out

    Given I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Test student enrolment |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on the link "Go to event" in row 1
    And I set the following fields to these values:
      | Signup text input | Lorem ipsum dolor sit amet |
      | Signup textarea   | Some other text data |
    And I press "Sign-up"
    Then I should see "Test seminar name: Your request was accepted"
    And I log out
  # Check signup note
    And I log in as "admin"
    And I am on "Test seminar name" seminar homepage
    When I click on the seminar event action "Attendees" in row "#1"
    Then I should see "Lorem ipsum dolor sit amet" in the "Student 1" "table_row"
    And I should see "Some other text data" in the "Student 1" "table_row"
