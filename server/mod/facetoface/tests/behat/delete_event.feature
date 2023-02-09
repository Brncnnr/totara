@javascript @mod @mod_facetoface @totara
Feature: Test deletion of a Seminar event
  In order to test that non-admin user
  As a editing teacher
  I need to create and edit custom rooms

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  # Tests that it is possible to delete an event with a custom asset and that the asset is cleaned up.
  Scenario: Delete an event that is using a custom asset
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "Test seminar name"
    And I follow "Add event"
    And I click on "Select assets" "link"
    And I click on "Create" "link"
    And I set the following fields to these values:
      | Name        | Projector       |
      | Description | A 3D projector  |
    When I click on "OK" "button" in the "Create new asset" "totaradialogue"
    Then I should see "Projector"

    When I press "Save changes"
    Then a seminar custom asset called "Projector" should exist
    # 86400 is 24 hours past
    And I age the "Projector" "asset timecreated" in the "mod_facetoface" plugin "86400" seconds

    When I click on the seminar event action "Delete event" in row "#1"
    Then I should see "Deleting event in Test seminar name"
    And I should see "Deleting this event will remove all of its booking, attendance and grade records. All attendees will be notified."

    When I press "Delete"
    And I run the scheduled task "\mod_facetoface\task\cleanup_task"
    Then I should see "Test seminar name" in the ".mod_facetoface__event-dashboard" "css_element"
    And I should see "No results" in the ".mod_facetoface__event-dashboard" "css_element"
    And a seminar custom asset called "Projector" should not exist

  # Tests that it is possible to delete a room with custom event and that the room is cleaned up.
  Scenario: Delete an event that is using a custom room
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "Test seminar name"
    And I follow "Add event"
    And I click on "Select rooms" "link"
    And I click on "Create" "link"
    And I set the following fields to these values:
      | Name         | Room 1          |
      | Building     | That house      |
      | Address      | 123 here street |
      | Capacity     | 5               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I should not see "Add to sitewide list"
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"
    Then I should see "Room 1"

    When I press "Save changes"
    Then I should see "Room 1"
    And a seminar custom room called "Room 1" should exist
    # 86400 is 24 hours past
    And I age the "Room 1" "room timecreated" in the "mod_facetoface" plugin "86400" seconds

    When I click on the seminar event action "Delete event" in row "#1"
    Then I should see "Deleting event in Test seminar name"
    And I should see "Room 1"
    And I should see "Deleting this event will remove all of its booking, attendance and grade records. All attendees will be notified."

    When I press "Delete"
    And I run the scheduled task "\mod_facetoface\task\cleanup_task"
    Then I should see "Test seminar name" in the ".mod_facetoface__event-dashboard" "css_element"
    And I should see "No results" in the ".mod_facetoface__event-dashboard" "css_element"
    And a seminar custom room called "Room 1" should not exist

  # Tests that it is possible to delete a facilitator with custom event and that the facilitator is cleaned up.
  Scenario: Delete an event that is using a custom room
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "Test seminar name"
    And I follow "Add event"
    And I click on "Select facilitators" "link"
    And I click on "Create" "link"
    And I set the following fields to these values:
      | Name         | Facilitator 1 |
    When I click on "OK" "button" in the "Create new facilitator" "totaradialogue"
    Then I should see "Facilitator 1"

    When I press "Save changes"
    Then I should see "Facilitator 1"
    And a seminar custom facilitator called "Facilitator 1" should exist
    # 86400 is 24 hours past
    And I age the "Facilitator 1" "facilitator timecreated" in the "mod_facetoface" plugin "86400" seconds

    When I click on the seminar event action "Delete event" in row "#1"
    Then I should see "Deleting event in Test seminar name"
    And I should see "Facilitator 1"
    And I should see "Deleting this event will remove all of its booking, attendance and grade records. All attendees will be notified."

    When I press "Delete"
    And I run the scheduled task "\mod_facetoface\task\cleanup_task"
    Then I should see "Test seminar name" in the ".mod_facetoface__event-dashboard" "css_element"
    And I should see "No results" in the ".mod_facetoface__event-dashboard" "css_element"
    And a seminar custom room called "Facilitator 1" should not exist