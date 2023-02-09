@totara @perform @totara_competency @pathway_manual @javascript @vuejs
Feature: Test rating a single competency via the competency detail page.

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname |
      | user1    | User      | One      |
      | user2    | User      | Two      |
      | user3    | User      | Three    |
    And the following job assignments exist:
      | user  | idnumber | appraiser | manager |
      | user1 | 1        | user3     | user2   |
    And the following "competency" frameworks exist:
      | fullname                 | idnumber |
      | Competency Framework One | fw1      |
    And the following "competency" hierarchy exists:
      | framework | fullname | idnumber |
      | fw1       | Comp1    | comp1    |
      | fw1       | Comp2    | comp2    |
    And the following "manual pathways" exist in "totara_competency" plugin:
      | competency | roles     |
      | comp1      | self      |
      | comp2      | self      |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | comp1      | user            | user1      |
      | comp2      | user            | user1      |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    When I log in as "user1"
    And I navigate to the competency profile of user "user1"
    And I change the competency profile to list view
    And I click on "Comp1" "link"
    And I click on "Add rating" "link"

  Scenario: Remove single competency filter
    Then I should see "Your view is currently filtered to show a single competency only."
    And I should see "Comp1"
    And I should not see "Comp2"

    When I click on "View all" "button"
    Then I should not see "Your view is currently filtered to show a single competency only."
    And I should see "Comp1"
    And I should see "Comp2"

  Scenario: Remove single competency filter after having adding a rating
    Then I should see "Your view is currently filtered to show a single competency only."
    And I should see "Comp1"
    And I should not see "Comp2"

    # Add rating
    When I click on "Rate" "button"
    And I click on "//*[contains(text(), 'Select scale value')]/parent::*//label[contains(text(), 'Competent')]" "xpath_element"
    And I click on "Done" "button"

    When I click on "View all" "button"
    Then I should see "Confirm selection update"
    And I should see "Are you sure you would like to update the selection?"
    When I click on "Cancel" "button" in the ".tui-modal" "css_element"

    Then I should see "Your view is currently filtered to show a single competency only."
    And I should see "Comp1"
    And I should not see "Comp2"

    When I click on "View all" "button"
    And I click on "OK" "button" in the ".tui-modal" "css_element"
    Then I should not see "Your view is currently filtered to show a single competency only."
    And I should see "Comp1"
    And I should see "Comp2"
