@javascript @totara_engage @totara_playlist @totara @engage
Feature: Guest should not be able to share playlist
  As a guest
  I should not be able to share a playlist

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

  Scenario: Guest should not be able to share
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
    Then ".tui-shareSetting" "css_element" should not exist in the ".tui-mediaSetting" "css_element"