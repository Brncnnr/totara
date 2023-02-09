@mod @mod_facetoface @totara @javascript
Feature: User with permission remove seminar's message recipients is able to perform
  the action correctly

  Background:
    Given the following "courses" exist:
      | fullname  | shortname |
      | Course101 | C101      |
    And the following "users" exist:
      | username  | firstname | lastname |email                  |
      | kianbomba | Kian      | Bomba    |kian.bomba@example.com |
      | userone   | User      | One      |user.one@example.com |
      | bolobala  | Bolo      | Bala     |bolo.bala@example.com  |
    And the following "course enrolments" exist:
      | user      | course    | role    |
      | kianbomba | C101      | student |
      | userone   | C101      | teacher |
      | bolobala  | C101      | student |
    And the following "activities" exist:
      | activity    | name     | course | idnumber |
      | facetoface | Seminar1  | C101   | 1080     |
    And I am on a totara site
    And I log in as "admin"
    And I am on "Course101" course homepage
    And I follow "Seminar1"
    And I follow "Add event"
    And I press "Save changes"
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "4 potential users" to "Kian Bomba, kian.bomba@example.com"
    And I press "Add"
    And I set the field "4 potential users" to "Bolo Bala, bolo.bala@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"

  Scenario: The trainer is able to remove the recipients of seminar's message
    with the remove recipients permission
    Given I am on a totara site
    And I log out
    And I log in as "userone"
    And I am on "Course101" course homepage
    And I follow "Seminar1"
    And I click on the seminar event action "Attendees" in row "#1"
    And I follow "Message users"
    And I set the field "Booked - 2 user(s)" to "1"
    And I follow "Recipients"
    And I press "Edit recipients individually"
    And I set the field "Existing recipients" to "Bolo Bala, bolo.bala@example.com"
    And I press "Remove"
    When I press "Update"
    Then I should not see "Bolo Bala, bolo.bala@example.com"

  Scenario: Show email beside the attendees fullname if set in user identity setting and the logged in user has the capability
    Given the following config values are set as admin:
      | Show user identity  | Email |
    And I am on a totara site
    And I log out
    And I log in as "userone"
    And I am on "Course101" course homepage
    And I follow "Seminar1"
    And I click on the seminar event action "Attendees" in row "#1"
    And I follow "Message users"
    And I set the field "Booked - 2 user(s)" to "1"
    When I follow "Recipients"
    Then I should see "Kian Bomba, kian.bomba@example.com"
    And I should see "Bolo Bala, bolo.bala@example.com"
    And I should not see "User One, user.one@example.com"
    When I press "Edit recipients individually"
    Then I should see "Kian Bomba, kian.bomba@example.com"
    And I should see "Bolo Bala, bolo.bala@example.com"
    And I should not see "User One, user.one@example.com"
    And I set the field "Existing recipients" to "Bolo Bala, bolo.bala@example.com"
    And I press "Remove"
    When I press "Update"
    Then I should not see "Bolo Bala, bolo.bala@example.com"
    And I log out

  Scenario: Do not show email beside the attendees fullname if the logged in user does not have the capability to view it
    Given the following config values are set as admin:
      | Show user identity  | Email |
    And I set the following system permissions of "Trainer" role:
      | moodle/site:viewuseridentity | Prohibit |
    And I am on a totara site
    And I log out
    And I log in as "userone"
    And I am on "Course101" course homepage
    And I follow "Seminar1"
    And I click on the seminar event action "Attendees" in row "#1"
    And I follow "Message users"
    And I set the field "Booked - 2 user(s)" to "1"
    When I follow "Recipients"
    Then I should see "Kian Bomba"
    And I should see "Bolo Bala"
    And I should not see "kian.bomba@example.com"
    And I should not see "bolo.bala@example.com"
    When I press "Edit recipients individually"
    Then I should see "Kian Bomba"
    And I should see "Bolo Bala"
    And I should not see "kian.bomba@example.com"
    And I should not see "bolo.bala@example.com"
    And I press "Cancel"
    And I log out
    When I log in as "admin"
    And I am on "Course101" course homepage
    And I follow "Seminar1"
    And I click on the seminar event action "Attendees" in row "#1"
    And I follow "Message users"
    And I set the field "Booked - 2 user(s)" to "1"
    And I follow "Recipients"
    Then I should see "Kian Bomba, kian.bomba@example.com"
    And I should see "Bolo Bala, bolo.bala@example.com"
    When I press "Edit recipients individually"
    Then I should see "Kian Bomba, kian.bomba@example.com"
    And I should see "Bolo Bala, bolo.bala@example.com"
