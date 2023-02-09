@mod @mod_facetoface @totara
Feature: Suspend user in different session times
  In order to test the suspended user in Face to face
  As admin
  I need to keep or remove the suspend user in/from session

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name              | multiplesessions | course | idnumber |
      | facetoface | Test seminar name | 1                | C1     | seminar  |

  @javascript
  Scenario: Create sessions with different dates and add users to a face to face sessions
    # Create four events (sessions) using relative formats
    # 1) 1 January in two years future
    # 2) Wait-listed = an event with no sessions
    # 3) 1 February in two years past
    # 4) from 1 March last year to 1 March next year

    # Then use a month name to look up each event
    # * January = future event
    # * February = past event
    # * March = ongoing event

    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"

    # 1) Session in the fututre
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1                  |
      | timestart[month]   | 1                  |
      | timestart[year]    | ## 2 years ## Y ## |
      | timestart[hour]    | 11                 |
      | timestart[minute]  | 00                 |
      | timefinish[day]    | 1                  |
      | timefinish[month]  | 1                  |
      | timefinish[year]   | ## 2 years ## Y ## |
      | timefinish[hour]   | 12                 |
      | timefinish[minute] | 00                 |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | Maximum bookings | 1 |
    And I press "Save changes"

    When I click on the seminar event action "Attendees" in row "January"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam1 Student1, student1@example.com,Sam2 Student2, student2@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I click on "View all events" "link"

    # 2) Session is wait-listed
    And I follow "Add event"
    And I click on "Delete" "link" in the ".f2fmanagedates" "css_element"
    And I set the following fields to these values:
      | Maximum bookings | 2 |
    And I press "Save changes"

    When I click on the seminar event action "Attendees" in row "Wait-listed"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam1 Student1, student1@example.com,Sam2 Student2, student2@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "Wait-list"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I click on "View all events" "link"

    # 3) Session in the past
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1                      |
      | timestart[month]   | 2                      |
      | timestart[year]    | ## 2 years ago ## Y ## |
      | timestart[hour]    | 11                     |
      | timestart[minute]  | 00                     |
      | timefinish[day]    | 1                      |
      | timefinish[month]  | 2                      |
      | timefinish[year]   | ## 2 years ago ## Y ## |
      | timefinish[hour]   | 12                     |
      | timefinish[minute] | 00                     |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | Maximum bookings | 2 |
    And I press "Save changes"

    When I click on the seminar event action "Attendees" in row "February"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam1 Student1, student1@example.com,Sam2 Student2, student2@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I click on "View all events" "link"

    # 4) Session in progress
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1                    |
      | timestart[month]   | 3                    |
      | timestart[year]    | ## last year ## Y ## |
      | timestart[hour]    | 0                    |
      | timestart[minute]  | 0                    |
      | timefinish[day]    | 1                    |
      | timefinish[month]  | 3                    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 1                    |
      | timefinish[minute] | 0                    |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | Maximum bookings | 2 |
    And I press "Save changes"

    When I click on the seminar event action "Attendees" in row "March"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam1 Student1, student1@example.com,Sam2 Student2, student2@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I click on "View all events" "link"

    # Suspend Sam1 Student1 user
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Manage login of Sam1 Student1" "link" in the "Sam1 Student1" "table_row"
    And I set the "Choose" Totara form field to "Suspend user account"
    And I press "Update"

    And I am on "Course 1" course homepage
    And I follow "Test seminar name"

    # Check the result
    When I click on the seminar event action "Attendees" in row "January"
    Then I should not see "Sam1 Student1"
    And I should see "Sam2 Student2"

    And I click on "View all events" "link"

    When I click on the seminar event action "Attendees" in row "Wait-listed"
    And I follow "Wait-list"
    Then I should not see "Sam1 Student1"
    And I should see "Sam2 Student2"

    And I click on "View all events" "link"
    When I click on the seminar event action "Attendees" in row "February"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"

    And I click on "View all events" "link"

    When I click on the seminar event action "Attendees" in row "March"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
