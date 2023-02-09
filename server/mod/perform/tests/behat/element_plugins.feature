@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: I can create and use performance activities with any and every custom element type

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user      | 1        | user1@example.com |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | aud1     |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | aud1   |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name        | activity_type | activity_status |
      | One of every element | check-in      | Draft           |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name        | track_description |
      | One of every element | track 1           |
    And I log in as "admin"
    And I navigate to the manage perform activities page
    And I should see the tui datatable contains:
      | Name                 | Type     | Status |
      | One of every element | Check-in | Draft  |

    And I click on "One of every element" "link"
    And I click the add responding participant button
    And I select "Subject" in the responding participants popover
    And I click on "Edit content elements" "link_or_button"

  Scenario: Save empty draft responses for required questions
    And I add one of every element type in the mod perform form builder and make them required
    And I follow "Content (One of every element)"

    And I click on "Assignments" "link"
    And I click on "Assign users" "button"
    And I click on "Audience" "link" in the ".tui-dropdown__menu" "css_element"
    And I toggle the adder picker entry with "aud1" for "Audience name"
    And I save my selections and close the adder

    And I click on "Activate" "button"
    And I confirm the tui confirmation modal
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"
    And I run the scheduled task "mod_perform\task\create_manual_participant_progress_task"
    And I log out

    And I log in as "user1"
    And I navigate to the outstanding perform activities list page
    Then I should see "One of every element" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Check-in" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "One of every element" "link"
    And I click on "Save as draft" "button"
    Then I should see "Draft saved" in the tui success notification toast

  Scenario: Submit empty responses for optional questions
    And I add one of every element type in the mod perform form builder
    And I follow "Content (One of every element)"

    And I click on "Assignments" "link"
    And I click on "Assign users" "button"
    And I click on "Audience" "link" in the ".tui-dropdown__menu" "css_element"
    And I toggle the adder picker entry with "aud1" for "Audience name"
    And I save my selections and close the adder

    And I click on "Activate" "button"
    And I confirm the tui confirmation modal
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"
    And I run the scheduled task "mod_perform\task\create_manual_participant_progress_task"
    And I log out

    And I log in as "user1"
    And I navigate to the outstanding perform activities list page
    Then I should see "One of every element" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Check-in" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "One of every element" "link"
    And I click on "Save as draft" "button"
    Then I should see "Draft saved" in the tui success notification toast and close it
    When I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Section submitted" in the tui success notification toast
