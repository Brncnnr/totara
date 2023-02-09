@totara @totara_engage @engage_survey @engage
Feature: Update survey
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And I enable the "engage_resources" advanced feature

    And the following "users" exist:
      | username | firstname  | lastname | email             |
      | user1    | User1      | One      | user1@example.com |
      | user2    | User2      | Two      | user2@example.com    |
      | user3    | User3      | Three    | user2@example.com    |

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
      | Topic 2 |

    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | access      | topics           | options                      |
      | Test Survey 1? | user1    | PUBLIC      | Topic 1, Topic 2 | Option 1, Option 2, Option 3 |
      | Test Survey 2? | user1    | RESTRICTED  | Topic 1          | Option 1, Option 2           |
      | Test Survey 3? | user1    | PRIVATE     | Topic 1, Topic 2 | Option 1, Option 2           |

    And "engage_survey" "Test Survey 2?" is shared with the following users:
      | sharer | recipient |
      | user1  | user2     |

  @javascript
  Scenario: Admin can update/delete survey

    #View public survey
    Given I log in as "admin"
    And I view survey "Test Survey 1?"
    And I should see "Share"
    When I click on "Actions" "button"
    Then I should see "Delete survey"
    When I click on "Delete survey" "link"
    And I close the tui modal
    And I click on "Share" "button"
    Then I should see "Content visibility settings"
    And I should see "Hidden (only you)"
    And I should see "Public (anyone can see and share this content)"
    And I should see "Limited (only people and workspaces you share to)"
    And I click on "Cancel" "button"

     #View restricted survey
    When I view survey "Test Survey 2?"
    Then I should see "Share"
    When I click on "Actions" "button"
    Then I should see "Delete survey"
    When I click on "Delete survey" "link"
    And I close the tui modal
    And I click on "Share" "button"
    Then I should see "Content visibility settings"
    And I should see "Hidden (only you)"
    And I should see "Public (anyone can see and share this content)"
    And I should see "Limited (only people and workspaces you share to)"
    And I click on "Cancel" "button"

    #View private survey
    When I view survey "Test Survey 3?"
    Then I should see "Share"
    When I click on "Actions" "button"
    Then I should see "Delete survey"
    When I click on "Delete survey" "link"
    And I close the tui modal
    And I click on "Share" "button"
    Then I should see "Content visibility settings"
    And I should see "Hidden (only you)"
    And I should see "Public (anyone can see and share this content)"
    And I should see "Limited (only people and workspaces you share to)"
    And I click on "Cancel" "button"

  @javascript
  Scenario: Owner can update/delete restricted survey
    Given I log in as "user1"
    And I view survey "Test Survey 2?"
    And I should see "Share"
    When I click on "Actions" "button"
    And I click on "Delete survey" "link"
    And I close the tui modal
    And I click on "Share" "button"
    Then I should see "Content visibility settings"
    And I should see "Hidden (only you)"
    And I should see "Public (anyone can see and share this content)"
    And I should see "Limited (only people and workspaces you share to)"
    And I click on "Public (anyone can see and share this content)" "text" in the ".tui-accessSelector" "css_element"
    When I click on "Tag list" "button" in the ".tui-topicsSelector" "css_element"
    And I click on "Topic 2" option in the dropdown menu
    Then the "Done" "button" should be enabled
    And I click on "Done" "button"

  @javascript
  Scenario: Recipient can not update/delete restricted survey
    Given I log in as "user2"
    And I view survey "Test Survey 2?"
    And I should not see "Share"
    When I click on "Actions" "button"
    Then I should not see "Delete survey"
    And I should see "Report content"