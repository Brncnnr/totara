@totara @totara_engage @container @container_workspace @engage @javascript
Feature: Include, view and edit images in discussions, comments, and replies

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | user_one | User      | One      | one@example.com |
    And the following "workspaces" exist in "container_workspace" plugin:
      | name          | owner    | summary         | private |
      | Workspace 101 | user_one | This is summary | 0       |

  Scenario: Discussions, comments and replies with images
    Given I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I activate the weka editor with css ".tui-workspaceDiscussionForm__editor"
    And I type "Discussion 100" in the weka editor
    And I upload embedded media to the weka editor using the file "container/type/workspace/tests/fixtures/blue.png"
    And I move the cursor to the end of the weka editor
    And I wait for the next second
    And I click on "Post" "button"
    Then I should see "Discussion 100"
    And the "Comment on discussion" "button" should be enabled

    When I click on "Comment on discussion" "button"
    Then ".tui-commentForm__form" "css_element" should exist
    And I activate the weka editor with css ".tui-commentForm__editor"
    And I type "this is comment 100" in the weka editor
    And I upload embedded media to the weka editor using the file "container/type/workspace/tests/fixtures/image_test.png"
    And I wait for the next second
    And I click on "Comment" "button" in the ".tui-commentForm__form" "css_element"
    Then I should see "this is comment 100"
    And the "Reply" "button" should be enabled

    When I click on "Reply" "button"
    Then ".tui-commentReplyForm__editor" "css_element" should exist
    And I activate the weka editor with css ".tui-commentReplyForm__editor"
    And I type "comment 100 reply" in the weka editor
    And I upload embedded media to the weka editor using the file "container/type/workspace/tests/fixtures/green.png"
    And I wait for the next second
    When I click on "Reply" "button" in the ".tui-commentReplyForm__form" "css_element"
    Then I should see "comment 100 reply"

    When I click on "Menu trigger" "button" in the ".tui-commentReplyCard__body" "css_element"
    Then I should see "Edit" in the ".tui-commentReplyCard__body" "css_element"
    # Just checking that no errors are shown
    When I click on "Edit" "link" in the ".tui-commentReplyCard__body" "css_element"
    And I wait for the next second
    And I click on "Cancel" "button" in the ".tui-commentReplyContent__editForm" "css_element"
    Then I should see "comment 100 reply"

    When I log out
    And I log in as "admin"
    And I click on "Find Workspaces" in the totara menu
    And I follow "Workspace 101"
    Then I should see "Discussion 100"
    And I should see "this is comment 100"
    And I should see "View replies"

    # Now verify that all can be edited without errors
    When I press "Discussion's actions"
    Then I should see "Edit" in the ".tui-workspaceDiscussionCard__card .tui-dropdown" "css_element"
    When I click on "Edit" "link" in the ".tui-workspaceDiscussionCard__card" "css_element"
    And I wait for the next second
    And I click on "Cancel" "button" in the ".tui-workspaceEditPostDiscussionForm" "css_element"
    Then I should see "Discussion 100"

    When I click on "Menu trigger" "button" in the ".tui-commentCard__body" "css_element"
    Then I should see "Edit" in the ".tui-commentCard__body" "css_element"
    When I click on "Edit" "link" in the ".tui-commentCard__body" "css_element"
    And I wait for the next second
    And I click on "Cancel" "button" in the ".tui-commentReplyContent__editForm" "css_element"
    Then I should see "this is comment 100"

    When I follow "View replies"
    And I wait for the next second
    Then I should see "comment 100 reply"
    When I click on "Menu trigger" "button" in the ".tui-commentReplyCard__body" "css_element"
    Then I should see "Edit" in the ".tui-commentReplyCard__body" "css_element"
    When I click on "Edit" "link" in the ".tui-commentReplyCard__body" "css_element"
    And I wait for the next second
    And I click on "Cancel" "button" in the ".tui-commentReplyContent__editForm" "css_element"
    Then I should see "comment 100 reply"
