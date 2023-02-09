@javascript @mod @mod_facetoface @totara
Feature: Seminar sign-up periods display
  In order to verify seminar sign-up periods display
  As a f2fadmin
  I need to set various dates

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | student1 | Stu       | Dent     | student@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | course  | intro                           |
      | Test seminar name | C1      | <p>Test seminar description</p> |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details |
      | Test seminar name | event 1 |
    And I log in as "admin"
    And I am on "Test seminar name" seminar homepage

  Scenario Outline: Sign up students regardless of sign in period status
    Given I follow "Add event"
    And I click on "Delete" "link" in the ".f2fmanagedates" "css_element"
    And I set the following fields to these values:
      | registrationtimestart[enabled]  | <periodopen>  |
      | registrationtimestart[month]    | June          |
      | registrationtimestart[day]      | 30            |
      | registrationtimestart[year]     | <startyear>   |
      | registrationtimestart[hour]     | 01            |
      | registrationtimestart[minute]   | 00            |
      | registrationtimestart[timezone] | <startzone>   |
      | registrationtimefinish[enabled] | <periodclose> |
      | registrationtimefinish[month]   | June          |
      | registrationtimefinish[day]     | 30            |
      | registrationtimefinish[year]    | <endyear>     |
      | registrationtimefinish[hour]    | 01            |
      | registrationtimefinish[minute]  | 00            |
      | registrationtimefinish[timezone]| <endzone>     |
    And I press "Save changes"
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "f2f-actions" to "Add users"
    And I set the field "potential users" to "student@example.com"
    And I press exact "add"
    And I click on "Continue" "button"
    And I click on "Confirm" "button"
    And I switch to "Wait-list" tab
    And I should see "Stu Dent"

    Examples:
      | periodopen | startyear          | startzone        | periodclose | endyear            | endzone          |
      | 1          | ## -2 year ## Y ## | Pacific/Auckland | 1           | ## -1 year ## Y ## | Pacific/Auckland |
      | 1          | ## -2 year ## Y ## | Pacific/Auckland | 1           | ## +2 year ## Y ## | Pacific/Auckland |
      | 1          | ## +1 year ## Y ## | Pacific/Auckland | 1           | ## +2 year ## Y ## | Pacific/Auckland |
      | 1          | ## +1 year ## Y ## | Pacific/Honolulu | 1           | ## +2 year ## Y ## | Pacific/Fiji     |
      | 0          | ## +1 year ## Y ## | Pacific/Auckland | 0           | ## +2 year ## Y ## | Pacific/Auckland |
      | 1          | ## +1 year ## Y ## | Pacific/Auckland | 0           | ## +2 year ## Y ## | Pacific/Auckland |
      | 0          | ## +1 year ## Y ## | Pacific/Auckland | 1           | ## +2 year ## Y ## | Pacific/Auckland |
