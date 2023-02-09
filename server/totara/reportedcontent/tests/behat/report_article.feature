@totara @totara_engage @engage @totara_reportedcontent @javascript
Feature: Report & remove engage articles

  Scenario: A user can report other comments but not their own in resources
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User1     | One      | user1@example.com |
      | user2    | User2     | Two      | user2@example.com |
    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
    And the following "articles" exist in "engage_article" plugin:
      | name      | username | content      | format       | access | topics  |
      | Article 1 | user1    | Test Article | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Article 2 | user2    | Test Article | FORMAT_PLAIN | PUBLIC | Topic 1 |
    And I log in as "user2"

    # Not reportable
    When I view article "Article 2"
    And I press "Actions"
    Then I should not see "Report"
    And I should see "Delete"

    # Reportable
    When I view article "Article 1"
    And I press "Actions"
    Then I should see "Report content"
    And I should not see "Delete"

    When I click on "Report content" "link"
    And I wait for the next second
    Then I should see "Content has been reported"