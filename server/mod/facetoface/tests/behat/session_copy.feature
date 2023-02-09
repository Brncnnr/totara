@mod @mod_facetoface @totara @javascript
Feature: Copying seminar sessions
  To help in managing sessions
  As a user
  I need to be able to correctly copy sessions

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | learner1 | C1     | student        |
      | learner2 | C1     | student        |
      | learner3 | C1     | student        |

    And the following "seminars" exist in "mod_facetoface" plugin:
      | name         | course | intro               |
      | Test Seminar | C1     | <p>Test Seminar</p> |

    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details |
      | Test Seminar | event 1 |
      | Test Seminar | event 2 |

    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                 | finish               | sessiontimezone  | starttimezone    | finishtimezone   |
      | event 1      | 10 Jan next year 10am | 10 Jan next year 4pm | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |
      | event 2      | 20 Jan next year 9am  | 20 Jan next year 2pm | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |

  Scenario: Copy session whose users are already booked in another session on the same day
    Given the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | learner1 | event 1      | booked |
      | learner2 | event 1      | booked |
      | learner1 | event 2      | booked |
      | learner2 | event 2      | booked |
    And I am on a totara site
    And I log in as "admin"
    And I am on "Course 1" course homepage

    When I click on the seminar event action "Event details" in row "10 January"
    Then I should see date "10 Jan next year 10:00 AM " formatted "%d %B %Y, %I:%M %p"

    When I switch to "Attendees" tab
    Then I should see "Learner One"
    And I should see "Learner Two"

    When I am on "Course 1" course homepage
    And I click on the seminar event action "Copy event" in row "10 January"
    And I click to edit the seminar event date at position 1
    And I set the following fields to these values:
      | timestart[day]       | 20                   |
      | timestart[month]     | 1                    |
      | timestart[year]      | ## next year ## Y ## |
      | timestart[hour]      | 09                   |
      | timestart[minute]    | 00                   |
      | timestart[timezone]  | Pacific/Auckland     |
      | timefinish[day]      | 20                   |
      | timefinish[month]    | 1                    |
      | timefinish[year]     | ## next year ## Y ## |
      | timefinish[hour]     | 14                   |
      | timefinish[minute]   | 00                   |
      | timefinish[timezone] | Pacific/Auckland     |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity | 300 |
    And I press "Save changes"
    And I click on the seminar event action "Event details" in row "0 / 300"
    Then I should see date "20 Jan next year 09:00 AM" formatted "%d %B %Y, %I:%M %p"

    When I switch to "Attendees" tab
    Then I should not see "Learner One"
    And I should not see "Learner Two"
