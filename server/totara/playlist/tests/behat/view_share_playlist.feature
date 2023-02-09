@totara @totara_playlist @engage @totara_engage @javascript
Feature: Viewing non owned playlist
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email           |
      | user_one | User      | One      | one@example.com |
      | user_two | User      | Two      | two@example.com |
    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | topic 1 |
    And the following "playlists" exist in "totara_playlist" plugin:
      | name       | username | access | topics  |
      | Playlist 1 | user_one | PUBLIC | topic 1 |

  Scenario: Other user should not be able to see text add description
    Given I log in as "user_one"
    When I view playlist "Playlist 1"
    Then I should see "Add a description (optional)"
    And I log out
    And I log in as "user_two"
    When I view playlist "Playlist 1"
    Then I should not see "Add a description (optional)"