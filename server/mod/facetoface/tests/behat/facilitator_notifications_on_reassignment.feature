@mod @mod_facetoface @mod_facetoface_notification @totara @javascript
Feature: Facilitator notifications on reassignment
  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username  | firstname | lastname | email                |
      | trainer1  | Trainer   | First    | trainer1@example.com |
      | trainer2  | Trainer   | Second   | trainer2@example.com |
      | trainer3  | Trainer   | Third    | trainer3@example.com |
      | trainer4  | Trainer   | Fourth   | trainer4@example.com |
    And the following "global facilitators" exist in "mod_facetoface" plugin:
      | name  | username | allowconflicts | usercreated |
      | One   | trainer1 | 1              | trainer4    |
      | Two   | trainer2 | 1              | trainer4    |
      | Three | trainer3 | 1              | trainer4    |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | trainer3 | C1     | teacher        |
      | trainer4 | C1     | editingteacher |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name    | course |
      | seminar | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | seminar    | event 1 |
    And the following "custom rooms" exist in "mod_facetoface" plugin:
      | name | capacity | description |
      | Uno  | 1        |             |
      | Dos  | 2        |             |
      | Tres | 3        |             |
    And I log in as "admin"

  Scenario: mod_facetoface_notification_facilitator_201: Notice with related sessions when removing a session and adding a new session
    Given the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start              | finish             | facilitators    |
      | event 1      | 3 Mar, +1 year 3am | 3 Mar, +1 year 3pm | One, Two, Three |
      | event 1      | 6 Jun, +1 year 6am | 6 Jun, +1 year 6pm | One,      Three |
      | event 1      | 9 Sep, +1 year 9am | 9 Sep, +1 year 9pm |           Three |
    When I am on "seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on ".dateremove" "css_element" in the "June" "table_row"
    And I press "Add a new session"
    And I click on "Edit session" "link" in the "table.f2fmanagedates > tbody > tr:last-child" "css_element"
    And I set the following fields to these values:
      | timestart[day]   | 12               |
      | timestart[month] | 12               |
      | timestart[year]  | ##next year##Y## |
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate3-dialog']" "xpath_element"
    And I click on "Select facilitators" "link" in the "December" "table_row"
    And I click on "Trainer Third" "link" in the "//div[@aria-describedby='selectfacilitators3-dialog']" "xpath_element"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectfacilitators3-dialog']" "xpath_element"
    And I press "Save changes"
    Then I should see "seminar" in the page title
    And I wait for the next second
    And I run all adhoc tasks
    And I log out
    When I log in as "trainer1"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator cancellation | June      |
    But the "logtable" table should not contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator cancellation | March     |
      | Seminar session facilitator cancellation | September |
      | Seminar session facilitator cancellation | December  |
    And I log out
    When I log in as "trainer2"
    Then "Alert" "block" should not exist
    And I log out
    When I log in as "trainer3"
    Then I should see "Showing 2 of 2"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator cancellation | June      |
    But the "logtable" table should not contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator cancellation | March     |
      | Seminar session facilitator cancellation | September |
      | Seminar session facilitator cancellation | December  |
    And the "logtable" table should contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | December  |
    But the "logtable" table should not contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | March     |
      | Seminar session facilitator confirmation | June      |
      | Seminar session facilitator confirmation | September |
    And I log out
    When I log in as "trainer4"
    Then "Alert" "block" should not exist

  Scenario: mod_facetoface_notification_facilitator_202: Notice with related sessions when removing a session and adding the exactly same session
    Given the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start              | finish             | facilitators    |
      | event 1      | 3 Mar, +1 year 3am | 3 Mar, +1 year 3pm | One, Two, Three |
      | event 1      | 6 Jun, +1 year 6am | 6 Jun, +1 year 6pm | One,      Three |
      | event 1      | 9 Sep, +1 year 9am | 9 Sep, +1 year 9pm |           Three |
    When I am on "seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on ".dateremove" "css_element" in the "June" "table_row"
    And I press "Add a new session"
    And I click on "Edit session" "link" in the "table.f2fmanagedates > tbody > tr:last-child" "css_element"
    And I set the following fields to these values:
      | timestart[day]     | 6                |
      | timestart[month]   | 6                |
      | timestart[year]    | ##next year##Y## |
      | timestart[hour]    | 6                |
      | timestart[minute]  | 00               |
      | timefinish[day]    | 6                |
      | timefinish[month]  | 6                |
      | timefinish[year]   | ##next year##Y## |
      | timefinish[hour]   | 18               |
      | timefinish[minute] | 00               |
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate3-dialog']" "xpath_element"
    # And I click on "Select facilitators" "link" in the "June" "table_row" doesn't work because of the hidden table row.
    And I click on "Select facilitators" "link" in the ".f2fmanagedates tbody > tr:last-child" "css_element"
    And I click on "Trainer First" "link" in the "//div[@aria-describedby='selectfacilitators3-dialog']" "xpath_element"
    And I click on "Trainer Third" "link" in the "//div[@aria-describedby='selectfacilitators3-dialog']" "xpath_element"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectfacilitators3-dialog']" "xpath_element"
    And I press "Save changes"
    Then I should see "seminar" in the page title
    And I wait for the next second
    And I run all adhoc tasks
    And I log out
    When I log in as "trainer1"
    Then I should see "Showing 2 of 2"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator cancellation | June      |
    But the "logtable" table should not contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator cancellation | March     |
      | Seminar session facilitator cancellation | September |
    Then the "logtable" table should contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | June      |
    But the "logtable" table should not contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | March     |
      | Seminar session facilitator confirmation | September |
    And I log out
    When I log in as "trainer2"
    Then "Alert" "block" should not exist
    And I log out
    When I log in as "trainer3"
    Then I should see "Showing 2 of 2"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator cancellation | June      |
    But the "logtable" table should not contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator cancellation | March     |
      | Seminar session facilitator cancellation | September |
    Then the "logtable" table should contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | June      |
    But the "logtable" table should not contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | March     |
      | Seminar session facilitator confirmation | September |
    And I log out
    When I log in as "trainer4"
    Then "Alert" "block" should not exist

  Scenario: mod_facetoface_notification_facilitator_203: Notice with related sessions when session dates are shifted
    Given the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start              | finish             | facilitators    | rooms |
      | event 1      | 1 Mar, +1 year 1am | 1 Mar, +1 year 1pm | One, Two,       | Uno   |
      | event 1      | 1 Jun, +1 year 1am | 1 Jun, +1 year 1pm | One,            | Dos   |
      | event 1      | 1 Sep, +1 year 1am | 1 Sep, +1 year 1pm |           Three | Tres  |
    When I am on "seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    # March -> June
    And I click to edit the seminar event date at position 1
    And I set the field "timestart[month]" to "6"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate0-dialog']" "xpath_element"
    # June -> September
    And I click to edit the seminar event date at position 2
    And I set the field "timestart[month]" to "9"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate1-dialog']" "xpath_element"
    # September -> December
    And I click to edit the seminar event date at position 3
    And I set the field "timestart[month]" to "12"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate2-dialog']" "xpath_element"
    And I press "Save changes"
    Then I should see "seminar" in the page title
    And I should see "Uno" in the "June" "table_row"
    And I should see "One" in the "June" "table_row"
    And I should see "Two" in the "June" "table_row"
    And I should see "Dos" in the "September" "table_row"
    And I should see "One" in the "September" "table_row"
    And I should see "Tres" in the "December" "table_row"
    And I should see "Three" in the "December" "table_row"
    And I wait for the next second
    And I run all adhoc tasks
    And I log out
    When I log in as "trainer1"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                       | Details   |
      | Seminar session date/time changed: seminar | June      |
      | Seminar session date/time changed: seminar | September |
    But the "logtable" table should not contain the following:
      | Type                                       | Details   |
      | Seminar session date/time changed: seminar | March     |
      | Seminar session date/time changed: seminar | December  |
    And I log out
    When I log in as "trainer2"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                       | Details   |
      | Seminar session date/time changed: seminar | June      |
    But the "logtable" table should not contain the following:
      | Type                                       | Details   |
      | Seminar session date/time changed: seminar | March     |
      | Seminar session date/time changed: seminar | September |
      | Seminar session date/time changed: seminar | December  |
    And I log out
    When I log in as "trainer3"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                       | Details   |
      | Seminar session date/time changed: seminar | December  |
    But the "logtable" table should not contain the following:
      | Type                                       | Details   |
      | Seminar session date/time changed: seminar | March     |
      | Seminar session date/time changed: seminar | June      |
      | Seminar session date/time changed: seminar | September |
    And I log out
    When I log in as "trainer4"
    Then "Alert" "block" should not exist

  Scenario: mod_facetoface_notification_facilitator_204: Notice with related sessions when session dates are rotated
    Given the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start              | finish             | facilitators    | rooms |
      | event 1      | 1 Mar, +1 year 1am | 1 Mar, +1 year 1pm | One, Two,       | Uno   |
      | event 1      | 1 Jun, +1 year 1am | 1 Jun, +1 year 1pm | One,            | Dos   |
      | event 1      | 1 Sep, +1 year 1am | 1 Sep, +1 year 1pm |           Three | Tres  |
    When I am on "seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    # March -> June
    And I click to edit the seminar event date at position 1
    And I set the field "timestart[month]" to "6"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate0-dialog']" "xpath_element"
    # June -> September
    And I click to edit the seminar event date at position 2
    And I set the field "timestart[month]" to "9"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate1-dialog']" "xpath_element"
    # September -> March
    And I click to edit the seminar event date at position 3
    And I set the field "timestart[month]" to "3"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate2-dialog']" "xpath_element"
    And I press "Save changes"
    Then I should see "seminar" in the page title
    And I should see "Uno" in the "June" "table_row"
    And I should see "One" in the "June" "table_row"
    And I should see "Two" in the "June" "table_row"
    And I should see "Dos" in the "September" "table_row"
    And I should see "One" in the "September" "table_row"
    And I should see "Tres" in the "March" "table_row"
    And I should see "Three" in the "March" "table_row"
    And I wait for the next second
    And I run all adhoc tasks
    And I log out
    When I log in as "trainer1"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                       | Details   |
      | Seminar session date/time changed: seminar | June      |
      | Seminar session date/time changed: seminar | September |
    But the "logtable" table should not contain the following:
      | Type                                       | Details   |
      | Seminar session date/time changed: seminar | March     |
    And I log out
    When I log in as "trainer2"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                       | Details   |
      | Seminar session date/time changed: seminar | June      |
    But the "logtable" table should not contain the following:
      | Type                                       | Details   |
      | Seminar session date/time changed: seminar | March     |
      | Seminar session date/time changed: seminar | September |
    And I log out
    When I log in as "trainer3"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                       | Details   |
      | Seminar session date/time changed: seminar | March     |
    But the "logtable" table should not contain the following:
      | Type                                       | Details   |
      | Seminar session date/time changed: seminar | June      |
      | Seminar session date/time changed: seminar | September |
    And I log out
    When I log in as "trainer4"
    Then "Alert" "block" should not exist
