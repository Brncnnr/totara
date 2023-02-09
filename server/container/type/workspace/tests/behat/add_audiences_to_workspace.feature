@totara @container @container_workspace @engage @javascript
Feature: Add audiences to a workspace
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname | lastname | email             |
      | user_one   | User      | One      | one@example.com   |
      | user_two   | User      | Two      | two@example.com   |
      | user_three | User      | Three    | three@example.com |
      | user_four  | User      | Four     | four@example.com  |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | user_two | manager | System       |           |
    And the following "cohorts" exist:
      | name      | idnumber | contextlevel | reference |
      | Audience1 | aud1     | System       |           |
      | Audience2 | aud2     | System       |           |
      | Audience3 | aud3     | System       |           |
    And the following "cohort members" exist:
      | user       | cohort |
      | user_one   | aud1   |
      | user_two   | aud1   |
      | user_four  | aud1   |
      | user_three | aud2   |
    And the following "workspaces" exist in "container_workspace" plugin:
      | name          | owner    | summary           |
      | Workspace 101 | user_one | Workspace summary |

  Scenario: Users can add audiences to workspaces
    # Log in as admin
    When I log in as "admin"
    When I access the "Workspace 101" workspace
    Then "Admin" "button" should exist
    When I click on "Admin" "button"
    And "Add audiences" "link_exact" should exist
    When I log out

    # Log in as the owner
    And I log in as "user_one"
    And I access the "Workspace 101" workspace
    Then "Owner" "button" should exist
    When I click on "Owner" "button"
    Then "Add audiences" "link_exact" should not exist
    When I log out

    # Log in as site manager and add audiences
    And  I log in as "user_two"
    When I access the "Workspace 101" workspace
    And I switch to "Audiences" tui tab
    Then I should see "No audiences added"
    And "Add audiences" "button" should exist
    Given "Admin" "button" should exist
    When I click on "Admin" "button"
    And I click on "Add audiences" "link_exact"
    And I should see "Select audiences"
    Then I should see the following unselected adder picker entries:
      | Audience name | Short name |
      | Audience1     | aud1       |
      | Audience2     | aud2       |
    And I toggle the adder picker entry with "Audience1" for "Audience name"
    And I toggle the adder picker entry with "Audience2" for "Audience name"
    And I save my selections and close the adder
    Then I should see "Audiences added. The members list will update shortly." in the tui success notification toast and close it
    When I click on "Admin" "button"
    And I click on "Add audiences" "link_exact"
    Then I should see the following disabled adder picker entries:
      | Audience name | Short name |
      | Audience1     | aud1       |
      | Audience2     | aud2       |
    And I click on "Cancel" "button"

    # Check audiences
    Then I should see the tui datatable in the ".tui-workspaceAudiencesTab" "css_element" contains:
      | Audience name | Audience ID | Members |
      | Audience1     | aud1        | 3       |
      | Audience2     | aud2        | 1       |

    # Remove audience
    When I click on "Remove \"Audience2\"" "button"
    Then I should see "Are you sure you want to remove \"Audience2\" from \"Workspace 101\"?"
    When I confirm the tui confirmation modal
    Then I should see the tui datatable in the ".tui-workspaceAudiencesTab" "css_element" contains:
      | Audience name | Audience ID | Members |
      | Audience1     | aud1        | 3       |

    # Check members
    When I run all adhoc tasks
    And I access the "Workspace 101" workspace
    And I switch to "Members" tui tab
    Then I should see "3 members"
    And I should see "User One"
    And I should see "User Two"
    And I should see "User Four"