@javascript @mod @mod_facetoface @totara
Feature: Seminar Signup User Approval
  In order to signup to seminar
  As a learner
  I need to request approval from learner-manager

  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname    | shortname | category |
      | Course 9360 | C9360     | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C9360  | student |
    And the following job assignments exist:
      | user     | manager  |
      | student1 | student2 |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name         | course | approvaltype |
      | Seminar 9360 | C9360  | 4            |

    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_approvaloptions[approval_none]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_self]" "checkbox"
    And I press "Save changes"

  Scenario: Student gets approved through manager approval by "learner" role
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details |
      | Seminar 9360 | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish        |
      | event 1      | tomorrow 9am | tomorrow 10am |
    And I log out

    And I log in as "student1"
    And I am on "Seminar 9360" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Manager Approval"
    And I press "Request approval"
    And I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks
    And I log out

    And I log in as "student2"
    And I am on "Dashboard" page
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Sam1 Student1 has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    When I click on "Attendees" "link"
    Then I should see "Sam1 Student1"
    When I click on "requests[3]" "radio" in the ".lastrow .lastcol" "css_element"
    And I click on "Update requests" "button"
    Then I should not see "Sam1 Student1"
    And I should see "Attendance requests updated"
    And I should see "No pending approvals"
