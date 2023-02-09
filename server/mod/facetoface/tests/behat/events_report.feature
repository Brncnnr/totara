@mod @mod_facetoface @totara @totara_reportbuilder @javascript
Feature: Check the seminar events and sessions reports display correctly

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
      | student1 | Sam1      | Student1 | student1@moodle.com |
      | student2 | Sam2      | Student2 | student2@example.com |
      | student3 | Sam3      | Student3 | student3@example.com |
      | student4 | Sam4      | Student4 | student4@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I turn editing mode off
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see date "1 January next year" formatted "%d %B %Y"

  Scenario: Seminar events report should only display one row per event with the sessions report showing one row per session
    #
    # Events with a single session display correctly.
    #
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then I should see "Test seminar name" in the "Course 1" "table_row"
    When I follow "Sessions view"
    Then I should see date "1 January next year" formatted "%d %B %Y" in the "Course 1" "table_row"

    #
    # Events with multiple sessions display correctly.
    # Lets add another session.
    # There should only be one row per event in the events report.
    #
    When I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Add a new session" "button"
    And I click to edit the seminar event date at position 2
    And I set the following fields to these values:
      | timestart[day]     | 2    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 2    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see date "1 January next year" formatted "%d %B %Y"
    And I should see date "2 January next year" formatted "%d %B %Y"

    # Check reports.
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then I should see "Test seminar name" in the "Course 1" "table_row"
    And "//table[@id='facetoface_events']/tbody/tr[2]" "xpath_element" should not exist
    When I follow "Sessions view"
    Then the following should exist in the "facetoface_summary" table:
      | Seminar Name      | Course Name | Session Start Date/Time |
      | Test seminar name | Course 1    | 1 January               |
      | Test seminar name | Course 1    | 2 January               |
    And I should see date "1 January next year" formatted "%d %B %Y"
    And I should see date "2 January next year" formatted "%d %B %Y"

  Scenario: Check the Seminar events report displays the event start and finish dates, times and timezones correctly

    # Add event start and finish cols to the events report.
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then I should see "Test seminar name" in the "Course 1" "table_row"
    When I click on "Edit this report" "button"
    And I follow "Columns"
    And I set the field "newcolumns" to "Event Start Date/Time"
    And I press "Add"
    And I set the field "newcolumns" to "Event Finish Date/Time"
    And I press "Add"
    Then I press "Save changes"

    # Set admin users timezone to Europe/London.
    When I follow "Profile" in the user menu
    And I follow "Edit profile"
    And I set the field "timezone" to "Europe/London"
    And I press "Update profile"
    Then I should see "Europe/London"

    # Check the events start and finish date display correctly.
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then the following should exist in the "facetoface_events" table:
      | Seminar Name      | Course Name | Event Start Date/Time | Event Start Date/Time | Event Start Date/Time   | Event Finish Date/Time | Event Finish Date/Time | Event Finish Date/Time  |
      | Test seminar name | Course 1    | 1 January             | 3:00 AM               | Timezone: Europe/London | 1 January              | 4:00 AM                | Timezone: Europe/London |
    And I should see date "1 January next year 3:00 AM Europe/London" formatted "%d %B %Y, %I:%M %p"
    And I should see date "1 January next year 4:00 AM Europe/London" formatted "%d %B %Y, %I:%M %p"
    # Set admin users timezone to Pacific/Auckland.
    When I follow "Profile" in the user menu
    And I follow "Edit profile"
    And I set the field "timezone" to "Pacific/Auckland"
    And I press "Update profile"
    Then I should see "Pacific/Auckland"

    # Check the events start and finish date display correctly.
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then the following should exist in the "facetoface_events" table:
      | Seminar Name      | Course Name | Event Start Date/Time | Event Start Date/Time | Event Start Date/Time      | Event Finish Date/Time | Event Finish Date/Time | Event Finish Date/Time     |
      | Test seminar name | Course 1    | 1 January             | 4:00 PM               | Timezone: Pacific/Auckland | 1 January              | 5:00 PM                | Timezone: Pacific/Auckland |
    And I should see date "1 January next year 4:00 PM Pacific/Auckland" formatted "%d %B %Y, %I:%M %p"
    And I should see date "1 January next year 5:00 PM Pacific/Auckland" formatted "%d %B %Y, %I:%M %p"

    # Set the sessions display timezone to America/Toronto.
    When I follow "Course 1"
    And I follow "View all events"
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Edit session" "link"
    And I set the field "sessiontimezone" to "America/Toronto"
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see date "31 December this year 10:00PM America/Toronto" formatted "%d %B %Y, 10:00 PM - 11:00 PM" in the "Timezone: America/Toronto" "table_row"

    # Check the events start and finish date display correctly.
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then the following should exist in the "facetoface_events" table:
      | Seminar Name      | Course Name | Event Start Date/Time | Event Start Date/Time | Event Start Date/Time     | Event Finish Date/Time | Event Finish Date/Time | Event Finish Date/Time    |
      | Test seminar name | Course 1    | 31 December           | 10:00 PM              | Timezone: America/Toronto | 31 December            | 11:00 PM               | Timezone: America/Toronto |
    And I should see date "31 December this year 10:00PM America/Toronto" formatted "%d %B %Y, %I:%M %p"
    And I should see date "31 December this year 11:00PM America/Toronto" formatted "%d %B %Y, %I:%M %p"

  Scenario: Seminar events and sessions reports should display FORMAT_JSON_EDITOR data correctly
    And I click on the seminar event action "Edit event" in row "#1"
    And I set the following fields to these values:
      | Details | {"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","marks":[{"type":"link","attrs":{"href":"https://www.totaralearning.com/products"}}],"text":"Test JSON"}]}]} |
    And I press "Save changes"
    When I navigate to "Events report" node in "Site administration > Seminars"
    And I press "Edit this report"
    And I switch to "Columns" tab
    And I add the "Event Details" column to the report
    And I press "Save changes"
    When I click on "View This Report" "link"
    Then "Test JSON" "link" should exist
    But I should not see "https://www.totaralearning.com/products"
    And I should not see "paragraph"
    And I follow "Sessions view"
    And I press "Edit this report"
    And I switch to "Columns" tab
    And I add the "Event Details" column to the report
    And I press "Save changes"
    When I click on "View This Report" "link"
    Then "Test JSON" "link" should exist
    But I should not see "https://www.totaralearning.com/products"
    And I should not see "paragraph"

  Scenario: Check the Seminar events report displays the Number of Attendees correctly when the students over booked with enabled waitlist
    When I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the seminar event action "Edit event" in row "#1"
    And I set the following fields to these values:
      | Enable waitlist  | 1 |
      | Maximum bookings | 3 |
    And I press "Save changes"
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam1 Student1, student1@moodle.com,Sam2 Student2, student2@example.com,Sam3 Student3, student3@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"

    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam4 Student4, student4@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then I should see "Test seminar name" in the "Course 1" "table_row"
    When I click on "Edit this report" "button"
    And I follow "Columns"
    And I set the field "newcolumns" to "Number of Attendees (inc Waiting Approval, Approved and Wait-listed)"
    And I press "Add"
    Then I press "Save changes"
    And I click on "View" "link"
    Then the following should exist in the "facetoface_events" table:
      | Seminar Name      | Course Name | Event Maximum Bookings | Overbooking allowed | Number of Attendees (linked to attendee page) | Booking Status | Number of Attendees (inc Waiting Approval, Approved and Wait-listed) |
      | Test seminar name | Course 1    | 3                      | Yes                 | 3                                             | Booking full   | 4                                                                    |

  Scenario: Check the Seminar events report displays the booking status correctly when Sign-up opens is used
    When I am on "Course 1" course homepage
    And I follow "View all events"

    And I click on the seminar event action "Delete event" in row "#1"
    And I press "Delete"

    And I follow "Add event"
    And I set the following fields to these values:
      | registrationtimestart[enabled]   | 1                    |
      | registrationtimestart[month]     | July                 |
      | registrationtimestart[day]       | 1                    |
      | registrationtimestart[year]      | ## next year ## Y ## |
      | registrationtimestart[hour]      | 01                   |
      | registrationtimestart[minute]    | 00                   |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[month]     | July                 |
      | timestart[day]       | 20                   |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 09                   |
      | timestart[minute]    | 00                   |
      | timefinish[month]    | July                 |
      | timefinish[day]      | 20                   |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 10                   |
      | timefinish[minute]   | 00                   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"

      # Check reports.
    When I navigate to "Events report" node in "Site administration > Seminars"
    Then I should see "Test seminar name" in the "Course 1" "table_row"
    And I should see "Booking not open" in the "Test seminar name" "table_row"
    And I should not see "Booking open" in the "Test seminar name" "table_row"