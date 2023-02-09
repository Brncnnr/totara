@totara @mod @mod_facetoface @totara_job @javascript
Feature: Cancel signup with notification banner
  Background:
    Given the following "users" exist:
      | firstname | lastname | username   | email             |
      | User      | One      | user_one   | one@example.com   |
      | User      | Two      | user_two   | two@example.com   |
      | User      | Three    | user_three | three@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | C101     | c101      | topics |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | user_one | c101   | student |
      | user_two | c101   | student |
    # Add user three as the manager of user two.
    And the following job assignments exist:
      | user     | fullname | shortname | manager    | idnumber |
      | user_two | ut_ja    | utja      | user_three | jau2     |
    And I am using legacy seminar notifications

  Scenario: Cancel seminar sign up as normal user without manager
    Given the following "seminars" exist in "mod_facetoface" plugin:
      | name | course |
      | Sem1 | c101   |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details   |
      | Sem1       | details 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish               |
      | details 1    | 1 Jan next year 11am | 1 Jan next year 12am |
    And I log in as "user_one"
    When I am on "C101" course homepage
    Then I should see "Sem1"
    And I should see "Go to event"
    When I follow "Go to event"
    Then "Sign-up" "button" should exist
    And I should not see "Cancel booking"
    And I should not see "Booked"
    When I click on "Sign-up" "button"
    And I should see "Your request was accepted."
    And I should see "Cancel booking"
    And I click on "Cancel booking" "link_or_button" in the seminar event sidebar "Booked"
    And I wait "1" seconds
    When I click on "Cancel booking" "button" in the seminar event sidebar "Cancel booking"
    Then I should see "Your booking has been cancelled."
    And I should see "You should immediately receive a cancellation email."
    And I should not see "You and your manager should immediately receive a cancellation email."

  Scenario: Cancel seminar sign up as normal user with manager
    Given the following "seminars" exist in "mod_facetoface" plugin:
      | name | course |
      | Sem1 | c101   |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details   |
      | Sem1       | details 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish               |
      | details 1    | 1 Jan next year 11am | 1 Jan next year 12am |
    And I log in as "user_two"
    When I am on "C101" course homepage
    Then I should see "Sem1"
    And I should see "Go to event"
    When I follow "Go to event"
    Then "Sign-up" "button" should exist
    And I should not see "Cancel booking"
    And I should not see "Booked"
    When I click on "Sign-up" "button"
    And I should see "Your request was accepted."
    And I should see "Cancel booking"
    And I click on "Cancel booking" "link_or_button" in the seminar event sidebar "Booked"
    And I wait "1" seconds
    When I click on "Cancel booking" "button" in the seminar event sidebar "Cancel booking"
    Then I should see "Your booking has been cancelled."
    And I should see "You and your manager should immediately receive a cancellation email."
    And I should not see "You should immediately receive a cancellation email."

  Scenario: Cancel seminar sign up as normal user with manager and not cc manager
    Given the following "seminars" exist in "mod_facetoface" plugin:
      | name | course |
      | Sem1 | c101   |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details   |
      | Sem1       | details 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish               |
      | details 1    | 1 Jan next year 11am | 1 Jan next year 12am |
    And I log in as "admin"
    When I am on "C101" course homepage
    Then I should see "Sem1"
    And I follow "Sem1"
    And I navigate to "Legacy notifications" in current page administration
    And I click on "Edit" "link" in the "Seminar booking cancellation" "table_row"
    And I set the field "Manager copy" to ""
    And I click on "Save" "button"
    And I log out
    And I log in as "user_two"
    And I am on "C101" course homepage
    And I should see "Go to event"
    When I follow "Go to event"
    Then "Sign-up" "button" should exist
    And I should not see "Cancel booking"
    And I should not see "Booked"
    When I click on "Sign-up" "button"
    And I should see "Your request was accepted."
    And I should see "Cancel booking"
    And I click on "Cancel booking" "link_or_button" in the seminar event sidebar "Booked"
    And I wait "1" seconds
    When I click on "Cancel booking" "button" in the seminar event sidebar "Cancel booking"
    Then I should see "Your booking has been cancelled."
    And I should see "You should immediately receive a cancellation email."
    And I should not see "You and your manager should immediately receive a cancellation email."
