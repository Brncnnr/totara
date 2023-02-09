@totara @totara_engage @engage @totara_reportedcontent @javascript
Feature: Report & remove comments in workspaces

  Scenario: A user can report other comments but not their own
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User1     | One      | user1@example.com |
      | user2    | User2     | Two      | user2@example.com |
      | user3    | User3     | Two      | user3@example.com |
    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
    And the following "workspaces" exist in "container_workspace" plugin:
      | name        | owner | summary       | topics |
      | Workspace 1 | user3 | The Workspace | Topic1 |

    # Create the comments & discussions as user 1
    When I log in as "user1"
    And I click on "Find Workspaces" in the totara menu
    And I click on "Workspace 1" "link"
    And I press "Join workspace"
    And I wait for the next second

    # Create the discussion
    And I activate the weka editor with css ".tui-workspaceDiscussionForm"
    And I type "Discussion 1" in the weka editor
    And I wait for the next second
    And I press "Post"
    And I wait for the next second
    Then I should see "Discussion 1"

    # Add the comment
    When I press "Comment"
    And I wait for the next second
    And I activate the weka editor with css ".tui-commentForm"
    And I type "Comment 1" in the weka editor
    And I wait for the next second
    And I click on "Comment" "button" in the ".tui-commentResponseBox__formBox" "css_element"
    Then I should see "Comment 1"

    # Add the reply
    When I press "Reply"
    And I wait for the next second
    And I activate the weka editor with css ".tui-commentReplyForm"
    And I type "Reply 1" in the weka editor
    And I wait for the next second
    And I click on "Reply" "button" in the ".tui-commentCard__replyBox .tui-commentResponseBox__formBox" "css_element"
    Then I should see "Reply 1"

    # Check that none of them can be reported
    When I press "Discussion's actions"
    Then I should see "Edit" in the ".tui-workspaceDiscussionCard__card" "css_element"
    And I should not see "Report" in the ".tui-workspaceDiscussionCard__card" "css_element"

    When I press "Discussion's actions"
    And I wait for the next second
    And I click on "Menu trigger" "button" in the ".tui-commentCard__body" "css_element"
    Then I should see "Edit" in the ".tui-commentCard__body" "css_element"
    And I should not see "Report" in the ".tui-commentCard__body" "css_element"

    When I click on "Menu trigger" "button" in the ".tui-commentCard__body" "css_element"
    And I wait for the next second
    And I click on "Menu trigger" "button" in the ".tui-commentReplyCard__body" "css_element"
    Then I should see "Edit" in the ".tui-commentReplyCard__body" "css_element"
    And I should not see "Report" in the ".tui-commentReplyCard__body" "css_element"

    # Now as user2, check they can be reported
    When I log out
    And I log in as "user2"
    And I click on "Find Workspaces" in the totara menu
    And I click on "Workspace 1" "link"

    # Discussion button
    When I press "Discussion's actions"
    And I wait for the next second
    Then I should not see "Edit" in the ".tui-workspaceDiscussionCard__card .tui-dropdown" "css_element"
    And I should see "Report" in the ".tui-workspaceDiscussionCard__card .tui-dropdown" "css_element"

    # Comment
    When I press "Discussion's actions"
    And I wait for the next second
    And I click on "Menu trigger" "button" in the ".tui-commentCard__body" "css_element"
    Then I should not see "Edit" in the ".tui-commentCard__body" "css_element"
    And I should see "Report" in the ".tui-commentCard__body" "css_element"

    # Reply
    When I click on "View replies" "link"
    And I wait for the next second
    And I click on "Menu trigger" "button" in the ".tui-commentReplyCard__body" "css_element"
    Then I should not see "Edit" in the ".tui-commentReplyCard__body" "css_element"
    And I should see "Report" in the ".tui-commentReplyCard__body" "css_element"