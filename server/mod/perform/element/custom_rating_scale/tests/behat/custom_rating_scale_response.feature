@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Custom Rating scale participant form

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
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name   | element_name        | title                                         | is_required | data                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
      | Single section | custom_rating_scale | Required custom rating scale question         | 1           | {"options":[{"name":"option_1","value":{"text":"A","score":"1"}}, {"name":"option_2","value":{"text":"B","score":"5"}}, {"name":"option_3","value":{"text":"C","score":"10"}}]}                                                                                                                                                                                                                                                                                                                                     |
      | Single section | custom_rating_scale | Optional custom rating scale with description | 0           | {"options":[{"name":"option_1","value":{"text":"A","score":"1"}}, {"name":"option_2","value":{"text":"B","score":"5"}, "descriptionEnabled":false,"descriptionWekaDoc":{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Unused option description"}]}]}},{"name":"option_3","value":{"text":"C","score":"15"}, "descriptionEnabled":true,"descriptionWekaDoc":{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Choose the letter you like best"}]}]}}]} |
    And I manually activate the perform activity "Rating scale activity"
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"
    And I run the scheduled task "mod_perform\task\create_manual_participant_progress_task"

  Scenario: Custom rating scale shows validation error when no response is submitted, but can save a draft
    And I log in as "user1"
    And I navigate to the outstanding perform activities list page
    And I click on "Rating scale activity" "link"

    Then I should not see "Unused description"
    And I should see "Choose the letter you like best" in the ".tui-hideShow__content--show p" "css_element" of perform element participant form for "Optional custom rating scale with description"

    # Can save as draft without doing anything
    When I click on "Save as draft" "button"
    And I should see "Draft saved" in the tui success notification toast
    And I should see "Required custom rating scale question" has no validation errors
    And I should see "Optional custom rating scale with description" has no validation errors

    # Must actually make a selection in order to submit
    When I click on "Submit" "button"
    Then I should see "Required custom rating scale question" has the validation error "Required"
    When I click on ".tui-radio__label" "css_element"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I should see "Section submitted" in the tui success notification toast
