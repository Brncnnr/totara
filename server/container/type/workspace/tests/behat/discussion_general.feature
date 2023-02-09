@totara @totara_engage @container @container_workspace @engage @javascript
Feature: General behat test for discussion feature within a workspace
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

  Scenario: None member user should not be able to see discussion form.
    Given I log in as "user_one"
    When I click on "Your Workspaces" in the totara menu
    Then I should see "Workspace 101"
    # Checking the existing of the discussion editor
    And ".tui-workspaceDiscussionForm" "css_element" should exist
    And I log out
    And I log in as "user_two"
    When I click on "Find Workspaces" in the totara menu
    And I should see "Workspace 101"
    When I follow "Workspace 101"
    Then ".tui-workspaceDiscussionForm" "css_element" should not exist
    When I click on "Join workspace Workspace 101" "button"
    Then ".tui-workspaceDiscussionForm" "css_element" should exist

  Scenario: Member can post the discussion
    Given I log in as "user_two"
    And I click on "Find Workspaces" in the totara menu
    And I follow "Workspace 101"
    And I click on "Join workspace Workspace 101" "button"
    And I activate the weka editor with css ".tui-workspaceDiscussionForm__editor"
    And I type "Some description with \"quotes\". Tag <example@example.com> and test icon tag: <i class=\"fab fa-accessible-icon\"></i> stuff" in the weka editor
    And I wait for the next second
    When I click on "Post" "button"
    And I wait for the next second
    Then I should not see "This is the discussion" in the weka editor
    And the "Post" "button" should be disabled

    # Seeing the discussion within the page, but not in the editor
    And I should see "Some description with \"quotes\". Tag <example@example.com> and test icon tag: <i class=\"fab fa-accessible-icon\"></i> stuff"

  Scenario: Member likes the discussion
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I activate the weka editor with css ".tui-workspaceDiscussionForm__editor"
    And I type "Discussion 100" in the weka editor
    And I wait for the next second
    When I click on "Post" "button"
    And the "Like discussion" "button" should be disabled
    And I log out
    And I log in as "user_two"
    And I click on "Find Workspaces" in the totara menu
    And I follow "Workspace 101"
    And the "Like discussion" "button" should be disabled
    When I click on "Join workspace Workspace 101" "button"
    Then the "Like discussion" "button" should be enabled
    And "Remove like for discussion" "button" should not exist
    When I click on "Like discussion" "button"
    Then "Remove like for discussion" "button" should exist
    And "Like discussion" "button" should not exist
    When I click on "Remove like for discussion" "button"
    And "Like discussion" "button" should exist
    And "Remove like for discussion" "button" should not exist

  Scenario: Member comment on the discussion
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I activate the weka editor with css ".tui-workspaceDiscussionForm__editor"
    And I type "Discussion 100" in the weka editor
    And I wait for the next second
    When I click on "Post" "button"
    And I log out
    And I log in as "user_two"
    And I click on "Find Workspaces" in the totara menu
    And I follow "Workspace 101"
    And I wait for the next second
    And the "Comment on discussion" "button" should be disabled
    And ".tui-commentForm__form" "css_element" should not exist
    When I click on "Join workspace Workspace 101" "button"
    Then the "Comment on discussion" "button" should be enabled
    When I click on "Comment on discussion" "button"
    Then ".tui-commentForm__form" "css_element" should exist
    And I activate the weka editor with css ".tui-commentForm__editor"
    And I type "Some comment with \"quotes\". Tag <example@example.com> and test icon tag: <i class=\"fab fa-accessible-icon\"></i> stuff" in the weka editor
    And I wait for the next second
    When I click on "Comment" "button" in the ".tui-commentForm__form" "css_element"
    Then I should not see "Some comment with \"quotes\". Tag <example@example.com> and test icon tag: <i class=\"fab fa-accessible-icon\"></i> stuff" in the weka editor
    And I should see "Some comment with \"quotes\". Tag <example@example.com> and test icon tag: <i class=\"fab fa-accessible-icon\"></i> stuff"

  Scenario: Discussion's author can edit the discussion in a discussion tab
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I activate the weka editor with css ".tui-workspaceDiscussionForm__editor"
    And I type "Discussion 100" in the weka editor
    And I wait for the next second
    And I click on "Post" "button"
    And I wait for the next second
    And I click on "Discussion's actions" "button"
    And I should see "View full discussion"
    And I should see "Edit"
    And I should see "Delete"
    And ".tui-workspaceEditPostDiscussionForm" "css_element" should not exist
    When I click on "Edit" "link" in the ".tui-workspaceDiscussionCard" "css_element"
    And ".tui-workspaceEditPostDiscussionForm" "css_element" should exist
    And I activate the weka editor with css ".tui-workspaceEditPostDiscussionForm .tui-workspaceDiscussionForm__editor"
    And I should see "Discussion 100" in the weka editor
    And I set the weka editor to "Some comment with \"quotes\". Tag <example@example.com> and test icon tag: <i class=\"fab fa-accessible-icon\"></i> stuff"
    And I wait for the next second
    When I click on "Done" "button" in the ".tui-workspaceEditPostDiscussionForm" "css_element"
    Then ".tui-workspaceEditPostDiscussionForm" "css_element" should not exist
    And I should not see "Discussion 100"
    And I should see "Some comment with \"quotes\". Tag <example@example.com> and test icon tag: <i class=\"fab fa-accessible-icon\"></i> stuff"

  Scenario: Owner deletes the discussion
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I activate the weka editor with css ".tui-workspaceDiscussionForm__editor"
    And I type "This is discussion 101" in the weka editor
    And I wait for pending js
    And I wait for the next second
    When I click on "Post" "button"
    And I wait for pending js
    And I wait for the next second
    Then I should see "This is discussion 101"
    And I click on "Discussion's actions" "button"
    When I click on "Delete" "link" in the ".tui-workspaceDiscussionCard" "css_element"
    Then I should see "Delete discussion"
    And I confirm the tui confirmation modal
    Then I should not see "This is discussion 101"