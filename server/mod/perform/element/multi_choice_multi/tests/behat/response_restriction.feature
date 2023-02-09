@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Manage performance activity multiple choice-answers elements with admin response restriction

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
      | activity_name       | activity_type | activity_status |
      | Multi-multi min-max | check-in      | Draft           |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name       | track_description |
      | Multi-multi min-max | track 1           |

  Scenario: Save and use response restrictions
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Multi-multi min-max" "link"
    And I click the add responding participant button
    And I select "Subject" in the responding participants popover
    And I click on "Edit content elements" "link_or_button"

    # No restrictions
    When I add a "Multiple choice: multi-select" activity content element
    And I click multiple answers question add new option
    And I set the following fields to these values:
      | rawTitle          | No restrictions |
      | options[0][value] | Option one      |
      | options[1][value] | Option two      |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast

    # No restrictions - Single required checkbox
    When I add a "Multiple choice: multi-select" activity content element
    And I set the following fields to these values:
      | rawTitle          | Single required checkbox |
      | options[0][value] | I agree                  |
    And I click on the "responseRequired" tui checkbox
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast

    # Single restriction value - optional
    When I add a "Multiple choice: multi-select" activity content element
    And I click multiple answers question add new option
    And I set the following fields to these values:
      | rawTitle          | Single restriction value - optional |
      | options[0][value] | Option one                          |
      | options[1][value] | Option two                          |
      | min               | 1                                   |
      | max               | 1                                   |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast

    # Minimum restrictions single value - required
    When I add a "Multiple choice: multi-select" activity content element
    And I click multiple answers question add new option
    And I set the following fields to these values:
      | rawTitle          | Minimum restrictions single value - required |
      | options[0][value] | Option one                                   |
      | options[1][value] | Option two                                   |
      | min               | 0                                            |
      | max               | 1                                            |
    And I click on the "responseRequired" tui checkbox
    And I save the activity content element
    Then I should see "Number must be 1 or more"

    When I set the following fields to these values:
      | min |   |
      | max | 1 |
    And I save the activity content element
    Then I should see "Required" in the ".tui-formFieldError__inner" "css_element"

    When I set the following fields to these values:
      | min |   |
      | max | 0 |
    And I save the activity content element
    Then I should see "Number must be 1 or more"

    When I set the following fields to these values:
      | min | 1 |
      | max | 1 |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast

    # Mixed restrictions - optional
    When I add a "Multiple choice: multi-select" activity content element
    And I click multiple answers question add new option
    And I click multiple answers question add new option
    And I set the following fields to these values:
      | rawTitle          | Mixed restrictions - optional |
      | options[0][value] | Option one                    |
      | options[1][value] | Option two                    |
      | options[2][value] | Option three                  |
      | min               | 1                             |
      | max               | 2                             |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast

    # Maximum restrictions mixed values - required
    When I add a "Multiple choice: multi-select" activity content element
    And I click multiple answers question add new option
    And I click multiple answers question add new option
    And I set the following fields to these values:
      | rawTitle          | Maximum restrictions mixed values - required |
      | options[0][value] | Option one                                   |
      | options[1][value] | Option two                                   |
      | options[2][value] | Option three                                 |
      | min               | 4                                            |
      | max               |                                              |
    And I click on the "responseRequired" tui checkbox
    And I save the activity content element
    Then I should see "Number must be 3 or less"

    When I set the following fields to these values:
      | min |   |
      | max | 4 |
    And I save the activity content element
    Then I should see "Number must be 3 or less"

    When I set the following fields to these values:
      | min | 1 |
      | max | 2 |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast
    And I should see perform "checkbox" question "No restrictions" is saved with options "Option one, Option two"
    And I should see perform "checkbox" question "Single restriction value - optional" is saved with options "Option one, Option two"
    And I should see perform "checkbox" question "Minimum restrictions single value - required" is saved with options "Option one, Option two"
    And I should see perform "checkbox" question "Mixed restrictions - optional" is saved with options "Option one, Option two, Option three"
    And I should see perform "checkbox" question "Maximum restrictions mixed values - required" is saved with options "Option one, Option two, Option three"

    When I click on "Content (Multi-multi min-max)" "link"
    Then I should see "3" in the "required" element summary of the activity section
    And I should see "3" in the "optional" element summary of the activity section
    And I should see "0" in the "other" element summary of the activity section

    When I click on "Assignments" "link"
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
    And I click on "Multi-multi min-max" "link"

    # Assert correct restrictions help text
    Then I should not see "Select at least" in the perform activity question "No restrictions"
    And I should not see "Select no more than" in the perform activity question "No restrictions"
    And I should not see "Select" in the perform activity question "Single required checkbox"
    And I should see "Select 1 options" in the perform activity question "Minimum restrictions single value - required"
    And I should see "Select at least 1 options" in the perform activity question "Mixed restrictions - optional"
    And I should see "Select no more than 2 options" in the perform activity question "Mixed restrictions - optional"
    And I should see "Select at least 1 options" in the perform activity question "Maximum restrictions mixed values - required"
    And I should see "Select no more than 2 options" in the perform activity question "Maximum restrictions mixed values - required"

    # Assert correct validation errors
    When I click on "Submit" "button"
    Then I should see validation error "Required" in the perform activity question "Minimum restrictions single value - required"
    And I should see validation error "Required" in the perform activity question "Single required checkbox"
    And I should see validation error "Required" in the perform activity question "Maximum restrictions mixed values - required"

    # Note "Single restriction value - optional" and "Mixed restrictions - optional", should not be touched and therefore not show any validation messages.
    And I should not see validation error "Select 1 options" in the perform activity question "Single restriction value - optional"
    And I should not see validation error "Select at least 1 options" in the perform activity question "Mixed restrictions - optional"

    When I answer "multi choice multi" question "Maximum restrictions mixed values - required" with "Option one"
    And I answer "multi choice multi" question "Single required checkbox" with "I agree"
    And I answer "multi choice multi" question "Maximum restrictions mixed values - required" with "Option two"
    And I answer "multi choice multi" question "Maximum restrictions mixed values - required" with "Option three"
    And I should not see validation error "Select at least 1 options" in the perform activity question "Maximum restrictions mixed values - required"
    Then I should see validation error "Select no more than 2 options" in the perform activity question "Maximum restrictions mixed values - required"
    Then I should see validation error "Required" in the perform activity question "Single required checkbox"

    When I answer "multi choice multi" question "Minimum restrictions single value - required" with "Option one"
    And I answer "multi choice multi" question "Minimum restrictions single value - required" with "Option two"
    And I click on "Submit" "button"
    Then I should see validation error "Select 1 options" in the perform activity question "Minimum restrictions single value - required"

    # Un-check some options to pass validation
    When I answer "multi choice multi" question "Minimum restrictions single value - required" with "Option one"
    And I answer "multi choice multi" question "Maximum restrictions mixed values - required" with "Option three"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Section submitted" in the tui success notification toast
