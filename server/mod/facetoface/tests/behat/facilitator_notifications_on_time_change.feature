@mod @mod_facetoface @mod_facetoface_notification @totara @javascript
Feature: Facilitator notifications on time change
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
      | Zero  |          | 1              | trainer4    |
      | One   | trainer1 | 1              | trainer4    |
      | Two   | trainer2 | 1              | trainer4    |
      | Three | trainer3 | 1              | trainer4    |
    And the following "custom facilitators" exist in "mod_facetoface" plugin:
      | name   | allowconflicts | usercreated |
      | Ad-hoc | 1              | trainer4    |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | trainer3 | C1     | teacher        |
      | trainer4 | C1     | editingteacher |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name    | course |
      | future  | C1     |
      | ongoing | C1     |
      | past    | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | future     | future  |
      | ongoing    | ongoing |
      | past       | past    |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start               | finish              | facilitators           |
      | future       | 15 Jan, +2 year 1am | 15 Jan, +2 year 1pm | Zero, One, Two, Ad-hoc |
      | future       | 15 Jan, +3 year 2am | 15 Jan, +3 year 2pm | Zero, One,      Ad-hoc |
      | ongoing      | 15 Jan, +2 year 3am | 15 Jan, +2 year 3pm | Zero, One,      Ad-hoc |
      | ongoing      | 15 Jan, -1 year 4am | 15 Jan, +1 year 4pm | Zero,      Two, Ad-hoc |
      | ongoing      | 15 Jan, -2 year 5am | 15 Jan, -2 year 5pm | Zero, One, Two, Ad-hoc |
      | past         | 15 Jan, -1 year 6am | 15 Jan, -1 year 6pm | Zero, One, Two, Ad-hoc |
      | past         | 15 Jan, -2 year 7am | 15 Jan, -2 year 7pm | Zero, One,      Ad-hoc |
    And I log in as "admin"

  Scenario: mod_facetoface_notification_facilitator_301: changing session time in a future event
    # as admin, change the session time
    # if #1 is changed, both 1 & 2 will be notified
    # if #2 is changed, only 1 will be notified
    # if anything is changed to past, nobody will be noticed
    When I am on "future" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "1:00" "table_row"
    And I set the field "timestart[month]" to "2"
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "2:00" "table_row"
    And I set the field "timestart[month]" to "3"
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "1:00" "table_row"
    And I set the field "timestart[month]" to "4"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate0-dialog']" "xpath_element"
    And I click on "Edit session" "link" in the "2:00" "table_row"
    And I set the field "timestart[month]" to "6"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate1-dialog']" "xpath_element"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "1:00" "table_row"
    And I set the field "timestart[year]" to "## -5 year ## Y ##"
    And I set the field "timestart[month]" to "7"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate0-dialog']" "xpath_element"
    And I click on "Edit session" "link" in the "2:00" "table_row"
    And I set the field "timestart[year]" to "## -5 year ## Y ##"
    And I set the field "timestart[month]" to "8"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate1-dialog']" "xpath_element"
    And I press "Save changes"
    Then I should see "future" in the page title
    And I wait for the next second
    And I run all adhoc tasks
    And I log out
    When I log in as "trainer1"
    Then I should see "Showing 3 of 3"
    And I follow "View all alerts"
    And I should not see "January" in the "#totara_messages" "css_element"
    And I should see "February" in the "#totara_messages" "css_element"
    And I should see "March" in the "#totara_messages" "css_element"
    And I should see "April" in the "#totara_messages" "css_element"
    And I should see "June" in the "#totara_messages" "css_element"
    And I should not see "July" in the "#totara_messages" "css_element"
    And I should not see "August" in the "#totara_messages" "css_element"
    And I log out
    When I log in as "trainer2"
    Then I should see "Showing 2 of 2"
    And I follow "View all alerts"
    And I should not see "January" in the "#totara_messages" "css_element"
    And I should see "February" in the "#totara_messages" "css_element"
    And I should not see "March" in the "#totara_messages" "css_element"
    And I should see "April" in the "#totara_messages" "css_element"
    And I should not see "June" in the "#totara_messages" "css_element"
    And I should not see "July" in the "#totara_messages" "css_element"
    And I should not see "August" in the "#totara_messages" "css_element"
    And I log out
    When I log in as "trainer3"
    Then "Alert" "block" should not exist
    And I log out
    When I log in as "trainer4"
    Then "Alert" "block" should not exist

  Scenario: mod_facetoface_notification_facilitator_302: changing session time in an ongoing event
    # as admin, change the session time
    # if #3 is changed, only 1 will be notified
    # if #4 is changed, only 2 will be notified
    # if #5 is changed, nobody will be notified
    # if anything is changed to past, nobody will be noticed
    When I am on "ongoing" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "3:00" "table_row"
    And I set the field "timestart[month]" to "2"
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "4:00" "table_row"
    And I set the field "timestart[month]" to "3"
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "5:00" "table_row"
    And I set the field "timestart[month]" to "4"
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "3:00" "table_row"
    And I set the field "timestart[month]" to "6"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate2-dialog']" "xpath_element"
    And I click on "Edit session" "link" in the "4:00" "table_row"
    And I set the field "timestart[month]" to "7"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate1-dialog']" "xpath_element"
    And I click on "Edit session" "link" in the "5:00" "table_row"
    And I set the field "timestart[month]" to "8"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate0-dialog']" "xpath_element"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "3:00" "table_row"
    And I set the field "timestart[month]" to "9"
    And I set the field "timestart[year]" to "## -3 year ## Y ##"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate2-dialog']" "xpath_element"
    And I click on "Edit session" "link" in the "4:00" "table_row"
    And I set the field "timestart[month]" to "10"
    And I set the field "timestart[year]" to "## -6 year ## Y ##"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate1-dialog']" "xpath_element"
    And I click on "Edit session" "link" in the "5:00" "table_row"
    And I set the field "timestart[month]" to "11"
    And I set the field "timestart[year]" to "## -7 year ## Y ##"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate0-dialog']" "xpath_element"
    And I press "Save changes"
    Then I should see "ongoing" in the page title
    And I wait for the next second
    And I run all adhoc tasks
    And I log out
    When I log in as "trainer1"
    Then I should see "Showing 2 of 2"
    And I follow "View all alerts"
    And I should not see "January" in the "#totara_messages" "css_element"
    And I should see "February" in the "#totara_messages" "css_element"
    And I should not see "March" in the "#totara_messages" "css_element"
    And I should not see "April" in the "#totara_messages" "css_element"
    And I should see "June" in the "#totara_messages" "css_element"
    And I should not see "July" in the "#totara_messages" "css_element"
    And I should not see "August" in the "#totara_messages" "css_element"
    And I should not see "September" in the "#totara_messages" "css_element"
    And I should not see "October" in the "#totara_messages" "css_element"
    And I should not see "November" in the "#totara_messages" "css_element"
    And I log out
    When I log in as "trainer2"
    Then I should see "Showing 2 of 2"
    And I follow "View all alerts"
    And I should not see "January" in the "#totara_messages" "css_element"
    And I should not see "February" in the "#totara_messages" "css_element"
    And I should see "March" in the "#totara_messages" "css_element"
    And I should not see "April" in the "#totara_messages" "css_element"
    And I should not see "June" in the "#totara_messages" "css_element"
    And I should see "July" in the "#totara_messages" "css_element"
    And I should not see "August" in the "#totara_messages" "css_element"
    And I should not see "September" in the "#totara_messages" "css_element"
    And I should not see "October" in the "#totara_messages" "css_element"
    And I should not see "November" in the "#totara_messages" "css_element"
    And I log out
    When I log in as "trainer3"
    Then "Alert" "block" should not exist
    And I log out
    When I log in as "trainer4"
    Then "Alert" "block" should not exist

  Scenario: mod_facetoface_notification_facilitator_303: changing session time in a past event
    # as admin, change the session time
    # if anything is changed to past, nobody will be noticed
    # if #6 is changed to ongoing, 1 & 2 will be notified
    # if #7 is changed to ongoing, only 1 will be notified
    When I am on "past" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "6:00" "table_row"
    And I set the field "timestart[month]" to "2"
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "7:00" "table_row"
    And I set the field "timestart[month]" to "3"
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "6:00" "table_row"
    And I set the field "timestart[month]" to "4"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate1-dialog']" "xpath_element"
    And I click on "Edit session" "link" in the "7:00" "table_row"
    And I set the field "timestart[month]" to "6"
    And I set the field "timestart[year]" to "## +5 year ## Y ##"
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate0-dialog']" "xpath_element"
    And I press "Save changes"
    Then I should see "past" in the page title
    And I wait for the next second
    And I run all adhoc tasks
    And I log out
    When I log in as "trainer1"
    Then I should see "Showing 1 of 1"
    And I follow "View all alerts"
    And I should not see "January" in the "#totara_messages" "css_element"
    And I should not see "February" in the "#totara_messages" "css_element"
    And I should not see "March" in the "#totara_messages" "css_element"
    And I should not see "April" in the "#totara_messages" "css_element"
    And I should see "June" in the "#totara_messages" "css_element"
    And I log out
    When I log in as "trainer2"
    Then "Alert" "block" should not exist
    And I log out
    When I log in as "trainer3"
    Then "Alert" "block" should not exist
    And I log out
    When I log in as "trainer4"
    Then "Alert" "block" should not exist
