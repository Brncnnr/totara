@javascript @totara_engage @engage_survey @totara @engage
Feature: Users can navigate back to the parent page from a survey.
  As a user
  I would like to return to the previous page that I opened the survey on.
  So I can view other content.

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And I enable the "engage_resources" advanced feature
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |
    And the following "surveys" exist in "engage_survey" plugin:
      | question | username | access | topics |
      | Survey 1 | user2    | PUBLIC | Topic1 |

  Scenario: Survey shows the correct back button when opening from a workspace.
    Given the following "workspaces" exist in "container_workspace" plugin:
      | name        | owner | summary       | topics |
      | Workspace 1 | user2 | The Workspace | Topic1 |
    And the following is shared with workspaces:
      | component     | name     | sharer | workspace_name |
      | engage_survey | Survey 1 | user2  | Workspace 1    |
    And I log in as "user2"

    # Open the workspace
    And I click on "Your Workspaces" in the totara menu
    And I click on "Workspace 1" "link" in the ".tui-workspaceMenu" "css_element"
    And I click on "Library" "link" in the ".tui-tabs__tabs" "css_element"
    Then I should see "Survey 1"

    # Should see the workspace back button
    When I click on "Edit survey" "link" in the ".tui-contributionBaseContent__cards" "css_element"
    Then I should see "Workspace 1" in the ".tui-resourceNavigationBar__backLink" "css_element"

    When I click on "Workspace 1" "link" in the ".tui-resourceNavigationBar" "css_element"
    Then I should see "Workspace 1" in the ".tui-workspacePageHeader__content" "css_element"

  Scenario: Survey shows the correct back button when opening from the dashboard/home page.
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

    # View the resource
    And I click on "Your Library" in the totara menu
    And I should see "Survey 1"
    And I click on "Edit survey" "link"

    # Testing - if we click on the survey from the home page, we should go back to it
    When I am on site homepage
    And I click on "Survey 1" "link" in the "Recently viewed" "block"
    And I should see "Back" in the ".tui-resourceNavigationBar__backLink" "css_element"

    # Testing - if we click on the survey from the dashboard, we should go back to it
    When I am on "Dashboard" page
    And I click on "Survey 1" "link" in the "Recently viewed" "block"
    And I should see "Dashboard" in the ".tui-resourceNavigationBar__backLink" "css_element"