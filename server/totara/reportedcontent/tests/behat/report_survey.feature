@totara @totara_engage @engage @totara_reportedcontent @javascript
Feature: Report & remove comments in engage surveys

  Scenario: A user can report other comments but not their own in resources
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User1     | One      | user1@example.com |
      | user2    | User2     | Two      | user2@example.com |
    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | access | topics  |
      | Test Survey 1? | user1    | PUBLIC | Topic 1 |
      | Test Survey 2? | user2    | PUBLIC | Topic 1 |
    And "engage_survey" "Test Survey 1?" is shared with the following users:
      | sharer | recipient |
      | user1  | user2     |
    And I log in as "user2"

    # Not reportable
    When I click on "Your Library" in the totara menu
    And I click on "Edit survey" "link"
    And I press "Actions"
    Then I should not see "Report"
    And I should see "Delete"

    # Reportable
    When I click on "Your Library" in the totara menu
    And I click on "Shared with you" "link"
    And I click on "Vote" "link"
    And I press "Actions"
    Then I should see "Report content"
    And I should not see "Delete"

    When I click on "Report content" "link"
    And I wait for the next second
    Then I should see "Content has been reported"