@container @workspace @container_workspace @totara @totara_engage @engage @javascript
Feature: Share existing items with workspace
  As a user
  I want to choose existing resources and playlists to share with a workspace

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

    And the following "workspaces" exist in "container_workspace" plugin:
      | name             | summary   | owner |
      | Test Workspace 1 | Workspace | user1 |

    # Create surveys with separate calls so that we can guarantee the created date will be different
    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content | access  | topics |
      | Test Article 1 | user1    | blah    | PRIVATE | Topic1 |
    And I wait for the next second

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content | access  | topics |
      | Test Article 2 | user2    | blah    | PRIVATE | Topic1 |
    And I wait for the next second

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content | access  | topics |
      | Test Article 3 | user2    | blah    | PUBLIC  | Topic1 |
    And I wait for the next second

    # Create surveys with separate calls so that we can guarantee the created date will be different
    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | access     | topics |
      | Test Survey 1? | user1    | PRIVATE    | Topic1 |
    And I wait for the next second

    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | access     | topics |
      | Test Survey 2? | user2    | PRIVATE    | Topic1 |
    And I wait for the next second

    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | access     | topics |
      | Test Survey 3? | user2    | PUBLIC     | Topic1 |
    And I wait for the next second

    # Create playlists with separate calls so that we can guarantee the created date will be different
    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access     | topics |
      | Test Playlist 1 | user1    | PRIVATE    | Topic1 |
    And I wait for the next second

    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access     | topics |
      | Test Playlist 2 | user2    | PRIVATE    | Topic1 |
    And I wait for the next second

    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access     | topics |
      | Test Playlist 3 | user2    | PUBLIC     | Topic1 |

  Scenario: Test All library and All site filter of the adder
    Given I log in as "user1"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Test Workspace 1" "link" in the ".tui-workspaceMenu" "css_element"
    And I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"
    And I click on "Contribute" "button"
    And I click on "select an existing resource" "button"

    # All library section should only show resources and playlists from your library
    When I select "Your Library" from the "filter_section" singleselect
    And I wait for the next second

    Then I should see "3" rows in the tui datatable
    And I should see the tui datatable contains:
      | Title           | Contributor |
      | Test Playlist 1 | User One    |
      | Test Survey 1?  | User One    |
      | Test Article 1  | User One    |

    # All site section should show all public resources and playlists in addition to your library
    When I select "All site" from the "filter_section" singleselect
    And I wait for the next second

    Then I should see "6" rows in the tui datatable
    And I should see the tui datatable contains:
      | Title           | Contributor |
      | Test Playlist 3 | User Two    |
      | Test Playlist 1 | User One    |
      | Test Survey 3?  | User Two    |
      | Test Survey 1?  | User One    |
      | Test Article 3  | User Two    |
      | Test Article 1  | User One    |

  Scenario: Test sharing existing content with workspace
    Given I log in as "user1"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Test Workspace 1" "link" in the ".tui-workspaceMenu" "css_element"
    And I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"
    And I click on "Contribute" "button"
    And I click on "select an existing resource" "button"

    When I click the select all checkbox in the tui datatable
    And I confirm the tui confirmation modal
    And I click on "Continue" "button"
    And I wait for the next second

    Then I should see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 3" in the ".tui-contributionBaseContent__cards" "css_element"

  Scenario: Check that sharing items appear with appropriate images
    Given I log in as "user1"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Test Workspace 1" "link" in the ".tui-workspaceMenu" "css_element"
    And I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"
    And I click on "Contribute" "button"
    And I click on "select an existing resource" "button"
    And I wait for pending js
    And I set the field "filter_section" to "All site"
    Then I should see "Select content to share with workspaces"

    When I set the field with css ".tui-adder input[type='search']" to "Test Article 1"
    Then ".tui-engageAdderBrowseTable img[src*='preview=totara_engage_adder_thumbnail']" "css_element" should exist

    When I set the field with css ".tui-adder input[type='search']" to "Test Survey 1?"
    Then ".tui-engageAdderBrowseTable .tui-engageSurveyIcon" "css_element" should exist

    When I set the field with css ".tui-adder input[type='search']" to "Test Playlist 1"
    Then ".tui-engageAdderBrowseTable img[src*='preview=totara_engage_adder_thumbnail']" "css_element" should exist

  Scenario: Test adding existing resource to workspace when user is not able to create any
    Given I log in as "admin"
    And I set the following system permissions of "Authenticated User" role:
      | engage/survey:create         | Prohibit   |
      | totara/playlist:create       | Prohibit   |
      | engage/article:create        | Prohibit   |
    Then I log out

    And I log in as "user1"
    Then I click on "Your Workspaces" in the totara menu
    And I click on "Test Workspace 1" "link" in the ".tui-workspaceMenu" "css_element"
    And I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"
    And I click on "Contribute" "button"
    And I click on "select an existing resource" "button"
    And I set the field "filter_section" to "All site"

    When I click the select all checkbox in the tui datatable
    And I confirm the tui confirmation modal
    And I click on "Continue" "button"

    Then I should see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Playlist 3" in the ".tui-contributionBaseContent__cards" "css_element"

  Scenario: User can only view items with share capability
    Given I log in as "admin"
    And I set the following system permissions of "Authenticated User" role:
      | engage/article:share       | Prohibit   |
      | engage/survey:share        | Prohibit   |
    Then I log out

    And I log in as "user1"
    Then I click on "Your Workspaces" in the totara menu
    And I click on "Test Workspace 1" "link" in the ".tui-workspaceMenu" "css_element"
    And I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"
    And I click on "Contribute" "button"
    And I click on "select an existing resource" "button"
    And I set the field "filter_section" to "All site"

    Then I should not see "Test Article 1"
    And I should not see "Test Article 2"
    And I should not see "Test Article 3"
    And I should not see "Test Survey 3?"
    And I should not see "Test Survey 1?"
    And I should see "Test Playlist 1"
    And I should see "Test Playlist 3"