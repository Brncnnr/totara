@totara @perform @mod_perform @mod_perform_notification @javascript @vuejs @totara_notification
Feature: Perform activity centralised notifications - participant instance submission
  As an activity participant
  I should be notified when other participants complete a participant instance

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | user1     | User      | One      | user1@example.com     |
      | manager   | Mana      | Ger      | manager@example.com   |
    And the following job assignments exist:
      | user  | manager |
      | user1 | manager |
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
      | component   | id                                                                                | string                                               |
      | mod_perform | notification_participant_instance_completion_by_participant_for_subject_subject   | Other relationship submission for subject            |
      | mod_perform | notification_participant_instance_completion_by_subject_for_participant_subject   | Subject submission notice for other relationship     |
    And the notification preference "mod_perform\totara_notification\notification\participant_instance_completion_by_subject_for_manager" is set to enabled
    And the notification preference "mod_perform\totara_notification\notification\participant_instance_completion_by_manager_for_subject" is set to enabled
    And I log in as "admin"
    And I navigate to the manage perform activities page
    And I follow "Activity test"
    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I wait for the next second
    And I trigger cron
    And I press the "back" button in the browser
    And I log out

  # Check submission by subject.
  Scenario: Participant instance notification of submission by subject
    When I log in as "user1"
    And I navigate to the outstanding perform activities list page
    And I click on "Activity test" "link"
    And I set the field "Your response" to "여보세요"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I wait for the next second
    And I run the scheduled task "totara_notification\task\process_event_queue_task"
    And I run the scheduled task "totara_notification\task\process_notification_queue_task"
    And I reload the page
    And I log out
    # Check for manager.
    When I log in as "manager"
    And I open the notification popover
    Then I should see "Subject submission notice for other relationship" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi Mana Ger"
    And I should see "User One has submitted their responses to their Activity test Feedback."
    And I follow "Activity test"
    And I switch to "As Manager" tui tab
    When I click on "Activity test" "link"
    Then I should see "Activity test" in the page title

  # Check submission by other relationship (manager). We don't need to check all the possible relationships as they
  # all use the same notification trigger and follow the same pattern. Unit tests are taking care of that.
  Scenario: Participant instance notification of submission by other relationship
    When I log in as "manager"
    And I navigate to the outstanding perform activities list page
    And I switch to "As Manager" tui tab
    And I click on "Activity test" "link"
    And I set the field "Your response" to "Manager response"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I wait for the next second
    And I run the scheduled task "totara_notification\task\process_event_queue_task"
    And I run the scheduled task "totara_notification\task\process_notification_queue_task"
    And I reload the page
    And I log out
    # Check for subject.
    When I log in as "user1"
    And I open the notification popover
    Then I should see "Other relationship submission for subject" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi User One"
    And I should see "Your Manager Mana Ger has submitted their responses to your Activity test Feedback."
    And I follow "Activity test"
    When I click on "Activity test" "link"
    Then I should see "Activity test" in the page title
