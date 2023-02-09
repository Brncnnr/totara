@totara @perform @totara_competency @javascript @vuejs
Feature: Test competencies can be user assigned

  Background:
    Given I am on a totara site
    And the following "competency" frameworks exist:
      | fullname                 | idnumber | description                    |
      | Competency Framework One | CFrame   | Framework for Competencies     |
      | Competency Framework Two | CFrame 2 | Framework for Competencies too |
    And the following hierarchy types exist:
      | hierarchy  | idnumber    | fullname            |
      | competency | Comp type 1 | Competency Type One |
      | competency | Comp type 2 | Competency Type Two |

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |

  Scenario: I can filter competencies and assign many to myself
    Given I log in as "admin"
    And the following "competency" hierarchy exists:
      | framework | fullname                       | idnumber | type        | description                                                        | assignavailability |
      | CFrame    | Self assignable                | sa1      | Comp type 1 | <a href="https://www.example.com" rel="noopener">Find out more</a> | any                |
      | CFrame    | Self assignable no description | sa2      | Comp type 2 |                                                                    | any                |
      | CFrame    | Non self assignable            | nsa1     |             |                                                                    | none               |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | sa1        | user            | admin      |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    And I navigate to my competency profile

    When I change the competency profile to list view
    And I should see the tui datatable contains:
      | Competency      |
      | Self assignable |

    When I click on "Self-assign competencies" "link"

    # Should have "self-assignment" title/heading/nav
    Then I should see "Self assignment" in the ".breadcrumb" "css_element"
    And I should see "Back to your competency profile"
    And I should see "Self-assign competencies" in the ".tui-pageHeading__title" "css_element"

    And I should not see "Competency assignment" in the ".breadcrumb" "css_element"
    And I should not see "Back to competency profile"

    # Filter should be visible
    And I should see "Filter competencies" in the ".tui-filterSidePanel__header" "css_element"
    And I should see "Filter competencies"
    And the tui basket should be empty
    And I should see "2 competencies"
    And I should see the tui datatable contains:
      | Competency                     | Status             | Reason assigned    |
      | Self assignable                | Currently assigned | Admin User (Admin) |
      | Self assignable no description | Not assigned       |                    |

    # Open the expandable content
    When I toggle expanding row "1" of the tui datatable
    Then I should see "Self assignable" under the expanded row of the tui datatable
    And I should see "Description" under the expanded row of the tui datatable
    # Assert there is an html description that has actually rendered html
    And I should see "Find out more" in the "Find out more" "link"

    When I close the tui datatable expandable content
    And I click "Competency Type One" in the "Competency types" tui multi select filter
    Then I should see "1 competencies"
    And I should see the tui datatable contains:
      | Competency      | Status             | Reason assigned    |
      | Self assignable | Currently assigned | Admin User (Admin) |


    # Now both types are selected
    And I click "Competency Type Two" in the "Competency types" tui multi select filter
    Then I should see "2 competencies"
    And I should see the tui datatable contains:
      | Competency                     | Status             | Reason assigned    |
      | Self assignable                | Currently assigned | Admin User (Admin) |
      | Self assignable no description | Not assigned       |                    |


    # Unselect type one
    And I click "Competency Type One" in the "Competency types" tui multi select filter
    Then I should see "1 competencies"
    And I should see the tui datatable contains:
      | Competency                     | Status       | Reason assigned |
      | Self assignable no description | Not assigned |                 |


    # Unselect type two, both are unselected
    And I click "Competency Type Two" in the "Competency types" tui multi select filter

    Then I click the select all checkbox in the tui datatable
    And I should see "2" items in the tui basket

    Then I select "Framework Two" from the "Competency frameworks" singleselect
    And I should see the tui datatable is empty

    When I click on "View selected" "button"

    # The filter side panel should not be visible now
    Then I should not see "Filter competencies"
    And I should see the tui datatable contains:
      | Competency                     | Status             | Reason assigned    |
      | Self assignable                | Currently assigned | Admin User (Admin) |
      | Self assignable no description | Not assigned       |                    |


    Then I click on "Assign competencies" "button"
    And I should see "You have selected 2 competencies to assign" in the tui modal

    When I confirm the tui confirmation modal
    Then I should be on my competency profile
    And I should see "2 competencies have been assigned." in the tui success notification toast

    When I change the competency profile to list view
    Then I should see the tui datatable contains:
      | Competency                     | Reason assigned   | Proficient | Achievement level |
      | Self assignable                | Directly assigned | - No       | No value achieved |
      |                                | Self-assigned     | - No       | No value achieved |
      | Self assignable no description | Self-assigned     | - No       | No value achieved |

  Scenario: I can return from and clear selections
    Given I log in as "admin"
    And the following "competency" hierarchy exists:
      | framework | fullname                       | idnumber | assignavailability | type        | description |
      | CFrame    | Self assignable                | sa1      | any                | Comp type 1 |             |
      | CFrame    | Self assignable no description | sa2      | any                | Comp type 2 |             |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group |
      | sa1        | user            | admin      |
    And I run the scheduled task "totara_competency\task\expand_assignments_task"
    And I navigate to the competency self assignment page

    When I select "Currently assigned" from the "Status" singleselect
    And I click the select all checkbox in the tui datatable
    Then I should see the tui datatable contains:
      | Competency      | Status             | Reason assigned    |
      | Self assignable | Currently assigned | Admin User (Admin) |
    And I should see "1" items in the tui basket

    When I click on "View selected" "button"
    Then I should see the tui datatable contains:
      | Competency      | Status             | Reason assigned    |
      | Self assignable | Currently assigned | Admin User (Admin) |

    # Going back should not remove the applied assignment status filter
    When I click on "Back to all competencies" "button"
    Then I should see the tui datatable contains:
      | Competency      | Status             | Reason assigned    |
      | Self assignable | Currently assigned | Admin User (Admin) |
    And I should see "1" items in the tui basket
    And the field "Status" matches value "Currently assigned"

    # Clearing all should reset all filters
    When I click on "View selected" "button"
    And I click on "Clear all" "button"
    Then I should see the tui datatable contains:
      | Competency                     | Status             | Reason assigned    |
      | Self assignable                | Currently assigned | Admin User (Admin) |
      | Self assignable no description | Not assigned       |                    |
    And the tui basket should be empty
    And the field "Status" matches value "Any"

  Scenario: I can load more pages of competencies
    Given I log in as "admin"
    And the following "competency" hierarchy exists:
      | framework | fullname                | idnumber | assignavailability | type        |

      # First page
      | CFrame    | AA First of first page  | sa1      | any                | Comp type 1 |
      | CFrame    | AB Self assignable 2    | sa2      | any                | Comp type 1 |
      | CFrame    | AB Self assignable 3    | sa3      | any                | Comp type 1 |
      | CFrame    | AB Self assignable 4    | sa4      | any                | Comp type 1 |
      | CFrame    | AB Self assignable 5    | sa5      | any                | Comp type 1 |
      | CFrame    | AB Self assignable 6    | sa6      | any                | Comp type 1 |
      | CFrame    | AB Self assignable 7    | sa7      | any                | Comp type 1 |
      | CFrame    | AB Self assignable 8    | sa8      | any                | Comp type 1 |
      | CFrame    | AB Self assignable 9    | sa9      | any                | Comp type 1 |
      | CFrame    | AB Self assignable 10   | sa10     | any                | Comp type 1 |
      | CFrame    | AB Self assignable 11   | sa11     | any                | Comp type 1 |
      | CFrame    | AB Self assignable 12   | sa12     | any                | Comp type 1 |
      | CFrame    | AB Self assignable 13   | sa13     | any                | Comp type 1 |
      | CFrame    | AB Self assignable 14   | sa14     | any                | Comp type 1 |
      | CFrame    | AB Self assignable 15   | sa15     | any                | Comp type 1 |
      | CFrame    | AB Self assignable 16   | sa16     | any                | Comp type 1 |
      | CFrame    | AB Self assignable 17   | sa17     | any                | Comp type 1 |
      | CFrame    | AB Self assignable 18   | sa18     | any                | Comp type 1 |
      | CFrame    | AB Self assignable 19   | sa19     | any                | Comp type 1 |
      | CFrame    | AZ Last of first page   | sa20     | any                | Comp type 1 |

      # Second page
      | CFrame    | BA First of second page | sa21     | any                | Comp type 1 |
      | CFrame    | BB Self assignable 22   | sa22     | any                | Comp type 1 |
      | CFrame    | BB Self assignable 23   | sa23     | any                | Comp type 1 |
      | CFrame    | BB Self assignable 24   | sa24     | any                | Comp type 1 |
      | CFrame    | BZ Last of second page  | sa25     | any                | Comp type 1 |
    And I navigate to the competency self assignment page

    Then I should see "Load more"
    # The total record count should be indicated before loading all pages
    And I should see "25 competencies"
    And I should see "20" rows in the tui datatable
    And I should see "AA First of first page" under "Competency" on row "1" of the tui datatable
    And I should see "AZ Last of first page" under "Competency" on row "20" of the tui datatable

    When I click on "Load more" "button"
    Then I should not see "Load more"
    And I should see "25 competencies"
    And I should see "25" rows in the tui datatable
    And I should see "BA First of second page" under "Competency" on row "21" of the tui datatable
    And I should see "BZ Last of second page" under "Competency" on row "25" of the tui datatable


  Scenario: I am shown an empty list when there is no competencies to self assign
    Given I log in as "admin"
    And I navigate to the competency self assignment page
    Then I should see the tui datatable is empty
    And I should see "No items to display"
    And I should see "Self assignment" in the ".breadcrumb" "css_element"
    And I should see "Back to your competency profile"
    And I should not see "Back to competency profile"

    # Side panel filters
    And I should see "Filter competencies"

    # Basket
    And the "Assign competencies" "button" should be disabled

  Scenario: Labels are generic when assigning competencies to someone else
    Given I log in as "admin"
    When I navigate to the competency user assignment page for guest user

    Then I should see "Competency assignment" in the ".breadcrumb" "css_element"
    And I should see "Back to competency profile"
    And I should see "Assign competencies" in the ".tui-pageHeading__title" "css_element"

    And I should not see "Self assignment"
    And I should not see "Back to your competency profile"
    And I should not see "Self-assign competencies"

  Scenario: Authenticated user for competency assigning self is prohibited
    Given I log in as "user1"
    And I navigate to my competency profile
    Then "Self-assign competencies" "link" should exist
    And I log out

    And I log in as "admin"
    When I set the following system permissions of "Authenticated User" role:
    | totara/competency:assign_self | Prohibit |
    And I log out
    And I log in as "user1"
    And I navigate to my competency profile
    Then "Self-assign competencies" "link" should not exist