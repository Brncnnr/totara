@totara @perform @mod_perform @javascript @vuejs
Feature: Remove individuals to perform activities
  As an activity administrator
  I need to be able to assign users to individual perform activities

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | learner1 | Learner   | One      | one@example.com   |
      | learner2 | Learner   | Two      | two@example.com   |
      | learner3 | Learner   | Three    | three@example.com |
      | learner4 | Learner   | Four     | four@example.com  |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name      | description        | activity_type | create_track | activity_status |
      | My Test Activity   | My Test Activity   | feedback      | true         | Draft           |
      | My Active Activity | My Active Activity | feedback      | true         | Active          |

  Scenario: Remove individuals from draft activity
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "My Test Activity" "link"
    And I wait until the page is ready
    And I click on "Assignments" "link"
    And I wait until the page is ready
    And I click on "Assign users" "button"
    And I click on "Individual" "link" in the ".tui-dropdown__menu" "css_element"
    And I toggle the selection of row "1" of the tui select table in the ".tui-adder" "css_element"
    And I toggle the selection of row "3" of the tui select table in the ".tui-adder" "css_element"
    And I save my selections and close the adder

    When I click on "Remove Learner Three from Individuals" "button"
    Then I should see "Remove assigned individual" in the ".tui-modalContent__header-title" "css_element"

    When I click on "Cancel" "button" in the ".tui-modal" "css_element"
    Then I should not see "Remove assigned individual"

    When I click on "Remove Learner Three from Individuals" "button"
    And I click on "Remove" "button" in the ".tui-modal" "css_element"
    And I wait until the page is ready
    Then I should see the tui select table contains:
      | Learner Four  |

  Scenario: Remove individuals from active activity
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "My Active Activity" "link"
    And I wait until the page is ready
    And I click on "Assignments" "link"
    And I wait until the page is ready
    And I click on "Assign users" "button"
    And I click on "Individual" "link" in the ".tui-dropdown__menu" "css_element"
    And I toggle the selection of row "1" of the tui select table in the ".tui-adder" "css_element"
    And I toggle the selection of row "3" of the tui select table in the ".tui-adder" "css_element"
    And I save my selections and close the adder

    When I click on "Remove Learner Three from Individuals" "button"
    Then I should see "Remove assigned individual" in the ".tui-modalContent__header-title" "css_element"

    When I click on "Cancel" "button" in the ".tui-modal" "css_element"
    Then I should not see "Remove assigned individual"

    When I click on "Remove Learner Three from Individuals" "button"
    And I click on "Remove" "button" in the ".tui-modal" "css_element"
    And I wait until the page is ready
    Then I should see the tui select table contains:
      | Learner Four  |