@totara @totara_contentmarketplace @contentmarketplace_linkedin @javascript
Feature: Test the LinkedIn Learning content marketplace plugin workflow

  Background:
    Given I set up the "linkedin" content marketplace plugin
    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User      | One      | user1@test.com |
      | user2    | User      | Two      | user2@test.com |
      | user3    | User      | Three    | user3@test.com |

    And the following "categories" exist:
      | name        | idnumber |
      | CategoryOne | cat1     |

    And the following "role assigns" exist:
      | user     | role          | contextlevel | reference |
      | user2    | coursecreator | System       |           |
      | user3    | coursecreator | Category     |  cat1     |

  Scenario: Workflow accessibility check
    Given I am on a totara site
    And I log in as "admin"
    When I click on "Find Learning" in the totara menu
    Then I should see "Explore content marketplace"
    And I log out

    And I log in as "user1"
    When I click on "Find Learning" in the totara menu
    Then I should not see "Explore content marketplace"
    And I should not see "Create Course"
    And I log out

    And I log in as "user2"
    When I click on "Find Learning" in the totara menu
    Then I should see "Explore content marketplace"
    And I should see "Create Course"
    And I log out

    And I log in as "user3"
    When I click on "Find Learning" in the totara menu
    Then I should see "Explore content marketplace"
    And I should see "Create Course"