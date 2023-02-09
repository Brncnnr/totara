@totara @perform @mod_perform @mod_perform_notification @javascript @vuejs
Feature: Perform activity notifications - edge cases
  As an activity administrator
  I should to be able to set that participants within an activity are notified when certain conditions are met

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname | lastname |  email                 |
      | user1      | User      | One      |      user1@example.com |
      | user2      | User      | Two      |      user2@example.com |
      | manager    | Mana      | Ger      |    manager@example.com |
      | appraiser  | Appra     | Iser     |  appraiser@example.com |
      | supervisor | Super     | Visor    | supervisor@example.com |
    And the following job assignments exist:
      | user      | manager    | appraiser | idnumber  | managerjaidnumber |
      | manager   | supervisor |           | managerja |                   |
      | user1     | manager    | appraiser | user1ja   | managerja         |
      | user2     | manager    |           | user2ja   |                   |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | aud1     |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | aud1   |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name | activity_type | activity_status | create_section | create_track |
      | Activity test | feedback      | Draft           | false          | false        |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name | section_name |
      | Activity test | section 1    |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship        |
      | section 1    | subject             |
      | section 1    | appraiser           |
      | section 1    | manager             |
      | section 1    | manager's manager   |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name | element_name |
      | section 1    | short_text   |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name | track_description | due_date_offset |
      | Activity test | track 1           | 3, DAY          |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 1           | cohort          | aud1            |
    And the following "language customisation" exist in "tool_customlang" plugin:
      | component   | id                                                          | string                                                                                                                         |
      | mod_perform | template_instance_created_reminder_subject_subject          | Your reminder - {$a->instance_days_active} days                                                                                |
      | mod_perform | template_instance_created_reminder_appraiser_subject        | Appraiser reminder - {$a->instance_days_active} days {$a->subject_fullname}                                                    |
      | mod_perform | template_instance_created_reminder_manager_subject          | Manager reminder - {$a->instance_days_active} days {$a->subject_fullname}                                                      |
      | mod_perform | template_instance_created_reminder_managers_manager_subject | Supervisor reminder - {$a->instance_days_active} days {$a->subject_fullname}                                                   |
      | mod_perform | template_instance_created_reminder_subject_body             | {$a->instance_days_active} days active, {$a->instance_days_remaining} days remaining, {$a->instance_days_overdue} days overdue |
      | mod_perform | template_instance_created_reminder_appraiser_body           | {$a->instance_days_active} days active, {$a->instance_days_remaining} days remaining, {$a->instance_days_overdue} days overdue |
      | mod_perform | template_instance_created_reminder_manager_body             | {$a->instance_days_active} days active, {$a->instance_days_remaining} days remaining, {$a->instance_days_overdue} days overdue |
      | mod_perform | template_instance_created_reminder_managers_manager_body    | {$a->instance_days_active} days active, {$a->instance_days_remaining} days remaining, {$a->instance_days_overdue} days overdue |
    And I log in as "admin"
    And I navigate to the manage perform activities page
    And I follow "Activity test"

  Scenario: mod_perform_notification_901: Recipient settings are preserved
    And I skip the scenario until issue "TL-35485" lands
    And I switch to "Notifications" tui tab
    # Participant instance creation notification is on, but only for the external participant recipient
    And I click on "Subject" tui "toggle_switch" in the "Participant instance creation" tui "collapsible"
    And I click on "Manager's manager" tui "toggle_switch" in the "Participant instance creation" tui "collapsible"
    When I switch to "Content" tui tab
    And I click on "Delete Subject" "button"
    And I click on "Delete Manager" "button"
    And I click on "Delete Manager's manager" "button"
    And I click on "Delete Appraiser" "button"
    And I close the tui notification toast
    And I reload the page
    And I click on "Add participants" "button"
    And I click on "Subject" tui "checkbox"
    And I click on "Manager" tui "checkbox"
    And I click on "Manager's manager" tui "checkbox"
    And I click on "Appraiser" tui "checkbox"
    And I click on "Done" "button"
    And I close the tui notification toast
    And I switch to "Notifications" tui tab
    Then the "Subject" tui "toggle_switch" should be on in the "Participant instance creation" tui "collapsible"
    And the "Appraiser" tui "toggle_switch" should be off in the "Participant instance creation" tui "collapsible"
    And the "Manager" tui "toggle_switch" should be off in the "Participant instance creation" tui "collapsible"
    And the "Manager's manager" tui "toggle_switch" should be on in the "Participant instance creation" tui "collapsible"

  Scenario: mod_perform_notification_902: Notification settings can be modified after activation
    And I skip the scenario until issue "TL-35485" lands
    And I switch to "Notifications" tui tab
    And I toggle the "Participant instance creation reminder" tui collapsible
    And I click on "Participant instance creation reminder notification" tui "toggle_switch"
    And I click on "Subject" tui "toggle_switch" in the "Participant instance creation reminder" tui "collapsible"
    And I click on "Manager's manager" tui "toggle_switch" in the "Participant instance creation reminder" tui "collapsible"
    And I click on "Appraiser" tui "toggle_switch" in the "Participant instance creation reminder" tui "collapsible"
    And I click on "Add" tui "button" in the "Participant instance creation reminder" tui "collapsible"
    And I click on "Add" tui "button" in the "Participant instance creation reminder" tui "collapsible"
    And I set the following fields to these values:
      | trigger-instance_created_reminder[0] | 1 |
      | trigger-instance_created_reminder[1] | 2 |
      | trigger-instance_created_reminder[2] | 4 |
    And I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I switch to "Content" tui tab
    And I click on "Delete Appraiser" "button"
    And I close the tui notification toast
    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I wait for the next second
    And I trigger cron
    And I press the "back" button in the browser

    # Delete an event trigger after activation
    And I switch to "Notifications" tui tab
    And I click on ".tui-repeater__row:first-child button[title=Delete]" "css_element"
    And I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast

    # day 0
    Given I time travel to "1 hour future" for perform activity notification

    # day 1
    Given I time travel to "1 day future" for perform activity notification

    # as admin, enable manager's notification
    And I reload the page
    And I switch to "Notifications" tui tab
    And I click on "Manager" tui "toggle_switch" in the "Participant instance creation reminder" tui "collapsible"

    # day 2
    Given I time travel to "1 day future" for perform activity notification

    # as admin, add event trigger on day 3
    And I reload the page
    And I switch to "Notifications" tui tab
    And I click on "Add" tui "button" in the "Participant instance creation reminder" tui "collapsible"
    And I set the following fields to these values:
      | trigger-instance_created_reminder[2] | 3 |
    And I close the tui notification toast

    # day 3
    Given I time travel to "1 day future" for perform activity notification

    # as admin, disable manager's notification
    And I reload the page
    And I switch to "Notifications" tui tab
    And I click on "Manager" tui "toggle_switch" in the "Participant instance creation reminder" tui "collapsible"

    # day 4
    Given I time travel to "1 day future" for perform activity notification

    # day 5
    Given I time travel to "1 day future" for perform activity notification
    And I log out

    When I log in as "user1"
    And I open the notification popover
    Then I should see "Your reminder - 2 days"
    And I should see "Your reminder - 3 days"
    And I should see "Your reminder - 4 days"
    And I follow "View full notification"
    And I wait "1" seconds
    When I click on "Your reminder - 4 days" "text"
    Then I should see "4 days active, 0 days remaining, 1 days overdue"
    And I wait "1" seconds
    When I click on "Your reminder - 3 days" "text"
    Then I should see "3 days active, 0 days remaining, 0 days overdue"
    And I wait "1" seconds
    When I click on "Your reminder - 2 days" "text"
    Then I should see "2 days active, 1 days remaining, 0 days overdue"
    And I log out

    When I log in as "user2"
    And I open the notification popover
    Then I should see "You have no notifications"
    And I log out

    When I log in as "appraiser"
    And I open the notification popover
    Then I should see "You have no notifications"
    And I log out

    When I log in as "manager"
    And I open the notification popover
    Then I should see "Manager reminder - 2 days User One"
    And I should see "Manager reminder - 3 days User One"
    But I should not see "Manager reminder - 4 days"
    And I log out

    When I log in as "supervisor"
    And I open the notification popover
    Then I should see "Supervisor reminder - 2 days User One"
    And I should see "Supervisor reminder - 3 days User One"
    And I should see "Supervisor reminder - 4 days User One"
    And I log out
