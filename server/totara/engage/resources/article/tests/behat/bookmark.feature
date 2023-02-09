@javascript @totara_engage @engage_article @totara @engage
Feature: Bookmark article
  As a user
  I need to bookmark an article
  So that I can easily navigate to it in the future

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
      | Topic 2 |

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User      | One      | user1@test.com |
      | user2    | User      | Two      | user2@test.com |

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content       | access | topics  |
      | Test Article 1 | user1    | Test Bookmark | PUBLIC | Topic 1 |
      | Test Article 2 | user1    | Test Bookmark | PUBLIC | Topic 2 |

    And "engage_article" "Test Article 1" is shared with the following users:
      | sharer | recipient |
      | user1  | admin     |

  Scenario: Test bookmarking a public article
    Given I log in as "admin"
    And I view article "Test Article 2"
    And I click on "Bookmark" "button"
    And I click on "Your Library" in the totara menu
    And I click on "Saved resources" "link" in the ".tui-engageNavigationPanel__menu" "css_element"
    Then I should see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"

  Scenario: Test bookmarking a shared article
    And I log in as "admin"
    And I click on "Your Library" in the totara menu

    When I click on "Saved resources" "link" in the ".tui-engageNavigationPanel__menu" "css_element"
    Then I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"

    When I click on "Shared with you" "link" in the ".tui-engageNavigationPanel__menu" "css_element"
    And I click on "Bookmark" "button" in the ".tui-engageArticleCard" "css_element"
    And I click on "Saved resources" "link" in the ".tui-engageNavigationPanel__menu" "css_element"
    Then I should see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"

    When I click on "Unbookmark" "button" in the ".tui-engageArticleCard" "css_element"
    And I wait for the next second
    Then I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"

  Scenario: Owners should not be able to bookmark
    And I log in as "user1"
    When I view article "Test Article 1"
    Then "Bookmark" "button" should not exist in the ".tui-engageArticleTitle__head" "css_element"

  Scenario: Authenticated user should be able to bookmark
    And I log in as "user2"
    When I view article "Test Article 1"
    Then "Bookmark" "button" should exist in the ".tui-engageArticleTitle__head" "css_element"

  Scenario: Guest should not be able to bookmark
    Given I log in as "admin"
    And the following "permission overrides" exist:
      | capability                | permission | role  | contextlevel | reference |
      | totara/engage:viewlibrary | Allow      | guest | User         | guest     |
    And I set the following administration settings values:
      | Guest login button | Show |
    When I log out
    And I am on homepage
    And I click on "#guestlogin input[type=submit]" "css_element"
    And I view article "Test Article 1"
    Then "Bookmark" "button" should not exist in the ".tui-engageArticleTitle__head" "css_element"