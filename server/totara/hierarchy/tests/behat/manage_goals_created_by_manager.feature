@totara @totara_hierarchy @totara_hierarchy_goals @javascript
Feature: Manage goals created by managers
  In order to manage goals
  As an manager
  I need to create goals for my team and check I or the user that I created the goal for can modify those goals
  if provided with the right capability to do so.

  # Test:
  # user3 manages user1 and user2
  # user4 is a temp manager of user2
  # user1 has the 'managemanagerassignedgoal' capability
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User1     | User1    | user1@example.com |
      | user2    | User2     | User2    | user2@example.com |
      | user3    | User3     | User3    | user3@example.com |
      | user4    | User4     | User4    | user4@example.com |
    And the following job assignments exist:
      | user  | manager | idnumber | usefirst | tempmanager | tempmanagerexpirydate |
      | user1 | user3   | job1     |          |             |                       |
      | user2 | user3   | job2     |          |             |                       |
      | user2 | user1   | job3     |          | user4       | 2228554800            |
    And I log in as "admin"
    And the following "permission overrides" exist:
      | capability                                 | permission | role | contextlevel | reference |
      | totara/hierarchy:managemanagerassignedgoal | Allow      | user | User         | user1     |
    And I log out
    When I log in as "user3"
    And I am on "Team" page
    Then I should see "User1 User1"
    And I should see "User2 User2"
    When I click on "Goals" "link" in the "User1 User1" "table_row"
    Then I should see "User1 User1's Goals"
    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name                | Think of more goals |
      | Scale               | Goal scale          |
      | targetdate[enabled] | 1                   |
      | targetdate[year]    | ## +2 years ## Y ## |
      | targetdate[month]   | August              |
      | targetdate[day]     | 15                  |
    And I press "Save changes"
    And I am on "Team" page
    And I should see "User2 User2"
    When I click on "Goals" "link" in the "User2 User2" "table_row"
    Then I should see "User2 User2's Goals"
    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name                | Think of more goals2 |
      | Scale               | Goal scale           |
      | targetdate[enabled] | 1                    |
      | targetdate[year]    | ## +2 years ## Y ##  |
      | targetdate[month]   | August               |
      | targetdate[day]     | 15                   |
    And I press "Save changes"
    And I log out

  Scenario: Check staff members can manage goals assigned by their manager if they have the right capability
    Given I log in as "user1"
    And I am on "Goals" page
    And I should see "Think of more goals"
    And I should see "Edit" in the "Think of more goals" "table_row"
    And I should see "Delete" in the "Think of more goals" "table_row"

    # Edit the goal assigned by their manager.
    When I click on "Edit" "link" in the "Think of more goals" "table_row"
    And I set the following fields to these values:
      | Name        | Think of more goals Edited |
      | Description | Description Edited         |
    And I press "Save changes"
    And I wait "1" seconds
    And I should see "Think of more goals Edited"

    # Delete the goal assigned by their manager.
    When I click on "Delete" "link" in the "Think of more goals Edited" "table_row"
    Then I should see "Are you sure you want to delete"
    And I press "Continue"
    And I wait "1" seconds
    And I should see "Successfully deleted personal goal Think of more goals Edited"
    And I log out

  Scenario: Check staff members can not manage goals assigned by their manager if they do not have the right capability
    Given I log in as "user2"
    And I am on "Goals" page
    And I should see "Think of more goals2"
    And I should not see "Edit" in the "Think of more goals" "table_row"
    And I should not see "Delete" in the "Think of more goals" "table_row"

  Scenario: Check managers can still manage their staff goals even if the staff changes the goal
    Given I log in as "user1"
    And I am on "Goals" page
    And I should see "Think of more goals"
    And I should see "Edit" in the "Think of more goals" "table_row"
    And I should see "Delete" in the "Think of more goals" "table_row"

    # Edit the goal assigned by their manager.
    When I click on "Edit" "link" in the "Think of more goals" "table_row"
    And I set the following fields to these values:
      | Name        | Think of more goals Edited |
      | Description | Description Edited         |
    And I press "Save changes"
    And I wait "1" seconds
    And I should see "Think of more goals Edited"
    And I log out

    When I log in as "user3"
    And I am on "Team" page
    Then I should see "User1 User1"
    When I click on "Goals" "link" in the "User1 User1" "table_row"
    Then I should see "User1 User1's Goals"

    And I set the field with xpath "//a[text()='Think of more goals Edited']/ancestor::tr//select[@name='scalevalueid']" to "Goal in progress"

  Scenario: Check temp managers can manage staff goals
    Given I log in as "user4"
    And I am on "Team" page
    Then I should see "User2 User2"
    When I click on "Goals" "link" in the "User2 User2" "table_row"
    Then I should see "User2 User2's Goals"
    And I should see "Think of more goals2"

    # Edit the goal assigned by their manager.
    When I click on "Edit" "link" in the "Think of more goals" "table_row"
    And I set the following fields to these values:
      | Name        | Think of more goals2 Edited |
      | Description | Description2  Edited         |
    And I press "Save changes"
    And I wait "1" seconds
    And I should see "Think of more goals2 Edited"

    # Delete the goal assigned by their manager.
    When I click on "Delete" "link" in the "Think of more goals2 Edited" "table_row"
    Then I should see "Are you sure you want to delete"
    And I press "Continue"
    And I wait "1" seconds
    And I should see "Successfully deleted personal goal Think of more goals2 Edited"
    And I log out
