@totara @block @block_totara_recommendations @ml_recommender @engage @totara_engage @javascript
Feature: Test Trending Content Block

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user1     | user1    | user1@example.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name                | username | access | topics  |
      | Trending Playlist 1 | user1    | PUBLIC | Topic 1 |
      | Trending Playlist 2 | user1    | PUBLIC | Topic 1 |

    And the following "articles" exist in "engage_article" plugin:
      | name               | username | content | format       |
      | Trending Article 1 | user1    | Test    | FORMAT_PLAIN |
      | Trending Article 2 | user1    | Test    | FORMAT_PLAIN |

    And the following "surveys" exist in "engage_survey" plugin:
      | question          | username | access | topics  |
      | Trending Survey 1 | user1    | PUBLIC | Topic 1 |
      | Trending Survey 2 | user1    | PUBLIC | Topic 1 |

    And the following "trending recommendations" exist in "ml_recommender" plugin:
      | name                | component       | counter |
      | Trending Playlist 1 | totara_playlist | 1       |
      | Trending Article 1  | engage_article  | 5       |
      | Trending Survey 1   | engage_survey   | 10      |
      | Trending Playlist 2 | totara_playlist | 15      |
      | Trending Article 2  | engage_article  | 8       |
      | Trending Survey 2   | engage_survey   | 25      |


  Scenario: Visible items are initially controlled via admin settings
    Given I log in as "user1"
    And I am on "Dashboard" page
    And I press "Customise this page"
    And I add the "Recommended for you" block to the "main" region
    And I press "Stop customising this page"

    Then I should see the "Recommended for you" block
    And I should see "Trending Survey 2" in the "Recommended for you" "block"
    And I should see "Trending Playlist 2" in the "Recommended for you" "block"
    And I should see "Trending Survey 1" in the "Recommended for you" "block"
    And I should not see "Trending Article 2" in the "Recommended for you" "block"
    And I should not see "Trending Article 1" in the "Recommended for you" "block"
    And I should not see "Trending Playlist 1" in the "Recommended for you" "block"

    And I log out

    # Change the number to something bigger
    When I log in as "admin"
    And I navigate to "Plugins > Blocks > Recommended for you" in site administration
    And I set the field "Items to show" to "5"
    And I press "Save changes"
    And I log out
    And I log in as "user1"
    And I am on "Dashboard" page

    Then I should see "Trending Survey 2" in the "Recommended for you" "block"
    And I should see "Trending Playlist 2" in the "Recommended for you" "block"
    And I should see "Trending Survey 1" in the "Recommended for you" "block"
    And I should see "Trending Article 2" in the "Recommended for you" "block"
    And I should see "Trending Article 1" in the "Recommended for you" "block"
    And I should not see "Trending Playlist 1" in the "Recommended for you" "block"
