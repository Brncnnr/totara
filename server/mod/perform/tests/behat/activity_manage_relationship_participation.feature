@totara @perform @mod_perform @javascript @vuejs
Feature: Manage relationship participation toggles
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user      | 1        | user1@example.com |
      | user2    | user      | 2        | user2@example.com |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name | activity_type | activity_status | create_track | create_section |
      | activity1     | check-in      | Draft           | true         | true           |

  Scenario: Select manual participant roles
    When I log in as "admin"
    And I navigate to the manage perform activities page
    Then I should see the tui datatable contains:
      | Name        | Type      | Status |
      | activity1   | Check-in  | Draft  |

    When I follow "activity1"
    And I click on "Assignments" "link"
    Then I should see "Manage relationship participation"
    And I should see "Override global settings"
    And the "Override global settings" tui toggle switch should be "off"
    And I should not see "Auto-assign new participants to existing activities on role change"
    And I should not see "Auto-close all participant instances for removed participants"

    When I click on the "Override global settings" tui toggle button
    Then I should see "Activity saved" in the tui success notification toast and close it
    Then I should see "Auto-assign new participants to existing activities on role change"
    And I should see "Auto-close all participant instances for removed participants"
    And the "Auto-assign new participants to existing activities on role change" tui toggle switch should be "off"
    And the "Auto-close all participant instances for removed participants" tui toggle switch should be "off"

    When I click on the "Auto-assign new participants to existing activities on role change" tui toggle button
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I click on the "Auto-close all participant instances for removed participants" tui toggle button
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I reload the page
    And I click on "Assignments" "link"
    Then the "Override global settings" tui toggle switch should be "on"
    And the "Auto-assign new participants to existing activities on role change" tui toggle switch should be "on"
    And the "Auto-close all participant instances for removed participants" tui toggle switch should be "on"

    # Make sure the toggle states persist even when hidden
    When I click on the "Override global settings" tui toggle button
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I click on the "Override global settings" tui toggle button
    Then the "Override global settings" tui toggle switch should be "on"
    And the "Auto-assign new participants to existing activities on role change" tui toggle switch should be "on"
    And the "Auto-close all participant instances for removed participants" tui toggle switch should be "on"
    When I click on the "Override global settings" tui toggle button
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I reload the page
    And I click on "Assignments" "link"
    Then the "Override global settings" tui toggle switch should be "off"
    And I click on the "Override global settings" tui toggle button
    Then I should see "Activity saved" in the tui success notification toast and close it
    And the "Override global settings" tui toggle switch should be "on"
    And the "Auto-assign new participants to existing activities on role change" tui toggle switch should be "on"
    And the "Auto-close all participant instances for removed participants" tui toggle switch should be "on"
