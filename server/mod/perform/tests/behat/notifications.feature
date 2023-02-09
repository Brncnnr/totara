@totara @perform @mod_perform @mod_perform_notification @javascript @vuejs
Feature: Perform activity notifications - core relationships
  As an activity administrator
  I should to be able to set that participants within an activity are notified when certain conditions are met

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | user1     | User      | One      | user1@example.com     |
      | user2     | User      | Two      | user2@example.com     |
      | user3     | User      | Three    | user3@example.com     |
      | manager   | Mana      | Ger      | manager@example.com   |
      | appraiser | Appra     | Iser     | appraiser@example.com |
    And the following job assignments exist:
      | user  | manager | appraiser |
      | user1 | manager | appraiser |
      | user2 | manager |           |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | aud1     |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | aud1   |
      | user3 | aud1   |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name | activity_type | activity_status | create_section | create_track |
      | Activity test | feedback      | Draft           | false          | false        |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name | section_name |
      | Activity test | section 1    |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship |
      | section 1    | subject      |
      | section 1    | manager      |
      | section 1    | appraiser    |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name | element_name |
      | section 1    | short_text   |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name | track_description | due_date_offset |
      | Activity test | track 1           | 2, WEEK         |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 1           | cohort          | aud1            |
    And the following "language customisation" exist in "tool_customlang" plugin:
      | component   | id                                                   | string                               | comment   |
      | mod_perform | template_instance_created_subject_subject            | New activity notice                  | English   |
      | mod_perform | template_instance_created_reminder_subject_subject   | Te manatu mō te whakarite            | Maori     |
      | mod_perform | template_due_date_reminder_subject_subject           | Si avvicina la scadenza              | Italian   |
      | mod_perform | template_due_date_subject_subject                    | Notificación de fecha de vencimiento | Spanish   |
      | mod_perform | template_overdue_reminder_subject_subject            | Försenad påminnelse                  | Swedish   |
      | mod_perform | template_completion_subject_subject                  | Ukončení činnosti                    | Czech     |
      | mod_perform | template_reopened_subject_subject                    | Ua toe tatalaina se gaoioiga         | Samoan    |
      | mod_perform | template_instance_created_appraiser_subject          | Nuwe aktiwiteitskennisgewing         | Afrikaans |
      | mod_perform | template_instance_created_reminder_appraiser_subject | Herinnering aan activiteit           | Dutch     |
      | mod_perform | template_due_date_reminder_appraiser_subject         | A határidő közeledik                 | Hungarian |
      | mod_perform | template_due_date_appraiser_subject                  | Iraungitze data jakinaraztea         | Basque    |
      | mod_perform | template_overdue_reminder_appraiser_subject          | Spomenuté oneskorenie                | Slovak    |
      | mod_perform | template_completion_appraiser_subject                | Finalizarea activității              | Romanian  |
      | mod_perform | template_reopened_appraiser_subject                  | Dejavnost je bila znova odprta       | Slovenian |
    And I log in as "admin"
    And I navigate to the manage perform activities page
    And I follow "Activity test"
    And I switch to "Notifications" tui tab

  Scenario: mod_perform_notification_101: Instance creation notification
    And I skip the scenario until issue "TL-35485" lands
    # By default instance creation is enabled
    # External respondent is also active as a recipient, but only shown if they are a participant for a section.
    Given I should not see "External respondent" in the "Participant instance creation" tui "collapsible"
    When I switch to "Content" tui tab
    And I click the add responding participant button
    And I select "External respondent" in the responding participants popover
    And I switch to "Notifications" tui tab
    Then the "External respondent" tui "toggle_switch" should be on in the "Participant instance creation" tui "collapsible"
    And I switch to "Content" tui tab
    And I remove "External respondent" as a perform activity participant
    And I switch to "Notifications" tui tab

    # Add the other recipients
    When I click on "Subject" tui "toggle_switch" in the "Participant instance creation" tui "collapsible"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Appraiser" tui "toggle_switch" in the "Participant instance creation" tui "collapsible"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I wait for the next second
    And I trigger cron

    # run adhoc task to send notifications.
    And I run the adhoc scheduled tasks "mod_perform\task\send_participant_instance_creation_notifications_task"
    And I am on homepage
    And I log out

    # user1 should receive a notification
    When I log in as "user1"
    And I open the notification popover
    And I wait for pending js
    Then I should see "New activity notice" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi User One"
    And I should see "Your Activity test Feedback is ready for you to complete"
    And I should see date "2 weeks" formatted "This needs to be completed by %d %B %Y"
    When I follow "Activity test"
    Then I should see "Activity test" in the page title
    And I log out

    # appraiser should receive a notification
    When I log in as "appraiser"
    And I open the notification popover
    Then I should see "Nuwe aktiwiteitskennisgewing" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi Appra Iser"
    And I should see "As User One’s Appraiser, you have been selected"
    And I should see "Activity test Feedback"
    And I should see date "2 weeks" formatted "This needs to be completed by %d %B %Y"
    When I follow "Activity test"
    Then I should see "Activity test" in the page title
    And I log out

    # user2 should not receive any notifications
    When I log in as "user2"
    And I open the notification popover
    And I wait for pending js
    Then I should see "You have no notifications"
    And I log out

    # manager should not receive any notifications
    When I log in as "manager"
    And I open the notification popover
    And I wait for pending js
    Then I should see "You have no notifications"

  Scenario: mod_perform_notification_102: Instance creation reminder
    And I skip the scenario until issue "TL-35485" lands
    And I toggle the "Participant instance creation reminder" tui collapsible
    And I click on "Participant instance creation reminder notification" tui "toggle_switch"
    And I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Subject" tui "toggle_switch" in the "Participant instance creation reminder" tui "collapsible"
    And I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Appraiser" tui "toggle_switch" in the "Participant instance creation reminder" tui "collapsible"
    And I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Add" tui "button" in the "Participant instance creation reminder" tui "collapsible"
    And I click on "Add" tui "button" in the "Participant instance creation reminder" tui "collapsible"
    And I click on "Add" tui "button" in the "Participant instance creation reminder" tui "collapsible"
    And I set the following fields to these values:
      | trigger-instance_created_reminder[0] | 1 |
      | trigger-instance_created_reminder[1] | 2 |
      | trigger-instance_created_reminder[2] | 4 |
      | trigger-instance_created_reminder[3] | 6 |
    And I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I wait for the next second
    And I trigger cron
    And I press the "back" button in the browser
    And I log out

    # 0 day 0 hour
    When I log in as "user1"
    And I open the notification popover
    And I wait for pending js
    Then I should see "You have no notifications"

    # 0 day 23 hour
    Given I time travel to "23 hours future" for perform activity notification
    And I reload the page
    And I open the notification popover
    And I wait for pending js
    Then I should see "You have no notifications"

    # 1 day 1 hour
    Given I time travel to "2 hours future" for perform activity notification
    And I reload the page
    And I open the notification popover
    And I wait for pending js
    Then I should see "Te manatu mō te whakarite" exactly "1" times
    And I am on site homepage

    # 1 day 23 hour
    Given I time travel to "22 hours future" for perform activity notification
    And I reload the page
    And I open the notification popover
    And I wait for pending js
    Then I should see "Te manatu mō te whakarite" exactly "1" times

    # 2 day 1 hour
    Given I time travel to "2 hours future" for perform activity notification
    And I reload the page
    And I open the notification popover
    And I wait for pending js
    Then I should see "Te manatu mō te whakarite" exactly "2" times

    # 2 day 23 hour
    Given I time travel to "22 hours future" for perform activity notification
    And I reload the page
    And I open the notification popover
    And I wait for pending js
    Then I should see "Te manatu mō te whakarite" exactly "2" times

    # 3 day 23 hour
    Given I time travel to "1 day future" for perform activity notification
    And I reload the page
    And I open the notification popover
    And I wait for pending js
    Then I should see "Te manatu mō te whakarite" exactly "2" times

    # 5 day 1 hour (notification is not sent on day 4 because cron is not run)
    Given I time travel to "26 hours future" for perform activity notification
    And I reload the page
    And I open the notification popover
    And I wait for pending js
    Then I should see "Te manatu mō te whakarite" exactly "2" times

    # 6 day 1 hour
    Given I time travel to "1 day future" for perform activity notification
    And I reload the page
    And I open the notification popover
    And I wait for pending js
    Then I should see "Te manatu mō te whakarite" exactly "3" times
    When I follow "View full notification"
    Then I should see "Hi User One"
    And I should see "6 days ago you were sent your Activity test Feedback to complete"
    And I should see date "2 weeks" formatted "This needs to be completed by %d %B %Y"
    When I follow "Activity test"
    Then I should see "Activity test" in the page title
    And I log out

    # appraiser should receive as many notifications as user1 does
    When I log in as "appraiser"
    And I open the notification popover
    And I wait for pending js
    Then I should see "Herinnering aan activiteit" exactly "3" times
    And I log out

    # user2 should not receive any notifications
    When I log in as "user2"
    And I open the notification popover
    And I wait for pending js
    Then I should see "You have no notifications"
    And I log out

    # manager should not receive any notifications
    When I log in as "manager"
    And I open the notification popover
    And I wait for pending js
    Then I should see "You have no notifications"

  Scenario: mod_perform_notification_103: Due date approaching reminder
    And I skip the scenario until issue "TL-35485" lands
    And I toggle the "Due date approaching reminder" tui collapsible
    And I click on "Due date approaching reminder notification" tui "toggle_switch"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Subject" tui "toggle_switch" in the "Due date approaching reminder" tui "collapsible"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Appraiser" tui "toggle_switch" in the "Due date approaching reminder" tui "collapsible"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Add" tui "button" in the "Due date approaching reminder" tui "collapsible"
    And I click on "Add" tui "button" in the "Due date approaching reminder" tui "collapsible"
    And I click on "Add" tui "button" in the "Due date approaching reminder" tui "collapsible"
    And I set the following fields to these values:
      | trigger-due_date_reminder[0] | 1 |
      | trigger-due_date_reminder[1] | 2 |
      | trigger-due_date_reminder[2] | 4 |
      | trigger-due_date_reminder[3] | 6 |
    And I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    # Explicitly check the due date setting in case someone messes up with the generator
    When I switch to "Assignments" tui tab
    Then the following fields match these values:
      | dueDateOffset[value] | 2     |
      | dueDateOffset[range] | weeks |
    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I wait for the next second
    And I trigger cron
    And I press the "back" button in the browser
    And I log out

    # day 0
    Given I time travel to "1 hour future" for perform activity notification
    When I log in as "user1"
    And I open the notification popover
    And I wait for pending js
    Then I should see "You have no notifications"

    # day 5
    Given I time travel to "5 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    And I wait for pending js
    Then I should see "You have no notifications"

    # day 7
    Given I time travel to "2 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    And I wait for pending js
    Then I should see "You have no notifications"

    # day 8 (6 days before due)
    Given I time travel to "1 day future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Si avvicina la scadenza" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi User One"
    And I should see "Your Activity test Feedback is due to be completed in 6 days"
    And I should see date "2 weeks" formatted "Please ensure you complete it by %d %B %Y"
    When I follow "Activity test"
    Then I should see "Activity test" in the page title
    And I am on site homepage

    # day 9 (5 days before due)
    Given I time travel to "1 day future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Si avvicina la scadenza" exactly "1" times

    # day 10 (4 days before due)
    Given I time travel to "1 day future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Si avvicina la scadenza" exactly "2" times

    # day 11 (3 days before due)
    Given I time travel to "1 day future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Si avvicina la scadenza" exactly "2" times

    # day 12 (2 days before due)
    Given I time travel to "1 day future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Si avvicina la scadenza" exactly "3" times

    # day 13 (1 days before due)
    Given I time travel to "1 day future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Si avvicina la scadenza" exactly "4" times

    # day 14 (due date)
    Given I time travel to "1 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Si avvicina la scadenza" exactly "4" times

    # day 15 (overdue)
    Given I time travel to "1 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Si avvicina la scadenza" exactly "4" times
    And I log out

    # appraiser should receive as many notifications as user1 does
    When I log in as "appraiser"
    And I open the notification popover
    Then I should see "A határidő közeledik" exactly "4" times
    And I log out

    # user2 should not receive any notifications
    When I log in as "user2"
    And I open the notification popover
    Then I should see "You have no notifications"
    And I log out

    # manager should not receive any notifications
    When I log in as "manager"
    And I open the notification popover
    Then I should see "You have no notifications"

  Scenario: mod_perform_notification_104: On due date reminder
    And I skip the scenario until issue "TL-35485" lands
    And I toggle the "On due date reminder" tui collapsible
    And I click on "On due date reminder notification" tui "toggle_switch"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Subject" tui "toggle_switch" in the "On due date reminder" tui "collapsible"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Appraiser" tui "toggle_switch" in the "On due date reminder" tui "collapsible"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I trigger cron
    And I press the "back" button in the browser
    And I log out

    # day 0
    Given I time travel to "1 hour future" for perform activity notification
    When I log in as "user1"
    And I open the notification popover
    Then I should see "You have no notifications"

    # day 2
    Given I time travel to "2 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "You have no notifications"

    # day 5
    Given I time travel to "3 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "You have no notifications"

    # day 10
    Given I time travel to "5 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "You have no notifications"

    # day 12 (2 day before due)
    Given I time travel to "2 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "You have no notifications"

    # day 13, 23:00 (1 day before due)
    Given I time travel to "midnight future" for perform activity notification
    And I time travel to "23 hours future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "You have no notifications"

    # day 14, 01:00 (due date)
    Given I time travel to "2 hours future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Notificación de fecha de vencimiento" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi User One"
    And I should see "Your Activity test Feedback is due to be completed today"
    And I should see "Please ensure you complete it by the end of the day"
    When I follow "Activity test"
    Then I should see "Activity test" in the page title
    And I am on site homepage

    # day 15 (overdue)
    Given I time travel to "1 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Notificación de fecha de vencimiento" exactly "1" times

    # day 365 (overdue)
    Given I time travel to "350 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Notificación de fecha de vencimiento" exactly "1" times
    And I log out

    # appraiser should receive as many notifications as user1 does
    When I log in as "appraiser"
    And I open the notification popover
    Then I should see "Iraungitze data jakinaraztea" exactly "1" times
    And I log out

    # user2 should not receive any notifications
    When I log in as "user2"
    And I open the notification popover
    Then I should see "You have no notifications"
    And I log out

    # manager should not receive any notifications
    When I log in as "manager"
    And I open the notification popover
    Then I should see "You have no notifications"

  Scenario: mod_perform_notification_105: Overdue reminder
    And I skip the scenario until issue "TL-35485" lands
    And I toggle the "Overdue reminder" tui collapsible
    And I click on "Overdue reminder notification" tui "toggle_switch"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Subject" tui "toggle_switch" in the "Overdue reminder" tui "collapsible"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Appraiser" tui "toggle_switch" in the "Overdue reminder" tui "collapsible"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Add" tui "button" in the "Overdue reminder" tui "collapsible"
    And I click on "Add" tui "button" in the "Overdue reminder" tui "collapsible"
    And I click on "Add" tui "button" in the "Overdue reminder" tui "collapsible"
    And I set the following fields to these values:
      | trigger-overdue_reminder[0] | 1 |
      | trigger-overdue_reminder[1] | 2 |
      | trigger-overdue_reminder[2] | 4 |
      | trigger-overdue_reminder[3] | 6 |
    And I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I wait for the next second
    And I trigger cron
    And I press the "back" button in the browser
    And I log out

    # day 0
    Given I time travel to "1 hour future" for perform activity notification
    When I log in as "user1"
    And I open the notification popover
    Then I should see "You have no notifications"

    # day 13 (1 day  before due)
    Given I time travel to "13 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "You have no notifications"

    # day 14 (due date)
    Given I time travel to "1 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "You have no notifications"

    # day 15 (1 day overdue)
    Given I time travel to "1 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Försenad påminnelse" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi User One"
    And I should see date "2 weeks" formatted "Your Activity test Feedback was due to be completed on %d %B %Y"
    When I follow "Activity test"
    Then I should see "Activity test" in the page title
    And I am on site homepage

    # day 16 (2 day overdue)
    Given I time travel to "1 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Försenad påminnelse" exactly "2" times

    # day 17 (3 day overdue)
    Given I time travel to "1 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Försenad påminnelse" exactly "2" times

    # day 18 (4 day overdue)
    Given I time travel to "1 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Försenad påminnelse" exactly "3" times

    # day 19 (5 day overdue)
    Given I time travel to "1 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Försenad påminnelse" exactly "3" times

    # day 20 (6 day overdue)
    Given I time travel to "1 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Försenad påminnelse" exactly "4" times

    # day 21 (7 day overdue)
    Given I time travel to "1 days future" for perform activity notification
    And I reload the page
    And I open the notification popover
    Then I should see "Försenad påminnelse" exactly "4" times
    And I log out

    # appraiser should receive as many notifications as user1 does
    When I log in as "appraiser"
    And I open the notification popover
    Then I should see "Spomenuté oneskorenie" exactly "4" times
    And I log out

    # user2 should not receive any notifications
    When I log in as "user2"
    And I open the notification popover
    Then I should see "You have no notifications"
    And I log out

    # manager should not receive any notifications
    When I log in as "manager"
    And I open the notification popover
    Then I should see "You have no notifications"

  Scenario: mod_perform_notification_106: Completion notification
    And I skip the scenario until issue "TL-35485" lands
    And I toggle the "Completion of subject instance" tui collapsible
    And I click on "Completion of subject instance notification" tui "toggle_switch"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Subject" tui "toggle_switch" in the "Completion of subject instance" tui "collapsible"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Appraiser" tui "toggle_switch" in the "Completion of subject instance" tui "collapsible"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I wait for the next second
    And I trigger cron
    And I press the "back" button in the browser
    And I log out

    And I log in as "user3"
    And I navigate to the outstanding perform activities list page
    And I click on "Activity test" "link"
    And I set the field "Your response" to "여보세요"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I log out

    And I log in as "manager"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link_or_button"
    And I click on "Activity test" "link"
    And I set the field "Your response" to "हैलो"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I log out

    And I log in as "appraiser"
    And I navigate to the outstanding perform activities list page
    And I click on "As Appraiser" "link_or_button"
    And I click on "Activity test" "link"
    And I set the field "Your response" to "שלום"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I log out

    When I log in as "user1"
    And I trigger cron
    And I press the "back" button in the browser
    And I reload the page
    And I open the notification popover
    Then I should see "You have no notifications"

    When I navigate to the outstanding perform activities list page
    And I click on "Activity test" "link"
    And I set the field "Your response" to "Mā te wā"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I wait for the next second
    And I trigger cron
    And I press the "back" button in the browser
    And I reload the page

    # user1 should receive a notification
    And I open the notification popover
    Then I should see "Ukončení činnosti" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi User One"
    And I should see "Your Activity test Feedback has been completed by all participants"
    When I follow "Activity test"
    Then I should see "Activity test" in the page title
    And I log out

    # user3 should receive a notification
    When I log in as "user3"
    And I open the notification popover
    Then I should see "Ukončení činnosti" exactly "1" times
    And I log out

    # appraiser should receive a notification
    When I log in as "appraiser"
    And I open the notification popover
    Then I should see "Finalizarea activității" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi Appra Iser"
    And I should see "The following activity has been completed by all participants"
    And I should see "Activity test Feedback: User One"
    When I follow "Activity test"
    Then I should see "Activity test" in the page title
    And I log out

    # user2 should not receive any notifications
    When I log in as "user2"
    And I open the notification popover
    Then I should see "You have no notifications"
    And I am on homepage
    And I log out

    # manager should not receive any notifications
    When I log in as "manager"
    And I open the notification popover
    Then I should see "You have no notifications"

  Scenario: mod_perform_notification_107: Reopened activity notification
    And I skip the scenario until issue "TL-35485" lands
    And I toggle the "Reopened activity" tui collapsible
    And I click on "Reopened activity notification" tui "toggle_switch"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Subject" tui "toggle_switch" in the "Reopened activity" tui "collapsible"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Appraiser" tui "toggle_switch" in the "Reopened activity" tui "collapsible"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I wait for the next second
    And I trigger cron
    And I press the "back" button in the browser
    And I log out

    When I log in as "user1"
    And I navigate to the outstanding perform activities list page
    And I click on "Activity test" "link"
    And I set the field "Your response" to "Mā te wā"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I log out

    And I log in as "manager"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link_or_button"
    And I click on "Activity test" "link"
    And I set the field "Your response" to "再见"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I log out

    And I log in as "appraiser"
    And I navigate to the outstanding perform activities list page
    And I click on "As Appraiser" "link_or_button"
    And I click on "Activity test" "link"
    And I set the field "Your response" to "Прощай"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I log out

    When I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link_or_button"
    And I click on "Actions" "button" in the "User One" "table_row"
    And I click on "Close" "button" in the ".tui-dropdown__menu--open" "css_element"
    And I confirm the tui confirmation modal
    And I click on "Actions" "button" in the "User Three" "table_row"
    And I click on "Close" "button" in the ".tui-dropdown__menu--open" "css_element"
    And I confirm the tui confirmation modal
    And I wait for the next second
    And I trigger cron
    And I am on homepage
    And I log out

    When I log in as "user1"
    And I open the notification popover
    Then I should see "You have no notifications"
    And I navigate to the outstanding perform activities list page
    And I log out

    When I log in as "user3"
    And I open the notification popover
    Then I should see "You have no notifications"
    And I navigate to the outstanding perform activities list page
    And I log out

    When I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link_or_button"
    And I click on "Actions" "button" in the "User One" "table_row"
    And I click on "Reopen" "button" in the ".tui-dropdown__menu--open" "css_element"
    And I confirm the tui confirmation modal
    And I click on "Actions" "button" in the "User Three" "table_row"
    And I click on "Reopen" "button" in the ".tui-dropdown__menu--open" "css_element"
    And I confirm the tui confirmation modal
    And I wait for the next second
    And I trigger cron
    And I am on homepage
    And I log out

    # user1 should receive a notification
    When I log in as "user1"
    And I open the notification popover
    Then I should see "Ua toe tatalaina se gaoioiga" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi User One"
    And I should see "Your Activity test Feedback has been reopened"
    When I follow "Activity test"
    Then I should see "Activity test" in the page title
    And I log out

    # user3 should receive a notification
    When I log in as "user3"
    And I open the notification popover
    Then I should see "Ua toe tatalaina se gaoioiga" exactly "1" times
    And I log out

    # appraiser should receive a notification
    When I log in as "appraiser"
    And I open the notification popover
    Then I should see "Dejavnost je bila znova odprta" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi Appra Iser"
    And I should see "The following activity has been reopened"
    And I should see "Activity test Feedback: User One"
    When I follow "Activity test"
    Then I should see "Activity test" in the page title
    And I log out

    # user2 should not receive any notifications
    When I log in as "user2"
    And I open the notification popover
    Then I should see "You have no notifications"
    And I am on homepage
    And I log out

    # manager should not receive any notifications
    When I log in as "manager"
    And I open the notification popover
    Then I should see "You have no notifications"
