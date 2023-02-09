@totara @engage @totara_engage @engage_survey @javascript
Feature: Like survey

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

    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | access      | topics  |  options                      |
      | Test Survey 1? | user1    | PUBLIC      | Topic 1 |  Option 1, Option 2, Option 3 |
      | Test Survey 2? | user1    | RESTRICTED  | Topic 1 |  Option 1, Option 2, Option 3 |
      | Test Survey 3? | user1    | PRIVATE     | Topic 1 |  Option 1, Option 2, Option 3 |

    And "engage_survey" "Test Survey 1?" is shared with the following users:
      | sharer | recipient |
      | user1  | user2     |

    And "engage_survey" "Test Survey 2?" is shared with the following users:
      | sharer | recipient |
      | user1  | user2     |

  Scenario: Owner can like public survey
    Given I log in as "user1"
    And I view survey "Test Survey 1?"
    When I click on "Like" "button"
    Then I should see "1"
    When I click on "Remove like" "button"
    Then I should see "0"

  Scenario: Recipient can like and remove the like for public survey
    Given I log in as "user2"
    And I view survey "Test Survey 1?"
    When I click on "Like" "button"
    Then I should see "1"
    When I click on "Remove like" "button"
    Then I should see "0"

  Scenario: Owner can like restricted survey
    Given I log in as "user1"
    And I view survey "Test Survey 2?"
    When I click on "Like" "button"
    Then I should see "1"
    When I click on "Remove like" "button"
    Then I should see "0"

  Scenario: Recipient can like and remove the like for restricted survey
    Given I log in as "user2"
    And I view survey "Test Survey 2?"
    When I click on "Like" "button"
    Then I should see "1"
    When I click on "Remove like" "button"
    Then I should see "0"

  Scenario: Owner can not like private survey
    Given I log in as "user1"
    And I view survey "Test Survey 3?"
    Then I should not see "0"

  Scenario: Admin can like public and restricted survey
    Given I log in as "admin"
    And I view survey "Test Survey 1?"
    When I click on "Like" "button"
    Then I should see "1"
    When I click on "Remove like" "button"
    Then I should see "0"

    When I view survey "Test Survey 2?"
    And I click on "Like" "button"
    Then I should see "1"
    When I click on "Remove like" "button"
    Then I should see "0"