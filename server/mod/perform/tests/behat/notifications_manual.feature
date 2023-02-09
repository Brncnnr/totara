@totara @perform @mod_perform @mod_perform_notification @javascript @vuejs
Feature: Perform activity notifications - manual relationships
  As an activity administrator
  I should to be able to set that participants within an activity are notified when certain conditions are met

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | user1     | User      | One      |     user1@example.com |
      | user2     | User      | Two      |     user2@example.com |
      | user3     | User      | Three    |     user3@example.com |
      | manager   | Mana      | Ger      |   manager@example.com |
      | appraiser | Appra     | Iser     | appraiser@example.com |
    And the following job assignments exist:
      | user      | manager | appraiser |
      | user1     | manager | appraiser |
      | user2     | manager |           |
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
      | section_name | relationship        |
      | section 1    | subject             |
      | section 1    | appraiser           |
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
      | component   | id                                                   | string                               | comment    |
      | mod_perform | template_instance_created_subject_subject            | New activity notice                  | English    |
      | mod_perform | template_participant_selection_subject_subject       | Wybierz uczestników                  | Polish     |
      | mod_perform | template_participant_selection_appraiser_subject     | Pasirinkite dalyvius                 | Lithuanian |
      | mod_perform | template_instance_created_appraiser_subject          | Nuwe aktiwiteitskennisgewing         | Afrikaans  |
      | mod_perform | template_instance_created_manager_subject            | Ný tilkynning um virkni              | Icelandic  |
      | mod_perform | template_instance_created_perform_peer_subject       | Uue tegevuse teatis                  | Estonian   |
      | mod_perform | template_instance_created_perform_mentor_subject     | Ny aktivitetsmeddelelse              | Danish     |
      | mod_perform | template_instance_created_perform_external_subject   | Yeni etkinlik bildirimi              | Turkish    |
    And I log in as "admin"
    And I navigate to the manage perform activities page
    And I follow "Activity test"

  Scenario: mod_perform_notification_201: Select participants notification
    And I skip the scenario until issue "TL-35485" lands
    And I switch to "Content" tui tab
    And I click on "Add participants" "button"
    And I click on "Peer" tui "checkbox"
    And I click on "Mentor" tui "checkbox"
    And I click on "Done" "button"
    And I close the tui notification toast

    And I switch to "Assignments" tui tab
    And I set the field with xpath "//select[@aria-label='Mentor']" to "Appraiser"
    And I switch to "Notifications" tui tab

    # By default participant selection & instance creation is enabled.
    # For participant selection, the selector recipients are enabled (i.e Subject, Manager, Manager's manager, Appraiser)
    Then the "Subject" tui "toggle_switch" should be on in the "Participant selection" tui "collapsible"
    And the "Manager" tui "toggle_switch" should be on in the "Participant selection" tui "collapsible"
    And the "Appraiser" tui "toggle_switch" should be on in the "Participant selection" tui "collapsible"
    # Activate the instance creation notifications.
    When I click on "Subject" tui "toggle_switch" in the "Participant instance creation" tui "collapsible"
    And I click on "Appraiser" tui "toggle_switch" in the "Participant instance creation" tui "collapsible"
    And I click on "Peer" tui "toggle_switch" in the "Participant instance creation" tui "collapsible"
    And I click on "Mentor" tui "toggle_switch" in the "Participant instance creation" tui "collapsible"

    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I wait for the next second
    And I trigger cron
    And I press the "back" button in the browser
    And I log out

    # user2 should not receive any notifications
    When I log in as "user2"
    And I open the notification popover
    Then I should see "You have no notifications"
    And I log out

    # manager should not receive a notification
    When I log in as "manager"
    And I open the notification popover
    Then I should see "You have no notifications"
    And I log out

    # user1 should receive a notification
    When I log in as "user1"
    And I open the notification popover
    Then I should not see "New activity notice"
    And I should see "Wybierz uczestników" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi User One"
    And I should see "You need to select who you want to participate in your Activity test Feedback"
    And I should see "Their input is needed by"
    When I follow "Select participants"
    And I select from the tui taglist in the ".tui-formRow" "css_element":
      | Appra Iser |
    And I click on "Save" "button"
    And I close the tui notification toast
    And I log out

    # appraiser should receive notifications
    When I log in as "appraiser"
    And I open the notification popover
    Then I should not see "Nuwe aktiwiteitskennisgewing"
    And I should see "Pasirinkite dalyvius" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi Appra Iser"
    And I should see "As User One’s Appraiser, you need to select who should participate in the following activity"
    And I should see "Activity test Feedback"
    And I should see "Their input is needed by"
    When I follow "Select participants"
    And I select from the tui taglist in the ".tui-formRow" "css_element":
      | Mana Ger |
    And I click on "Save" "button"
    And I close the tui notification toast
    And I wait for the next second
    And I trigger cron
    And I press the "back" button in the browser
    And I open the notification popover
    Then I should see "Nuwe aktiwiteitskennisgewing" exactly "1" times
    And I should see "Uue tegevuse teatis" exactly "1" times
    And I log out

    # user1 should receive notifications
    When I log in as "user1"
    And I open the notification popover
    Then I should see "New activity notice" exactly "1" times
    And I log out

    # user2 should not receive any notifications
    When I log in as "user2"
    And I open the notification popover
    Then I should see "You have no notifications"
    And I log out

    # manager should receive a notification
    When I log in as "manager"
    And I open the notification popover
    Then I should see "Ny aktivitetsmeddelelse"

  Scenario: mod_perform_notification_202: External participation notification
    And I skip the scenario until issue "TL-35485" lands
    And I switch to "Content" tui tab
    And I click on "Add participants" "button"
    And I click on "External respondent" tui "checkbox"
    And I click on "Done" "button"
    And I close the tui notification toast

    And I switch to "Notifications" tui tab

    # By default participant selection & instance creation is enabled.
    # For participant selection, the selector recipients are enabled (i.e Subject, Manager, Manager's manager, Appraiser)
    # For participant instance creation, the external respondent recipient is enabled by default.
    # So we just need to enable the subject recipient for instance creation, because that is not enabled by default.
    Then the "Subject" tui "toggle_switch" should be on in the "Participant selection" tui "collapsible"
    And the "Manager" tui "toggle_switch" should be on in the "Participant selection" tui "collapsible"
    And the "Manager's manager" tui "toggle_switch" should be on in the "Participant selection" tui "collapsible"
    And the "Appraiser" tui "toggle_switch" should be on in the "Participant selection" tui "collapsible"
    When I click on "Subject" tui "toggle_switch" in the "Participant instance creation" tui "collapsible"

    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I wait for the next second
    And I trigger cron
    And I press the "back" button in the browser
    And I log out

    When I log in as "user1"
    And I open the notification popover
    Then I should not see "New activity notice"
    And I should see "Wybierz uczestników" exactly "1" times
    When I follow "View full notification"
    Then I should see "Hi User One"
    And I should see "You need to select who you want to participate in your Activity test Feedback"
    And I should see "Their input is needed by"
    When I follow "Select participants"
    And I click on "Add" tui "button" in the "External respondent" tui "form"
    And I set the following fields to these values:
      | External respondent 1's name          | External One             |
      | External respondent 1's email address | external.one@example.com |
      | External respondent 2's name          | External Two             |
      | External respondent 2's email address | external.two@example.com |
    And I click on "Save" "button"
    And I wait for the next second
    And I trigger cron
    And I press the "back" button in the browser
    And I reload the page
    And I open the notification popover
    # Make sure the instance creation notification is not sent twice.
    Then I should see "New activity notice" exactly "1" times
    And I log out

    And I log in as "admin"
    And I navigate to "Logs" node in "Site administration > Server"
    And I press "Get these logs"
    # See if there are two records of "The user with id '0' sent a message with id 'X' to the user with id '-45'."
    Then I should see "to the user with id '-45'." exactly "2" times
