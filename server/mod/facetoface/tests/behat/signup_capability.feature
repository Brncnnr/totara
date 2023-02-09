@mod @mod_facetoface @totara @totara_reportbuilder @javascript
Feature: Add seminar attendees without signup capability
  In order to test the add attendees without signup capability
  As admin
  I need to disable signup capability, upload attendees through the bulk add attendees options.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
      | student3 | Sam3      | Student3 | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "permission overrides" exist:
      | capability            | permission | role    | contextlevel | reference |
      | mod/facetoface:signup | Prohibit   | student | Course       |        C1 |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                           | course |
      | Test seminar name | <p>Test seminar description</p> | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details | capacity |
      | Test seminar name | event 1 | 10       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish        |
      | event 1      | tomorrow 9am | tomorrow 10am |

  Scenario: Confirms that teacher still can add users with disabled signup capability.
    Given I log in as "student1"
    And I am on "Test seminar name" seminar homepage
    When I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should see "You don't have permission to signup to this seminar event."
    And I log out

    And I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "menuf2f-actions" to "Add users"
    And I set the field "potential users" to "Sam1 Student1, student1@example.com,Sam2 Student2, student2@example.com"
    And I press "Add"
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Sam1 Student1" in the "#facetoface_sessions" "css_element"
    And I should see "Sam2 Student2" in the "#facetoface_sessions" "css_element"
