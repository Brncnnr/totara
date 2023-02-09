@totara @engage @engage_article @javascript
Feature: Handling deleted users in article
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | user_one | User      | One      | one@example.com |
      | user_two | User      | Two      | two@example.com |
    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content       | format       | access | topics  |
      | Test Article 1 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |

  Scenario: Delete user should make the article unavailable
    Given I log in as "admin"
    And I navigate to "Bulk user actions" node in "Site administration > Users"
    And I set the field "Available" to "User One"
    And I press "Add to selection"
    And I set the field "id_action" to "Delete"
    And I press "Go"
    And I press "Delete"
    And I press "Continue"
    And I log out
    And I log in as "user_two"
    When I view article "Test Article 1"
    Then I should see "The resource is no longer available"

  Scenario: Suspend user should not make the article unavailable
    Given I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Manage login of User One" "link" in the "User One" "table_row"
    And I set the "Choose" Totara form field to "Suspend user account"
    And I press "Update"
    And I log out
    And I log in as "user_two"
    When I view article "Test Article 1"
    Then I should not see "The resource is no longer available"
    And I should see "Test Article 1"

  Scenario: Delete user should remove article from catalog
    Given I log in as "user_two"
    And I click on "Find Learning" in the totara menu
    And I should see "Test Article 1"
    And I log out
    And I log in as "admin"
    And I navigate to "Bulk user actions" node in "Site administration > Users"
    And I set the field "Available" to "User One"
    And I press "Add to selection"
    And I set the field "id_action" to "Delete"
    And I press "Go"
    And I press "Delete"
    And I press "Continue"
    And I log out
    And I log in as "user_two"
    When I click on "Find Learning" in the totara menu
    Then I should not see "Test Article 1"

  Scenario: Suspend user should not remove article from catalog
    Given I log in as "user_two"
    And I click on "Find Learning" in the totara menu
    And I should see "Test Article 1"
    And I log out
    And I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Manage login of User One" "link" in the "User One" "table_row"
    And I set the "Choose" Totara form field to "Suspend user account"
    And I press "Update"
    And I log out
    And I log in as "user_two"
    When I click on "Find Learning" in the totara menu
    Then I should see "Test Article 1"

  Scenario: Delete user should remove the shared articles.
    Given "engage_article" "Test Article 1" is shared with the following users:
      | sharer   | recipient |
      | user_one | user_two  |

    And I log in as "user_two"
    And I click on "Your Library" in the totara menu
    When I follow "Shared with you"
    Then I should see "Test Article 1"
    And I log out
    And I log in as "admin"
    And I navigate to "Bulk user actions" node in "Site administration > Users"
    And I set the field "Available" to "User One"
    And I press "Add to selection"
    And I set the field "id_action" to "Delete"
    And I press "Go"
    And I press "Delete"
    And I press "Continue"
    And I log out
    And I log in as "user_two"
    And I click on "Your Library" in the totara menu
    When I follow "Shared with you"
    Then I should not see "Test Article 1"
