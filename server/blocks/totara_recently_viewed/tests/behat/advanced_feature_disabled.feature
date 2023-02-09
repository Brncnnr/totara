@engage @block @javascript @totara @block_totara_recently_viewed
Feature: Recently viewed block is hidden when the feature is disabled

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And I enable the "ml_recommender" advanced feature

  Scenario: Disabling the recommender plugin will hide the recently viewed block from view mode
    Given I log in as "admin"
    And I am on "Dashboard" page
    And I click on "Customise this page" "button"
    And I add the "Recently viewed" block
    And I click on "Stop customising this page" "button"

    Then I should see the "Recently viewed" block

    # Disable the plugin
    When I set the following administration settings values:
      | enableml_recommender | Disable |
    And I am on "Dashboard" page
    Then I should not see the "Recently viewed" block
