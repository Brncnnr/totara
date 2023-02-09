@mod @mod_facetoface @javascript
Feature: Take attendance tracking time controls
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | course1  | course1   | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
      | user4    | User      | Four     | user4@example.com |
      | user5    | User      | Five     | user5@example.com |
    And the following "course enrolments" exist:
      | user  | course  | role    |
      | user1 | course1 | student |
      | user2 | course1 | student |
      | user3 | course1 | student |
      | user4 | course1 | student |
      | user5 | course1 | student |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name     | course  |
      | seminar1 | course1 |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | seminar1   | event1  |
      | seminar1   | event2  |
      | seminar1   | event3  |
      | seminar1   | event4  |
      | seminar1   | event5  |

    #         PAST             NOW          FUTURE
    #                           |
    # event1                    : <====>    <====>
    # event2                 <====>    <====>
    # event3            <====>  :  <====>
    # event4       <====>    <====>
    # event5  <====>    <====>  :
    #                           |
    #         PAST             NOW          FUTURE
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish       |
      | event1       | +2 day  0:00 | +2 day  1:00 |
      | event1       | +2 day  2:00 | +2 day  3:00 |
      | event2       | -1 day  4:00 | +1 day  5:00 |
      | event2       | +2 day  6:00 | +2 day  7:00 |
      | event3       | -2 day  8:00 | -2 day  9:00 |
      | event3       | +2 day 10:00 | +2 day 11:00 |
      | event4       | -2 day 12:00 | -2 day 13:00 |
      | event4       | -1 day 14:00 | +1 day 15:00 |
      | event5       | -1 day 16:00 | -1 day 17:00 |
      | event5       | -2 day 18:00 | -2 day 19:00 |

    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user  | eventdetails |
      | user1 | event1       |
      | user2 | event2       |
      | user3 | event3       |
      | user4 | event4       |
      | user5 | event5       |
    And I log in as "admin"
    Given I am on "seminar1" seminar homepage

  Scenario: Session attendance tracking - End of session
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Session attendance tracking | 4 |
    And I click on "Save and display" "button"

    When I click on "Take attendance" "link" in the ", 8:00 AM" "table_row"
    Then the ", 10:00 AM" "option" should be disabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 12:00 PM" "table_row"
    Then the ", 2:00 PM" "option" should be disabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 4:00 PM" "table_row"
    Then the ", 6:00 PM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 6:00 PM" "table_row"
    Then the ", 4:00 PM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on the seminar event action "Attendees" in row ", 12:00 AM"
    Then I should see the "Take attendance" tab is disabled
    And I press the "back" button in the browser

    When I click on the seminar event action "Attendees" in row ", 4:00 AM"
    Then I should see the "Take attendance" tab is disabled
    And I press the "back" button in the browser

  Scenario: Session attendance tracking - Beginning of session
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Session attendance tracking | 5 |
    And I click on "Save and display" "button"

    When I click on "Take attendance" "link" in the ", 4:00 AM" "table_row"
    Then the ", 6:00 AM" "option" should be disabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 8:00 AM" "table_row"
    Then the ", 10:00 AM" "option" should be disabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 12:00 PM" "table_row"
    Then the ", 2:00 PM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 2:00 PM" "table_row"
    Then the ", 12:00 PM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 4:00 PM" "table_row"
    Then the ", 6:00 PM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 6:00 PM" "table_row"
    Then the ", 4:00 PM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on the seminar event action "Attendees" in row ", 12:00 AM"
    Then I should see the "Take attendance" tab is disabled
    And I press the "back" button in the browser

  Scenario: Session attendance tracking - Unrestricted
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Session attendance tracking | 2 |
    And I click on "Save and display" "button"

    When I click on "Take attendance" "link" in the ", 12:00 AM" "table_row"
    Then the ", 2:00 AM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 2:00 AM" "table_row"
    Then the ", 12:00 AM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 4:00 AM" "table_row"
    Then the ", 6:00 AM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 6:00 AM" "table_row"
    Then the ", 4:00 AM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 8:00 AM" "table_row"
    Then the ", 10:00 AM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 10:00 AM" "table_row"
    Then the ", 8:00 AM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 12:00 PM" "table_row"
    Then the ", 2:00 PM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 2:00 PM" "table_row"
    Then the ", 12:00 PM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 4:00 PM" "table_row"
    Then the ", 6:00 PM" "option" should be enabled
    And I press the "back" button in the browser

    When I click on "Take attendance" "link" in the ", 6:00 PM" "table_row"
    Then the ", 4:00 PM" "option" should be enabled
    And I press the "back" button in the browser

  Scenario: Event attendance tracking - End of final session
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Event attendance – mark at | 0 |
    And I click on "Save and display" "button"

    When I click on the seminar event action "Attendees" in row ", 12:00 AM"
    Then I should see the "Take attendance" tab is disabled
    And I press the "back" button in the browser

    When I click on the seminar event action "Attendees" in row ", 4:00 AM"
    Then I should see the "Take attendance" tab is disabled
    And I press the "back" button in the browser

    When I click on the seminar event action "Attendees" in row ", 8:00 AM"
    Then I should see the "Take attendance" tab is disabled
    And I press the "back" button in the browser

    When I click on the seminar event action "Attendees" in row ", 12:00 PM"
    Then I should see the "Take attendance" tab is disabled
    And I press the "back" button in the browser

    When I click on "Take event attendance" "link" in the ", 6:00 PM" "table_row"
    Then the "User Five's attendance" "select" should be enabled
    And I press the "back" button in the browser

  Scenario: Event attendance tracking - Beginning of first session
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Event attendance – mark at | 1 |
    And I click on "Save and display" "button"

    When I click on the seminar event action "Attendees" in row ", 12:00 AM"
    Then I should see the "Take attendance" tab is disabled
    And I press the "back" button in the browser

    When I click on "Take event attendance" "link" in the ", 4:00 AM" "table_row"
    Then the "User Two's attendance" "select" should be enabled
    And I press the "back" button in the browser

    When I click on "Take event attendance" "link" in the ", 8:00 AM" "table_row"
    Then the "User Three's attendance" "select" should be enabled
    And I press the "back" button in the browser

    When I click on "Take event attendance" "link" in the ", 12:00 PM" "table_row"
    Then the "User Four's attendance" "select" should be enabled
    And I press the "back" button in the browser

    When I click on "Take event attendance" "link" in the ", 6:00 PM" "table_row"
    Then the "User Five's attendance" "select" should be enabled
    And I press the "back" button in the browser

  Scenario: Event attendance tracking - Beginning of final session
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Event attendance – mark at | 3 |
    And I click on "Save and display" "button"

    When I click on the seminar event action "Attendees" in row ", 12:00 AM"
    Then I should see the "Take attendance" tab is disabled
    And I press the "back" button in the browser

    When I click on the seminar event action "Attendees" in row ", 4:00 AM"
    Then I should see the "Take attendance" tab is disabled
    And I press the "back" button in the browser

    When I click on the seminar event action "Attendees" in row ", 8:00 AM"
    Then I should see the "Take attendance" tab is disabled
    And I press the "back" button in the browser

    When I click on "Take event attendance" "link" in the ", 12:00 PM" "table_row"
    Then the "User Four's attendance" "select" should be enabled
    And I press the "back" button in the browser

    When I click on "Take event attendance" "link" in the ", 6:00 PM" "table_row"
    Then the "User Five's attendance" "select" should be enabled
    And I press the "back" button in the browser

  Scenario: Event attendance tracking - Unrestricted
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Event attendance – mark at | 2 |
    And I click on "Save and display" "button"

    When I click on "Take event attendance" "link" in the ", 12:00 AM" "table_row"
    Then the "User One's attendance" "select" should be enabled
    And I press the "back" button in the browser

    When I click on "Take event attendance" "link" in the ", 4:00 AM" "table_row"
    Then the "User Two's attendance" "select" should be enabled
    And I press the "back" button in the browser

    When I click on "Take event attendance" "link" in the ", 8:00 AM" "table_row"
    Then the "User Three's attendance" "select" should be enabled
    And I press the "back" button in the browser

    When I click on "Take event attendance" "link" in the ", 12:00 PM" "table_row"
    Then the "User Four's attendance" "select" should be enabled
    And I press the "back" button in the browser

    When I click on "Take event attendance" "link" in the ", 6:00 PM" "table_row"
    Then the "User Five's attendance" "select" should be enabled
    And I press the "back" button in the browser
