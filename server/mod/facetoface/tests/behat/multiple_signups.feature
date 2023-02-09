@mod @mod_facetoface @totara @javascript
Feature: Multi signup restrictions
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                |
      | teacher1  | Tea       | Cher     | teacher@example.com  |
      | student1  | Student   | One      | student1@example.com |
      | student2  | Student   | Two      | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name      | course | attendancetime |
      | Seminar 1 | C1     | 2              |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | Seminar 1  | Event 1 |
      | Seminar 1  | Event 2 |
      | Seminar 1  | Event 3 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start         | finish                |
      | Event 1      | 1 Jan +1 year | 1 Jan +1 year +3 hour |
      | Event 2      | 2 Feb +1 year | 2 Feb +1 year +3 hour |
      | Event 3      | 3 Mar +1 year | 3 Mar +1 year +3 hour |

  Scenario: Ensure muilti signup restriction applies to event page
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Seminar 1"
    And I follow "Edit settings"
    And I set the following fields to these values:
      | How many times the user can sign-up? | 2 |
      | id_multisignuprestrictfully          | 0 |
      | id_multisignuprestrictpartly         | 1 |
    And I press "Save and display"
    And I log out

    # NOTE: Do not use `And the following "seminar signups" exist in "mod_facetoface" plugin:` here!
    # Whether students can sign up for a seminar event or not is part of the test.

    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I click on "Go to event" "link" in the "1 January" "table_row"
    And I press "Sign-up"
    And I should see "Your request was accepted"
    And I log out

    And I log in as "student2"
    And I am on "Course 1" course homepage
    When I click on "Go to event" "link" in the "1 January" "table_row"
    And I press "Sign-up"
    And I should see "Your request was accepted"
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I click on the seminar event action "Attendees" in row "1 January"
    And I switch to "Take attendance" tab
    And I set the field "Student One's attendance" to "Fully attended"
    And I set the field "Student Two's attendance" to "Partially attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Seminar 1"

    When I click on "Go to event" "link" in the "1 January" "table_row"
    Then I should see "Booked" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I should not see "Cancel booking" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I press the "back" button in the browser

    When I click on "Go to event" "link" in the "2 February" "table_row"
    Then I should see "Sign-up unavailable" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I should see "Attendance in other events restrict booking this event" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I press the "back" button in the browser

    When I click on "Go to event" "link" in the "3 March" "table_row"
    Then I should see "Sign-up unavailable" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I should see "Attendance in other events restrict booking this event" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I press the "back" button in the browser
    And I log out

    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Seminar 1"

    When I click on "Go to event" "link" in the "1 January" "table_row"
    Then I should see "Booked" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I should not see "Cancel booking" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I press the "back" button in the browser

    When I click on "Go to event" "link" in the "2 February" "table_row"
    Then I should see "Sign-up" in the ".mod_facetoface__eventinfo__sidebar__signup" "css_element"
    And I should not see "Sign-up unavailable" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I press the "back" button in the browser

    When I click on "Go to event" "link" in the "3 March" "table_row"
    Then I should see "Sign-up" in the ".mod_facetoface__eventinfo__sidebar__signup" "css_element"
    And I should not see "Sign-up unavailable" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    When I press "Sign-up"
    Then I should see "Your request was accepted"
    And I follow "View all events"

    When I click on "Go to event" "link" in the "1 January" "table_row"
    Then I should see "Booked" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I should not see "Cancel booking" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I press the "back" button in the browser

    When I click on "Go to event" "link" in the "2 February" "table_row"
    Then I should see "Sign-up unavailable" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I press the "back" button in the browser

    When I click on "Go to event" "link" in the "3 March" "table_row"
    Then I should see "Booked" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I should see "Cancel booking" in the ".mod_facetoface__eventinfo__sidebar__cancellation" "css_element"
    And I log out
