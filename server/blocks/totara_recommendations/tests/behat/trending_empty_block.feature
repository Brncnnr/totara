@totara @block @block_totara_recommendations @ml_recommender @engage @totara_engage @javascript
Feature: Empty Trending Content Block should not be displayed

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And I enable the "ml_recommender" advanced feature

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user1     | user1    | user1@example.com |


  Scenario: Visible items are initially controlled via admin settings
    Given I log in as "user1"
    And I am on "Dashboard" page
    And I press "Customise this page"
    And I add the "Recommended for you" block to the "main" region
    When I press "Stop customising this page"
    Then I should not see the "Recommended for you" block
