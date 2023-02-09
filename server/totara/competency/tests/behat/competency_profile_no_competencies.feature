@totara @perform @totara_competency @javascript @vuejs
Feature: Message is shown if there are no competencies to be shown for a user's competency profile.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
    And the following job assignments exist:
      | user  | idnumber | manager |
      | user1 | 1        | user2   |

    And the following "competency" frameworks exist:
      | fullname                 | idnumber |
      | Competency Framework One | fw1      |
    And the following "competency" hierarchy exists:
      | framework | fullname | idnumber |
      | fw1       | Comp1    | comp1    |

  Scenario: User views their own competency profile that has no competencies
    When I log in as "user1"
    And I navigate to the competency profile of user "user1"
    Then I should see "User One" in the ".breadcrumb-nav" "css_element"
    And I should see "Competency profile" in the ".breadcrumb-nav" "css_element"
    And I should see "Competency profile" in the ".tui-pageHeading__title" "css_element"
    And I should see "There are no competencies currently assigned."
    And I should not see "Current assignment progress"

    # Make sure the no competencies message disappears
    When the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | comp1      | user            | user1      |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"

    Then I should see "User One" in the ".breadcrumb-nav" "css_element"
    And I should see "Competency profile" in the ".breadcrumb-nav" "css_element"
    And I should see "Competency profile" in the ".tui-pageHeading__title" "css_element"
    And I should not see "There are no competencies currently assigned."
    And I should see "Current assignment progress"

  Scenario: User views their staff's competency profile that has no competencies
    When I log in as "user2"
    And I navigate to the competency profile of user "user1"
    Then I should see "User One" in the ".breadcrumb-nav" "css_element"
    And I should see "User One" in the ".tui-miniProfileCard" "css_element"
    And I should see "There are no competencies currently assigned."
    And I should not see "Current assignment progress"

    # Make sure the no competencies message disappears
    When the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | comp1      | user            | user1      |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"

    Then I should see "User One" in the ".breadcrumb-nav" "css_element"
    And I should see "User One" in the ".tui-miniProfileCard" "css_element"
    And I should not see "There are no competencies currently assigned."
    And I should see "Current assignment progress"
