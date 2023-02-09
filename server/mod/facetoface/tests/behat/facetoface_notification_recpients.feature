@totara @mod_facetoface @mod_facetoface_notification @javascript
Feature: User is not able to create a notification with a booked recipients checked but not booked type set.
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | c101     | c101      | 0        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name      | course |
      | Seminar 1 | c101   |
    And I am on a totara site
    And I am using legacy seminar notifications
    And I log in as "admin"

  Scenario: User is not able to create a notification due to fail validation on booked type
    Given I am on "c101" course homepage
    And I follow "Seminar 1"
    And I click on "Legacy notifications" "link" in the "Administration" "block"
    When I click on "Add" "button"
    Then "recipients[upcoming_events]" "checkbox" should exist
    And I set the following fields to these values:
      | templateid | 1 |

    When I click on "Save" "button"
    Then I should see "You must choose which learners will receive this notification"
    And I set the field "recipients[upcoming_events]" to "1"
    # Notification created at this point
    When I click on "Save" "button"
    Then I should not see "You must choose which learners will receive this notification"
