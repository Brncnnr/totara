@totara @perform @totara_competency @pathway_manual @javascript @vuejs
Feature: Test filtering by users, roles, assignments etc. functions as expected.

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname |
      | user1    | User      | 1-One    |
      | user2    | User      | 2-Two    |
      | user3    | User      | 3-Three  |
      | rater    | Rater     | User     |
    And the following job assignments exist:
      | user  | idnumber | appraiser | manager |
      | user1 | 1        |           | rater   |
      | user2 | 2        | rater     |         |
      | user3 | 3        | rater     | rater   |
    And the following "competency" frameworks exist:
      | fullname                 | idnumber |
      | Competency Framework One | fw1      |
    And the following "competency" hierarchy exists:
      | framework | fullname | idnumber |
      | fw1       | Comp1    | comp1    |
      | fw1       | Comp2    | comp2    |
      | fw1       | Comp3    | comp3    |
    And the following "manual pathways" exist in "totara_competency" plugin:
      | competency | roles             |
      | comp1      | manager           |
      | comp2      | appraiser         |
      | comp3      | manager,appraiser |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | comp1      | user            | user1      |
      | comp1      | user            | user2      |
      | comp1      | user            | user3      |
      | comp2      | user            | user2      |
      | comp2      | user            | user3      |
      | comp3      | user            | user3      |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    And I log in as "rater"

  Scenario: View team list and filter the users
    Given I am on profile page for user "rater"
    When I click on "Rate others' competencies" "link" in the ".block_totara_user_profile_category_development" "css_element"

    Then I should see "Rate competencies" in the "#page h2" "css_element"
    And I should see "User 1-One" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "1" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "User 3-Three" in the "//div[@class='tui-dataTableRow'][2]" "xpath_element"
    And I should see "3" in the "//div[@class='tui-dataTableRow'][2]" "xpath_element"
    And I should not see "User 2-Two"

    When I set the field with xpath "//select[@class='tui-select__input']" to "appraiser"
    Then I should see "User 2-Two" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "2" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "User 3-Three" in the "//div[@class='tui-dataTableRow'][2]" "xpath_element"
    And I should see "3" in the "//div[@class='tui-dataTableRow'][2]" "xpath_element"
    And I should not see "User 1-One"

    When I set the field with xpath "//*[@aria-label='Search people']" to "Three"
    Then I should see "User 3-Three" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "3" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should not see "User 1-One"
    And I should not see "User 2-Two"

  Scenario: Rate competencies for a user and switch roles
    When I navigate to the competency profile of user "user3"
    And I click on "Rate competencies" "link"

    # Select manager role
    When I set the field with xpath "//select[@class='tui-select__input']" to "manager"
    Then I should see "Comp1" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "Comp3" in the "//div[@class='tui-dataTableRow'][2]" "xpath_element"

    # Switch to appraiser role
    When I set the field with xpath "//select[@class='tui-select__input']" to "appraiser"
    Then I should see "Comp2" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "Comp3" in the "//div[@class='tui-dataTableRow'][2]" "xpath_element"

    # Make a rating, switch back to manager, should get a warning
    When I click on "Rate" "button" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I click on "//div[@class='tui-radioGroup']/div[@class='tui-radio'][1]/label[@class='tui-radio__label']" "xpath_element"
    And I click on "Done" "button"
    Then I should see "Competent" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I set the field with xpath "//select[@class='tui-select__input']" to "manager"
    Then I should see "Confirm role update"
    And I should see "You have unsubmitted ratings. If you change the role you are rating as, you will lose these unsaved changes."
    And I should see "Are you sure you would like to change your role?"

    # Dismiss the warning
    When I click on "Cancel" "button" in the ".tui-modal" "css_element"
    Then I should see "Comp2" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "Comp3" in the "//div[@class='tui-dataTableRow'][2]" "xpath_element"
    And I should see "Competent" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"

    # Confirm changing role despite draft rating
    When I set the field with xpath "//select[@class='tui-select__input']" to "appraiser"
    And I set the field with xpath "//select[@class='tui-select__input']" to "manager"
    Then I should see "Confirm role update"
    When I click on "OK" "button"
    Then I should see "Comp1" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "Comp3" in the "//div[@class='tui-dataTableRow'][2]" "xpath_element"
    And I should not see "Competent" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"

  Scenario: Filter the list of competencies when rating a user
    When I navigate to the competency profile of user "user3"
    And I click on "Rate competencies" "link"
    When I set the field with xpath "//select[@class='tui-select__input']" to "manager"

    # No filter options should be visible
    Then I should not see "Competency type" in the ".tui-bulkManualRatingRateUserCompetencies__filters" "css_element"
    Then I should not see "Reason assigned" in the ".tui-bulkManualRatingRateUserCompetencies__filters" "css_element"
    Then I should not see "Rating history" in the ".tui-bulkManualRatingRateUserCompetencies__filters" "css_element"

    # Add a competency type to enable the 'Competency type' filter
    When the following hierarchy types exist:
      | hierarchy  | idnumber | fullname            |
      | competency | type1    | Competency Type One |
    And the following "competency" hierarchy exists:
      | framework | fullname | idnumber | type  |
      | fw1       | Comp4    | comp4    | type1 |
    And the following "manual pathways" exist in "totara_competency" plugin:
      | competency | roles             |
      | comp4      | manager           |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | comp4      | user            | user3      |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    And I reload the page
    And I set the field with xpath "//select[@class='tui-select__input']" to "manager"
    Then I should see "Competency type" in the ".tui-bulkManualRatingRateUserCompetencies__filters" "css_element"
    And I should not see "Reason assigned" in the ".tui-bulkManualRatingRateUserCompetencies__filters" "css_element"
    And I should not see "Rating history" in the ".tui-bulkManualRatingRateUserCompetencies__filters" "css_element"

    # Add a different assignment to enable the 'Reason assigned' filter
    When the following "cohorts" exist:
      | name       | idnumber |
      | Cohort One | cohort1  |
    And the following "cohort members" exist:
      | user  | cohort  |
      | user3 | cohort1 |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | comp3      | cohort          | cohort1    |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    And I reload the page
    And I set the field with xpath "//select[@class='tui-select__input']" to "manager"
    Then I should see "Competency type" in the ".tui-bulkManualRatingRateUserCompetencies__filters" "css_element"
    And I should see "Reason assigned" in the ".tui-bulkManualRatingRateUserCompetencies__filters" "css_element"
    And I should not see "Rating history" in the ".tui-bulkManualRatingRateUserCompetencies__filters" "css_element"

    # Rate a competency to enable the 'Rating history' filter
    And the following "manual ratings" exist in "totara_competency" plugin:
      | competency | subject_user | rater_user | role      | scale_value  | comment   |
      | comp4      | user3        | rater      | manager   |              | Good job. |
    And I reload the page
    And I set the field with xpath "//select[@class='tui-select__input']" to "manager"
    Then I should see "Competency type" in the ".tui-bulkManualRatingRateUserCompetencies__filters" "css_element"
    And I should see "Reason assigned" in the ".tui-bulkManualRatingRateUserCompetencies__filters" "css_element"
    And I should see "Rating history" in the ".tui-bulkManualRatingRateUserCompetencies__filters" "css_element"

    # Apply the competency type filter
    When I set the field "Competency type" to "Competency Type One"
    And I click on "Update selection" "button"
    Then I should see "Comp4" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should not see "Comp1"
    And I should not see "Comp3"
    When I set the field "Competency type" to "All"
    And I click on "Update selection" "button"
    Then I should see "Comp1" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "Comp3" in the "//div[@class='tui-dataTableRow'][2]" "xpath_element"
    And I should see "Comp4" in the "//div[@class='tui-dataTableRow'][3]" "xpath_element"

    # Apply the reason assigned filter
    When I set the field "Reason assigned" to "Cohort One (Audience)"
    And I click on "Update selection" "button"
    Then I should see "Comp3" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should not see "Comp1"
    And I should not see "Comp4"
    When I set the field "Reason assigned" to "Admin User (Admin)"
    And I click on "Update selection" "button"
    Then I should see "Comp1" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "Comp3" in the "//div[@class='tui-dataTableRow'][2]" "xpath_element"
    And I should see "Comp4" in the "//div[@class='tui-dataTableRow'][3]" "xpath_element"
    When I set the field "Reason assigned" to "Cohort One (Audience)"
    And I click on "Update selection" "button"
    Then I should see "Comp3" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should not see "Comp1"
    And I should not see "Comp4"
    When I set the field "Reason assigned" to "All"
    And I click on "Update selection" "button"
    Then I should see "Comp1" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "Comp3" in the "//div[@class='tui-dataTableRow'][2]" "xpath_element"
    And I should see "Comp4" in the "//div[@class='tui-dataTableRow'][3]" "xpath_element"

    # Apply the rating history filter
    When I set the field "Rating history" to "Never rated"
    And I click on "Update selection" "button"
    Then I should see "Comp1" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "Comp3" in the "//div[@class='tui-dataTableRow'][2]" "xpath_element"
    And I should not see "Comp4"
    When I set the field "Rating history" to "Previously rated"
    And I click on "Update selection" "button"
    Then I should see "Comp4" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should not see "Comp1"
    And I should not see "Comp3"

    # Confirmation modal shown when applying filters without saving ratings
    When I click on "Rate" "button" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I click on "//div[@class='tui-radioGroup']/div[@class='tui-radio'][1]/label[@class='tui-radio__label']" "xpath_element"
    And I click on "Done" "button"
    Then I should see "Competent" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    When I set the field "Rating history" to "All"
    And I click on "Update selection" "button"
    Then I should see "Confirm selection update"
    And I should see "You have unsubmitted ratings. If you apply the filters to change the selection of competencies, you will lose these unsaved changes."
    And I should see "Are you sure you would like to update the selection?"

    # Dismiss the modal
    When I click on "Cancel" "button" in the ".tui-modal" "css_element"
    Then I should see "Comp4" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should not see "Comp1"
    And I should not see "Comp3"
    When I click on "Update selection" "button"
    Then I should see "Confirm selection update"

    # Confirm the modal, which applies the filter
    When I click on "OK" "button" in the ".tui-modal" "css_element"
    Then I should see "Comp1" in the "//div[@class='tui-dataTableRow'][1]" "xpath_element"
    And I should see "Comp3" in the "//div[@class='tui-dataTableRow'][2]" "xpath_element"
    And I should see "Comp4" in the "//div[@class='tui-dataTableRow'][3]" "xpath_element"
