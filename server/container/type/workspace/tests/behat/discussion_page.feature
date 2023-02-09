@totara @totara_engage @container @container_workspace @engage @javascript
Feature: Single discussion page
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | user_one | User      | One      | one@example.com |
      | user_two | User      | Two      | two@example.com |
    And the following "workspaces" exist in "container_workspace" plugin:
      | name          | owner    | summary         |
      | Workspace 101 | user_one | This is summary |

  Scenario: Member can navigate to the discussion page and put comment on it
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I activate the weka editor with css ".tui-workspaceDiscussionForm__editor"
    And I type "Discussion 100" in the weka editor
    And I wait for the next second
    And I click on "Post" "button"
    And I log out
    And I log in as "user_two"
    And I click on "Find Workspaces" in the totara menu
    And I follow "Workspace 101"
    And I click on "Join workspace Workspace 101" "button"
    And I click on "Discussion's actions" "button"
    And I should see "View full discussion"
    And I should not see "Edit"
    And I should not see "Delete"
    When I follow "View full discussion"
    Then I should see "Comments (0)"
    And I activate the weka editor with css ".tui-commentForm__editor"
    And I type "this is reply 100" in the weka editor
    And I wait for the next second
    When I click on "Comment" "button" in the ".tui-commentForm__form" "css_element"
    Then I should see "Comments (1)"

  Scenario: Discussion's author can edit the discussion
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I activate the weka editor with css ".tui-workspaceDiscussionForm__editor"
    And I type "Discussion 100" in the weka editor
    And I wait for the next second
    And I click on "Post" "button"
    And I click on "Discussion's actions" "button"
    And I should see "View full discussion"
    And I should see "Edit"
    And I should see "Delete"
    And I click on "View full discussion" "link"
    And I click on "Discussion's actions" "button"
    And ".tui-workspaceEditPostDiscussionForm" "css_element" should not exist
    When I click on "Edit" "link" in the ".tui-workspaceDiscussionCard" "css_element"
    And ".tui-workspaceEditPostDiscussionForm" "css_element" should exist
    And I activate the weka editor with css ".tui-workspaceDiscussionForm__editor"
    And I should see "Discussion 100" in the weka editor
    And I set the weka editor to "Discussion 101"
    And I wait for the next second
    When I click on "Done" "button" in the ".tui-workspaceEditPostDiscussionForm" "css_element"
    Then ".tui-workspaceEditPostDiscussionForm" "css_element" should not exist
    And I should not see "Discussion 100"
    And I should see "Discussion 101"

  Scenario: User can not access non existence discussion
    Given I log in as "user_one"
    When I access the discussion by id "100"
    Then I should see "The discussion cannot be found. It appears to be deleted."