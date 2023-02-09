@totara @perform @mod_perform @mod_perform_notification @javascript @vuejs
Feature: Perform activity validation in the notifications tab
  Background:
    Given I am on a totara site
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name | activity_type | activity_status | create_section | create_track |
      | Activity test | feedback      | Draft           | false          | true         |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name | section_name |
      | Activity test | section 1    |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name | element_name |
      | section 1    | short_text   |
    And I log in as "admin"
    And I navigate to the manage perform activities page
    And I follow "Activity test"
    And I switch to "Notifications" tui tab

  Scenario: mod_perform_notification_001: Only participants are displayed as recipients
    And I skip the scenario until issue "TL-35485" lands
    And I click on "Participant instance creation reminder notification" tui "toggle_switch"
    When I toggle the "Participant instance creation reminder" tui collapsible
    Then I should see "No recipients. Go to Content tab: Responding participants, to add recipients" in the "Participant instance creation reminder" tui "collapsible"
    Given I switch to "Content" tui tab
    And I click on "Add participants" "button"
    And I click on "Subject" tui "checkbox" in the "Select participants" tui "popover"
    And I click on "Appraiser" tui "checkbox" in the "Select participants" tui "popover"
    And I click on "Peer" tui "checkbox" in the "Select participants" tui "popover"
    And I click on "Reviewer" tui "checkbox" in the "Select participants" tui "popover"
    And I click on "External respondent" tui "checkbox" in the "Select participants" tui "popover"
    And I click on "Done" tui "button" in the "Select participants" tui "popover"
    And I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    When I switch to "Notifications" tui tab
    Then I should see "Subject" in the "Participant instance creation reminder" tui "collapsible"
    And I should see "Appraiser" in the "Participant instance creation reminder" tui "collapsible"
    And I should see "Peer" in the "Participant instance creation reminder" tui "collapsible"
    And I should see "Reviewer" in the "Participant instance creation reminder" tui "collapsible"
    And I should see "External respondent" in the "Participant instance creation reminder" tui "collapsible"
    But I should not see "Manager" in the "Participant instance creation reminder" tui "collapsible"
    But I should not see "Mentor" in the "Participant instance creation reminder" tui "collapsible"
    Given I switch to "Content" tui tab
    And I click on "Delete Subject" "button"
    And I close the tui notification toast
    And I click on "Delete Appraiser" "button"
    And I close the tui notification toast
    And I click on "Delete Peer" "button"
    And I close the tui notification toast
    And I click on "Delete Reviewer" "button"
    And I close the tui notification toast
    And I click on "Delete External respondent" "button"
    And I close the tui notification toast
    When I switch to "Notifications" tui tab
    Then I should see "No recipients. Go to Content tab: Responding participants, to add recipients" in the "Participant instance creation reminder" tui "collapsible"

  Scenario: mod_perform_notification_002: Trigger events are displayed only on the reminders
    And I skip the scenario until issue "TL-13952" lands
    And I click on "Participant selection notification" tui "toggle_switch"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Participant instance creation notification" tui "toggle_switch"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Participant instance creation reminder notification" tui "toggle_switch"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Due date approaching reminder notification" tui "toggle_switch"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "On due date reminder notification" tui "toggle_switch"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Overdue reminder notification" tui "toggle_switch"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Completion of subject instance notification" tui "toggle_switch"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Reopened activity notification" tui "toggle_switch"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I toggle the "Participant selection" tui collapsible
    And I toggle the "Participant instance creation" tui collapsible
    And I toggle the "Participant instance creation reminder" tui collapsible
    And I toggle the "Due date approaching reminder" tui collapsible
    And I toggle the "On due date reminder" tui collapsible
    And I toggle the "Overdue reminder" tui collapsible
    And I toggle the "Completion of subject instance" tui collapsible
    And I toggle the "Reopened activity" tui collapsible
    And the field "trigger-instance_created_reminder[0]" matches value "1"
    And the field "trigger-due_date_reminder[0]" matches value "1"
    And the field "trigger-overdue_reminder[0]" matches value "1"
    But I should not see "Trigger events" in the "Participant selection" tui "collapsible"
    But I should not see "Trigger events" in the "Participant instance creation" tui "collapsible"
    And I should see "day(s) after instance creation" in the "Participant instance creation reminder" tui "collapsible"
    And I should see "day(s) before due date" in the "Due date approaching reminder" tui "collapsible"
    But I should not see "Trigger events" in the "On due date reminder" tui "collapsible"
    And I should see "day(s) after due date" in the "Overdue reminder" tui "collapsible"
    But I should not see "Trigger events" in the "Completion of subject instance" tui "collapsible"
    But I should not see "Trigger events" in the "Reopened activity" tui "collapsible"

  Scenario: mod_perform_notification_003: Trigger events must be unique and between 1 and 365
    And I skip the scenario until issue "TL-13952" lands
    When I toggle the "Participant instance creation reminder" tui collapsible
    And I click on "Add" tui "button" in the "Participant instance creation reminder" tui "collapsible"
    Then I should not see "Set trigger from 1 to 365 days" in the "Participant instance creation reminder" tui "collapsible"
    When I set the field "trigger-instance_created_reminder[1]" to "0"
    Then I should see "Set trigger from 1 to 365 days" in the "Participant instance creation reminder" tui "collapsible"
    When I set the field "trigger-instance_created_reminder[1]" to "366"
    Then I should see "Set trigger from 1 to 365 days" in the "Participant instance creation reminder" tui "collapsible"
    When I set the field "trigger-instance_created_reminder[1]" to "1"
    Then I should see "Duplicate trigger. Delete or change number" exactly "2" times
    When I set the field "trigger-instance_created_reminder[1]" to "3"
    Then I should see "Activity saved" in the tui success notification toast
    But I should not see "Set trigger from 1 to 365 days" in the "Participant instance creation reminder" tui "collapsible"
    But I should not see "Duplicate trigger" in the "Participant instance creation reminder" tui "collapsible"

  Scenario: mod_perform_notification_004: Trigger events are sorted after reloading the page
    And I skip the scenario until issue "TL-13952" lands
    And I click on "Due date approaching reminder notification" tui "toggle_switch"
    And I click on "Overdue reminder notification" tui "toggle_switch"
    And I toggle the "Due date approaching reminder" tui collapsible
    And I toggle the "Overdue reminder" tui collapsible
    And I click on "Add" tui "button" in the "Due date approaching reminder" tui "collapsible"
    And I click on "Add" tui "button" in the "Due date approaching reminder" tui "collapsible"
    And I click on "Add" tui "button" in the "Overdue reminder" tui "collapsible"
    And I click on "Add" tui "button" in the "Overdue reminder" tui "collapsible"
    When I set the following fields to these values:
      | trigger-due_date_reminder[0] | 3 |
      | trigger-due_date_reminder[1] | 1 |
      | trigger-due_date_reminder[2] | 4 |
      | trigger-overdue_reminder[0]  | 3 |
      | trigger-overdue_reminder[1]  | 1 |
      | trigger-overdue_reminder[2]  | 4 |
    Then I should see "Activity saved" in the tui success notification toast
    And I reload the page
    Given I switch to "Notifications" tui tab
    Then the following fields match these values:
      | trigger-due_date_reminder[0] | 1 |
      | trigger-due_date_reminder[1] | 3 |
      | trigger-due_date_reminder[2] | 4 |
      | trigger-overdue_reminder[0]  | 1 |
      | trigger-overdue_reminder[1]  | 3 |
      | trigger-overdue_reminder[2]  | 4 |
