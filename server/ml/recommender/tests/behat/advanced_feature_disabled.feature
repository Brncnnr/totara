@engage @totara @core_ml @ml_recommender @javascript
Feature: Recommender plugin is hidden/disabled when advanced feature is disabled

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And I enable the "ml_recommender" advanced feature

  Scenario: Disabling the recommender advanced feature will automatically disable the ml_recommender plugin
    Given I log in as "admin"

    # Check that the plugin shows as enabled
    When I navigate to "Manage machine learning plugins" node in "Site administration > Plugins > Machine learning settings"
    Then I should see "Recommendation engine"
    And "input[name=plugin][value=recommender]" "css_element" should exist

    # Disable the feature & check the plugin is disabled/hidden
#    When I set the following administration settings values:
#      | enableml_recommender | Disable |
#    And I navigate to "Manage machine learning plugins" node in "Site administration > Plugins > Machine learning settings"
#    Then I should see "Recommendation engine"
#    And "input[name=plugin][value=recommender]" "css_element" should not exist
