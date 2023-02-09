@totara @perform @mod_perform @javascript @vuejs
Feature: Assign position user groups to perform activities
  As an activity administrator
  I need to be able to assign position user groups to individual perform activities

  Background:
    Given I am on a totara site

    And the following "activities" exist in "mod_perform" plugin:
      | activity_name    | description      | activity_type | create_track |
      | My Test Activity | My Test Activity | feedback      | true         |

    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname      | idnumber |
      | Position FW 1 | PFW01    |

    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname     | shortname | idnumber |
      | PFW01         | IT Manager   | IT Man    | POS01    |
      | PFW01         | IT Developer | IT Dev    | POS02    |
      | PFW01         | IT Tester    | IT Tst    | POS03    |

    And the following "users" exist:
      | username | firstname | lastname | email                |
      | itmgr    | Manager   | IT       | manager1@example.com |
      | itdev    | Dev       | IT       | itdev@example.com    |
      | ittst    | Test      | IT       | ittst@example.com    |

    And the following job assignments exist:
      | user  | fullname | shortname | manager | position | idnumber |
      | itmgr | itmgr ja | itmgr ja  |         | POS01    | JA0000   |
      | itdev | itdev ja | itdev ja  | itmgr   | POS02    | JA0001   |
      | ittst | ittst ja | ittst ja  | itmgr   | POS03    | JA0002   |

  Scenario: Assign positions to activity
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    Then I should see the tui datatable contains:
      | Name             | Type     | Status |
      | My Test Activity | Feedback | Active |

    When I click on "My Test Activity" "link"
    And I click on "Assignments" "link"
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Position" "link" in the ".tui-dropdown__menu" "css_element"
    Then I should see the following unselected adder picker entries:
      | Position     |
      | IT Developer |
      | IT Manager   |
      | IT Tester    |

    When I toggle the adder picker entry with "IT Manager" for "Position"
    And I discard my selections and close the adder
    And I wait until the page is ready
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Position" "link" in the ".tui-dropdown__menu" "css_element"
    And I toggle the adder picker entry with "IT Developer" for "Position"
    And I toggle the adder picker entry with "IT Manager" for "Position"
    Then I should see the following selected adder picker entries:
      | Position     |
      | IT Developer |
      | IT Manager   |
    And I should see the following unselected adder picker entries:
      | Position  |
      | IT Tester |

    When I save my selections and close the adder
    Then I should see the tui datatable contains:
      | Name         | Users |
      | IT Manager   | 1     |
      | IT Developer | 1     |

    When I click on "Assign users" "button"
    And I click on "Position" "link" in the ".tui-dropdown__menu" "css_element"
    Then I should see the following disabled adder picker entries:
      | Position     |
      | IT Developer |
      | IT Manager   |
    And I should see the following unselected adder picker entries:
      | Position  |
      | IT Tester |

  Scenario: Adder basket reflects selections
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "My Test Activity" "link"
    And I click on "Assignments" "link"
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Position" "link" in the ".tui-dropdown__menu" "css_element"
    And I toggle the adder picker entry with "IT Developer" for "Position"
    And I toggle the adder picker entry with "IT Manager" for "Position"
    And I click on "Selected &#8237;( 2 )&#8237;" "link"
    Then I should see the following selected adder basket entries:
      | Position     |
      | IT Developer |
      | IT Manager   |
    And I should not see the following adder basket entries:
      | Position  |
      | IT Tester |

    When I toggle the adder basket entry with "IT Manager" for "Position"
    And I click on "Browse all" "link"
    Then I should see the following selected adder picker entries:
      | Position     |
      | IT Developer |
    And I should see the following unselected adder picker entries:
      | Position   |
      | IT Manager |
      | IT Tester  |

  Scenario: Search for positions to assign to activity
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "My Test Activity" "link"
    And I click on "Assignments" "link"
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Position" "link" in the ".tui-dropdown__menu" "css_element"
    Then I should see the following unselected adder picker entries:
      | Audience name |
      | IT Developer  |
      | IT Manager    |
      | IT Tester     |

    When I set the following fields to these values:
      | Search hierarchy | Dev |
    Then I should see the following unselected adder picker entries:
      | Position     |
      | IT Developer |
    And I should not see the following adder picker entries:
      | Position   |
      | IT Manager |
      | IT Tester  |

    When I set the following fields to these values:
      | Search hierarchy |  |
    Then I should see the following unselected adder picker entries:
      | Position     |
      | IT Developer |
      | IT Manager   |
      | IT Tester    |