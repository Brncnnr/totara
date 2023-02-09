@mod @mod_facetoface @totara @javascript @totara_customfield
Feature: Manage pre-defined rooms
  In order to test seminar rooms
  As a site manager
  I need to create and allocate rooms

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                    |
      | teacher1 | Teacher   | One      | teacher1@example.invalid |
      | user1    | User      | One      | user1@example.invalid    |
      | user2    | User      | Two      | user2@example.invalid    |
      | user3    | User      | Three    | user3@example.invalid    |
      | user4    | User      | Four     | user4@example.invalid    |
      | user5    | User      | Five     | user5@example.invalid    |
      | user6    | User      | Six      | user6@example.invalid    |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | user1    | C1     | student        |
      | user2    | C1     | student        |
      | user3    | C1     | student        |
      | user4    | C1     | student        |
      | user5    | C1     | student        |
      | user6    | C1     | student        |
    And I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 1          |
      | Building          | That house      |
      | Address           | 123 here street |
      | Capacity          | 5               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 2          |
      | Building          | Your house      |
      | Address           | 123 near street |
      | Capacity          | 6               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

  Scenario: See that the rooms were created correctly
    Given I navigate to "Rooms" node in "Site administration > Seminars"
    Then I should see "That house" in the "Room 1" "table_row"
    And I should see "123 here street" in the "Room 1" "table_row"
    And I should see "5" in the "Room 1" "table_row"

    Then I should see "Your house" in the "Room 2" "table_row"
    And I should see "123 near street" in the "Room 2" "table_row"
    And I should see "6" in the "Room 2" "table_row"

    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add event"
    When I click on "Select rooms" "link"
    Then I should see "Room 1, That house, 123 here street (Capacity: 5)" in the "Choose rooms" "totaradialogue"
    And I should see "Room 2, Your house, 123 near street (Capacity: 6)" in the "Choose rooms" "totaradialogue"

  Scenario: Fill a room
    Given I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I turn editing mode off
    And I follow "View all events"
    And I follow "Add event"
    And I set the following fields to these values:
      | capacity           | 7   |
    When I click on "Select rooms" "link"
    And I wait "1" seconds
    And I click on "Room 1, That house, 123 here street (Capacity: 5)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I wait "1" seconds
    And I press "Use room capacity"
    And I wait "1" seconds
    And I press "Save changes"

    When I click on the seminar event action "Attendees" in row "#1"
    And I set the field "menuf2f-actions" to "Add users"
    And I set the field "potential users" to "User One, user1@example.invalid, User Two, user2@example.invalid, User Three, user3@example.invalid,User Four, user4@example.invalid,User Five, user5@example.invalid"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "User One"
    And I should see "User Two"
    And I should see "User Three"
    And I should see "User Four"
    And I should see "User Five"
    And I should see "Bulk add attendees success - Successfully added/edited 5 attendees."
    And I should not see "This session is overbooked"

    And I set the field "menuf2f-actions" to "Add users"
    And I set the field "potential users" to "User Six, user6@example.invalid"
    And I press exact "add"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "User Six"
    And I should see "This event is overbooked"

  Scenario: Try and clash a room
    Given I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity           | 5    |
    When I click on "Select rooms" "link"
    And I wait "1" seconds
    And I click on "Room 1, That house, 123 here street (Capacity: 5)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"

    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    When I click on "Select rooms" "link"
    And I wait "1" seconds
    Then I should see "(Room unavailable)" in the "Choose rooms" "totaradialogue"
    And I click on "Cancel" "button" in the "Choose rooms" "totaradialogue"
    And I wait "1" seconds
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 14   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 15   |
      | timefinish[minute] | 0    |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    When I click on "Select rooms" "link"
    And I click on "Room 1, That house, 123 here street (Capacity: 5)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I wait "1" seconds
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I should see "The new dates you have selected are unavailable due to a scheduling conflict"
    And I click on "Cancel" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see date "1 January next year" formatted "%d %B %Y" in the "Room 1" "table_row"

  Scenario: Clash a room with different timezones
    Given I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 1                |
      | timestart[month]     | 1                |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 19               |
      | timestart[minute]    | 0                |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[day]      | 1                |
      | timefinish[month]    | 1                |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 20               |
      | timefinish[minute]   | 0                |
      | timefinish[timezone] | Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity                | 7                |
    When I click on "Select rooms" "link"
    And I wait "1" seconds
    And I click on "Room 1, That house, 123 here street (Capacity: 5)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"

    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 1             |
      | timestart[month]     | 1             |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 6             |
      | timestart[minute]    | 0             |
      | timestart[timezone]  | Europe/London |
      | timefinish[day]      | 1             |
      | timefinish[month]    | 1             |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 7             |
      | timefinish[minute]   | 0             |
      | timefinish[timezone] | Europe/London |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity                | 7             |
    When I click on "Select rooms" "link"
    And I wait "1" seconds
    Then I should see "(Room unavailable)" in the "Choose rooms" "totaradialogue"
    And I click on "Cancel" "button" in the "Choose rooms" "totaradialogue"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 2             |
      | timestart[month]     | 1             |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 14            |
      | timestart[minute]    | 0             |
      | timestart[timezone]  | Europe/London |
      | timefinish[day]      | 2             |
      | timefinish[month]    | 1             |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 15            |
      | timefinish[minute]   | 0             |
      | timefinish[timezone] | Europe/London |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I wait "1" seconds
    When I click on "Select rooms" "link"
    And I click on "Room 1, That house, 123 here street (Capacity: 5)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I wait "1" seconds
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 1             |
      | timestart[month]     | 1             |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 6             |
      | timestart[minute]    | 0             |
      | timestart[timezone]  | Europe/London |
      | timefinish[day]      | 1             |
      | timefinish[month]    | 1             |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 7             |
      | timefinish[minute]   | 0             |
      | timefinish[timezone] | Europe/London |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I should see "The new dates you have selected are unavailable due to a scheduling conflict"
    And I click on "Cancel" "button" in the "Select date" "totaradialogue"
    And I click on "Delete" "link" in the ".f2fmanagedates" "css_element"
    And I press "Save changes"
    Then I should see date "1 January next year" formatted "%d %B %Y" in the "Room 1" "table_row"
