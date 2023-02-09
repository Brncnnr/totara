@totara @perform @mod_perform @javascript @vuejs
Feature: Assign individuals to perform activities
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
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name    | description      | activity_type | create_track |
      | My Test Activity | My Test Activity | feedback      | true         |

  Scenario: Assign individuals to activity
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    Then I should see the tui datatable contains:
      | Name             | Type     | Status |
      | My Test Activity | Feedback | Active |

    When I click on "My Test Activity" "link"
    And I click on "Assignments" "link"
    And I wait until the page is ready
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Individual" "link" in the ".tui-dropdown__menu" "css_element"
    Then I should see the tui select table in the ".tui-adder" "css_element" contains:
      | Learner Four  |
      | Learner One   |
      | Learner Three |
      | Learner Two   |

    When I toggle the selection of row "1" of the tui select table in the ".tui-adder" "css_element"
    And I discard my selections and close the adder
    And I wait until the page is ready
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Individual" "link" in the ".tui-dropdown__menu" "css_element"
    And I toggle the selection of row "1" of the tui select table in the ".tui-adder" "css_element"
    And I toggle the selection of row "3" of the tui select table in the ".tui-adder" "css_element"

    When I save my selections and close the adder
    Then I should see the tui select table contains:
      | Learner Three |
      | Learner Four  |

    When I click on "Assign users" "button"
    And I click on "Individual" "link" in the ".tui-dropdown__menu" "css_element"
    Then I should see the adder disabled row "1" of the tui select table
    And I should see the adder disabled row "3" of the tui select table


  Scenario: Individuals adder basket reflects selections
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "My Test Activity" "link"
    And I click on "Assignments" "link"
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Individual" "link" in the ".tui-dropdown__menu" "css_element"
    And I toggle the selection of row "1" of the tui select table in the ".tui-adder" "css_element"
    And I toggle the selection of row "3" of the tui select table in the ".tui-adder" "css_element"
    And I click on "Selected &#8237;( 2 )&#8237;" "link"
    Then I should see the tui select table in the ".tui-adder" "css_element" contains:
      | Learner Four  |
      | Learner Three |

    When I toggle the selection of row "1" of the tui select table in the ".tui-adder" "css_element"
    And I click on "Browse all" "link"
    Then I should see the adder selected row "3" of the tui select table
    And I should see the adder unselected row "1" of the tui select table
    And I should see the adder unselected row "2" of the tui select table
    And I should see the adder unselected row "4" of the tui select table

  Scenario: Search for individuals to assign to activity
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "My Test Activity" "link"
    And I click on "Assignments" "link"
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Individual" "link" in the ".tui-dropdown__menu" "css_element"
    Then I should see the tui select table in the ".tui-adder" "css_element" contains:
      | Learner Four  |
      | Learner One   |
      | Learner Three |
      | Learner Two   |

    When I set the following fields to these values:
      | Filter items by search | One |
    Then I should see the tui select table in the ".tui-adder" "css_element" contains:
      | Learner One |

    When I set the following fields to these values:
      | Filter items by search |  |
    Then I should see the tui select table in the ".tui-adder" "css_element" contains:
      | Learner Four  |
      | Learner One   |
      | Learner Three |
      | Learner Two   |

