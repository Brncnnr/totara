@totara @perform @mod_perform @mod_perform_notification @javascript @vuejs @totara_notification
Feature: Perform activity centralised notifications - participant instance selection
  As a manual participants selector
  I should be notified when the instance is ready to select the manual participant

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | user1     | User      | One      | user1@example.com     |
      | manager   | Mana      | Ger      | manager@example.com   |
      | managers  | Dan       | Don      | managersmanager@example.com   |
    And the following job assignments exist:
      | user    | fullname         | manager  | idnumber | managerjaidnumber |
      | manager | job assignment 1 | managers | jajaja1  |                   |
      | user1   | job assignment 2 | manager  | jajaja2  |    jajaja1        |
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
      | section_name | relationship |
      | section 1    | subject      |
      | section 1    | manager      |
      | section 1    | Peer         |
      | section 1    | Mentor       |
      | section 1    | Reviewer     |
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
      | component   | id                                                    | string                                               |
      | mod_perform | notification_select_participant_participant_subject   | Other relationship - Participant Selection Required  |
      | mod_perform | notification_select_participant_subject_user_subject  | Subject - Participant Selection Required             |
    And the notification preference "mod_perform\totara_notification\notification\participant_selection" is set to enabled
    And the notification preference "mod_perform\totara_notification\notification\participant_selection_for_subject" is set to enabled
    And I log in as "admin"
    And I navigate to the manage perform activities page
    And I follow "Activity test"
    And I switch to "Assignments" tui tab
    And I set the field with xpath "//select[@aria-label='Peer']" to "Subject"
    And I set the field with xpath "//select[@aria-label='Mentor']" to "Manager"
    And I set the field with xpath "//select[@aria-label='Reviewer']" to "Manager's manager"
    And I switch to "Instance creation" tui tab
    And the "Due date" tui toggle switch should be "on"
    And I click on "Update instance creation" tui "button"
    And I confirm the tui confirmation modal
    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I wait for the next second
    # Scheduler handle both on-event and schedule event.
    And I run the scheduled task "totara_notification\task\process_scheduled_event_task"
    And I wait for the next second
    And I navigate to "Scheduled tasks" node in "Site administration > Server"
    And I press "Set all enabled tasks to run on next cron"
    And I trigger cron
    And I wait for the next second
    And I press the "back" button in the browser
    And I log out

  # Check Participant Selection notification for subject.
  Scenario: Participant selection notification to subject
    When I log in as "user1"
    And I open the notification popover
    Then I should see "Subject - Participant Selection Required " exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi User One"
    And I should see "You need to select who you want to participate in your Activity test Feedback."
    And I should see "Their input is needed by"
    When I follow "Select participants"
    Then I should see "Select participants" in the page title
    And I reload the page
    And I log out

  # Check Participant Selection notification for Manager.
  Scenario: Participant selection notification to manager
    # Check for manager.
    When I log in as "manager"
    And I open the notification popover
    Then I should see "Other relationship - Participant Selection Required " exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi Mana Ger"
    And I should see "As User One’s Manager, you need to select who should participate in the following activity: "
    And I should see "Their input is needed by"
    When I follow "Select participants"
    Then I should see "Select participants" in the page title
    And I reload the page
    And I log out

    # Check Participant Selection notification for Manager's manager.
  Scenario: Participant selection notification to manager's manager
    # Check for manager.
    When I log in as "managers"
    And I open the notification popover
    Then I should see "Other relationship - Participant Selection Required " exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi Dan Don"
    And I should see "As User One’s Manager's manager, you need to select who should participate in the following activity: "
    And I should see "Their input is needed by"
    When I follow "Select participants"
    Then I should see "Select participants" in the page title
    And I reload the page
    And I log out


