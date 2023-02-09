@mod @mod_facetoface @totara @javascript
Feature: Confirm overlapping sessions can be removed
  In order to remove additional dates
  As a user
  I need to be able to remove overlapping times

  Scenario Outline: Test removing overlapping sessions
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                           | course |
      | Test seminar name | <p>Test seminar description</p> | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details |
      | Test seminar name | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                 | starttimezone    | finish                | finishtimezone   |
      | event 1      | 15 July next year 3pm | Pacific/Auckland | 15 July next year 4pm | Pacific/Auckland |
    And I log in as "admin"
    Given I am on "Test seminar name" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I press "Add a new session"
    And I click on "Edit session" "link" in the ".f2fmanagedates .lastrow" "css_element"
    And I set the following fields to these values:
      | timestart[day]       | 15             |
      | timestart[month]     | 7              |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | <starthour>    |
      | timestart[minute]    | <startminute>  |
      | timestart[timezone]  | <timezone>     |
      | timefinish[day]      | 15             |
      | timefinish[month]    | 7              |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | <finishhour>   |
      | timefinish[minute]   | <finishminute> |
      | timefinish[timezone] | <timezone>     |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Delete" "link" in the ".f2fmanagedates .lastrow" "css_element"
    And I press "Save changes"
    Then I should not see "This date conflicts with an earlier date in this event"
    And I should see "Upcoming events"

    Examples:
      | starthour | startminute | finishhour | finishminute | timezone         |
      | 12        | 00          | 13         | 00           | Pacific/Auckland |
      | 15        | 00          | 16         | 00           | Pacific/Auckland |
      | 15        | 30          | 16         | 30           | Pacific/Auckland |
      | 14        | 30          | 15         | 30           | Pacific/Auckland |
      | 14        | 30          | 16         | 30           | Pacific/Auckland |
      | 15        | 05          | 15         | 55           | Pacific/Auckland |
      | 03        | 00          | 04         | 00           | UTC              |
