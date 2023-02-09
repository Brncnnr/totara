@mod @mod_facetoface @totara @javascript
Feature: Minimum Seminar bookings
  In order to test minimum bookings work as expected
  As a manager
  I need to change approval required value

  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
      | trainer1 | Trainer   | One      | trainer1@example.com |
      | trainer2 | Trainer   | Two      | trainer2@example.com |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | teacher2 | Teacher   | Two      | teacher2@example.com |
      | creator  | Cre       | Ater     | creator@example.com  |
      | siteman  | Site      | Manager  | sm@example.com       |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C2     | student        |
      | trainer1 | C1     | teacher        |
      | trainer2 | C2     | teacher        |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C2     | editingteacher |
    And the following "role assigns" exist:
      | user    | role          | contextlevel | reference |
      | creator | coursecreator | System       |           |
      | siteman | manager       | System       |           |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name          | course   |
      | test activity | C1 |
    And I log in as "admin"

  Scenario: Confirm default minimum bookings is set correctly
    When I set the following administration settings values:
      | Default minimum bookings | 5 |
    And I am on "Course 1" course homepage
    And I follow "View all events"
    When I follow "Add event"
    Then the field "Minimum bookings" matches value "5"

    When I set the field "Minimum bookings" to "2"
    And I click on "Edit session" "link" in the "Select rooms" "table_row"
    And I set the following fields to these values:
      | timestart[day]     | 29       |
      | timestart[month]   | December |
      | timestart[year]    | ## next year ## Y ## |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Save changes" "button"
    And I click on the seminar event action "Edit event" in row "29 December"
    Then the field "Minimum bookings" matches value "2"

  @mod_facetoface_notification
  Scenario Outline: Confirm notifications are sent out once cutoff has been reached
    Given the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface    | details | mincapacity | sendcapacityemail | cutoff |
      | test activity | event 1 | 5           | 1                 | 172800 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start     | finish    |
      | event 1      | +1 day    | +3 days   |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user      | eventdetails |
      | student1  | event 1      |
      | trainer1  | event 1      |
      | teacher1  | event 1      |
    Given I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_rolesnotify" "css_element"
    And I click on "<notification to>" "checkbox" in the "#admin-facetoface_session_rolesnotify" "css_element"
    And I press "Save changes"
    And I run the scheduled task "mod_facetoface\task\send_notifications_task"
    And I run all adhoc tasks
    # Confirm that the alert was sent.
    And I log out
    And I log in as "student1"
    And I am on "Dashboard" page
    And I <student> see "Event under minimum bookings for: test activity"
    And I log out
    And I log in as "trainer1"
    And I am on "Dashboard" page
    And I <trainer> see "Event under minimum bookings for: test activity"
    And I log out
    And I log in as "teacher1"
    And I am on "Dashboard" page
    And I <teacher> see "Event under minimum bookings for: test activity"
    And I log out
    And I log in as "creator"
    And I am on "Dashboard" page
    And I <creator> see "Event under minimum bookings for: test activity"
    And I log out
    And I log in as "siteman"
    And I am on "Dashboard" page
    And I <manager> see "Event under minimum bookings for: test activity"
    And I log out

    # Confirm it wasn't set elsewhere - these are failing as it is sent to all people of the given role
    And I log in as "student2"
    And I am on "Dashboard" page
    And I should not see "Event under minimum bookings for: test activity"
    And I log out
    And I log in as "trainer2"
    And I am on "Dashboard" page
    And I should not see "Event under minimum bookings for: test activity"
    And I log out
    And I log in as "teacher2"
    And I am on "Dashboard" page
    And I should not see "Event under minimum bookings for: test activity"

    Examples:
      | notification to                        | student    | trainer    | teacher    | creator    | manager    |
      | Learner                                | should     | should not | should not | should not | should not |

      # Trainer, otherwise it clicks on "Editing Trainer"
      | id_s__facetoface_session_rolesnotify_4 | should not | should     | should not | should not | should not |
      | Editing Trainer                        | should not | should not | should     | should not | should not |
      | Course Creator                         | should not | should not | should not | should     | should not |
      | Site Manager                           | should not | should not | should not | should not | should     |
