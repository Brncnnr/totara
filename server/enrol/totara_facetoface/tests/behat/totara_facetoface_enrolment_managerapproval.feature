@enrol @javascript @totara @enrol_totara_facetoface @mod_facetoface
Feature: Users are forced to get manager approval where required

  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | manager1 | Manager   | 1        | manager1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | summary |
      | Course 1 | C1        | topics |         |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                           | course | approvaltype |
      | Test seminar name | <p>Test seminar description</p> | C1     | 4            |
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

    And I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Test student enrolment |
    And I log out

  Scenario: Should be unable to enrol using seminar direct without a manager
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Enrolment is disabled or inactive"

  Scenario: A user with a manager can request access, withdraw request and be granted access
    Given the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
    And the following job assignments exist:
      | user     | position | manager  |
      | student1 | POS001   | teacher1 |

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on the link "Go to event" in row 1
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I log out

    When I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I follow "Approval required"
    Then I should see "Student 1"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "manager request already pending"
    And I follow "Withdraw pending request"
    And I press "Confirm"
    And I click on the link "Go to event" in row 1
    Then I should see "Request approval"
    And I log out

    When I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I follow "Cancellations"
    Then I should see "Student 1"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on the link "Go to event" in row 1
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I log out
    And I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I follow "Approval required"
    And I click on "input[value='2']" "css_element" in the "Student 1" "table_row"
    And I press "Update requests"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Topic 1"

  Scenario: A manager who is not enrolled in the course can nevertheless approve a signup
    Given the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
    And the following job assignments exist:
      | user     | position | manager  |
      | student1 | POS001   | manager1 |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on the link "Go to event" in row 1
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks
    And I log out
    When I log in as "manager1"
    And I am on "Dashboard" page
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Student 1 has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "Attendees" "link"
    Then I should see "Student 1"
    And I follow "Student 1"
    Then I should see "User details"
    And I press the "back" button in the browser
    Then I should see "Decide Later"
    And I should see "Student 1"
    And I set the following fields to these values:
      | Approve Student 1 for this event | 1 |
    And I press "Update requests"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Go to event"
    Then I should see "Cancel booking" "link_or_button" in the seminar event sidebar "Booked"
