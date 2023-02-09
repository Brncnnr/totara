@totara @perform @mod_perform @mod_perform_notification @javascript @vuejs @totara_notification
Feature: Perform activity centralised notifications - administration interface
  As an activity administrator
  I should be able to set that participants within an activity are notified when a participant instance is completed

  Background:
    Given I am on a totara site
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name | activity_type | activity_status | create_section | create_track |
      | Activity test | feedback      | Draft           | false          | false        |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name | track_description | due_date_offset |
      | Activity test | track 1           | 2, WEEK         |

  # Check that admin UI is there and the expected default notifications exist.
  Scenario: Centralised notification administration is embedded in notifications tab
    When I log in as "admin"
    And I navigate to the manage perform activities page
    And I follow "Activity test"
    And I switch to "Notifications" tui tab
    When I click on "Participant instance completion" "button"
    # Check the default notification triggers.
    Then I should see "Participant instance completion by direct report" under the expanded row of the tui datatable
    And I should see "Inherited" under the expanded row of the tui datatable
    And I should see "Participant: Manager" under the expanded row of the tui datatable
    And I should see "Participant: Subject" under the expanded row of the tui datatable
    And I should see "On event" under the expanded row of the tui datatable
    And I should see "Disabled" under the expanded row of the tui datatable

    And I should see "Participant instance completion by external respondent" under the expanded row of the tui datatable
    And I should see "Participant instance completion by manager" under the expanded row of the tui datatable
    And I should see "Participant instance completion by manager's manager" under the expanded row of the tui datatable
    And I should see "Participant instance completion by mentor" under the expanded row of the tui datatable
    And I should see "Participant instance completion by peer" under the expanded row of the tui datatable
    And I should see "Participant instance completion by reviewer" under the expanded row of the tui datatable
    And I should see "Participant instance completion by subject" under the expanded row of the tui datatable


