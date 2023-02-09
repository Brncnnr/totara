@totara_engage @totara @engage @javascript
Feature: View other' library page
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user_one | User      | One      | user1@example.com |

  Scenario: Admin view other's library
    Given I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "User One" "link"
    When I click on "User One's library" "link"
    Then I should see "User One has not made any contributions yet." in the ".tui-contributionBaseContent__emptyText" "css_element"
    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content       | format       | access | topics  |
      | Test Article 1 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
    And I click on "User One" "link"
    When I click on "User One's library" "link"
    Then I should see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "1 item"

  Scenario: Admin view other's library for more than 20 items
    Given I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "User One" "link"
    When I click on "User One's library" "link"
    Then I should see "User One has not made any contributions yet." in the ".tui-contributionBaseContent__emptyText" "css_element"
    And the following "articles" exist in "engage_article" plugin:
      | name            | username | content       | format       | access | topics  |
      | Test Article 1  | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 2  | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 3  | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 4  | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 5  | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 6  | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 7  | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 8  | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 9  | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 10 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 11 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 12 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 13 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 14 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 15 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 16 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 17 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 18 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 19 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 20 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 21 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 22 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 23 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 24 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Test Article 25 | user_one | Test Article  | FORMAT_PLAIN | PUBLIC | Topic 1 |
    And I click on "User One" "link"
    When I click on "User One's library" "link"
    Then I should see "25 items"