@totara_playlist @totara @totara_engage @engage @javascript
Feature: Increase visibility of playlist
  As a user
  I want to increase the visibility of the playlist

  Background:
    Given I am on a totara site

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
      | Topic 2 |

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content | access  | topics  |
      | Test Article 1 | user1    | blah    | PRIVATE | Topic 1 |
    And I wait for the next second

    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access  | topics  |
      | Test Playlist 1 | user1    | PRIVATE | Topic 1 |
      | Test Playlist 2 | user1    | PUBLIC  | Topic 1 |

  # Empty playlist should not give warning message.
  Scenario: Test updating the empty playlist to public
    Given I log in as "user1"
    And I view playlist "Test Playlist 1"

    When I click on "Edit settings" "button"
    Then I should see "Settings" in the tui modal

    When I click on "Public (anyone can see and share this content)" "text" in the ".tui-accessSelector" "css_element"
    And I click on "Tag list" "button" in the ".tui-topicsSelector" "css_element"
    And I click on "Topic 2" option in the dropdown menu
    Then the "Done" "button" should be enabled

    When I click on "Done" "button"
    Then ".tui-modalContent__header-title" "css_element" should not exist
    And I should see "Everyone can view" in the ".tui-engageAccessDisplay__accessIcon" "css_element"

  # A playlist with at a private resource should give warning message.
  Scenario: Test updating the playlist that is not empty to public
    Given I log in as "user1"
    And I view playlist "Test Playlist 1"

    When I click on "Contribute" "button" in the ".tui-addNewPlaylistCard__card" "css_element"
    And I set the field "Enter resource title" to "Article 1"
    And I activate the weka editor with css ".tui-engageArticleForm__description-formRow"
    And I type "Create article" in the weka editor
    And I wait for the next second
    And I click on "Next" "button"
    And I wait for the next second
    And I click on "Hidden (only you)" "text" in the ".tui-accessSelector" "css_element"
    And I click on "Done" "button"
    Then I should see "Article 1" in the ".tui-contributionBaseContent__cards" "css_element"

    When I click on "Edit settings" "button"
    Then I should see "Settings" in the tui modal

    When I click on "Public (anyone can see and share this content)" "text" in the ".tui-accessSelector" "css_element"
    Then the "Done" "button" should be enabled

    When I click on "Done" "button"

    Then I should see "Change playlist visibility" in the tui modal

    When I confirm the tui confirmation modal
    Then I should see "Everyone can view" in the ".tui-engageAccessDisplay__accessIcon" "css_element"
    And ".tui-engageIconPublic" "css_element" should exist in the ".tui-engageArticleCard__footer" "css_element"

  # We have one resource that is private so adding it should give a warning message.
  Scenario: Test adding a private resource to the public
    Given I log in as "user1"
    And I view playlist "Test Playlist 2"

    When I click on "Contribute" "button" in the ".tui-addNewPlaylistCard__card" "css_element"
    And I click on "select an existing resource" "button"
    And I click the select all checkbox in the tui datatable
    And I confirm the tui confirmation modal
    And I wait for the next second
    Then I should see "Add to playlist" in the tui modal
    When I confirm the tui confirmation modal
    Then ".tui-engageIconPublic" "css_element" should exist in the ".tui-engageArticleCard__footer" "css_element"

  # Cancelling the warning message should not update the resources or the playlist.
  Scenario: Test cancelling the warning when changing the non-empty playlist to public
    Given I log in as "user1"
    And I view playlist "Test Playlist 1"

    When I click on "Contribute" "button" in the ".tui-addNewPlaylistCard__card" "css_element"
    And I set the field "Enter resource title" to "Article 1"
    And I activate the weka editor with css ".tui-engageArticleForm__description-formRow"
    And I type "Create article" in the weka editor
    And I wait for the next second
    And I click on "Next" "button"
    And I wait for the next second
    And I click on "Hidden (only you)" "text" in the ".tui-accessSelector" "css_element"
    And I click on "Done" "button"
    Then I should see "Article 1" in the ".tui-contributionBaseContent__cards" "css_element"

    When I click on "Edit settings" "button"
    Then I should see "Settings" in the tui modal

    When I click on "Public (anyone can see and share this content)" "text" in the ".tui-accessSelector" "css_element"
    Then the "Done" "button" should be enabled

    When I click on "Done" "button"
    Then I should see "Change playlist visibility" in the tui modal

    When I click on "Cancel" "button"
    Then I should see "Only you can view" in the ".tui-engageAccessDisplay__accessIcon" "css_element"
    And ".tui-engageIconPrivate" "css_element" should exist in the ".tui-engageArticleCard__footer" "css_element"