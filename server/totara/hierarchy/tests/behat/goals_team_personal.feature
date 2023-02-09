@totara @totara_hierarchy @totara_hierarchy_goals @javascript
Feature: Verify own and team goals pages can be accessed

  # job1:
  # user3 manages user1 manages user2
  # job2:
  # user2 manages user1
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
      | user1 | user2   | job2     |          |             |                       |
      | user2 | user1   | job3     | true     |             |                       |
      | user2 | user1   | job3     |          | user4       | 2228554800            |

  Scenario: Verify that own goals page can be accessed for a user who has a manager and is a manager in the same job assignment

    Given I log in as "user1"
    And I am on "Goals" page
    Then I should see "Personal goals"

  Scenario: Verify that own goals page can be accessed for a user is just a manager in one job assignment

    Given I log in as "user3"
    And I am on "Goals" page
    Then I should see "Personal goals"

  Scenario: Verify that own goals page can be accessed for a user is a manager in one job assignment and team member in another

    Given I log in as "user2"
    And I am on "Goals" page
    Then I should see "Personal goals"

  Scenario: Verify that team members goals page can be accessed for a user who has a manager and is a manager in the same job assignment

    Given I log in as "user1"
    And I am on "Team" page
    Then I should see "User2 User2"
    When I click on "Goals" "link" in the "User2 User2" "table_row"
    Then I should see "User2 User2's Goals"

  Scenario: Verify that team members goals page can be accessed for a user who is a manager in the one job assignment

    Given I log in as "user3"
    And I am on "Team" page
    Then I should see "User1 User1"
    When I click on "Goals" "link" in the "User1 User1" "table_row"
    Then I should see "User1 User1's Goals"

  Scenario: Verify that team members goals page can be accessed for a user who is a manager in one job assignment and team member in another

    Given I log in as "user2"
    And I am on "Team" page
    Then I should see "User1 User1"
    When I click on "Goals" "link" in the "User1 User1" "table_row"
    Then I should see "User1 User1's Goals"

  Scenario: Verify that team members goals page can be accessed for a user who is a temporary manager

    Given I log in as "user4"
    And I am on "Team" page
    Then I should see "User2 User2"
    When I click on "Goals" "link" in the "User2 User2" "table_row"
    Then I should see "User2 User2's Goals"
