@totara @totara_engage @engage @totara_reportedcontent @javascript
Feature: Report & remove comments in engage articles

  Scenario: A user can report other comments but not their own in resources
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User1     | One      | user1@example.com |
      | user2    | User2     | Two      | user2@example.com |
    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
    And the following "articles" exist in "engage_article" plugin:
      | name      | username | content      | format       | access | topics  |
      | Article 1 | user1    | Test Article | FORMAT_PLAIN | PUBLIC | Topic 1 |
      | Article 2 | user1    | Test Article | FORMAT_PLAIN | PUBLIC | Topic 1 |
    And the following "comments" exist in "totara_comment" plugin:
      | name      | username | component      | area    | content                |
      | Article 1 | user2    | engage_article | comment | comment not reportable |
      | Article 2 | user1    | engage_article | comment | comment is reportable  |
    And I log in as "user2"

    # Reportable
    When I view article "Article 1"
    And I click on "Comments" "link"
    Then I should see "comment not reportable"

    When I click on ".tui-commentCard button[aria-label=\"Menu trigger\"]" "css_element"
    And I wait for the next second
    Then I should not see "Report"
    And I should see "Edit"

    # Not reportable
    When I view article "Article 2"
    And I click on "Comments" "link"
    Then I should see "comment is reportable"

    When I click on ".tui-commentCard button[aria-label=\"Menu trigger\"]" "css_element"
    And I wait for the next second
    Then I should see "Report"
    And I should not see "Edit"

    When I click on "Report" "link" in the ".tui-commentCard__comment" "css_element"
    And I wait for the next second
    Then I should see "Content has been reported"