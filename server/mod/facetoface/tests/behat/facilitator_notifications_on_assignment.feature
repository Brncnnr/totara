@mod @mod_facetoface @mod_facetoface_notification @totara @javascript
Feature: Facilitator notifications on assignment
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
    And I log in as "admin"

  Scenario: mod_facetoface_notification_facilitator_101: Notice with related sessions when assigned to a new event
    When I am on "seminar" seminar homepage
    And I press "Add event"
    And I press "Add a new session"
    And I press "Add a new session"
    And I click to edit the seminar event date at position 1
    And I set the following fields to these values:
      | timestart[day]   | 3                |
      | timestart[month] | 3                |
      | timestart[year]  | ##next year##Y## |
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate0-dialog']" "xpath_element"
    And I click to edit the seminar event date at position 2
    And I set the following fields to these values:
      | timestart[day]   | 6                |
      | timestart[month] | 6                |
      | timestart[year]  | ##next year##Y## |
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate1-dialog']" "xpath_element"
    And I click to edit the seminar event date at position 3
    And I set the following fields to these values:
      | timestart[day]   | 9                |
      | timestart[month] | 9                |
      | timestart[year]  | ##next year##Y## |
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate2-dialog']" "xpath_element"
    And I click on "Select facilitators" "link" in the "March" "table_row"
    And I click on "Trainer First" "link" in the "//div[@aria-describedby='selectfacilitators0-dialog']" "xpath_element"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectfacilitators0-dialog']" "xpath_element"
    And I click on "Select facilitators" "link" in the "June" "table_row"
    And I click on "Trainer First" "link" in the "//div[@aria-describedby='selectfacilitators1-dialog']" "xpath_element"
    And I click on "Trainer Second" "link" in the "//div[@aria-describedby='selectfacilitators1-dialog']" "xpath_element"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectfacilitators1-dialog']" "xpath_element"
    And I click on "Select facilitators" "link" in the "September" "table_row"
    And I click on "Trainer Third" "link" in the "//div[@aria-describedby='selectfacilitators2-dialog']" "xpath_element"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectfacilitators2-dialog']" "xpath_element"
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
      | Seminar session facilitator confirmation | March     |
      | Seminar session facilitator confirmation | June      |
    But the "logtable" table should not contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | September |
    And I log out
    When I log in as "trainer2"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | June      |
    But the "logtable" table should not contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | March     |
      | Seminar session facilitator confirmation | September |
    And I log out
    When I log in as "trainer3"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | September |
    But the "logtable" table should not contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | March     |
      | Seminar session facilitator confirmation | June      |
    And I log out
    When I log in as "trainer4"
    Then "Alert" "block" should not exist

  Scenario: mod_facetoface_notification_facilitator_102: Notice with related sessions when assigned to an existing event
    Given the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | seminar    | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start              | finish             | facilitators    |
      | event 1      | 3 Mar, +1 year 3am | 3 Mar, +1 year 3pm |      Two, Three |
      | event 1      | 6 Jun, +1 year 6am | 6 Jun, +1 year 6pm |           Three |
      | event 1      | 9 Sep, +1 year 9am | 9 Sep, +1 year 9pm |                 |
    When I am on "seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Select facilitators" "link" in the "March" "table_row"
    And I click on "Trainer First" "link" in the "//div[@aria-describedby='selectfacilitators0-dialog']" "xpath_element"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectfacilitators0-dialog']" "xpath_element"
    And I click on "Select facilitators" "link" in the "June" "table_row"
    And I click on "Trainer First" "link" in the "//div[@aria-describedby='selectfacilitators1-dialog']" "xpath_element"
    And I click on "Trainer Second" "link" in the "//div[@aria-describedby='selectfacilitators1-dialog']" "xpath_element"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectfacilitators1-dialog']" "xpath_element"
    And I click on "Select facilitators" "link" in the "September" "table_row"
    And I click on "Trainer Third" "link" in the "//div[@aria-describedby='selectfacilitators2-dialog']" "xpath_element"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectfacilitators2-dialog']" "xpath_element"
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
      | Seminar session facilitator confirmation | March     |
      | Seminar session facilitator confirmation | June      |
    But the "logtable" table should not contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | September |
    And I log out
    When I log in as "trainer2"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | June      |
    But the "logtable" table should not contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | March     |
      | Seminar session facilitator confirmation | September |
    And I log out
    When I log in as "trainer3"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | September |
    But the "logtable" table should not contain the following:
      | Type                                     | Details   |
      | Seminar session facilitator confirmation | March     |
      | Seminar session facilitator confirmation | June      |
    And I log out
    When I log in as "trainer4"
    Then "Alert" "block" should not exist

  Scenario: mod_facetoface_notification_facilitator_103: Notice with related sessions when unassigned from an existing event
    Given the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | seminar    | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start              | finish             | facilitators    |
      | event 1      | 3 Mar, +1 year 3am | 3 Mar, +1 year 3pm | One, Two, Three |
      | event 1      | 6 Jun, +1 year 6am | 6 Jun, +1 year 6pm | One, Two, Three |
      | event 1      | 9 Sep, +1 year 9am | 9 Sep, +1 year 9pm |           Three |
    When I am on "seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Remove facilitator One from session" "link" in the "March" "table_row"
    And I click on "Remove facilitator One from session" "link" in the "June" "table_row"
    And I click on "Remove facilitator Two from session" "link" in the "June" "table_row"
    And I click on "Remove facilitator Three from session" "link" in the "September" "table_row"
    And I press "Save changes"
    Then I should see "seminar" in the page title
    And I wait for the next second
    And I run all adhoc tasks
    And I log out
    When I log in as "trainer1"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                   | Details   |
      | Seminar session facilitator unassigned | March     |
      | Seminar session facilitator unassigned | June      |
    But the "logtable" table should not contain the following:
      | Type                                   | Details   |
      | Seminar session facilitator unassigned | September |
    And I log out
    When I log in as "trainer2"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                   | Details   |
      | Seminar session facilitator unassigned | June      |
    But the "logtable" table should not contain the following:
      | Type                                   | Details   |
      | Seminar session facilitator unassigned | March     |
      | Seminar session facilitator unassigned | September |
    And I log out
    When I log in as "trainer3"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    Then the "logtable" table should contain the following:
      | Type                                   | Details   |
      | Seminar session facilitator unassigned | September |
    But the "logtable" table should not contain the following:
      | Type                                   | Details   |
      | Seminar session facilitator unassigned | March     |
      | Seminar session facilitator unassigned | June      |
    And I log out
    When I log in as "trainer4"
    Then "Alert" "block" should not exist
