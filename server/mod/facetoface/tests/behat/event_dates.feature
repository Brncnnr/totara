@mod @mod_facetoface @totara
Feature: I can add and edit seminar session dates
  In order to test the add/remove Face to face attendees
  As admin
  I need to add and remove attendees to/from a face to face session

  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                           | course  |
      | Test seminar name | <p>Test seminar description</p> | C1      |

  @javascript
  Scenario: I can edit a past seminar session
    Given I log in as "admin"
    And I am on "Test seminar name" seminar homepage
    And I follow "Add event"
    And I click to edit the seminar event date at position 1
    And I set the following fields to these values:
      | timestart[day]       | 1                |
      | timestart[month]     | 1                |
      | timestart[year]      | 2037             |
      | timestart[hour]      | 10               |
      | timestart[minute]    | 00               |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[day]      | 1                |
      | timefinish[month]    | 1                |
      | timefinish[year]     | 2037             |
      | timefinish[hour]     | 11               |
      | timefinish[minute]   | 00               |
      | timefinish[timezone] | Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity                  | 10   |

    When I press "Save changes"
    Then I should not see "The cut-off for minimum bookings is after the events earliest start date, it must be before to have any effect."
    And I should see "Upcoming events"
    And I should see "1 January 2037"

    When I use magic to adjust the seminar event "start" from "01/01/2037 10:00" "Pacific/Auckland" to "26/10/2016 10:00"
    And I use magic to adjust the seminar event "end" from "01/01/2037 11:00" "Pacific/Auckland" to "26/10/2016 11:00"
    And I follow "Test seminar name"
    Then I should see "Upcoming events"
    And I should see "26 October 2016"

    When I click on the seminar event action "Edit event" in row "#1"
    Then I should see "Editing event in Test seminar name"

    When I set the following fields to these values:
      | Details | This event was run in the past |
    And I press "Save changes"
    And I should not see "The cut-off for minimum bookings is after the events earliest start date, it must be before to have any effect."
    Then I should see "Upcoming events"

    When I click on the seminar event action "Edit event" in row "#1"
    Then I should see "This event was run in the past"

    When I click to edit the seminar event date at position 1
    And I set the following fields to these values:
      | timestart[year]    | 2016 |
      | timefinish[year]   | 2016 |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see "Upcoming events"
    And I should not see "The cut-off for minimum bookings is after the events earliest start date, it must be before to have any effect."

  @javascript
  Scenario: I can edit a future seminar session
    Given I log in as "admin"
    And I am on "Test seminar name" seminar homepage
    And I follow "Add event"
    And I click to edit the seminar event date at position 1
    And I set the following fields to these values:
      | timestart[day]       | 9                |
      | timestart[month]     | 1                |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 10               |
      | timestart[minute]    | 00               |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[day]      | 9                |
      | timefinish[month]    | 1                |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 11               |
      | timefinish[minute]   | 00               |
      | timefinish[timezone] | Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity                  | 10   |
    When I press "Save changes"
    Then I should not see "The cut-off for minimum bookings is after the events earliest start date, it must be before to have any effect."
    And I should see "Upcoming events"
    And I should see date "9 Jan next year" formatted "%d %B %Y"

    When I click on the seminar event action "Edit event" in row "#1"
    Then I should see "Editing event in Test seminar name"

    When I set the following fields to these values:
      | Details | This event was run in the past |
    And I press "Save changes"
    And I should not see "The cut-off for minimum bookings is after the events earliest start date, it must be before to have any effect."
    Then I should see "Upcoming events"

    When I click on the seminar event action "Edit event" in row "#1"
    Then I should see "This event was run in the past"

    When I click to edit the seminar event date at position 1
    And I set the following fields to these values:
      | timestart[day]       | 3                |
      | timestart[month]     | 2                |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 10               |
      | timestart[minute]    | 00               |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[day]      | 3                |
      | timefinish[month]    | 2                |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 11               |
      | timefinish[minute]   | 00               |
      | timefinish[timezone] | Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see "Upcoming events"
    And I should not see "The cut-off for minimum bookings is after the events earliest start date, it must be before to have any effect."

  @javascript
  Scenario: I can edit a past seminar session with a minimum bookings and cutoff
    Given I log in as "admin"
    And I am on "Test seminar name" seminar homepage
    And I follow "Add event"
    And I click to edit the seminar event date at position 1
    And I set the following fields to these values:
      | timestart[day]       | 1                |
      | timestart[month]     | 1                |
      | timestart[year]      | 2037             |
      | timestart[hour]      | 10               |
      | timestart[minute]    | 00               |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[day]      | 1                |
      | timefinish[month]    | 1                |
      | timefinish[year]     | 2037             |
      | timefinish[hour]     | 11               |
      | timefinish[minute]   | 00               |
      | timefinish[timezone] | Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity                   | 10   |
      | id_allowcancellations_2    | 1    |
      | Minimum bookings           | 5    |
      | sendcapacityemail          | 1    |
      | cutoff[number]             | 24   |
    And I press "Save changes"
    And I should not see "The cut-off for minimum bookings is after the events earliest start date, it must be before to have any effect."
    And I should see "Upcoming events"
    And I should see "1 January 2037"
    And I use magic to adjust the seminar event "start" from "01/01/2037 10:00" "Pacific/Auckland" to "26/10/2016 10:00"
    And I use magic to adjust the seminar event "end" from "01/01/2037 11:00" "Pacific/Auckland" to "26/10/2016 11:00"

    When I follow "Test seminar name"
    Then I should see "Upcoming events"
    And I should see "26 October 2016"

    When I click on the seminar event action "Edit event" in row "#1"
    Then I should see "Editing event in Test seminar name"

    When I set the following fields to these values:
      | Details | This event was run in the past |
    And I press "Save changes"
    And I should not see "The cut-off for minimum bookings is after the events earliest start date, it must be before to have any effect."
    Then I should see "Upcoming events"

    When I click on the seminar event action "Edit event" in row "#1"
    Then I should see "This event was run in the past"

    When I click to edit the seminar event date at position 1
    And I set the following fields to these values:
      | timestart[day]       | 3                |
      | timestart[month]     | 2                |
      | timestart[year]      | 2016             |
      | timestart[hour]      | 10               |
      | timestart[minute]    | 00               |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[day]      | 3                |
      | timefinish[month]    | 2                |
      | timefinish[year]     | 2016             |
      | timefinish[hour]     | 11               |
      | timefinish[minute]   | 00               |
      | timefinish[timezone] | Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see "The cut-off for minimum bookings is after the events earliest start date, it must be before to have any effect."
    And I should not see "Upcoming events"

  @javascript
  Scenario: I can edit a future seminar session with a minimum bookings and cutoff
    Given I log in as "admin"
    And I am on "Test seminar name" seminar homepage
    And I follow "Add event"
    And I click to edit the seminar event date at position 1
    And I set the following fields to these values:
      | timestart[day]       | 9                |
      | timestart[month]     | 1                |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 10               |
      | timestart[minute]    | 00               |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[day]      | 9                |
      | timefinish[month]    | 1                |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 11               |
      | timefinish[minute]   | 00               |
      | timefinish[timezone] | Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity                   | 10   |
      | id_allowcancellations_2    | 1    |
      | Minimum bookings           | 5    |
      | sendcapacityemail          | 1    |
      | cutoff[number]             | 24   |
    When I press "Save changes"
    Then I should not see "The cut-off for minimum bookings is after the events earliest start date, it must be before to have any effect."
    And I should see "Upcoming events"
    And I should see date "9 Jan next year" formatted "%d %B %Y"

    When I click on the seminar event action "Edit event" in row "#1"
    Then I should see "Editing event in Test seminar name"

    When I set the following fields to these values:
      | Details | This event was run in the past |
    And I press "Save changes"
    And I should not see "The cut-off for minimum bookings is after the events earliest start date, it must be before to have any effect."
    Then I should see "Upcoming events"

    When I click on the seminar event action "Edit event" in row "#1"
    Then I should see "This event was run in the past"

    When I click to edit the seminar event date at position 1
    And I set the following fields to these values:
      | timestart[day]       | 3                |
      | timestart[month]     | 2                |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 10               |
      | timestart[minute]    | 00               |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[day]      | 3                |
      | timefinish[month]    | 2                |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 11               |
      | timefinish[minute]   | 00               |
      | timefinish[timezone] | Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should not see "The cut-off for minimum bookings is after the events earliest start date, it must be before to have any effect."
    And I should see "Upcoming events"