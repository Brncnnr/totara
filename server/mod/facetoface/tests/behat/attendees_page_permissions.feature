@javascript @mod @mod_facetoface @totara
Feature: Check attendees actions are performed by users with the right permissions
  In order to check users with the right permission could perform action on the attendees page
  As Admin
  I need to set users with different capabilities and perform actions as the users

  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username  | firstname | lastname | email                |
      | trainer1  | Trainer   | One      | trainer1@example.com |
      | student1  | Sam1      | Student1 | student1@example.com |
      | student2  | Sam2      | Student2 | student2@example.com |
      | student3  | Sam3      | Student3 | student3@example.com |
      | manager1  | Manager   | One      | student4@example.com |
    And the following job assignments exist:
      | user     | fullname           | idnumber | manager   |
      | student1 | Job Assignment One | 1        | manager1  |
      | student2 | Job Assignment One | 1        | manager1  |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | trainer1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                           | course  |
      | Test seminar name | <p>Test seminar description</p> | C1      |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details |
      | Test seminar name | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start           | finish  | sessiontimezone  |
      | event 1      | -2 days -1 hour | -2 days | Pacific/Auckland |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | student1 | event 1      | booked |
      | student2 | event 1      | booked |
      | student3 | event 1      | booked |

    And I log in as "admin"
    And I set the following administration settings values:
      | Enable restricted access | 1 |
    And I am on "Test seminar name" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I should see "Sam3 Student3"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 1                                                 |
    And I press "Save and display"
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Seminar - Test seminar name | 1 |
    And I press "Save changes"

    And I log out

  Scenario: Check trainer actions on attendees page
    Given I log in as "trainer1"
    And I am on "Test seminar name" seminar homepage
    When I click on the seminar event action "Attendees" in row "#1"
    Then I should see "Attendees" in the "div.tabtree" "css_element"
    And I should see "Wait-list" in the "div.tabtree" "css_element"
    And I should see "Cancellations" in the "div.tabtree" "css_element"
    And I should see "Take attendance" in the "div.tabtree" "css_element"
    And I should see "Message users" in the "div.tabtree" "css_element"
    And I log out

  Scenario: Check trainer actions on attendees page after removing take attendance capability
    Given the following "permission overrides" exist:
      | capability                       | permission | role           | contextlevel | reference |
      | mod/facetoface:takeattendance    | Prohibit   | editingteacher | Course       |        C1 |
    When I log in as "trainer1"
    And I am on "Test seminar name" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    Then I should see "Attendees" in the "div.tabtree" "css_element"
    And I should see "Wait-list" in the "div.tabtree" "css_element"
    And I should see "Cancellations" in the "div.tabtree" "css_element"
    And I should see "Message users" in the "div.tabtree" "css_element"
    And I should not see "Take attendance" in the "div.tabtree" "css_element"
    When I visit the attendees page for session "1" with action "takeattendance"
    Then I should not see "Sam1 Student1"
    And I should not see "Sam2 Student2"
    And I should not see "Sam3 Student3"
    And I should not see "Mark all selected as:"
    And "Save attendance" "button" should not exist

  Scenario: Check trainer actions on attendees page after removing view cancellations capability
    Given the following "permission overrides" exist:
      | capability                       | permission | role           | contextlevel | reference |
      | mod/facetoface:viewcancellations | Prohibit   | editingteacher | Course       |        C1 |
    And I log in as "admin"
    And I am on "Test seminar name" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Remove users"
    And I set the field "Current attendees" to "Sam1 Student1, student1@example.com"
    And I press "Remove"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I log out
    And I log in as "trainer1"
    And I am on "Test seminar name" seminar homepage
    When I click on the seminar event action "Attendees" in row "#1"
    Then I should see "Attendees" in the "div.tabtree" "css_element"
    And I should see "Wait-list" in the "div.tabtree" "css_element"
    And I should see "Take attendance" in the "div.tabtree" "css_element"
    And I should see "Message users" in the "div.tabtree" "css_element"
    And I should not see "Cancellations" in the "div.tabtree" "css_element"

  Scenario: Check trainer actions on attendees page after removing view attendees capability
    Given the following "permission overrides" exist:
      | capability                    | permission | role           | contextlevel | reference |
      | mod/facetoface:viewattendees  | Prohibit   | editingteacher | Course       |        C1 |
    When I log in as "trainer1"
    And I am on "Test seminar name" seminar homepage
    Then I should not see the seminar event action "Attendees" in row "#1"

    When I visit the attendees page for session "1" with action "takeattendance"
    And I should see "Cancellations" in the "div.tabtree" "css_element"
    And I should see "Take attendance" in the "div.tabtree" "css_element"
    And I should not see "Message users" in the "div.tabtree" "css_element"
    And I should not see "Attendees" in the "div.tabtree" "css_element"
    And I should not see "Wait-list" in the "div.tabtree" "css_element"
    When I visit the attendees page for session "1" with action "view"
    Then "Attendee actions" "select" should not exist

  Scenario: Check managers can view attendees page
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name               | intro                            | course  | approvaltype |
      | Test seminar2 name | <p>Test seminar2 description</p> | C1      | 4            |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface         | details |
      | Test seminar2 name | event 2 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start   | finish              | sessiontimezone  |
      | event 2      | +8 days | +8 days +30 minutes | Pacific/Auckland |

    Given I log in as "admin"
    And I am on "Test seminar2 name" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 1                                                 |
    And I press "Save and display"
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Seminar - Test seminar2 name | 1 |
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Request approval"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks
    And I log out

    When I log in as "manager1"
    And I am on "Dashboard" page
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Sam1 Student1 has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "Attendees" "link"
    Then I should see "Sam1 Student1"
    And I follow "Sam1 Student1"
    Then I should see "User details"
    And I press the "back" button in the browser
    Then I should see "Decide Later"
    And I should see "Sam1 Student1"
    And I should not see "Cancellations" in the "div.tabtree" "css_element"
    And I should not see "Take attendance" in the "div.tabtree" "css_element"
    And I should not see "Message users" in the "div.tabtree" "css_element"
    And I should not see "Attendees" in the "div.tabtree" "css_element"
    And I should not see "Wait-list" in the "div.tabtree" "css_element"
    And I set the following fields to these values:
      | Approve Sam1 Student1 for this event | 1 |
    And I press "Update requests"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Go to event"
    Then I should see "Cancel booking" "link_or_button" in the seminar event sidebar "Booked"

  Scenario: Check trainer ability to view user profiles before and after prohibition
    Given I log in as "trainer1"
    And I am on "Test seminar name" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I follow "Sam1 Student1"
    Then I should see "User details"
    And I press the "back" button in the browser
    And I click on "Take attendance" "link"
    And I follow "Sam1 Student1"
    Then I should see "User details"
    And I log out
    Given the following "permission overrides" exist:
      | capability               | permission | role           | contextlevel | reference |
      | moodle/user:viewdetails  | Prohibit   | editingteacher | Course       |        C1 |
    And I log in as "trainer1"
    And I am on "Test seminar name" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    Then I should see "Sam1 Student1"
    And "Sam1 Student1" "link" should not exist
    And I click on "Take attendance" "link"
    Then I should see "Sam1 Student1"
    And "Sam1 Student1" "link" should not exist

  Scenario: Check trainer actions on attendees page after removing send message capability
    Given the following "permission overrides" exist:
      | capability              | permission | role           | contextlevel | reference |
      | moodle/site:sendmessage | Prohibit   | editingteacher | System       | C1        |
    When I log in as "trainer1"
    And I am on "Test seminar name" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    Then I should see "Attendees" in the "div.tabtree" "css_element"
    And I should see "Wait-list" in the "div.tabtree" "css_element"
    And I should see "Cancellations" in the "div.tabtree" "css_element"
    And I should see "Take attendance" in the "div.tabtree" "css_element"
    And I should not see "Message users" in the "div.tabtree" "css_element"
    When I visit the attendees page for session "1" with action "messageusers"
    Then I should see "You do not have the necessary permissions to send messages"

