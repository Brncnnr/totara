@totara @engage @container_workspace @container @javascript
Feature: Private workspace workflow
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username   | firstname | lastname | email             |
      | user_one   | User      | One      | one@example.com   |
      | user_two   | User      | Two      | two@example.com   |
      | user_three | User      | Three    | three@example.com |

  Scenario: Create private workspace
    Given I am on a totara site
    And I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I click on "Create a workspace" "button"
    And I set the field "Workspace name" to "This is private workspace"
    And I click on "Private" "text" in the ".tui-radioGroup" "css_element"
    When I click on "Submit" "button"
    Then I should see "This is private workspace"
    And I should see "Private workspace"

  Scenario: Request to join private workspace
    Given the following "workspaces" exist in "container_workspace" plugin:
      | name               | owner    | private | summary                             |
      | User one workspace | user_one | 1       | This is user's one privateworkspace |

    And I am on a totara site
    And I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    When I follow "Members"
    Then I should not see "Requests to join"
    And I log out
    And I log in as "user_two"
    When I click on "Find Workspaces" in the totara menu
    Then I should see "Request to join"
    When I click on "Request to join" "button"
    Then I should see "Request to join"
    And I should see "Write to workspace administrators (optional)"
    And I set the field "messageContent" to "Request to join"
    When I click on "Submit" "button"
    Then I should see "Your request to join is awaiting approval"
    And I should see "Cancel request"
    And I should not see "Request to join"

    When I follow "User one workspace"
    And I should see "Cancel request"
    And I should not see "Request to join"
    And I click on "Cancel request" "button"
    And I should see "Request to join"
    And I should not see "Cancel request"
    When I click on "Request to join" "button"
    Then I should see "Request to join"
    And I should see "Write to workspace administrators (optional)"
    And I set the field "messageContent" to "Request to join again"
    When I click on "Submit" "button"
    Then I should see "Cancel request"
    And I should not see "Request to join"

    And I log out
    And I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I follow "Members"
    And I should not see "2 members"
    And I should see "1 member"
    And I should see "Requests to join"
    And I should see "User Two"
    And I should see "Approve"
    And I should see "Decline"
    And I should see "Request to join again"
    And I should not see "Approved"
    When I click on "Approve member request User Two" "button"
    Then I should see "Approved"

    # Reload
    And I click on "Your Workspaces" in the totara menu
    And I follow "Members"
    And I should see "2 members"

  Scenario: Decline join request to private workspace
    Given the following "workspaces" exist in "container_workspace" plugin:
      | name               | owner    | private | summary                             |
      | User one workspace | user_one | 1       | This is user's one privateworkspace |

    And I log in as "user_two"
    When I click on "Find Workspaces" in the totara menu
    Then I should see "Request to join"
    When I click on "Request to join" "button"
    Then I should see "Request to join"
    And I should see "Write to workspace administrators (optional)"
    And I set the field "messageContent" to "Request to join User one workspace"
    When I click on "Submit" "button"
    Then I should see "Your request to join is awaiting approval"
    And I should see "Cancel request"
    And I should not see "Request to join"
    And I log out

    And I log in as "user_one"
    And I click on "Your Workspaces" in the totara menu
    And I follow "Members"
    And I should not see "2 members"
    And I should see "1 member"
    And I should see "Requests to join this workspace"
    And I should see "User Two"
    And I should see "Approve"
    And I should see "Decline"
    And I should see "Request to join User one workspace"
    And I should not see "Approved"
    When I click on "Decline" "button"
    Then I should see "Decline this request"
    And I should see "Write to the requesting user (optional)"
    And I set the field "messageContent" to "Decline this join request"
    And I click on "Continue" "button"
    Then I should see "Decline"
    And I reset the email sink
    And I trigger cron
    And the following emails should have been sent:
      | To              |                   Subject                                 | Body |
      | two@example.com | Your request to join User one workspace has been declined | Your request to join the private workspace User one workspace has been declined. Decline this join request |

  Scenario: Check access restriction on the private hidden workspace
    Given the following "workspaces" exist in "container_workspace" plugin:
      | name               | owner    | private | hidden | summary                             |
      | User one workspace | user_one | 1       | 1      | This is user's one privateworkspace |
    And I am on a totara site
    And I log in as "user_three"
    And I access the "User one workspace" workspace
    And I should see "You don't have permission to view this page."
    And I should not see "User one workspace"
