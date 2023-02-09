@totara @totara_engage @engage_article @engage
Feature: Delete article
  As a user
  I need to delete my resources
  So I can keep my library clean

  Background:
    Given I am on a totara site
    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content       | format       | access | topics  |
      | Test Article 1 | user1    | Test Article | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 2 | user1    | Test Article | FORMAT_PLAIN | PUBLIC | Topic 1 |

    And "engage_article" "Test Article 1" is shared with the following users:
      | sharer | recipient |
      | user1  | user2     |

  @javascript
  Scenario: Delete resource with permission
    Given I log in as "user1"
    And I view article "Test Article 1"
    And I click on "Actions" "button"
    And I should see "Delete"
    And I click on "Delete" "link"
    And I confirm the tui confirmation modal
    Then I should not see "Test Article 1"
    And I should see "Test Article 2"

  @javascript
  Scenario: Delete resource without permission
    Given I log in as "user2"
    And I view article "Test Article 1"
    And I should not see "Delete"
