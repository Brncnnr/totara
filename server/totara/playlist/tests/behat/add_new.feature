@totara_playlist @totara @totara_engage @javascript @engage
Feature: Add new items to playlist
  As a user
  I want to create a new item and have it added to a playlist in the same process

  Background:
    Given I am on a totara site

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |
    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access  | topics |
      | Test Playlist 1 | user1    | PRIVATE | Topic1 |
    And the following "workspaces" exist in "container_workspace" plugin:
      | name             | summary   | owner | topics |
      | Test Workspace 1 | Workspace | user1 | Topic1 |
    And "totara_playlist" "Test Playlist 1" is shared with the following users:
      | sharer | recipient |
      | user1  | user2     |
      | user1  | user3     |

  Scenario: Playlist shares are displayed when adding a new item to a playlist
    When I log in as "user1"
    And I click on "Your Library" in the totara menu
    And I click on "Test Playlist 1" "link" in the ".tui-sidePanel__content" "css_element"
    And I click on "Contribute" "button" in the ".tui-addNewPlaylistCard__card" "css_element"
    And I set the field "Enter resource title" to "Article 1"
    And I activate the weka editor with css ".tui-engageArticleForm__description-formRow"
    And I type "Create article" in the weka editor
    And I click on "Next" "button"
    And I click on "Limited (only people and workspaces you share to)" "text" in the ".tui-accessSelector" "css_element"

    # SharedBoard should display playlist share summary.
    Then I should see "Shared with 2 people and 0 workspace(s)" in the ".tui-engageSharedBoardForm__label" "css_element"
    # When expanded it should display the actual shares.
    When I click on "Show" "button" in the ".tui-engageSharedBoardForm" "css_element"
    Then I should see "User Two"
    And I should see "User Three"

    # Also make sure the article is added.
    When I click on "Less than 5 mins" "text" in the ".tui-timeViewSelector" "css_element"
    And I click on "Done" "button"
    Then I should see "Article 1" in the ".tui-contributionBaseContent__cards" "css_element"