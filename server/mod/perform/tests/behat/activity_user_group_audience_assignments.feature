@totara @perform @mod_perform @javascript @vuejs
Feature: Assign user groups to perform activities
  As an activity administrator
  I need to be able to assign user groups to individual perform activities

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
    And the following "cohorts" exist:
      | name        | idnumber    | description            | contextlevel | reference | cohorttype |
      | 22 SAS      | 22 SAS      | UK Special Air Service | System       | 0         | 1          |
      | Delta Force | Delta Force | US Delta Force         | System       | 0         | 1          |
      | Seal Team 6 | Seal Team 6 | US Seal Team 6         | System       | 0         | 1          |
    And the following "cohort members" exist:
      | user     | cohort      |
      | learner1 | Seal Team 6 |
      | learner2 | Seal Team 6 |
      | learner3 | Delta Force |
      | learner4 | 22 SAS      |

  Scenario: Assign audiences to activity
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
    And I click on "Audience" "link" in the ".tui-dropdown__menu" "css_element"
    Then I should see the following unselected adder picker entries:
      | Audience name | Short name  |
      | 22 SAS        | 22 SAS      |
      | Delta Force   | Delta Force |
      | Seal Team 6   | Seal Team 6 |

    When I toggle the adder picker entry with "22 SAS" for "Audience name"
    And I discard my selections and close the adder
    And I wait until the page is ready
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Audience" "link" in the ".tui-dropdown__menu" "css_element"
    And I toggle the adder picker entry with "22 SAS" for "Audience name"
    And I toggle the adder picker entry with "Seal Team 6" for "Short name"
    Then I should see the following selected adder picker entries:
      | Audience name | Short name  |
      | 22 SAS        | 22 SAS      |
      | Seal Team 6   | Seal Team 6 |
    And I should see the following unselected adder picker entries:
      | Audience name | Short name  |
      | Delta Force   | Delta Force |

    When I save my selections and close the adder
    Then I should see the tui datatable contains:
      | Name        | Users |
      | 22 SAS      | 1     |
      | Seal Team 6 | 2     |

    When I click on "Assign users" "button"
    And I click on "Audience" "link" in the ".tui-dropdown__menu" "css_element"
    Then I should see the following disabled adder picker entries:
      | Audience name | Short name  |
      | Seal Team 6   | Seal Team 6 |
      | 22 SAS        | 22 SAS      |
    And I should see the following unselected adder picker entries:
      | Audience name | Short name  |
      | Delta Force   | Delta Force |

  Scenario: Adder basket reflects selections
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "My Test Activity" "link"
    And I click on "Assignments" "link"
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Audience" "link" in the ".tui-dropdown__menu" "css_element"
    And I toggle the adder picker entry with "22 SAS" for "Audience name"
    And I toggle the adder picker entry with "Seal Team 6" for "Short name"
    And I click on "Selected &#8237;( 2 )&#8237;" "link"
    Then I should see the following selected adder basket entries:
      | Audience name | Short name  |
      | Seal Team 6   | Seal Team 6 |
      | 22 SAS        | 22 SAS      |
    And I should not see the following adder basket entries:
      | Audience name | Short name  |
      | Delta Force   | Delta Force |

    When I toggle the adder basket entry with "22 SAS" for "Audience name"
    And I click on "Browse all" "link"
    Then I should see the following selected adder picker entries:
      | Audience name | Short name  |
      | Seal Team 6   | Seal Team 6 |
    And I should see the following unselected adder picker entries:
      | Audience name | Short name  |
      | 22 SAS        | 22 SAS      |
      | Delta Force   | Delta Force |

  Scenario: Search for audiences to assign to activity
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "My Test Activity" "link"
    And I click on "Assignments" "link"
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Audience" "link" in the ".tui-dropdown__menu" "css_element"
    Then I should see the following unselected adder picker entries:
      | Audience name | Short name  |
      | 22 SAS        | 22 SAS      |
      | Delta Force   | Delta Force |
      | Seal Team 6   | Seal Team 6 |

    When I set the following fields to these values:
      | Filter items by search | Delta |
    Then I should see the following unselected adder picker entries:
      | Audience name | Short name  |
      | Delta Force   | Delta Force |
    And  I should not see the following adder picker entries:
      | Audience name | Short name  |
      | 22 SAS        | 22 SAS      |
      | Seal Team 6   | Seal Team 6 |

    When I set the following fields to these values:
      | Filter items by search |  |
    Then I should see the following unselected adder picker entries:
      | Audience name | Short name  |
      | 22 SAS        | 22 SAS      |
      | Seal Team 6   | Seal Team 6 |
      | Delta Force   | Delta Force |
