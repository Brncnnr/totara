@totara @totara_topic @engage @totara_engage
Feature: Managing engage topics across the site

  Background:
    Given I am on a totara site

  Scenario: As an admin I can create one or more topics
    Given I log in as "admin"
    And I navigate to "Topic > Manage topics" in site administration
    And I follow "Add topics"
    And I set the field "topics" to multiline:
      """
      topic 1
      topic 2
      topic 3
      """
    And I press "Add"
    Then I should see "topic 1"
    And I should see "topic 2"
    And I should see "topic 2"

  Scenario: As an admin I cannot create a duplicate topic
    Given the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
    And I log in as "admin"
    And I navigate to "Topic > Manage topics" in site administration
    And I follow "Add topics"
    And I set the field "topics" to "topic 1"
    And I press "Add"
    Then I should see "Some topics already exist: topic 1. Please remove duplicates before adding."

  Scenario: As an admin I can change the case of an existing topic
    Given the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
    And I log in as "admin"
    And I navigate to "Topic > Manage topics" in site administration
    And I follow "Edit"
    And I set the field "value" to "ToPiC 1"
    And I press "Save"
    Then I should see "ToPiC 1"