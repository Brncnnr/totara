@javascript @totara_engage @engage_article @totara @totara_catalog @engage
Feature: Article catalog content
  As a user
  I need to view articles on the catalog
  So that I can easily navigate to it in the future

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Configure catalogue"
    And I follow "General"
    And I set the following Totara form fields to these values:
      | Details content | 0 |
    And I click on "Save" "button"

    And I follow "Filters"
    And I set the field "Add another..." to "Topics"
    And I click on "Save" "button"

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | harry    | Harry     | One      | user1@example.com |
      | sally    | Sally     | One      | user2@example.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
      | Topic 2 |

    And the following "articles" exist in "engage_article" plugin:
      | name                  | username | content        | access  | topics  |
      | Harry Public Article  | harry    | View article 1 | PUBLIC  | Topic 1 |
      | Harry Private Article | harry    | View article 2 | PRIVATE | Topic 1 |
      | Sally Public Article  | sally    | View article 3 | PUBLIC  | Topic 1 |
      | Sally Private Article | sally    | View article 4 | PRIVATE | Topic 1 |
      | Harry Topic Article   | harry    | View article 5 | PRIVATE | Topic 2 |

    And I log out

  Scenario: Test viewing an article on the catalog
    Given I log in as "harry"
    And I click on "Find Learning" in the totara menu
    Then I should see "Harry Public Article"
    And I should see "Harry Private Article"
    And I should see "Sally Public Article"
    And I should not see "Sally Private Article"

    When I click on "Sally Public Article" "text"
    Then I should see "Sally Public Article"
    And I should see "View article 3"

    When I log out
    And I log in as "sally"
    And I click on "Find Learning" in the totara menu
    Then I should see "Sally Public Article"
    And I should see "Sally Private Article"
    And I should see "Harry Public Article"
    And I should not see "Harry Private Article"

  Scenario: Test that articles cannot be seen on the catalog when advanced features are disabled
    Given I enable the "engage_resources" advanced feature
    And I log in as "harry"
    And I click on "Find Learning" in the totara menu
    Then I should see "Harry Public Article"

    When I disable the "engage_resources" advanced feature
    And I click on "Find Learning" in the totara menu
    Then I should not see "Harry Public Article"
    And I should not see "Resources" in the ".tw-catalog__aside" "css_element"

  Scenario: Test article images work both directly and when a filter is applied
    Given I log in as "harry"
    And I click on "Find Learning" in the totara menu
    Then I should see "Harry Topic Article"
    And "//div[@class='tw-catalogItemNarrow__image']/div/div[contains(@style, 'engage_article') and contains(@style, 'background-image')]" "xpath_element" should exist

    When I click on "Topic 2" "link" in the "section.tw-selectRegionPanel" "css_element"
    Then I should see "Harry Topic Article"
    And I should see "1 items"
    And "//div[@class='tw-catalogItemNarrow__image']/div/div[contains(@style, 'engage_article') and contains(@style, 'background-image')]" "xpath_element" should exist

  Scenario: Test article can be filtered by a topic
    Given I log in as "harry"
    And I click on "Find Learning" in the totara menu
    Then I should see "Harry Topic Article"
    And I should see "Harry Public Article"

    When I click on "Topic 2" "link" in the "section.tw-selectRegionPanel" "css_element"
    Then I should see "Harry Topic Article"
    And I should not see "Harry Public Article"

    When I click on "Topic 2" "link" in the "section.tw-selectRegionPanel" "css_element"
    And I click on "Topic 1" "link" in the "section.tw-selectRegionPanel" "css_element"
    Then I should not see "Harry Topic Article"
    And I should see "Harry Public Article"

  Scenario: Test article topic link can create catalog filtered by topic
    Given I log in as "harry"
    And I click on "Find Learning" in the totara menu
    Then I should see "Harry Topic Article"
    And I should see "Harry Public Article"

    When I click on "Harry Topic Article" "text"
    Then I should see "Harry Topic Article"
    And I wait for the next second

    When I click on "Topic 2" "link"
    Then I should see "Harry Topic Article"
    And I should not see "Harry Public Article"
    And I should see "Find learning"