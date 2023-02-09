@mod @mod_facetoface @totara @totara_customfield
Feature: Seminar session date with timezone management
  In order to set up a session
  As an administrator
  I need to be able to use timezones

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                | timezone        |
      | teacher1 | Terry     | Teacher  | teacher1@example.com | Australia/Perth |
      | teacher2 | Herry     | Tutor    | teacher2@example.com | Europe/Prague   |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
    And the following "global rooms" exist in "mod_facetoface" plugin:
      | name   | allowconflicts | hidden | capacity | custom:building | custom:location |
      | Room 1 | 0              | 0      | 10       | Building 123    | {"address":"123 Tory street","size":"medium","view":"satellite","display":"map","zoom":12,"location":{"latitude":-31.95,"longitude":115.85}} |
      | Room 2 | 0              | 0      | 10       | Building 234    | {"address":"234 Tory street","size":"medium","view":"satellite","display":"map","zoom":12,"location":{"latitude":-31.95,"longitude":115.85}} |
      | Room 3 | 0              | 0      | 10       | Building 345    | {"address":"345 Tory street","size":"medium","view":"satellite","display":"map","zoom":12,"location":{"latitude":-31.95,"longitude":115.85}} |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                           | course |
      | Test seminar name | <p>Test seminar description</p> | C1     |

  @javascript
  Scenario: Create seminar session by teacher in one timezone, check that timezones stored correctly, and check be teacher in another timezone
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And the field "sessiontimezone" matches value "User timezone"
    And I set the following fields to these values:
      | sessiontimezone      | Pacific/Auckland |
      | timestart[day]       | 2                |
      | timestart[month]     | 1                |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 3                |
      | timestart[minute]    | 0                |
      | timestart[timezone]  | Europe/Prague    |
      | timefinish[day]      | 2                |
      | timefinish[month]    | 1                |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 4                |
      | timefinish[minute]   | 0                |
      | timefinish[timezone] | Europe/Prague    |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    When I click on "Select rooms" "link"
    And I click on "Room 1" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"

    And I press "Add a new session"
    And I click on "Edit session" "link" in the ".f2fmanagedates .lastrow" "css_element"
    And I set the following fields to these values:
      | sessiontimezone      | User timezone |
      | timestart[day]       | 3             |
      | timestart[month]     | 2             |
      | timestart[year]      | ## 2 years ## Y ## |
      | timestart[hour]      | 9             |
      | timestart[minute]    | 0             |
      | timestart[timezone]  | Europe/London |
      | timefinish[day]      | 3             |
      | timefinish[month]    | 2             |
      | timefinish[year]     | ## 2 years ## Y ## |
      | timefinish[hour]     | 11            |
      | timefinish[minute]   | 0             |
      | timefinish[timezone] | Europe/Prague |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    When I click on "Select rooms" "link" in the ".f2fmanagedates .lastrow" "css_element"
    And I click on "Room 2" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    When I press "Save changes"
    Then I should see "3:00 PM - 4:00 PM" in the "Room 1" "table_row"
    Then I should see "Timezone: Pacific/Auckland" in the "Room 1" "table_row"
    And I should see "5:00 PM - 6:00 PM" in the "Room 2" "table_row"
    And I should see "Timezone: Australia/Perth" in the "Room 2" "table_row"
    When I click on the seminar event action "Edit event" in row "Room 1"
    And I click on "Edit session" "link"
    Then I set the following fields to these values:
      | sessiontimezone      | Pacific/Auckland |
      | timestart[day]       | 2                |
      | timestart[month]     | January          |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 15               |
      | timestart[minute]    | 00               |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[day]      | 2                |
      | timefinish[month]    | January          |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 16               |
      | timefinish[minute]   | 00               |
      | timefinish[timezone] | Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    When I click on the seminar event action "Edit event" in row "Room 1"
    And I click on "Edit session" "link" in the ".f2fmanagedates .lastrow" "css_element"
    Then I set the following fields to these values:
      | sessiontimezone      | User timezone    |
      | timestart[day]       | 3                |
      | timestart[month]     | February         |
      | timestart[year]      | ## 2 years ## Y ## |
      | timestart[hour]      | 17               |
      | timestart[minute]    | 00               |
      | timestart[timezone]  | Australia/Perth  |
      | timefinish[day]      | 3                |
      | timefinish[month]    | February         |
      | timefinish[year]     | ## 2 years ## Y ## |
      | timefinish[hour]     | 18               |
      | timefinish[minute]   | 00               |
      | timefinish[timezone] | Australia/Perth  |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    When I press "Add a new session"
    And I click on "Edit session" "link" in the ".f2fmanagedates .lastrow" "css_element"
    Then I set the following fields to these values:
      | sessiontimezone      | Pacific/Auckland |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[timezone] | Pacific/Auckland |

    And I set the following fields to these values:
      | timestart[day]       | ## first Mon of April 2035 ## j ## |
      | timestart[month]     | 4             |
      | timestart[year]      | 2035          |
      | timestart[hour]      | 1             |
      | timestart[minute]    | 00            |
      | timefinish[day]      | ## first Mon of April 2035 ## j ## |
      | timefinish[month]    | 4             |
      | timefinish[year]     | 2035          |
      | timefinish[hour]     | 2             |
      | timefinish[minute]   | 00            |
      | sessiontimezone      | Europe/Prague |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    When I click on "Select rooms" "link" in the ".f2fmanagedates .lastrow" "css_element"
    And I click on "Room 3" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"

    When I press "Save changes"
    Then I should see "3:00 PM - 4:00 PM" in the "Room 1" "table_row"
    Then I should see "Timezone: Pacific/Auckland" in the "Room 1" "table_row"
    And I should see "5:00 PM - 6:00 PM" in the "Room 2" "table_row"
    And I should see "Timezone: Australia/Perth" in the "Room 2" "table_row"
    And I should see "3:00 PM - 4:00 PM" in the "Room 3" "table_row"
    And I should see "Timezone: Europe/Prague" in the "Room 3" "table_row"

    When I log out
    And I log in as "teacher2"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    Then I should see "3:00 PM - 4:00 PM" in the "Room 1" "table_row"
    Then I should see "Timezone: Pacific/Auckland" in the "Room 1" "table_row"
    And I should see "10:00 AM - 11:00 AM" in the "Room 2" "table_row"
    And I should see "Timezone: Europe/Prague" in the "Room 2" "table_row"
    And I should see "3:00 PM - 4:00 PM" in the "Room 3" "table_row"
    And I should see "Timezone: Europe/Prague" in the "Room 3" "table_row"

    When I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | facetoface_displaysessiontimezones | 0 |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    Then I should see "10:00 AM - 11:00 AM" in the "Room 1" "table_row"
    And I should see "5:00 PM - 6:00 PM" in the "Room 2" "table_row"
    And I should see "9:00 PM - 10:00 PM" in the "Room 3" "table_row"

  @javascript
  Scenario: Ensure that the generator sets correct timezone
    Given the following "seminars" exist in "mod_facetoface" plugin:
      | name                   | intro                                | course |
      | Test auto seminar name | <p>Test auto seminar description</p> | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface             | details |
      | Test auto seminar name | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start               | finish              |
      | event 1      | 1 Jan next year 1pm | 1 Jan next year 6pm |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start               | finish              | sessiontimezone  |
      | event 1      | 2 Feb next year 1pm | 2 Feb next year 3pm | Asia/Tokyo       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start               | starttimezone    | finish               | finishtimezone  | sessiontimezone    |
      | event 1      | 5 May next year 2am | 99               | 4 May next year 10pm | Europe/London   | Australia/Adelaide |
      | event 1      | 6 Jun next year 3pm | Pacific/Auckland | 6 Jun next year 2am  | America/Toronto | Europe/Rome        |
    And I log in as "admin"
    And I am on "Course 1" course homepage

    When I follow "Test seminar name"
    And I follow "Add event"
    And I follow "show-selectdate0-dialog"
    And I set the following fields to these values:
      | timestart[day]     | 1  |
      | timestart[month]   | 1  |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 13 |
      | timestart[minute]  | 0  |
      | timefinish[hour]   | 18 |
    And I click on "OK" "button" in the "Select date" "totaradialogue"

    And I press "Add a new session"
    And I follow "show-selectdate1-dialog"
    And I set the following fields to these values:
      | sessiontimezone    | Asia/Tokyo |
      | timestart[day]     | 2 |
      | timestart[month]   | 2 |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 13 |
      | timestart[minute]  | 0  |
      | timefinish[day]    | 2  |
      | timefinish[month]  | 2  |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 15 |
      | timefinish[minute] | 0  |
    And I click on "OK" "button" in the "Select date" "totaradialogue"

    And I press "Add a new session"
    And I follow "show-selectdate2-dialog"
    And I set the following fields to these values:
      | sessiontimezone      | Australia/Adelaide |
      | timestart[day]       | 5  |
      | timestart[month]     | 5  |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 2  |
      | timestart[minute]    | 0  |
      | timefinish[day]      | 4  |
      | timefinish[month]    | 5  |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 22 |
      | timefinish[minute]   | 0  |
      | timefinish[timezone] | Europe/London |
    And I click on "OK" "button" in the "Select date" "totaradialogue"

    And I press "Add a new session"
    And I follow "show-selectdate3-dialog"
    And I set the following fields to these values:
      | sessiontimezone      | Europe/Rome |
      | timestart[day]       | 6  |
      | timestart[month]     | 6  |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 15 |
      | timestart[minute]    | 0  |
      | timestart[timezone]  | Pacific/Auckland |
      | timefinish[day]      | 6  |
      | timefinish[month]    | 6  |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 2  |
      | timefinish[minute]   | 0  |
      | timefinish[timezone] | America/Toronto |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"

    Then I should see "Timezone: Australia/Perth" in the "1:00 PM - 6:00 PM" "table_row"
    And  I should see "Timezone: Asia/Tokyo" in the "2:00 PM - 4:00 PM" "table_row"
    And  I should see "Timezone: Australia/Adelaide" in the "3:30 AM - 6:30 AM" "table_row"
    And  I should see "Timezone: Europe/Rome" in the "5:00 AM - 8:00 AM" "table_row"

    When I follow "Test auto seminar name"
    Then I should see "Timezone: Australia/Perth" in the "1:00 PM - 6:00 PM" "table_row"
    And  I should see "Timezone: Asia/Tokyo" in the "2:00 PM - 4:00 PM" "table_row"
    And  I should see "Timezone: Australia/Adelaide" in the "3:30 AM - 6:30 AM" "table_row"
    And  I should see "Timezone: Europe/Rome" in the "5:00 AM - 8:00 AM" "table_row"
