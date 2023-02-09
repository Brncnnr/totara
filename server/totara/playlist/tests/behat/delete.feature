@totara_playlist @totara @totara_engage @javascript @engage
Feature: Delete playlist
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access | topics  |
      | Test Playlist 1 | user1    | PUBLIC | Topic1  |

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content | access  | topics |
      | Test Article 1 | user1    | blah    | PUBLIC  | Topic1 |

  Scenario: Delete the empty playlist
    Given I log in as "user1"
    And I click on "Your Library" in the totara menu
    And I click on "Test Playlist 1" "link" in the ".tui-sidePanel__content" "css_element"
    And I click on ".tui-sidePanel__outsideClose" "css_element"
    And I click on "Actions" "button"
    And I should see "Delete"
    And I click on "Delete" "link"
    And I confirm the tui confirmation modal
    And I should see "Test Article 1"

  Scenario: Add resources to playlist and check resource usage
    Given I log in as "user1"
    And I click on "Your Library" in the totara menu
    And I should see "Test Article 1"
    And "[aria-label='Appears in 0 playlist(s)']" "css_element" should exist in the ".tui-engageArticleCard" "css_element"
    And I click on "Test Playlist 1" "link" in the ".tui-sidePanel__content" "css_element"
    And I click on "Contribute" "button" in the ".tui-addNewPlaylistCard__card" "css_element"
    And I click on "select an existing resource" "button"
    And I click the select all checkbox in the tui datatable
    And I confirm the tui confirmation modal
    And I should see "Test Article 1"
    And "[aria-label='Appears in 1 playlist(s)']" "css_element" should exist in the ".tui-engageArticleCard" "css_element"
    And I click on "Side panel" "button"
    And I click on "Actions" "button"
    And I should see "Delete"
    And I click on "Delete" "link"
    And I confirm the tui confirmation modal
    And I should see "Test Article 1"
    And "[aria-label='Appears in 0 playlist(s)']" "css_element" should exist in the ".tui-engageArticleCard" "css_element"