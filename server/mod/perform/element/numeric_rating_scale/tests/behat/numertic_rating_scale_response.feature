@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Rating scale: Numeric participant form

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
      | activity_name         | activity_type | activity_status | create_section |
      | Rating scale activity | check-in      | Draft           | false          |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name         | track_description |
      | Rating scale activity | track 1           |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 1           | cohort          | aud1            |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name         | section_name   |
      | Rating scale activity | Single section |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name   | relationship | can_view | can_answer |
      | Single section | subject      | yes      | yes        |

  Scenario: Rating scale: Numeric shows validation error when no response is submitted, but can save a draft
    When I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Rating scale activity" "link"
    And I click on "Edit content elements" "link_or_button"

    And I add a "Rating scale: Numeric" activity content element
    And I set the following fields to these values:
      | rawTitle     | Rating scale: Numeric |
      | lowValue     | 1                     |
      | defaultValue | 2                     |
      | highValue    | 3                     |
    And I click on the "responseRequired" tui checkbox
    And I save the activity content element

    And I manually activate the perform activity "Rating scale activity"
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"
    And I run the scheduled task "mod_perform\task\create_manual_participant_progress_task"
    And I log out
    And I log in as "user1"
    And I navigate to the outstanding perform activities list page
    And I click on "Rating scale activity" "link"

    # Can save as draft without doing anything
    When I click on "Save as draft" "button"
    And I should see "Rating scale: Numeric" has no validation errors
    And I should see "Draft saved" in the tui success notification toast

    # Must actually make a selection in order to submit
    When I click on "Submit" "button"
    Then I should see "Rating scale: Numeric" has the validation error "Required"
    When I click on ".tui-range__input" "css_element"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I should see "Section submitted" in the tui success notification toast

  Scenario: Descriptions are visible if enabled
    When the following "section elements" exist in "mod_perform" plugin:
      | section_name   | element_name         | title           | data                                                                                                                                                                                                                     |
      | Single section | numeric_rating_scale | No description | {"defaultValue":"3","highValue":"5","lowValue":"1","descriptionEnabled":false,"descriptionWekaDoc":{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Unused description"}]}]}} |
      | Single section | numeric_rating_scale | Has description  | {"defaultValue":"3","highValue":"5","lowValue":"1","descriptionEnabled":true,"descriptionWekaDoc":{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Choose the number you like best"}]}]}}  |
    And I manually activate the perform activity "Rating scale activity"
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"
    And I run the scheduled task "mod_perform\task\create_manual_participant_progress_task"

    And I log out
    And I log in as "user1"
    And I navigate to the outstanding perform activities list page
    And I click on "Rating scale activity" "link"

    Then I should not see "Unused description"
    And I should see "Choose the number you like best" in the ".tui-hideShow__content--show" "css_element" of perform element participant form for "Has description"