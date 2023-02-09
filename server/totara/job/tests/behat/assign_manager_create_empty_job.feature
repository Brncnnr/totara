@totara @totara_job @javascript
Feature: Assigning a manager to a user via the job assignment page with multiple job assignments disabled
  In order to assign a manager to a user
  As a user with correct permissions
  I should not be able to create an empty job assignment for a manager when Enable multiple job assignments is disabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
     | username | firstname | lastname | email                   |
     | user1    | User      | One      | user1@example.com       |
     | user2    | User      | Two      | user2@example.com       |
     | manager1 | Manager   | One      | manager1@example.com    |
     | manager2 | Manager   | Two      | manager2@example.com    |
    And I log in as "admin"

  Scenario: Disabling multiple job assignments removes the create empty job assignment option in the totara dialog
    And I navigate to "Shared services settings" node in "Site administration > System information > Configure features"
    And I set the field "Enable multiple job assignments" to "0"
    And I press "Save changes"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "User One" "link" in the "User One" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Developer |
      | ID Number | 1         |
    And I press "Choose manager"
    Then I should see "Manager One (manager1@example.com) - create empty job assignment" in the "Choose manager" "totaradialogue"
    And I should see "Manager Two (manager2@example.com) - create empty job assignment" in the "Choose manager" "totaradialogue"
    When I click on "Manager One (manager1@example.com) - create empty job assignment" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I click on "Add job assignment" "button"
    Then I should see "Job assignment saved"
    When I navigate to "Manage users" node in "Site administration > Users"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Developer |
      | ID Number | 1         |
    And I press "Choose manager"
    Then I should not see "Manager One (manager1@example.com) - create empty job assignment" in the "Choose manager" "totaradialogue"
    And I should see "Manager One (manager1@example.com)" in the "Choose manager" "totaradialogue"
    And I should see "Manager Two (manager2@example.com) - create empty job assignment" in the "Choose manager" "totaradialogue"
