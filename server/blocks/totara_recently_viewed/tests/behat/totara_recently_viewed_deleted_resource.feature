@engage @block @javascript @totara @block_totara_recently_viewed
Feature: Test that the dashboard does not crash when a viewed resource is deleted
  In order to handle deleted resources,
  the dashboard needs to filter out views for non-existent items.

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User      | One      | user1@example.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content | access     | topics |
      | Test Article 1 | user1    | blah    | PUBLIC     | Topic1 |
      | Test Article 2 | user1    | blah    | PUBLIC     | Topic1 |
      | Test Article 3 | user1    | blah    | PUBLIC     | Topic1 |

  Scenario: Test that the block still loads if one of the articles is deleted
    When I log in as "user1"
    And I view article "Test Article 1"
    And I view article "Test Article 2"
    And I view article "Test Article 3"

    And I am on "Dashboard" page
    And I click on "Customise this page" "button"
    And I add the "Recently viewed" block to the "main" region

    # Check they show
    Then I should see "Test Article 1" in the ".block-totara-recently-viewed" "css_element"
    And I should see "Test Article 2" in the ".block-totara-recently-viewed" "css_element"
    And I should see "Test Article 3" in the ".block-totara-recently-viewed" "css_element"

    # Now delete it
    And I view article "Test Article 1"
    And I click on "Actions" "button"
    And I should see "Delete"
    And I click on "Delete" "link"
    And I confirm the tui confirmation modal
    And I am on "Dashboard" page

    Then I should not see "Test Article 1" in the ".block-totara-recently-viewed" "css_element"
    And I should see "Test Article 2" in the ".block-totara-recently-viewed" "css_element"
    And I should see "Test Article 3" in the ".block-totara-recently-viewed" "css_element"