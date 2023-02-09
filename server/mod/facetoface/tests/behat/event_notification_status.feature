@mod @mod_facetoface @mod_facetoface_notification @totara @javascript
Feature: Seminar event notification must not be available for user after it has been disabled locally or globally
  After seminar events have been created
  As a user I should not be prompted to receive notifications if notifications have been disabled

  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username | firstname | lastname     | email               |
      | student1 | Boris     | Nikolaevich  | boris@example.com    |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity   | name              | course | idnumber |
      | facetoface | Test seminar name | C1     | seminar  |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details |
      | Test seminar name | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish        |
      | event 1      | tomorrow 9am | tomorrow 10am |
    When I log in as "admin"

  # Booking confirmation notifications.
  @javascript
  Scenario Outline: Seminar booking confirmation notifications are not available when disabled
    And I am on "Test seminar name" seminar homepage
    And I navigate to "Legacy notifications" node in "Seminar administration"
    And I click on "Edit" "link" in the "Seminar booking confirmation: [seminarname], [starttime]-[finishtime], [sessiondate]" "table_row"
    And I click on "<signup_enabled>" "radio_exact"
    And I press "Save"
    And I am on "Test seminar name" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Boris Nikolaevich, boris@example.com"
    And I press exact "add"
    When I press "Continue"
    Then I <visibility> "Send booking confirmation to new attendees"
    And I <visibility> "Send booking confirmation to new attendees' managers"
    And I log out

    And I log in as "student1"
    And I am on "Test seminar name" seminar homepage
    When I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I <visibility> "Receive confirmation by"
    When I press "Sign-up"
    Then I <visibility> "You will receive a booking confirmation email shortly."
    And I log out
    Examples:
      | signup_enabled | visibility     |
      | Active         | should see     |
      | Inactive       | should not see |

  # Booking cancellation notifications.
  @javascript
  Scenario Outline: Seminar booking cancellation notifications are not available when disabled
    And I am on "Test seminar name" seminar homepage
    And I navigate to "Legacy notifications" node in "Seminar administration"
    And I click on "Edit" "link" in the "Seminar booking cancellation" "table_row"
    And I click on "<cancellation_enabled>" "radio_exact"
    And I press "Save"
    And I am on "Test seminar name" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Boris Nikolaevich, boris@example.com"
    And I press exact "add"
    And I press "Continue"
    And I press "Confirm"
    And I set the field "Attendee actions" to "Remove users"
    And I set the field "Current attendees" to "Boris Nikolaevich, boris@example.com"
    And I press "Remove"
    When I press "Continue"
    Then I <visibility> "Notify cancelled attendees"
    Then I <visibility> "Notify cancelled attendees' managers"
    And I log out
    Examples:
      | cancellation_enabled | visibility     |
      | Active               | should see     |
      | Inactive             | should not see |
