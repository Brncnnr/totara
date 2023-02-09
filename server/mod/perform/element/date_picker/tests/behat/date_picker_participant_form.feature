@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Interact with the date picker in the participant form

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
      | activity_name | activity_type | activity_status |
      | Date pickers  | check-in      | Draft           |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name | track_description |
      | Date pickers  | track 1           |

  Scenario: Save required and optional date picker elements
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Date pickers" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Date picker" activity content element

    # Setting the year range values to things clearly outside the default range.
    When I click on the "responseRequired" tui checkbox
    And I set the following fields to these values:
      | rawTitle       | Req date picker |
      | yearRangeStart | 900             |
      | yearRangeEnd   | 2071            |
    And I save the activity content element
    Then I should see "yearRangeStart" form field has the tui validation error "Number must be 1900 or more"
    When I set the following fields to these values:
      | yearRangeStart | 1900 |
      | yearRangeEnd   | 9999 |
    And I save the activity content element
    Then I should see "yearRangeEnd" form field has the tui validation error "Number must be## +50 years ## Y## or less"

    When I set the following fields to these values:
      | yearRangeStart | 2020 |
      | yearRangeEnd   | 2025 |
    And I save the activity content element

    And I add a "Date picker" activity content element

    When I set the following fields to these values:
      | rawTitle | Opt date picker |
    And I save the activity content element
    And I close the tui notification toast
    And I follow "Content (Date pickers)"

    And I click the add responding participant button
    And I select "Subject" in the responding participants popover

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
    Then I log out

    When I log in as "user1"
    And I navigate to the outstanding perform activities list page
    And I click on "Date pickers" "link"
    And I click on "Save as draft" "button"
    Then I should see "Draft saved" in the tui success notification toast
    And I should see "Req date picker" has no validation errors
    And I should see "Opt date picker" has no validation errors

    When I click on "Submit" "button"
    Then I should see "Req date picker" has the validation error "Required"
    And I should see "Opt date picker" has no validation errors

    # Validation should be tested on the Vue item (TL-29558)
    When I set the field "uid-10-event-date-day" to "1"
    And I set the field "uid-14-event-date-day" to "1"
    And I click on "Save as draft" "button"
    Then I should see "Req date picker" has the validation error "Invalid date. Select day, month and year"
    And I should see "Opt date picker" has the validation error "Invalid date. Select day, month and year"

    When I click on "Submit" "button"
    Then I should see "Req date picker" has the validation error "Invalid date. Select day, month and year"
    Then I should see "Opt date picker" has the validation error "Invalid date. Select day, month and year"

    When I set the field "uid-10-event-date-month" to "January"
    And I set the field "uid-14-event-date-month" to "January"
    And I click on "Save as draft" "button"
    Then I should see "Req date picker" has the validation error "Invalid date. Select day, month and year"
    And I should see "Opt date picker" has the validation error "Invalid date. Select day, month and year"

    When I click on "Submit" "button"
    Then I should see "Req date picker" has the validation error "Invalid date. Select day, month and year"
    And I should see "Opt date picker" has the validation error "Invalid date. Select day, month and year"

    When I set the field "uid-10-event-date-year" to "2020"
    And  I set the field "uid-14-event-date-year" to "2021"
    And I click on "Save as draft" "button"
    Then I should see "Req date picker" has no validation errors
    And I should see "Opt date picker" has no validation errors

    When I set the field "uid-10-event-date-year" to "2021"
    And I click on "Submit" "button"
    Then I should see "Req date picker" has no validation errors
    And I should see "Opt date picker" has no validation errors
