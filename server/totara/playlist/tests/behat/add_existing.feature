@totara_playlist @totara @totara_engage @javascript @engage
Feature: Add existing items to playlist
  As a user
  I want to choose existing resources to add into a playlist

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

    # Create articles with separate calls so that we can guarantee the created date will be different
    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content | access  | topics |
      | Test Article 1 | user1    | blah    | PRIVATE | Topic1 |
    And I wait for the next second

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content | access  | topics |
      | Test Article 2 | user2    | blah    | PRIVATE | Topic1 |
    And I wait for the next second

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content | access | topics |
      | Test Article 3 | user2    | blah    | PUBLIC | Topic1 |
    And I wait for the next second

    # Create surveys with separate calls so that we can guarantee the created date will be different
    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | access  | topics |
      | Test Survey 1? | user1    | PRIVATE | Topic1 |
    And I wait for the next second

    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | access  | topics |
      | Test Survey 2? | user2    | PRIVATE | Topic1 |
    And I wait for the next second

    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | access | topics |
      | Test Survey 3? | user2    | PUBLIC | Topic1 |
    And I wait for the next second

    # Create playlists with separate calls so that we can guarantee the created date will be different
    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access  | topics |
      | Test Playlist 1 | user1    | PRIVATE | Topic1 |

  Scenario: Test adding All library and All site filter of the adder into the playlist
    Given I log in as "user1"
    And I click on "Your Library" in the totara menu
    And I click on "Test Playlist 1" "link" in the ".tui-sidePanel__content" "css_element"
    And I click on "Contribute" "button" in the ".tui-addNewPlaylistCard__card" "css_element"
    And I click on "select an existing resource" "button"

    When I click the select all checkbox in the tui datatable
    And I confirm the tui confirmation modal
    And I wait for the next second

    Then I should see "Test Article 1" in the ".tui-playlistResourcesGrid__row" "css_element"
    And I should see "Test Survey 1?" in the ".tui-playlistResourcesGrid__row" "css_element"
    And I should not see "Test Article 2" in the ".tui-playlistResourcesGrid__row" "css_element"
    And I should not see "Test Article 3" in the ".tui-playlistResourcesGrid__row" "css_element"
    And I should not see "Test Survey 2?" in the ".tui-playlistResourcesGrid__row" "css_element"
    And I should not see "Test Survey 3?" in the ".tui-playlistResourcesGrid__row" "css_element"
    And I should not see "Test Playlist 2" in the ".tui-playlistResourcesGrid__row" "css_element"
    And I should not see "Test Playlist 3" in the ".tui-playlistResourcesGrid__row" "css_element"

  Scenario: Test adding resources to a playlist as admin
    Given I log in as "admin"
    And I view playlist "Test Playlist 1"
    And I click on "Contribute" "button" in the ".tui-addNewPlaylistCard__card" "css_element"
    Then I should see "select an existing resource"

    When I click on "select an existing resource" "button"
    And I wait for pending js
    And I set the field "filter_section" to "All site"
    And I click the select all checkbox in the tui datatable
    And I confirm the tui confirmation modal
    And I wait for the next second
    Then I should see "Test Article 3" in the ".tui-playlistResourcesGrid__row" "css_element"
    And I should see "Test Survey 3?" in the ".tui-playlistResourcesGrid__row" "css_element"
    And I should not see "Test Article 1" in the ".tui-playlistResourcesGrid__row" "css_element"
    And I should not see "Test Article 2" in the ".tui-playlistResourcesGrid__row" "css_element"
    And I should not see "Test Survey 1?" in the ".tui-playlistResourcesGrid__row" "css_element"
    And I should not see "Test Survey 2?" in the ".tui-playlistResourcesGrid__row" "css_element"

  Scenario: Check that playlist adder images appear with the correct preview mode
    Given I log in as "user1"
    And I view playlist "Test Playlist 1"
    And I click on "Contribute" "button" in the ".tui-addNewPlaylistCard__card" "css_element"
    Then I should see "select an existing resource"

    When I click on "select an existing resource" "button"
    And I wait for pending js
    And I set the field "filter_section" to "All site"
    And I set the field "Search" to "Test Article 1"
    Then ".tui-engageAdderBrowseTable img[src*='preview=totara_engage_adder_thumbnail']" "css_element" should exist

    When I set the field "Search" to "Test Survey 1?"
    Then ".tui-engageAdderBrowseTable .tui-engageSurveyIcon" "css_element" should exist

  Scenario: User can not add resources without share capability
    Given I log in as "admin"
    When I set the following system permissions of "Authenticated User" role:
      | engage/article:share   | Prohibit |
      | engage/survey:share    | Prohibit |
      | totara/playlist:share  | Prohibit |
    Then I log out
    And I log in as "user1"
    And I view playlist "Test Playlist 1"
    And ".tui-engageContribute" "css_element" should not exist

  Scenario: User can only view items with share capability
    Given I log in as "admin"
    And I set the following system permissions of "Authenticated User" role:
      | engage/survey:share        | Prohibit   |
    Then I log out

    And I log in as "user1"
    And I view playlist "Test Playlist 1"
    And I click on "Contribute" "button"
    And I click on "select an existing resource" "button"
    And I set the field "filter_section" to "All site"

    Then I should see "Test Article 1"
    And I should see "Test Article 3"
    And I should not see "Test Article 2"
    And I should not see "Test Survey 1?"
    And I should not see "Test Survey 3?"