@javascript @mod @mod_facetoface @totara
Feature: Return to previous page after actions in seminar
  In order to use seminar activity comfortably
  As a user
  I need to be automatically returned back to course page or sessions page after action

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                           | course |
      | Test seminar name | <p>Test seminar description</p> | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details            |
      | Test seminar name | Test seminar event |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails       | start       | finish       |
      | Test seminar event | +3 days 9am | +3 days 10am |
    And I log in as "teacher1"

  Scenario: Course page - Seminar edit session actions return to original page
    Given I am on "Course 1" course homepage
    When I click on the seminar event action "Edit event" in row "Booking open"
    And I click on "Cancel" "button"
    Then I should see "View all events"
    And ".mod_facetoface__event-dashboard" "css_element" should not exist

    Given I am on "Course 1" course homepage
    When I click on the seminar event action "Edit event" in row "Booking open"
    And I click on "Save changes" "button"
    Then I should see "View all events"
    And ".mod_facetoface__event-dashboard" "css_element" should not exist

  Scenario: Sessions page - Seminar edit session actions return to original page
    Given I am on "Course 1" course homepage
    And I follow "View all events"
    When I click on the seminar event action "Edit event" in row "Booking open"
    And I click on "Cancel" "button"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

    Given I am on "Course 1" course homepage
    And I follow "View all events"
    When I click on the seminar event action "Edit event" in row "Booking open"
    And I click on "Save changes" "button"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

  Scenario: Course page - Seminar cancel session actions return to original page
    Given I am on "Course 1" course homepage
    When I click on the seminar event action "Cancel event" in row "Booking open"
    And I click on "No" "button"
    Then I should see "View all events"
    And ".mod_facetoface__event-dashboard" "css_element" should not exist

    Given I am on "Course 1" course homepage
    When I click on the seminar event action "Cancel event" in row "Booking open"
    And I click on "Yes" "button"
    Then I should see "View all events"
    And ".mod_facetoface__event-dashboard" "css_element" should not exist
    And I should see "Event cancelled"

  Scenario: Sessions page - Seminar cancel session actions return to original page
    Given I am on "Course 1" course homepage
    And I follow "View all events"
    When I click on the seminar event action "Cancel event" in row "Booking open"
    And I click on "No" "button"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

    Given I am on "Course 1" course homepage
    And I follow "View all events"
    When I click on the seminar event action "Cancel event" in row "Booking open"
    And I click on "Yes" "button"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"
    And I should see "Event cancelled"

  Scenario: Course page - Seminar clone session actions return to original page
    Given I am on "Course 1" course homepage
    When I click on the seminar event action "Copy event" in row "Booking open"
    And I click on "Cancel" "button"
    Then I should see "View all events"
    And ".mod_facetoface__event-dashboard" "css_element" should not exist

    Given I am on "Course 1" course homepage
    When I click on the seminar event action "Copy event" in row "Booking open"
    And I click on "Save changes" "button"
    Then I should see "View all events"
    And ".mod_facetoface__event-dashboard" "css_element" should not exist

  Scenario: Sessions page - Seminar clone session actions return to original page
    Given I am on "Course 1" course homepage
    And I follow "View all events"
    When I click on the seminar event action "Copy event" in row "Booking open"
    And I click on "Cancel" "button"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

    Given I am on "Course 1" course homepage
    And I follow "View all events"
    When I click on the seminar event action "Copy event" in row "Booking open"
    And I click on "Save changes" "button"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

  Scenario: Course page - Seminar delete session actions return to original page
    Given I am on "Course 1" course homepage
    When I click on the seminar event action "Delete event" in row "Booking open"
    And I click on "Cancel" "button"
    Then I should see "View all events"
    And ".mod_facetoface__event-dashboard" "css_element" should not exist

    Given I am on "Course 1" course homepage
    When I click on the seminar event action "Delete event" in row "Booking open"
    And I click on "Delete" "button"
    Then I should see "View all events"
    And ".mod_facetoface__event-dashboard" "css_element" should not exist
    And I should not see "Booking open"

  Scenario: Sessions page - Seminar delete session actions return to original page
    Given I am on "Course 1" course homepage
    And I follow "View all events"
    When I click on the seminar event action "Delete event" in row "Booking open"
    And I click on "Cancel" "button"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

    Given I am on "Course 1" course homepage
    And I follow "View all events"
    When I click on the seminar event action "Delete event" in row "Booking open"
    And I click on "Delete" "button"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"
    And I should not see "Booking open"

  Scenario: Course page - Seminar singup and cancel actions return to original page
    Given I am on "Course 1" course homepage

    # Course page -> Go to event -> All events -> Event dashboard
    When I click on "Go to event" "link" in the "Booking open" "table_row"
    And I click on "All events" "link" in the ".mod_facetoface__navigation" "css_element"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

    # Course page -> Go to event -> View all events -> Event dashboard
    When I click on "Go to event" "link" in the "Booking open" "table_row"
    And I click on "View all events" "button"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

    # Course page -> Go to event -> Sign-up -> Event page
    When I click on "Go to event" "link" in the "Booking open" "table_row"
    And I click on "Sign-up" "button"
    Then I should see "Back to top"

    # ... Booked ...
    Given I am on "Course 1" course homepage
    # Course page -> Go to event -> All events -> Event dashboard
    When I click on "Go to event" "link" in the "(Booked)" "table_row"
    And I click on "All events" "link" in the ".mod_facetoface__navigation" "css_element"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

    # Course page -> Go to event -> View all events -> Event dashboard
    When I click on "Go to event" "link" in the "(Booked)" "table_row"
    And I click on "View all events" "button"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

    # Course page -> Go to event -> Cancel booking -> Cancel booking -> Event page
    When I click on "Go to event" "link" in the "(Booked)" "table_row"
    And I click on "Cancel booking" "link_or_button" in the seminar event sidebar "Booked"
    And I wait "1" seconds
    And I click on "Cancel booking" "button" in the seminar event sidebar "Cancel booking"
    Then I should see "Back to top"

  Scenario: Sessions page - Seminar singup and cancel actions return to original page
    Given I am on "Course 1" course homepage
    And I follow "View all events"

    # Event dashboard -> Go to event -> All events -> Event dashboard
    When I click on "Go to event" "link" in the "Booking open" "table_row"
    And I click on "All events" "link" in the ".mod_facetoface__navigation" "css_element"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

    # Event dashboard -> Go to event -> View all events -> Event dashboard
    When I click on "Go to event" "link" in the "Booking open" "table_row"
    And I click on "View all events" "button"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

    # Event dashboard -> Go to event -> Sign-up -> Event page
    When I click on "Go to event" "link" in the "Booking open" "table_row"
    And I click on "Sign-up" "button"
    Then I should see "Back to top"

    # ... Booked ...
    Given I am on "Course 1" course homepage
    # Event dashboard -> Go to event -> All events -> Event dashboard
    When I click on "Go to event" "link" in the "(Booked)" "table_row"
    And I click on "All events" "link" in the ".mod_facetoface__navigation" "css_element"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

    # Event dashboard -> Go to event -> View all events -> Event dashboard
    When I click on "Go to event" "link" in the "Booking open" "table_row"
    And I click on "View all events" "button"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

    # Event dashboard -> Go to event -> Cancel booking -> Cancel booking -> Event page
    When I click on "Go to event" "link" in the "(Booked)" "table_row"
    And I click on "Cancel booking" "link_or_button" in the seminar event sidebar "Booked"
    And I wait "1" seconds
    And I click on "Cancel booking" "button" in the seminar event sidebar "Cancel booking"
    Then I should see "Back to top"

  Scenario: Seminar attendees back link return to seminar page - top level only
    Given I am on "Course 1" course homepage
    When I click on the seminar event action "Attendees" in row "Booking open"
    And I click on "View all events" "link"
    Then I should not see "View all events"
    And ".mod_facetoface__event-dashboard" "css_element" should exist

    Given I am on "Course 1" course homepage
    And I follow "View all events"
    When I click on the seminar event action "Attendees" in row "Booking open"
    And I click on "View all events" "link"
    Then ".mod_facetoface__event-dashboard" "css_element" should exist
    And I should not see "View all events"

  Scenario Outline: Event page - manager actions return to original page
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | teacher1 | manager | System       |           |
    And the following "position" frameworks exist:
      | fullname | idnumber |
      | position | fw1      |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname |
      | fw1       | jajaja   | jajaja   |
    And the following job assignments exist:
      | user     | position | manager  |
      | student1 | jajaja   | teacher1 |
    Given I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Allow manager reservations | Yes |
      | Maximum reservations       | 2   |
      | Reservation deadline       | 2   |
    And I press "<savebutton>"

    And I click on "Go to event" "link" in the "Upcoming" "table_row"

    # Event page -> Allocate spaces for team -> Go back -> Event page
    When I follow "Allocate spaces for team"
    And I click on "Go to event" "button"
    Then ".mod_facetoface__navigation" "css_element" should exist

    # Event page -> Allocate spaces for team -> View all events -> Event dashboard
    When I follow "Allocate spaces for team"
    And I click on "View all events" "button"
    Then ".mod_facetoface__navigation" "css_element" should not exist
    And ".mod_facetoface__event-dashboard" "css_element" <visibility> exist
    And I click on "Go to event" "link" in the "Upcoming" "table_row"

    # Event page -> Allocate spaces for team -> Add -> Event page
    When I follow "Allocate spaces for team"
    And I set the field "Available team members" to "Sam1 Student1"
    And I press "Add"
    Then ".mod_facetoface__navigation" "css_element" should exist

    # Event page -> Allocate spaces for team -> Remove -> Event page
    When I follow "Allocate spaces for team"
    And I set the field "Allocated team members" to "Sam1 Student1"
    And I press "Remove"
    Then ".mod_facetoface__navigation" "css_element" should exist

    # Event page -> Reserve spaces for team -> Go back -> Event page
    When I follow "Reserve spaces for team"
    And I click on "Go to event" "button"
    Then ".mod_facetoface__navigation" "css_element" should exist

    # Event page -> Reserve spaces for team -> View all events -> Event dashboard
    When I follow "Reserve spaces for team"
    And I click on "View all events" "button"
    Then ".mod_facetoface__navigation" "css_element" should not exist
    And ".mod_facetoface__event-dashboard" "css_element" <visibility> exist
    And I click on "Go to event" "link" in the "Upcoming" "table_row"

    # Event page -> Reserve spaces for team -> Update -> Event page
    When I follow "Reserve spaces for team"
    And I set the field "Reserve spaces for team" to "1"
    And I press "Update"
    Then ".mod_facetoface__navigation" "css_element" should exist

    # Event page -> Manage reservations -> Go back -> Event page
    When I follow "Manage reservations"
    And I click on "Go to event" "button"
    Then ".mod_facetoface__navigation" "css_element" should exist

    # Event page -> Manage reservations -> View all events -> Event dashboard
    When I follow "Manage reservations"
    And I click on "View all events" "button"
    Then ".mod_facetoface__navigation" "css_element" should not exist
    And ".mod_facetoface__event-dashboard" "css_element" <visibility> exist
    And I click on "Go to event" "link" in the "Upcoming" "table_row"

    # Event page -> Manage reservations -> Delete -> Cancel -> Go back -> Event page
    When I follow "Manage reservations"
    And I click on "Delete" "link" in the "Teacher1" "table_row"
    And I press "Cancel"
    And I click on "Go to event" "button"
    Then ".mod_facetoface__navigation" "css_element" should exist

    # Event page -> Manage reservations -> Delete -> Cancel -> View all events -> Event dashboard
    When I follow "Manage reservations"
    And I click on "Delete" "link" in the "Teacher1" "table_row"
    And I press "Cancel"
    And I click on "View all events" "button"
    Then ".mod_facetoface__navigation" "css_element" should not exist
    And ".mod_facetoface__event-dashboard" "css_element" <visibility> exist
    And I click on "Go to event" "link" in the "Upcoming" "table_row"

    # Event page -> Manage reservations -> Delete -> Continue -> Event page
    When I follow "Manage reservations"
    And I click on "Delete" "link" in the "Teacher1" "table_row"
    And I press "Continue"
    Then ".mod_facetoface__navigation" "css_element" should exist

    Examples:
      | savebutton                | visibility |
      | Save and return to course | should     |
      | Save and display          | should     |
