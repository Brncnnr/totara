@engage @block @javascript @totara @block_recommendations
Feature: Recommendations block is hidden when the feature is disabled

  Scenario: Disabling the recommender plugin will hide the recommended for you block from view mode
    Given I log in as "admin"

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user1     | user1    | user1@example.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content       | access     | topics |
      | Test Article 1 | user1    | Test Filters  | PUBLIC     | Topic1 |

    And the following "trending recommendations" exist in "ml_recommender" plugin:
      | name                | component       | counter |
      | Test Article 1      | engage_article  | 5       |

    And I am on "Dashboard" page
    And I press "Customise this page"
    And I add the "Recommended for you" block if not present
    And I click on "Stop customising this page" "button"
    Then I should see the "Recommended for you" block

    # Disable the plugin
    When I set the following administration settings values:
      | enableml_recommender | Disable |
    And I am on "Dashboard" page
    Then I should not see the "Recommended for you" block
