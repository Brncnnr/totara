@totara @engage @container @container_workspace
Feature: User search discussions
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | user_one | User      | One      | one@example.com |
      | user_two | User      | Two      | two@example.com |
    And the following "workspaces" exist in "container_workspace" plugin:
      | name           | summary         | owner    |
      | Workspace 1010 | This is summary | user_one |

  @javascript
  Scenario: Search for the discussion with case insensitive
    Given I am on a totara site
    And I log in as "user_two"
    And I click on "Find Workspaces" in the totara menu
    And I follow "Workspace 101"
    And I click on "Join workspace" "button"
    # This is pretty bad - but on a slow machine such as Macbook pro - we will have to wait for javascript
    # to finish in order to execute the next step.
    And I wait for the next second
    And I activate the weka editor with css ".tui-workspaceDiscussionForm__editor"
    And I type "This is the discussion 1" in the weka editor
    And I wait for the next second
    And I click on "Post" "button"
    And I wait for the next second
    And I type "The second discussion" in the weka editor
    And I wait for the next second
    And I click on "Post" "button"
    And I should see "This is the discussion 1"
    And I should see "The second discussion"
    When I set the field "Search discussions" to "SECOND"
    And I click on "Search discussions" "button"
    Then I should not see "This is the discussion 1"
    And I should see "The second discussion"

  @javascript
  Scenario: Search for the discussion via comment
    Given I am on a totara site
    And I log in as "user_two"
    And I click on "Find Workspaces" in the totara menu
    And I follow "Workspace 101"
    And I click on "Join workspace" "button"
    # This is pretty bad - but on a slow machine such as Macbook pro - we will have to wait for javascript
    # to finish in order to execute the next step.
    And I wait for the next second
    And I activate the weka editor with css ".tui-workspaceDiscussionForm__editor"
    And I type "This is the discussion 1" in the weka editor
    And I wait for the next second
    And I click on "Post" "button"
    And I wait for the next second
    And I type "The second discussion" in the weka editor
    And I wait for the next second
    And I click on "Post" "button"
    And I click on "Comment" "button"
    And I activate the weka editor with css ".tui-commentForm__editor"
    And I type "Discussion one comment" in the weka editor
    When I click on "Comment" "button" in the ".tui-commentForm__form" "css_element"
    And I wait for the next second
    Then I should not see "Discussion one comment" in the weka editor
    And I should see "Discussion one comment"
    And ".tui-commentReplyForm__editor" "css_element" should not exist
    And I click on "Reply" "button"
    And ".tui-commentReplyForm__editor" "css_element" should exist
    And I activate the weka editor with css ".tui-commentReplyForm__editor"
    And I type " Discussion one reply" in the weka editor
    When I click on "Reply" "button" in the ".tui-commentReplyForm__form" "css_element"
    Then I should see "Discussion one reply"
    And I should see "This is the discussion 1"
    And I should see "The second discussion"
    When I set the field "Search discussions" to "COMMENT"
    And I click on "Search discussions" "button"
    Then I should not see "This is the discussion 1"
    And I should not see "The second discussion"
    And I should see "Discussion one comment"
    When I set the field "Search discussions" to "reply"
    And I click on "Search discussions" "button"
    Then I should not see "This is the discussion 1"
    And I should not see "The second discussion"
    And I should see "Discussion one reply"

  @javascript
  Scenario: Search for the discussion with no result
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I activate the weka editor with css ".tui-workspaceDiscussionForm__editor"
    And I type "The first discussion" in the weka editor
    And I wait for the next second
    When I click on "Post" "button"
    Then I should see "The first discussion"
    And I wait for the next second
    And I type "The second discussion" in the weka editor
    And I wait for the next second
    When I click on "Post" "button"
    Then I should see "The second discussion"
    When I set the field "Search discussions" to "test"
    And I click on "Search discussions" "button"
    Then I should see "No results found." in the ".tui-workspaceDiscussionTab__message" "css_element"
    When I click on "Clear this search term" "button"
    Then I should see "The first discussion"
    And I should see "The second discussion"