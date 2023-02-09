@mod @mod_facetoface @totara @javascript
Feature: Seminar Signup Manager Approval
  In order to signup to classroom connect
  As a learner
  I need to request approval from my manager

  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username    | firstname | lastname | email              |
      | sysapprover | Terry     | Ter      | terry@example.com  |
      | actapprover | Larry     | Lar      | larry@example.com  |
      | teacher     | Freddy    | Fred     | freddy@example.com |
      | trainer     | Benny     | Ben      | benny@example.com  |
      | manager     | Cassy     | Cas      | cassy@example.com  |
      | jimmy       | Jimmy     | Jim      | jimmy@example.com  |
      | timmy       | Timmy     | Tim      | timmy@example.com  |
      | sammy       | Sammy     | Sam      | sammy@example.com  |
      | sally       | Sally     | Sal      | sally@example.com  |
    And the following "courses" exist:
      | fullname                 | shortname | category |
      | Classroom Connect Course | CCC       | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | CCC    | editingteacher |
      | trainer | CCC    | teacher        |
      | jimmy   | CCC    | student        |
      | timmy   | CCC    | student        |
      | sammy   | CCC    | student        |
      | sally   | CCC    | student        |
    And the following job assignments exist:
      | user  | manager |
      | jimmy | manager |
      | timmy | manager |
      | sammy | manager |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                          | course  | approvaltype |
      | Classroom Connect | <p>Classroom Connect Tests</p> | CCC     | 4            |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details  | capacity |
      | Classroom Connect | event 1a | 10       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish        |
      | event 1a     | tomorrow 9am | tomorrow 10am |
    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_approvaloptions[approval_none]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_self]" "checkbox"
    And I press "Save changes"

  Scenario: Student signs up with no manager assigned
    When I log out
    When I log in as "sally"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Manager Approval"
    And I should see "This seminar requires manager approval. Users without a manager cannot join the seminar."

  Scenario: Student signs up with two managers assigned with manager select enabled and manager approval required
    # Add two more managers
    And the following "users" exist:
      | username    | firstname | lastname | email              |
      | tammy       | Tammy     | Tam      | tammy@example.com  |
      | yummy       | Yummy     | Yum      | yummy@example.com  |
      | funny       | Funny     | Fun      | funny@example.com  |
    And the following job assignments exist:
      | user  | fullname | idnumber | manager |
      | sally | jajaja1  | 1        | tammy   |
      | sally | jajaja2  | 2        | yummy   |
    And I set the following administration settings values:
      | facetoface_managerselect | 1 |
    And I log out

    And I log in as "sally"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Manager Approval"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks
    And I log out

    And I log in as "tammy"
    And I am on "Dashboard" page
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Sally Sal has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "mod/facetoface/attendees" "link" in the "td.message_values_statement" "css_element"
    Then I should see "Tammy Tam" in the "Sally Sal" "table_row"
    Then I should see "Yummy Yum" in the "Sally Sal" "table_row"
    And I log out

    And I log in as "yummy"
    And I am on "Dashboard" page
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Sally Sal has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "mod/facetoface/attendees" "link" in the "td.message_values_statement" "css_element"
    Then I should see "Tammy Tam" in the "Sally Sal" "table_row"
    Then I should see "Yummy Yum" in the "Sally Sal" "table_row"
    And I log out

    And I log in as "funny"
    And I am on "Dashboard" page
    And I should not see "View all tasks"

  Scenario: Student signs up with no manager assigned with manager select enabled and manager approval required
    When I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_managerselect" "checkbox"
    And I press "Save changes"
    And I log out
    And I log in as "sally"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Manager Approval"
    And I press "Request approval"
    Then I should see "This seminar requires manager approval. Please select a manager to request approval"

    And I press "Choose manager"
    And I click on "Cassy Cas" "link" in the "Select manager" "totaradialogue"
    And I click on "OK" "button" in the "Select manager" "totaradialogue"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks

    When I log out
    And I log in as "manager"
    And I am on "Dashboard" page
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Sally Sal has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "Attendees" "link"

    Then I should see "Sally Sal"
    When I click on "requests[11]" "radio" in the ".lastrow .lastcol" "css_element"
    And I click on "Update requests" "button"
    Then I should not see "Sally Sal"

  Scenario: Student gets approved through manager approval
    When I log out
    And I log in as "jimmy"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Manager Approval"
    And I should see "Cassy Cas"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks
    And I log out

    And I log in as "manager"
    And I am on "Dashboard" page
    Then I should see "Seminar booking request"
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Jimmy Jim has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "Attendees" "link" in the "Follow the link" "table_row"
    Then I should see "Jimmy Jim" in the ".lastrow" "css_element"

    When I click on "requests[8]" "radio" in the ".lastrow .lastcol" "css_element"
    And I click on "Update requests" "button"
    Then I should not see "Jimmy Jim"
    And I run all adhoc tasks

    When I log out
    And I log in as "jimmy"
    And I am on "Dashboard" page
    Then I should see "Seminar booking confirmation"

    When I am on "Classroom Connect" seminar homepage
    Then I should see "Booked" in the "Upcoming" "table_row"

    When I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should see "Manager's name"
    And I should see "Cassy Cas"

  Scenario: Student remove the existing manager and assign a new manager itself.
    When I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_managerselect" "checkbox"
    And I press "Save changes"
    And I log out
    And I log in as "jimmy"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Manager Approval"
    And I should see "Cassy Cas"

    And I press "Choose manager"
    And I click on "Timmy Tim" "link" in the "Select manager" "totaradialogue"
    And I click on "OK" "button" in the "Select manager" "totaradialogue"

    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks
    And I log out

    And I log in as "timmy"
    And I am on "Dashboard" page
    Then I should see "Seminar booking request"
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Jimmy Jim has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "Attendees" "link" in the "Follow the link" "table_row"
    Then I should see "Jimmy Jim" in the ".lastrow" "css_element"

    When I click on "requests[8]" "radio" in the ".lastrow .lastcol" "css_element"
    And I click on "Update requests" "button"
    Then I should not see "Jimmy Jim"
    And I run all adhoc tasks

    When I log out
    And I log in as "jimmy"
    And I am on "Dashboard" page
    Then I should see "Seminar booking confirmation"

    When I am on "Classroom Connect" seminar homepage
    Then I should see "Booked" in the "Upcoming" "table_row"

  Scenario: Trainer is given permission to approve any bookings
    And I log out
    When I log in as "jimmy"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Manager Approval"
    And I should see "Cassy Cas"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks
    And I log out
    When I log in as "trainer"
    And I am on "Classroom Connect" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    Then I should not see "Approval required" in the ".tabtree" "css_element"

    And I log out
    And I log in as "admin"
    And the following "permission overrides" exist:
      | capability                       | permission | role    | contextlevel | reference |
      | mod/facetoface:approveanyrequest | Allow      | teacher | Course       | CCC       |
    And I log out
    When I log in as "trainer"
    And I am on "Classroom Connect" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I follow "Approval required"
    And I click on "input[value='2']" "css_element" in the "Jimmy Jim" "table_row"
    And I press "Update requests"
    Then I should see "Attendance requests updated"
    And I should not see "Jimmy Jim"

  Scenario: Student is not given permission to sign up a seminar event
    And the following "permission overrides" exist:
      | capability            | permission | role    | contextlevel | reference |
      | mod/facetoface:signup | Prohibit   | student | Course       | CCC       |
    And I log out

    And I log in as "jimmy"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Manager Approval"
    But I should see "You don't have permission to signup to this seminar event"
    And I should not see "Request approval"

  Scenario: Seminar event is deleted and manager logs in to approve request
    When I log out
    And I log in as "jimmy"
    And I am on "Classroom Connect Course" course homepage
    And I should see "Request approval"
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Manager Approval"
    And I should see "Cassy Cas"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks
    And I log out
    And I log in as "admin"
    And I am on "Classroom Connect" seminar homepage
    And I click on the seminar event action "Delete event" in row "#1"
    And I press "Delete"
    And I run all adhoc tasks
    And I log out
    And I log in as "manager"
    And I click on "Click for more information" "link"
    Then I should see "(Event has been deleted)"
    And I should see "Dismiss"
    And I should not see "Approve"

  Scenario: Student can not request approval for another session with same dates
    And the following job assignments exist:
      | user  | fullname | idnumber | manager |
      | sally | jajaja1  | 1        | timmy   |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name                | intro                            | course  | approvaltype |
      | Classroom Connect 2 | <p>Classroom Connect Tests 2</p> | CCC     | 4            |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface          | details  | capacity |
      | Classroom Connect   | event 1b | 10       |
      | Classroom Connect 2 | event 2a | 5        |
      | Classroom Connect 2 | event 2b | 10       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish        |
      | event 1b     | +2 days 12am | +3 days 12am  |
      | event 2a     | tomorrow 9am | tomorrow 10am |
      | event 2b     | +2 days 12am | +3 days 12am  |
    And I log out

    And I log in as "sally"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    When I press "Request approval"
    And I should see "Manager Approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks
    When I follow "View all events"
    Then I should see "(Requested)" in the "Upcoming" "table_row"
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Cancel booking" "link_or_button" in the seminar event sidebar "Requested"

  Scenario Outline: Manager approves or declines via task notification
    When I log out
    And I log in as "jimmy"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Manager Approval"
    And I should see "Cassy Cas"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks
    And I log out

    And I log in as "manager"
    And I am on "Dashboard" page
    # Click the info-icon that opens the task form with the options to accept and reject.
    And I click on "#detailtask2-dialog" "css_element" in the ".block_totara_tasks" "css_element"
    And I press "<Action>"
    Then I should see "Attendance requests updated"
    And I run all adhoc tasks

    When I log out
    And I log in as "jimmy"
    And I am on "Dashboard" page
    Then I should see "<Expected alert subject>" in the ".block_totara_alerts" "css_element"

    Examples:
      | Action | Expected alert subject                             |
      | Accept | Seminar booking confirmation: Classroom Connect    |
      | Reject | Request to attend event Classroom Connect Declined |
