@totara @perform @totara_competency @pathway_manual @javascript @vuejs
Feature: Test rating a competency for a user as different roles via the rate competencies user profile link.
  Ensures that the basic navigation workflow can be followed.

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
    And I run the scheduled task "totara_competency\task\expand_assignments_task"

  Scenario: As a manager, make a rating for a user via the competencies block
    Given I log in as "user2"
    And I am on profile page for user "user2"
    When I click on "Rate others' competencies" "link" in the ".block_totara_user_profile_category_development" "css_element"
    Then I should see "Rate competencies" in the ".tui-pageHeading__title" "css_element"
    And I should see "Rating as a manager"
    When I click on "User One" "link"
    Then I should see "Rate competencies" in the ".tui-pageHeading__title" "css_element"
    And I should see "User One" in the ".tui-miniProfileCard" "css_element"
    And I should see "Rating as a manager"

    # Actually make a rating
    When I click on "Rate" "button"
    And I click on "//div[@class='tui-radioGroup']/div[@class='tui-radio'][1]/label[@class='tui-radio__label']" "xpath_element"
    And I click on "Done" "button"
    And I click on "Submit" "button"
    And I click on "OK" "button"
    Then I should see "Competency profile"
    And I should see "Your rating has been saved." in the tui success notification toast

  Scenario: As an appraiser, make a rating for a user via the competencies block
    Given I log in as "user3"
    And I am on profile page for user "user3"
    When I click on "Rate others' competencies" "link" in the ".block_totara_user_profile_category_development" "css_element"
    Then I should see "Rate competencies" in the ".tui-pageHeading__title" "css_element"
    And I should see "Rating as an appraiser"
    When I click on "User One" "link"
    Then I should see "Rate competencies" in the ".tui-pageHeading__title" "css_element"
    And I should see "User One" in the ".tui-miniProfileCard" "css_element"
    And I should see "Rating as an appraiser"

    # Actually make a rating
    When I click on "Rate" "button"
    And I click on "//div[@class='tui-radioGroup']/div[@class='tui-radio'][1]/label[@class='tui-radio__label']" "xpath_element"
    And I click on "Done" "button"
    And I click on "Submit" "button"
    And I click on "OK" "button"
    Then I should see "Competency profile"
    And I should see "Your rating has been saved." in the tui success notification toast
