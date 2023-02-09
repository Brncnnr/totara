@totara @perform @totara_competency @pathway_manual @javascript @vuejs
Feature: Test miscellaneous bulk manual rating features.

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
    And the following "manual pathways" exist in "totara_competency" plugin:
      | competency | roles     |
      | comp1      | self      |
      | comp1      | manager   |
      | comp1      | appraiser |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | comp1      | user            | user1      |
    When I run the scheduled task "totara_competency\task\expand_assignments_task"

  Scenario: Show last rating help for myself
    Given I log in as "user1"
    When I navigate to the competency profile of user "user1"
    And I click on "Rate competencies" "link"

    Then I should see "Previous rating"
    When I click on "Show help for Previous rating" "button"
    Then I should see "The last rating you gave yourself for this competency" in the ".tui-popoverFrame" "css_element"
    When I click on "Close" "button" in the ".tui-popoverFrame" "css_element"

  Scenario: Show last rating help for another user
    Given I log in as "user3"
    And I am on profile page for user "user3"
    When I click on "Rate others' competencies" "link" in the ".block_totara_user_profile_category_development" "css_element"

    Then I should see "Rate competencies" in the "#page h2" "css_element"
    Then I should see "Last rated"
    When I click on "Show help for Previous rating" "button"
    Then I should see "The last rating this employee was given by someone in this role" in the ".tui-popoverFrame" "css_element"
    When I click on "Close" "button" in the ".tui-popoverFrame" "css_element"

    And I click on "User One" "link"
    Then I should see "Rate competencies" in the ".tui-pageHeading__title" "css_element"
    And I should see "User One" in the ".tui-miniProfileCard" "css_element"
    And I should see "Previous rating"
    When I click on "Show help for Previous rating" "button"
    Then I should see "The last rating this employee was given by someone in this role" in the ".tui-popoverFrame" "css_element"
    When I click on "Close" "button" in the ".tui-popoverFrame" "css_element"

  Scenario: Show rating scale help
    Given I log in as "user1"
    When I navigate to the competency profile of user "user1"
    And I click on "Rate competencies" "link"

    Then I should see "New rating"
    When I click on "Show help for Rating scale" "button"
    Then I should see "Rating scale" in the ".tui-competencyRatingScaleOverview" "css_element"
    And I should see "This value is proficient." in the ".tui-competencyRatingScaleOverview" "css_element"
    And I should see "This value is not proficient." in the ".tui-competencyRatingScaleOverview" "css_element"

  Scenario: Show the last rating made for a user
    When I log in as "user2"
    Given I am on profile page for user "user2"
    And I click on "Rate others' competencies" "link" in the ".block_totara_user_profile_category_development" "css_element"
    Then I should not see "January 2020"
    Given the following "manual ratings" exist in "totara_competency" plugin:
      | competency | subject_user | rater_user | role    | scale_value | date       |
      | comp1      | user1        | user2      | manager |             | 2020-01-02 |
      | comp1      | user1        | user2      | manager |             | 2020-01-01 |
    When I reload the page
    Then I should see "2 January 2020"
    When I click on "User One" "link"
    Then I should see "2 January 2020"
