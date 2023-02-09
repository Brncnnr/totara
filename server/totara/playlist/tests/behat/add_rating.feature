@totara_playlist @totara @totara_engage @javascript @engage
Feature: Rate the playliat
  As a user
  I need to rate a playlist
  So that I can tell the owner how I think of the playlist

  Background:
    Given I am on a totara site

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User1      | One      | user1@test.com |
      | user2    | User2      | two      | user2@test.com |
      | user3    | User3      | three    | user3@test.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
      | Topic 2 |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access | topics           |
      | Test Playlist 1 | user1    | PUBLIC | Topic 1, Topic 2 |

    And "totara_playlist" "Test Playlist 1" is shared with the following users:
      | user1  | user2     |
    And "totara_playlist" "Test Playlist 1" is shared with the following users:
      | user1  | user3    |

  Scenario: Rate a playlist
    Given I log in as "user2"
    And I view playlist "Test Playlist 1"
    And I click on "Add your rating" "button"
    And I rate the playlist 0
    And I click on "Done" "button"
    And I log out
    Then I log in as "user3"
    And I view playlist "Test Playlist 1"
    And I click on "Add your rating" "button"
    And I rate the playlist 4
    And I click on "Done" "button"
    And I log out

  Scenario: Guest should not be able to rate a playlist
    Given I log in as "admin"
    And the following "permission overrides" exist:
      | capability                | permission | role  | contextlevel | reference |
      | totara/engage:viewlibrary | Allow      | guest | User         | guest     |
    And I set the following administration settings values:
      | Guest login button | Show |
    When I log out
    And I am on homepage
    And I click on "#guestlogin input[type=submit]" "css_element"
    And I view playlist "Test Playlist 1"
    Then "Add your rating" "button" should not exist