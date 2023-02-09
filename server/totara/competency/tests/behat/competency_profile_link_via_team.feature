@totara @perform @totara_competency
Feature: Competency profile link in the team members report

  Scenario: View my staff's competency profile as a manager via the team members page
    Given the following "users" exist:
      | username | firstname | lastname |
      | manager  | Manager   | User     |
      | staff    | Staff     | User     |
    And the following job assignments exist:
      | user  | idnumber | manager |
      | staff | 1        | manager |

    When I log in as "manager"
    And I am on "Team" page
    Then "Staff User" "link" should exist in the "team_members" "table"
    # The link should be visible and take you to the competency profile page for the staff member
    And "Competency profile" "link" should exist in the "team_members" "table"
    When I click on "Competency profile" "link" in the "team_members" "table"
    Then I should see "Staff User" in the ".breadcrumb-nav" "css_element"
    And I should see "Competency profile" in the ".breadcrumb-nav" "css_element"

    When I disable the "competency_assignment" advanced feature
    And I am on "Team" page
    Then "Staff User" "link" should exist in the "team_members" "table"
    # The feature is disabled
    And "Competency profile" "link" should not exist in the "team_members" "table"

    When I enable the "competency_assignment" advanced feature
    And I reload the page
    Then "Staff User" "link" should exist in the "team_members" "table"
    And "Competency profile" "link" should exist in the "team_members" "table"
    When the following "permission overrides" exist:
      | capability                           | permission | role         | contextlevel | reference |
      | totara/competency:view_other_profile | Prohibit   | staffmanager | System       |           |
    And I reload the page
    Then "Staff User" "link" should exist in the "team_members" "table"
    # The totara/competency:view_other_profile capability is prohibited so the link shouldn't appear
    And "Competency profile" "link" should not exist in the "team_members" "table"
