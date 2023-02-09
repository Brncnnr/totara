@javascript @mod @mod_facetoface @mod_facetoface_virtual_room @totara @totara_core_virtualmeeting
Feature: User sees virtual meeting status
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username     | firstname | lastname   | email                | alternatename |
      | host         | Meeting   | Host       | host@example.com     |               |
      | trainer      | Meeting   | Trainer    | trainer@example.com  |               |
      | learner      | Meating   | Attendee   | attendee@example.com |               |
      | outsider     | Meating   | Unattendee | attendee@example.com |               |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "custom rooms" exist in "mod_facetoface" plugin:
      | name             |
      | Virtual Room Uno |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name            | intro | course |
      | Virtual seminar |       | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface      | details       |
      | Virtual seminar | Virtual event |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | host     | C1     | teacher        |
      | trainer  | C1     | editingteacher |
      | learner  | C1     | student        |
      | outsider | C1     | student        |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails  | start      | finish    | rooms            | sessiontimezone  | starttimezone   | finishtimezone  |
      | Virtual event | -1 hour    | +4 minute | Virtual Room Uno | Indian/Christmas | Australia/Perth | Australia/Perth |
      | Virtual event | +12 minute | +1 hour   |                  | Antarctica/Troll | Australia/Perth | Australia/Perth |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user    | eventdetails  |
      | learner | Virtual event |
    Given I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I switch to "Room" tab
    And I click on "Hide" "link" in the "Building" "table_row"
    And I click on "Hide" "link" in the "Location" "table_row"
    And I log out
    Given I log in as "host"
    And I am on "Virtual seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    And I set the field "Add virtual room link" to "Fake Dev App"
    And I click on "OK" "button_exact" in the "[aria-describedby='editcustomroom0-dialog']" "css_element"
    And I click on "Select rooms" "link" in the "Troll" "table_row"
    And I click on "Create" "link_exact" in the "[aria-describedby='selectrooms1-dialog']" "css_element"
    And I set the following fields to these values:
      | Name                  | Virtual Room Dos |
      | Capacity              | 100              |
      | Add virtual room link | Fake Dev App     |
    And I click on "OK" "button_exact" in the "[aria-describedby='editcustomroom1-dialog']" "css_element"
    And I press "Save changes"
    Then I should not see "Editing event in"
    And I log out

  Scenario: mod_facetoface_virtualmeeting_201: See the virtual room card before/after ad-hoc task creates meetings
    Given I log in as "host"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then I should see "Updating virtual room"
    And I press the "back" button in the browser
    And I click on "Virtual Room Dos" "link" in the "Troll" "table_row"
    Then I should see "Updating virtual room"
    And I log out

    Given I log in as "trainer"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then I should see "Updating virtual room"
    And I press the "back" button in the browser
    And I click on "Virtual Room Dos" "link" in the "Troll" "table_row"
    Then I should see "Updating virtual room"
    And I log out

    Given I log in as "learner"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then I should see "Virtual room is unavailable"
    And I press the "back" button in the browser
    And I click on "Virtual Room Dos" "link" in the "Troll" "table_row"
    Then I should see "Virtual room is unavailable"
    And I log out

    Given I log in as "outsider"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then I should see "Virtual room is unavailable"
    And I press the "back" button in the browser
    And I click on "Virtual Room Dos" "link" in the "Troll" "table_row"
    Then I should see "Virtual room is unavailable"
    And I log out

    And I run all adhoc tasks

    Given I log in as "host"
    When I am on "Virtual seminar" seminar homepage
    Then "Join now" "link" should exist in the "Christmas" "table_row"
    And "Join now" "link" should exist in the "Troll" "table_row"

    When I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then "Host meeting" "link" should exist
    But I should not see "Updating virtual room"
    And I press the "back" button in the browser
    And I click on "Virtual Room Dos" "link" in the "Troll" "table_row"
    Then "Host meeting" "link" should exist
    But I should not see "Updating virtual room"
    And I log out

    Given I log in as "trainer"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then "Go to room" "link" should exist
    But I should not see "Updating virtual room"
    And I press the "back" button in the browser
    And I click on "Virtual Room Dos" "link" in the "Troll" "table_row"
    Then "Go to room" "link" should exist
    But I should not see "Updating virtual room"
    And I log out

    Given I log in as "learner"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then "Join now" "link" should exist
    But I should not see "Virtual room is unavailable"
    And I press the "back" button in the browser
    And I click on "Virtual Room Dos" "link" in the "Troll" "table_row"
    Then "Join now" "link" should exist
    But I should not see "Virtual room is unavailable"
    And I log out

    Given I log in as "outsider"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then I should see "Virtual room is unavailable"
    And I press the "back" button in the browser
    And I click on "Virtual Room Dos" "link" in the "Troll" "table_row"
    Then I should see "Virtual room is unavailable"
    And I log out

  Scenario: mod_facetoface_virtualmeeting_202: See the virtual room card before/after ad-hoc task updates meetings
    And I run all adhoc tasks

    Given I log in as "host"
    And I am on "Virtual seminar" seminar homepage
    Then "Join now" "link" should exist in the "Christmas" "table_row"
    And "Join now" "link" should exist in the "Troll" "table_row"

    When I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "Christmas" "table_row"
    And I fill seminar session with relative date in form data:
      | timefinish[timezone] | Australia/Perth |
      | timefinish[minute]   | +5 |
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate0-dialog']" "xpath_element"
    And I press "Save changes"
    Then I should not see "Editing event in"

    Then "Join now" "link" should not exist in the "Christmas" "table_row"
    But "Join now" "link" should exist in the "Troll" "table_row"

    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then I should see "Updating virtual room"
    And I log out

    Given I log in as "trainer"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then I should see "Updating virtual room"
    And I log out

    Given I log in as "learner"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then I should see "Virtual room is unavailable"
    And I log out

    Given I log in as "outsider"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then I should see "Virtual room is unavailable"
    And I log out

    And I run all adhoc tasks

    Given I log in as "host"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then "Host meeting" "link" should exist
    But I should not see "Updating virtual room"
    And I log out

    Given I log in as "trainer"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then "Go to room" "link" should exist
    But I should not see "Updating virtual room"
    And I log out

    Given I log in as "learner"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then "Join now" "link" should exist
    But I should not see "Virtual room is unavailable"
    And I log out

    Given I log in as "outsider"
    When I am on "Virtual seminar" seminar homepage
    And I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then I should see "Virtual room is unavailable"
    And I log out

  Scenario: mod_facetoface_virtualmeeting_203: See the virtual room card before/after ad-hoc task deletes meetings
    And I run all adhoc tasks

    Given I log in as "host"
    And I am on "Virtual seminar" seminar homepage
    Then "Join now" "link" should exist in the "Christmas" "table_row"
    And "Join now" "link" should exist in the "Troll" "table_row"

    When I click on the seminar event action "Edit event" in row "#1"
    And I click on "Remove room" "link_or_button" in the "Virtual Room Uno" "table_row"
    And I click on "Delete" "link_or_button" in the "Virtual Room Dos" "table_row"

    # Because there is no way to access the room detail page of the deleted virtual meeting,
    # we just simply save changes and run ad-hoc tasks to make sure that the system doesn't throw an exception.

    And I press "Save changes"
    Then I should not see "Editing event in"
    And "Join now" "link" should not exist in the "Christmas" "table_row"
    And I run all adhoc tasks

  Scenario: mod_facetoface_virtualmeeting_204: See the virtual room card before/after ad-hoc task fails to create meetings
    Given I log in as "admin"
    When I am on "Virtual seminar" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the field "Name" to "Virtual seminar failure"
    And I press "Save and display"
    And I run all adhoc tasks
    And I click on the seminar event action "Edit event" in row "#1"
    # Confirm the failure has the right CSS class
    And I should see "Virtual Room Uno" in the "#roomlist0 .mod_facetoface-room_failure_creation" "css_element"
    And I log out

    Given I log in as "host"
    When I am on "Virtual seminar failure" seminar homepage
    Then "Join now" "link" should not exist in the "Christmas" "table_row"

    When I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then I should see "Virtual room is unavailable"
    But "Host meeting" "link" should not exist
    And I click on "Please click to assign the room again." "link"
    Then I should see "Editing event in Virtual seminar"
    And I press "Save changes"
    Then I should not see "Editing event in"
    And I log out

    Given I log in as "admin"
    When I am on "Virtual seminar failure" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the field "Name" to "Virtual seminar success"
    And I press "Save and display"
    And I run all adhoc tasks
    And I click on the seminar event action "Edit event" in row "#1"
    # Confirm the failure has the right CSS class
    And I should see "Virtual Room Uno" in the "#roomlist0 .mod_facetoface-room_available" "css_element"
    And I log out

    Given I log in as "host"
    When I am on "Virtual seminar success" seminar homepage
    Then "Join now" "link" should exist in the "Christmas" "table_row"

    When I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then "Host meeting" "link" should exist
    But I should not see "Updating virtual room"
    And I log out

  Scenario: mod_facetoface_virtualmeeting_205: See the virtual room card before/after ad-hoc task fails to update meetings
    And I run all adhoc tasks

    Given I log in as "host"
    And I am on "Virtual seminar" seminar homepage
    Then "Join now" "link" should exist in the "Christmas" "table_row"

    When I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link" in the "Christmas" "table_row"
    And I fill seminar session with relative date in form data:
      | timefinish[timezone] | Australia/Perth |
      | timefinish[minute]   | +5 |
    And I click on "OK" "button" in the "//div[@aria-describedby='selectdate0-dialog']" "xpath_element"
    And I press "Save changes"
    Then I should not see "Editing event in"

    Then "Join now" "link" should not exist in the "Christmas" "table_row"
    And I log out

    Given I log in as "admin"
    When I am on "Virtual seminar" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the field "Name" to "Virtual seminar failure"
    And I press "Save and display"
    And I log out

    And I run all adhoc tasks

    Given I log in as "host"
    When I am on "Virtual seminar failure" seminar homepage
    Then "Join now" "link" should not exist in the "Christmas" "table_row"

    When I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then I should see "Virtual room is unavailable"
    But "Host meeting" "link" should not exist
    And I click on "Please click to assign the room again." "link"
    Then I should see "Editing event in Virtual seminar"
    And I press "Save changes"
    Then I should not see "Editing event in"
    And I log out

    Given I log in as "admin"
    When I am on "Virtual seminar failure" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the field "Name" to "Virtual seminar success"
    And I press "Save and display"
    And I log out

    And I run all adhoc tasks

    Given I log in as "host"
    When I am on "Virtual seminar success" seminar homepage
    Then "Join now" "link" should exist in the "Christmas" "table_row"

    When I click on "Virtual Room Uno" "link" in the "Christmas" "table_row"
    Then "Host meeting" "link" should exist
    But I should not see "Updating virtual room"
    And I log out

  Scenario: mod_facetoface_virtualmeeting_206: See the virtual room card before/after ad-hoc task fails to delete meetings
    And I run all adhoc tasks

    Given I log in as "host"
    And I am on "Virtual seminar" seminar homepage
    Then "Join now" "link" should exist in the "Christmas" "table_row"

    When I click on the seminar event action "Edit event" in row "#1"
    And I click on "Remove room" "link_or_button" in the "Virtual Room Uno" "table_row"
    And I click on "Delete" "link_or_button" in the "Virtual Room Dos" "table_row"

    # Because there is no way to access the room detail page of the deleted virtual meeting,
    # we just simply save changes and run ad-hoc tasks to make sure that the system doesn't throw an exception.

    And I press "Save changes"
    Then I should not see "Editing event in"
    And "Join now" "link" should not exist in the "Christmas" "table_row"
    And I log out

    Given I log in as "admin"
    When I am on "Virtual seminar" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the field "Name" to "Virtual seminar failure"
    And I press "Save and display"
    And I log out

    And I run all adhoc tasks

  Scenario: mod_facetoface_virtualmeeting_207: See the virtual room card of a reincarnated virtual meeting
    And I run all adhoc tasks

    Given I log in as "host"
    And I am on "Virtual seminar" seminar homepage

    When I click on the seminar event action "Edit event" in row "#1"
    And I click on "Remove room" "link_or_button" in the "Virtual Room Uno" "table_row"
    And I click on "Delete" "link_or_button" in the "Virtual Room Dos" "table_row"

    And I press "Save changes"
    Then I should not see "Editing event in"

    When I click on the seminar event action "Edit event" in row "#1"
    And I click on "Select rooms" "link" in the "Christmas" "table_row"
    And I click on "Virtual Room Dos (Capacity: 100)" "text"
    And I click on "OK" "button_exact" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"

    And I click on "Virtual Room Dos" "link" in the "Christmas" "table_row"
    Then I should see "Updating virtual room"

    And I run all adhoc tasks
    Then "Host meeting" "link" should exist
    But I should not see "Updating virtual room"
