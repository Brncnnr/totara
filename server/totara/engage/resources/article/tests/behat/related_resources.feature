@totara @totara_engage @engage_article @engage @javascript @ml_recommender
Feature: Confirm related resources are displayed
  Background:
    Given I am on a totara site
    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content       | format       | access     | topics  |
      | Test Article 1 | user1    | Test Aticle 1 | FORMAT_PLAIN | PUBLIC     | Topic 1 |
      | Test Article 2 | user1    | Test Aticle 1 | FORMAT_PLAIN | PUBLIC     | Topic 1 |
    And the following "item recommendations" exist in "ml_recommender" plugin:
      | name            | target_name    | component       |
      | Test Article 1  | Test Article 2 | engage_article  |
    And I log in as "admin"

  Scenario: Testing the related resources
    Given I view article "Test Article 1"
    And I click on "Related" "link"
    Then I should see "Test Article 2"

    When I click on "Test Article 2" "text"
    Then I should see "Test Article 2" in the ".tui-engageArticleTitle__head" "css_element"
    #And I should not see "Related" in the ".tui_tabs" "css_element"

  Scenario: Turning off recommendations turns off related tab
    When I set the following administration settings values:
      | enableml_recommender | Disable |
    And I view article "Test Article 1"
    Then I should not see "related"