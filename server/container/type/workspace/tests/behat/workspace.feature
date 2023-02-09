@totara @engage @container_workspace @container @javascript
Feature: General workspace workflow
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | user_one | User      | One      | one@example.com |

  Scenario: Authenticated user can not create workspace
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And ".tui-contributeWorkspace__button" "css_element" should exist
    When I click on "Create a workspace" "button"
    Then I should see "Create a workspace"
    And I log out

    And I log in as "admin"
    When I set the following system permissions of "Authenticated User" role:
      | container/workspace:createhidden  | Prohibit |
      | container/workspace:createprivate | Prohibit |
      | container/workspace:create        | Prohibit |
    Then I log out

    And I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And ".tui-contributeWorkspace__button" "css_element" should not exist

  Scenario: User can not access non existence workspace
    Given I log in as "user_one"
    When I access the workspace by id "100"
    Then I should see "The workspace cannot be found. It appears to be deleted."

  Scenario: Guest can access public workspace
    Given I log in as "admin"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Create a workspace" "button"
    And I set the field "Workspace name" to "workspace_a"
    And I click on "Submit" "button"
    And I set the following system permissions of "Guest" role:
      | capability                         | permission |
      | container/workspace:workspacesview | Allow      |
    And I set the following administration settings values:
      | guestloginbutton | Show |
    Then I log out
    And I log in as "guest"
    Then I should see "Collaborate" in the totara menu
    When I click on "Find Workspaces" in the totara menu
    Then I should see "workspace_a"
    When I follow "workspace_a"
    Then I should see "workspace_a"
    And I should not see "joined"

  Scenario: User only can link resource to workspace with valid capability
    Given I log in as "admin"
    And the following "workspaces" exist in "container_workspace" plugin:
      | name             | summary   | owner    |
      | Test Workspace 1 | Workspace | user_one |

    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access    | topics |
      | Test Playlist 1 | user_one | PUBLIC    | Topic1 |
      | Test Playlist 2 | user_one | PUBLIC    | Topic1 |

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content | access     | topics |
      | Test Article 1 | user_one | blah    | PUBLIC     | Topic1 |
      | Test Article 2 | user_one | blah    | PUBLIC    |  Topic1 |

    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | access    | topics |
      | Test Survey 1  | user_one | PUBLIC    | Topic1 |

    When I set the following system permissions of "Authenticated User" role:
      | engage/article:share    | Prohibit |
    Then I log out
    And I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Test Workspace 1" "link" in the ".tui-workspaceMenu" "css_element"
    And I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"
    And I click on "Contribute" "button"
    When I click on "select an existing resource" "button"
    Then I should see "Test Survey 1"
    And I should see "Test Playlist 1"
    And I should see "Test Playlist 2"
    And I should not see "Test Article 1"
    And I should not see "Test Article 2"

  Scenario: User can not link resource to workspace
    Given I log in as "admin"
    And the following "workspaces" exist in "container_workspace" plugin:
      | name             | summary   | owner    |
      | Test Workspace 1 | Workspace | user_one |
    When I set the following system permissions of "Authenticated User" role:
      | engage/article:share   | Prohibit |
      | engage/survey:share    | Prohibit |
      | totara/playlist:share  | Prohibit |
    Then I log out
    And I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Test Workspace 1" "link" in the ".tui-workspaceMenu" "css_element"
    And I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"
    And ".tui-engageContribute" "css_element" should not exist