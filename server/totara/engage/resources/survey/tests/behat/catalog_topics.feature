@javascript @totara_engage @engage_survey @totara @totara_catalog @engage
Feature: Survey topic links to the catalog
  As a user
  I need to view related articles when I click on a topic in a survey
  So I can find articles related to my survey

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
      | username | firstname | lastname | email             |
      | harry    | Harry     | One      | user1@example.com |
      | sally    | Sally     | One      | user2@example.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
      | Topic 2 |

    And the following "articles" exist in "engage_article" plugin:
      | name               | username | content        | access | topics           |
      | Topic One Article  | harry    | View article 1 | PUBLIC | Topic 1          |
      | Topic Two Article  | harry    | View article 2 | PUBLIC | Topic 2          |
      | Topic Both Article | harry    | View article 3 | PUBLIC | Topic 1, Topic 2 |

    And the following "surveys" exist in "engage_survey" plugin:
      | question          | username | access | topics           |
      | Topic One Survey  | harry    | PUBLIC | Topic 1          |
      | Topic Two Survey  | harry    | PUBLIC | Topic 2          |
      | Topic Both Survey | harry    | PUBLIC | Topic 1, Topic 2 |

    And I log out

  Scenario: Test related articles can be filtered by a topic via the survey
    Given I log in as "harry"
    And I click on "Find Learning" in the totara menu
    Then I should see "Topic One Article"
    And I should see "Topic Two Article"

    When I view survey "Topic One Survey"
    And I follow "Topic 1"
    Then I should see "Topic One Article"
    And I should see "Topic Both Article"
    And I should not see "Topic Two Article"

    When I view survey "Topic Two Survey"
    And I follow "Topic 2"
    Then I should see "Topic Two Article"
    And I should see "Topic Both Article"
    And I should not see "Topic One Article"
