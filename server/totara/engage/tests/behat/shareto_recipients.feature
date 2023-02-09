@totara @engage @totara_engage @javascript
Feature: Share items to recipients
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And I enable the "engage_resources" advanced feature

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | one@example.com   |
      | user2    | User      | two      | two@example.com   |
      | user3    | User      | three    | three@example.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content       | access     | topics |
      | Test Article 1 | user1    | Test Filters  | RESTRICTED | Topic1 |
      | Test Article 2 | user1    | Test Filters  | PUBLIC     | Topic1 |

  Scenario: View list of recipients from people picker
    Given I log in as "admin"
    And I click on "Your Library" in the totara menu
    And I view article "Test Article 1"
    Then I should see "Test Article 1"

    When I press "Share"
    And I click on "Tag list" "button" in the ".tui-engageSharedRecipientsSelector" "css_element"
    Then I should see "User three"
    And  I should see "User two"

  Scenario: Authenticated user should be able to share
    Given I log in as "user2"
    When I view article "Test Article 2"
    Then ".tui-shareSetting" "css_element" should exist in the ".tui-mediaSetting" "css_element"

  Scenario: Guest should not be able to share
    Given I log in as "admin"
    And the following "permission overrides" exist:
      | capability                | permission | role  | contextlevel | reference |
      | totara/engage:viewlibrary | Allow      | guest | User         | guest     |
    And I set the following administration settings values:
      | Guest login button | Show |
    When I log out
    And I am on homepage
    And I click on "#guestlogin input[type=submit]" "css_element"
    And I view article "Test Article 2"
    Then ".tui-shareSetting" "css_element" should not exist in the ".tui-mediaSetting" "css_element"