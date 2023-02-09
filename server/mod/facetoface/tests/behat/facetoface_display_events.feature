@mod @mod_facetoface @totara @javascript
Feature: Displaying seminar's events with settings overlapping
  Background:
    Given the following "courses" exist:
        | fullname | shortname | category |
        | course1  | c101      | 0        |
    And the following "users" exist:
        | username | firstname | lastname | email             |
        | bomba    | bomba     | bolo     | bomba@example.com |
    And the following "course enrolments" exist:
        | user  | course | role    |
        | bomba | c101   | student |
    And I am on a totara site
    And I log in as "admin"
    And I am on "course1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
        | Name                                 | Seminar 1           |
        | Description                          | This is description |
        | How many times the user can sign-up? | Unlimited           |
        | Events displayed on course page      | 2                   |
    And I follow "Seminar 1"
    #Creating Event 1 - future event
    And I follow "Add event"
    And I click on "Save changes" "button"
    #Creating Event 2 - future event
    And I follow "Add event"
    And I click on "Save changes" "button"
    #Creating Event 3 - On going event
    And I follow "Add event"
    And I follow "Edit session"
    And I fill seminar session with relative date in form data:
        | sessiontimezone    | Pacific/Auckland |
        | timestart[day]     | 0                |
        | timestart[hour]    | 0                |
        | timestart[minute]  | -5               |
        | timefinish[day]    | 0                |
        | timefinish[hour]   | +1               |
        | timefinish[minute] | 0                |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I log out

  Scenario: Learner should be able to see the three events with settings overlapping
    whereas one of the event is what they have signed up
    and the other two events came from the setting
    Given I am on a totara site
    And I log in as "bomba"
    And I am on "course1" course homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    When I click on "Sign-up" "button"
    And I am on "course1" course homepage
    Then I should see "(Booked)"
    And I should not see "Event in progress"
    And I should not see "In progress"
    And I should see "Booking open"
    When I follow "View all events"
    Then I should see "(Booked)"
    And I should not see "Event in progress"
    And I should see "In progress"
    And I should see "Booking open"
