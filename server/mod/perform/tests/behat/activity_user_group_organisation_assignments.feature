@totara @perform @mod_perform @javascript @vuejs
Feature: Assign organisation user groups to perform activities
  As an activity administrator
  I need to be able to assign organisation user groups to individual perform activities

  Background:
    Given I am on a totara site

    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name    | description      | activity_type | create_track |
      | My Test Activity | My Test Activity | feedback      | true         |

    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname              | idnumber |
      | Department of Defense | USDOD    |

    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname               | shortname | idnumber |
      | USDOD         | Information Technology | IT        | ORG01    |
      | USDOD         | Finance                | FIN       | ORG02    |
      | USDOD         | Logistics              | LOG       | ORG03    |

    And the following "users" exist:
      | username | firstname | lastname | email                |
      | itmgr    | Manager   | IT       | manager1@example.com |
      | itdev    | Dev       | IT       | itdev@example.com    |
      | ittst    | Test      | IT       | ittst@example.com    |

    And the following job assignments exist:
      | user  | fullname | shortname | manager | organisation | idnumber |
      | itmgr | itmgr ja | itmgr ja  |         | ORG01        | JA0000   |
      | itdev | itdev ja | itdev ja  | itmgr   | ORG01        | JA0001   |
      | ittst | ittst ja | ittst ja  | itmgr   | ORG01        | JA0002   |

  Scenario: Assign organisations to activity
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    Then I should see the tui datatable contains:
      | Name             | Type     | Status |
      | My Test Activity | Feedback | Active |

    When I click on "My Test Activity" "link"
    And I click on "Assignments" "link"
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Organisation" "link" in the ".tui-dropdown__menu" "css_element"
    Then I should see the following unselected adder picker entries:
      | Organisation           |
      | Information Technology |
      | Finance                |
      | Logistics              |

    When I toggle the adder picker entry with "Finance" for "Organisation"
    And I discard my selections and close the adder
    And I wait until the page is ready
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Organisation" "link" in the ".tui-dropdown__menu" "css_element"
    And I toggle the adder picker entry with "Information Technology" for "Organisation"
    And I toggle the adder picker entry with "Finance" for "Organisation"
    Then I should see the following selected adder picker entries:
      | Organisation           |
      | Information Technology |
      | Finance                |
    And I should see the following unselected adder picker entries:
      | Organisation |
      | Logistics    |

    When I save my selections and close the adder
    Then I should see the tui datatable contains:
      | Name                   | Users |
      | Information Technology | 3     |
      | Finance                | 0     |

    When I click on "Assign users" "button"
    And I click on "Organisation" "link" in the ".tui-dropdown__menu" "css_element"
    Then I should see the following disabled adder picker entries:
      | Organisation           |
      | Information Technology |
      | Finance                |
    And I should see the following unselected adder picker entries:
      | Organisation |
      | Logistics    |

  Scenario: Adder basket reflects selections
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "My Test Activity" "link"
    And I click on "Assignments" "link"
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Organisation" "link" in the ".tui-dropdown__menu" "css_element"
    And I toggle the adder picker entry with "Information Technology" for "Organisation"
    And I toggle the adder picker entry with "Finance" for "Organisation"
    And I click on "Selected &#8237;( 2 )&#8237;" "link"
    Then I should see the following selected adder basket entries:
      | Organisation           |
      | Information Technology |
      | Finance                |
    And I should not see the following adder basket entries:
      | Organisation |
      | Logistics    |

    When I toggle the adder basket entry with "Finance" for "Organisation"
    And I click on "Browse all" "link"
    Then I should see the following selected adder picker entries:
      | Organisation           |
      | Information Technology |
    And I should see the following unselected adder picker entries:
      | Organisation |
      | Finance      |
      | Logistics    |

  Scenario: Search for organisations to assign to activity
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "My Test Activity" "link"
    And I click on "Assignments" "link"
    Then I should see "No groups assigned"

    When I click on "Assign users" "button"
    And I click on "Organisation" "link" in the ".tui-dropdown__menu" "css_element"
    Then I should see the following unselected adder picker entries:
      | Audience name          |
      | Information Technology |
      | Finance                |
      | Logistics              |

    When I set the following fields to these values:
      | Search hierarchy | Tech |
    Then I should see the following unselected adder picker entries:
      | Organisation           |
      | Information Technology |
    And I should not see the following adder picker entries:
      | Organisation |
      | Finance      |
      | Logistics    |

    When I set the following fields to these values:
      | Search hierarchy |  |
    Then I should see the following unselected adder picker entries:
      | Organisation           |
      | Information Technology |
      | Finance                |
      | Logistics              |