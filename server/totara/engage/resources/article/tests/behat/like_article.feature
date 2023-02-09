@totara @engage @totara_engage @engage_article @javascript
Feature: Like article

  Background:
    Given I am on a totara site

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content       | format       | access     | topics  |
      | Test Article 1 | user1    | Test Aticle 1 | FORMAT_PLAIN | PUBLIC     | Topic 1 |
      | Test Article 2 | user1    | Test Aticle 2 | FORMAT_PLAIN | RESTRICTED | Topic 1 |
      | Test Article 3 | user1    | Test Aticle 3 | FORMAT_PLAIN | PRIVATE    | Topic 1 |

    And "engage_article" "Test Article 1" is shared with the following users:
      | sharer | recipient |
      | user1  | user2     |

    And "engage_article" "Test Article 2" is shared with the following users:
      | sharer | recipient |
      | user1  | user2     |

  Scenario: Owner can like public resource
    Given I log in as "user1"
    And I view article "Test Article 1"
    When I click on "Like" "button"
    Then I should see "1"
    When I click on "Remove like" "button"
    Then I should see "0"

  Scenario: Recipient can like and remove the like for public resource
    Given I log in as "user2"
    And I view article "Test Article 1"
    When I click on "Like" "button"
    Then I should see "1"
    When I click on "Remove like" "button"
    Then I should see "0"

  Scenario: Owner can like restricted resource
    Given I log in as "user1"
    And I view article "Test Article 2"
    When I click on "Like" "button"
    Then I should see "1"
    When I click on "Remove like" "button"
    Then I should see "0"

  Scenario: Recipient can like and remove the like for restricted resource
    Given I log in as "user2"
    And I view article "Test Article 1"
    When I click on "Like" "button"
    Then I should see "1"
    When I click on "Remove like" "button"
    Then I should see "0"

  Scenario: Owner can not like private resource
    Given I log in as "user1"
    And I view article "Test Article 3"
    Then I should not see "0"

  Scenario: Admin can like public and restricted resource
    Given I log in as "admin"
    And I view article "Test Article 1"
    When I click on "Like" "button"
    Then I should see "1"
    When I click on "Remove like" "button"
    Then I should see "0"

    When I view article "Test Article 2"
    And I click on "Like" "button"
    Then I should see "1"
    When I click on "Remove like" "button"
    Then I should see "0"

  Scenario: Guest should not be able to like
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
    Then ".tui-sidePanelLike" "css_element" should not exist in the ".tui-mediaSetting" "css_element"