@totara @totara_topic @engage @javascript
Feature: Delete topics
  Background:
    Given the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
      | Topic 2 |
    And the following "articles" exist in "engage_article" plugin:
      | name      | username | format             | content | access | topics  |
      | Article 1 | admin    | FORMAT_JSON_EDITOR | blah    | PUBLIC | Topic 1 |

  Scenario: Deleting a topic that does not have any usage.
    Given I log in as "admin"
    And I navigate to "Topic > Manage topics" in site administration
    Then I should see "Topic 1"
    And I should see "Topic 2"
    When I click on "Delete topic Topic 2" "link"
    Then I should see "Topic 'Topic 2' has successfully been deleted"
    And I should see "Topic 1"

  Scenario: Deleting a topic that does have usage
    Given I log in as "admin"
    And I navigate to "Topic > Manage topics" in site administration
    Then I should see "Topic 1"
    And I should see "Topic 2"
    And I should not see "Are you sure you want to delete this topic?"
    And "Delete" "button" should not exist
    When I click on "Delete topic Topic 1" "link"
    Then I should see "Are you sure you want to delete this topic?"
    And "Delete" "button" should exist
    When I click on "Delete" "button"
    Then I should see "Topic 'Topic 1' has successfully been deleted"
    And I should see "Topic 2"