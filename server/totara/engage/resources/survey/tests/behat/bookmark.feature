@javascript @totara_engage @engage_survey @totara @engage
Feature: Bookmark survey
  As a user
  I need to bookmark an survey
  So that I can easily navigate to it in the future

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User      | One      | user1@test.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
      | Topic 2 |

    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | access | topics           |
      | Test Survey 1? | user1    | PUBLIC | Topic 1, Topic 2 |
      | Test Survey 2? | user1    | PUBLIC | Topic 1          |

    And "engage_survey" "Test Survey 1?" is shared with the following users:
      | sharer | recipient |
      | user1  | admin     |

  Scenario: Test bookmarking a shared survey
    And I log in as "admin"
    And I click on "Your Library" in the totara menu

    When I click on "Saved resources" "link" in the ".tui-engageNavigationPanel__menu" "css_element"
    Then I should not see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"

    When I click on "Shared with you" "link" in the ".tui-engageNavigationPanel__menu" "css_element"
    And I click on "Bookmark" "button" in the ".tui-engageSurveyCard" "css_element"
    And I click on "Saved resources" "link" in the ".tui-engageNavigationPanel__menu" "css_element"
    Then I should see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"

    When I click on "Unbookmark" "button" in the ".tui-engageSurveyCard" "css_element"
    And I wait for the next second
    Then I should not see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"