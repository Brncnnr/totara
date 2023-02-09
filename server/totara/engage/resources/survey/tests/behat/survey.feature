@totara @engage @totara_engage @engage_survey @javascript
Feature: Vote survey
  As a user
  I need to vote an survey
  So that I can answer the question
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User1      | One      | user1@test.com |
      | user2    | User2      | Two      | user2@test.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
      | Topic 2 |

    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | access      | topics           | options                      |
      | Test Survey 1? | user1    | PUBLIC      | Topic 1, Topic 2 |  Option 1, Option 2, Option 3|
      | Test Survey 2? | user1    | RESTRICTED  | Topic 1, Topic 2 |  Option 1, Option 2, Option 3|

    And "engage_survey" "Test Survey 1?" is shared with the following users:
      | sharer | recipient |
      | user1  | admin     |

  Scenario: Edit survey
    Given I log in as "user1"
    When I click on "Your Library" in the totara menu
    Then I should see "No results yet" in the ".tui-engageSurveyCardBody__text" "css_element"
    And I click on "Edit survey" "link"
    And I wait for the next second
    And I set the field "Enter survey question" to "Changing test Survey 1?"
    And I click on "Single answer" "text" in the ".tui-engageSurveyForm__optionType--single" "css_element"
    And I set the field "Option" to " Option 4"
    And I click on "Save" "button"
    And I should see "Changing test Survey 1?"
    And I should see "Vote" in the ".tui-formBtn" "css_element"
    And I click on "Your resources" "link"
    And I should see "Your resources" in the ".tui-contributionBaseContent__title" "css_element"

  Scenario: Vote and bookmark survey
    Given I log in as "admin"
    And I click on "Your Library" in the totara menu
    And I click on "Shared with you" "link"
    And I should see "Test Survey 1?"
    And I click on "Vote" "link"
    And I click on "Bookmark" "button"
    And I click on "Option 1" "text"
    And I click on "Option 2" "text"
    And I click on "Vote" "button"
    And I should see "Total votes: 2"
    And I click on "Shared with you" "link"
    And I should see "Shared with you" in the ".tui-contributionBaseContent__title" "css_element"
    And I click on "Saved resources" "link"
    And I should see "Test Survey 1?"
    And I should see "Showing 3 of 3 results" in the ".tui-engageSurveyResultBody__text" "css_element"

  Scenario: Like survey
    Given I log in as "admin"
    And I click on "Your Library" in the totara menu
    And I click on "Shared with you" "link"
    And I should see "Test Survey 1?"
    And I click on "Vote" "link"
    And I click on "Like" "button"
    And I should see "1"
    And I click on "Remove like" "button"
    And I should see "0"

  Scenario: User views restricted survey and public survey
    Given I log in as "admin"
    And I view survey "Test Survey 2?"
    Then I should not see "Reshare"
    When I view survey "Test Survey 1?"
    And I click on "Reshare survey" "button"
    Then I should see "Reshare" in the ".tui-modalContent__header-title" "css_element"

  Scenario: Survey votes are saved in the order they're entered
    Given I log in as "admin"
    And I click on "Your Library" in the totara menu
    And I press "Contribute"
    And I wait for pending js
    And I switch to "Survey" tui tab
    And I set the field "Enter survey question" to "Creating a survey"
    And I set the field with xpath "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-engageSurveyForm__repeater ')]//input[@name='options[0][text]']" to "A"
    And I set the field with xpath "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-engageSurveyForm__repeater ')]//input[@name='options[1][text]']" to "B"
    And I press "Next"
    And I press "Done"
    And I wait for the next second
    Then I should see "Creating a survey"

    When I view survey "Creating a survey"
    Then the field with xpath "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-engageSurveyForm__repeater ')]//input[@name='options[0][text]']" matches value "A"
    And the field with xpath "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-engageSurveyForm__repeater ')]//input[@name='options[1][text]']" matches value "B"

    # Add a new option and check it's in the third position
    When I press "Add"
    And I set the field with xpath "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-engageSurveyForm__repeater ')]//input[@name='options[2][text]']" to "C"
    And I press "Save"
    And I wait for the next second
    And I view survey "Creating a survey"
    Then the field with xpath "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-engageSurveyForm__repeater ')]//input[@name='options[0][text]']" matches value "A"
    And the field with xpath "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-engageSurveyForm__repeater ')]//input[@name='options[1][text]']" matches value "B"
    And the field with xpath "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-engageSurveyForm__repeater ')]//input[@name='options[2][text]']" matches value "C"

  Scenario: Can not create restrict survey without share capability
    Given I log in as "admin"
    When I set the following system permissions of "Authenticated User" role:
      | engage/survey:share   | Prohibit |
    Then I log out
    And I log in as "user1"
    And I click on "Your Library" in the totara menu
    And I click on "Contribute" "button"
    And I switch to "Survey" tui tab
    And I set the field "Enter survey question" to "Creating a survey"
    And I set the field with xpath "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-engageSurveyForm__repeater ')]//input[@name='options[0][text]']" to "A"
    And I set the field with xpath "//div[contains(concat(' ', normalize-space(@class), ' '), ' tui-engageSurveyForm__repeater ')]//input[@name='options[1][text]']" to "B"
    And I press "Next"
    And the "Limited (only people and workspaces you share to)" "radio" should be disabled
    When I click on "Public (anyone can see and share this content)" "text" in the ".tui-accessSelector" "css_element"
    Then I should not see "Share to specific people or workspaces (optional)"
    And I should see "Assign one or more topics (required)"

  Scenario: User can not view share button and edit settings
    Given I log in as "user1"
    When I view survey "Test Survey 1?"
    Then I should see "Share"
    And I should see "Edit settings"
    When I view survey "Test Survey 2?"
    Then I should see "Share"
    And I should see "Edit settings"
    And I log out

    And I log in as "admin"
    When I set the following system permissions of "Authenticated User" role:
      | engage/survey:share   | Prohibit |
    Then I log out

    And I log in as "user1"
    When I view survey "Test Survey 1?"
    Then I should not see "Share"
    And I should not see "Edit settings"
    And I should not see "Reshare"
    When I view survey "Test Survey 2?"
    Then I should not see "Share"
    And I should not see "Edit settings"
    And I should not see "Reshare"