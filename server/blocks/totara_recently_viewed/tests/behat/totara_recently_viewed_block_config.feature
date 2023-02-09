@engage @block @javascript @totara @block_totara_recently_viewed
Feature: Test edit the block config for the recently viewed block
  In order to be able to make the block look good
  the user needs to be able to configure the block

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |
      | Topic2 |

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content | access | topics |
      | Test Article 1 | user1    | blah    | PUBLIC | Topic1 |
      | Test Article 2 | user1    | blah    | PUBLIC | Topic2 |
      | Test Article 3 | user1    | blah    | PUBLIC | Topic2 |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access | topics |
      | Test Playlist 1 | user1    | PUBLIC | Topic1 |
      | Test Playlist 2 | user1    | PUBLIC | Topic2 |
      | Test Playlist 3 | user1    | PUBLIC | Topic2 |

    And I log in as "user1"
    And I view playlist "Test Playlist 1"
    And I view article "Test Article 1"
    And I view playlist "Test Playlist 2"
    And I view article "Test Article 2"
    And I view playlist "Test Playlist 3"
    And I view article "Test Article 3"

    And I am on "Dashboard" page
    And I click on "Customise this page" "button"
    And I add the "Recently viewed" block

  Scenario: Test the correct number of cards shows based on the config
    Then I should see "Test Playlist 3" in the "Recently viewed" "block"
    And I should see "Test Article 3" in the "Recently viewed" "block"
    And I should not see "Test Article 1" in the "Recently viewed" "block"

  Scenario: Test the default view is the tile view
    Then ".block-trv-tiles" "css_element" should exist
    And ".block-trv-list" "css_element" should not exist

  Scenario: Test the list view
    When I configure the "Recently viewed" block
    And I set the following fields to these values:
      | Display type | List |
    And I press "Save changes"

    Then ".block-trv-tiles" "css_element" should not exist
    And ".block-trv-list" "css_element" should exist

  Scenario: Configure the number of visible items
    # 5 Items
    When I configure the "Recently viewed" block
    And I set the following fields to these values:
      | Display type    | List |
      | Number of items | 5    |
    And I press "Save changes"

    Then I should see "Test Article 3" in the "Recently viewed" "block"
    And I should see "Test Playlist 3" in the "Recently viewed" "block"
    And I should see "Test Article 2" in the "Recently viewed" "block"
    And I should see "Test Playlist 2" in the "Recently viewed" "block"
    And I should see "Test Article 1" in the "Recently viewed" "block"
    And I should not see "Test Playlist 1" in the "Recently viewed" "block"

    # 1 Item
    When I configure the "Recently viewed" block
    And I set the following fields to these values:
      | Display type    | List |
      | Number of items | 1    |
    And I press "Save changes"

    Then I should see "Test Article 3"
    And I should not see "Test Playlist 3" in the "Recently viewed" "block"
    And I should not see "Test Article 2" in the "Recently viewed" "block"
    And I should not see "Test Playlist 2" in the "Recently viewed" "block"
    And I should not see "Test Article 1" in the "Recently viewed" "block"
    And I should not see "Test Playlist 1" in the "Recently viewed" "block"

  Scenario: Configure the ratings to hide/show
    When I configure the "Recently viewed" block
    And I set the following fields to these values:
      | Display type                   | List |
      | Number of items                | 3    |
      | Show likes/rating and comments | No   |
    And I press "Save changes"

    Then ".block-trv-likes" "css_element" should not exist

    And I configure the "Recently viewed" block
    And I set the following fields to these values:
      | Display type                   | List |
      | Number of items                | 3    |
      | Show likes/rating and comments | Yes  |
    And I press "Save changes"

    Then ".block-trv-likes" "css_element" should exist
