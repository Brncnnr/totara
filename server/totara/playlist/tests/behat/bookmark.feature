@javascript @totara_engage @totara_playlist @totara @engage
Feature: Bookmark playlist
  As a user
  I need to bookmark a playlist
  So that I can easily navigate to it in the future

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
      | Topic 2 |

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User      | One      | user1@test.com |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access | topics           |
      | Test Playlist 1 | user1    | PUBLIC | Topic 1, Topic 2 |
      | Test Playlist 2 | user1    | PUBLIC | Topic 1, Topic 2 |

    And "totara_playlist" "Test Playlist 1" is shared with the following users:
      | sharer | recipient |
      | user1  | admin     |

  Scenario: Test bookmarking a public playlist
    Given I log in as "admin"
    And I view playlist "Test Playlist 2"
    And I click on "Bookmark" "button"
    And I click on "Your Library" in the totara menu
    Then I should see "Test Playlist 2" in the ".tui-playlistNavigation" "css_element"

  Scenario: Guest should not be able to bookmark
    Given I log in as "admin"
    And the following "permission overrides" exist:
      | capability                | permission | role  | contextlevel | reference |
      | totara/engage:viewlibrary | Allow      | guest | User         | guest     |
    And I set the following administration settings values:
      | Guest login button | Show |
    When I log out
    And I am on homepage
    And I click on "#guestlogin input[type=submit]" "css_element"
    And I view playlist "Test Playlist 2"
    Then "Bookmark" "button" should not exist in the ".tui-contributionBaseContent__header" "css_element"

  Scenario: Test bookmarking a shared playlist
    And I log in as "admin"
    And I click on "Your Library" in the totara menu
    Then I should not see "Test Playlist 1" in the ".tui-playlistNavigation" "css_element"

    When I click on "Shared with you" "link" in the ".tui-engageNavigationPanel__menu" "css_element"
    And I click on "Bookmark" "button" in the ".tui-playlistCard__header" "css_element"
    And I wait for the next second
    Then I should see "Test Playlist 1" in the ".tui-playlistNavigation" "css_element"

    When I click on "Unbookmark" "button" in the ".tui-playlistCard__header" "css_element"
    And I wait for the next second
    Then I should not see "Test Playlist 1" in the ".tui-playlistNavigation" "css_element"