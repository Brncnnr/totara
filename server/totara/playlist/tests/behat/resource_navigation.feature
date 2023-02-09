@totara_playlist @totara @totara_engage @javascript @engage
Feature: Users can navigate between resources/surveys inside a playlist.
  As a user
  I would like to switch between resources inside a playlist
  So I can navigate through a playlist from start to finish.

  Background:
    Given I am on a totara site
    And I enable the "engage_resources" advanced feature
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |
    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access | topics |
      | Test Playlist 1 | user1    | PUBLIC | Topic1 |
      | Test Playlist 2 | user2    | PUBLIC | Topic1 |
    And the following "articles" exist in "engage_article" plugin:
      | name      | username | content | access | topics |
      | Article 1 | user1    | A1      | PUBLIC | Topic1 |
      | Article 2 | user1    | A2      | PUBLIC | Topic1 |
    And the following "surveys" exist in "engage_survey" plugin:
      | question | username | access  | topics |
      | Survey 3 | user1    | PRIVATE | Topic1 |
      | Survey 4 | user1    | PRIVATE | Topic1 |
    # Share the articles/surveys with the playlist
    And the following "playlist resources" exist in "totara_playlist" plugin:
      | component      | name      | playlist        | user  |
      | engage_article | Article 1 | Test Playlist 1 | user1 |
      | engage_article | Article 2 | Test Playlist 1 | user1 |
      | engage_survey  | Survey 3  | Test Playlist 1 | user1 |
      | engage_survey  | Survey 4  | Test Playlist 1 | user1 |

  Scenario: Users can navigate through resources on a playlist
    Given I log in as "user2"

    # Should see a back button for resources
    When I view playlist "Test Playlist 1"
    Then I should see "User One's library" in the ".tui-resourceNavigationBar__backLink" "css_element"

    # Should see the back button + the next/previous
    When I press "Article 1"
    Then I should see "A1"
    And I should see "Test Playlist 1" in the ".tui-resourceNavigationBar__backLink" "css_element"
    And I should see "1 of 4 resources"

    # Can navigate from the start to the end of the playlist items
    When I click on "Next resource in playlist" "link"
    Then I should see "A2"
    And I should see "Test Playlist 1" in the ".tui-resourceNavigationBar__backLink" "css_element"
    And I should see "2 of 4 resources"

    When I click on "Next resource in playlist" "link"
    Then I should see "Survey 3"
    And I should see "Test Playlist 1" in the ".tui-resourceNavigationBar__backLink" "css_element"
    And I should see "3 of 4 resources"

    When I click on "Next resource in playlist" "link"
    Then I should see "Survey 4"
    And I should see "Test Playlist 1" in the ".tui-resourceNavigationBar__backLink" "css_element"
    And I should see "4 of 4 resources"

    When I click on "Previous resource in playlist" "link"
    Then I should see "Survey 3"
    And I should see "Test Playlist 1" in the ".tui-resourceNavigationBar__backLink" "css_element"
    And I should see "3 of 4 resources"

  Scenario: Playlist shows the correct back button when opening from a workspace.
    Given the following "workspaces" exist in "container_workspace" plugin:
      | name        | owner | summary       | topics |
      | Workspace 1 | user2 | The Workspace | Topic1 |
    And the following is shared with workspaces:
      | component       | name            | sharer | workspace_name |
      | totara_playlist | Test Playlist 1 | user2  | Workspace 1    |
    And I log in as "user2"

    # Open the workspace
    And I click on "Your Workspaces" in the totara menu
    And I click on "Workspace 1" "link" in the ".tui-workspaceMenu" "css_element"
    And I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"
    Then I should see "Test Playlist 1"

    # Should see the workspace back button
    When I click on "Test Playlist 1" "link" in the ".tui-contributionBaseContent__cards" "css_element"
    Then I should see "Workspace 1" in the ".tui-resourceNavigationBar__backLink" "css_element"

    When I click on "Workspace 1" "link" in the ".tui-resourceNavigationBar" "css_element"
    Then I should see "Workspace 1" in the ".tui-workspacePageHeader__content" "css_element"

  Scenario: Playlist shows the correct back button when opening from the dashboard/home page.
    Given I log in as "admin"
    And I am on site homepage

    # Add Recently viewed block to the homepage
    And I click on "Turn editing on" "link"
    And I wait for the next second
    And I add the "Recently viewed" block if not present
    And I log out

    # Add recently viewed block to the dashboard
    And I log in as "user2"
    And I am on "Dashboard" page
    And I click on "Customise this page" "button"
    And I add the "Recently viewed" block if not present
    And I click on "Stop customising this page" "button"

    # View the playlist
    And I view playlist "Test Playlist 1"

    # Testing - if we click on the playlist from the home page, we should go back to it
    When I am on site homepage
    And I click on "Test Playlist 1" "link" in the "Recently viewed" "block"
    Then I should see "Test Playlist 1"
    And I should see "Back" in the ".tui-resourceNavigationBar__backLink" "css_element"

    # Testing - if we click on the playlist from the dashboard, we should go back to it
    When I am on "Dashboard" page
    And I click on "Test Playlist 1" "link" in the "Recently viewed" "block"
    Then I should see "Test Playlist 1"
    And I should see "Dashboard" in the ".tui-resourceNavigationBar__backLink" "css_element"