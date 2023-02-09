@totara @totara_reportbuilder @javascript
Feature: Test my reports page

  Background:
    Given I am on a totara site
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname             | shortname                   | source | summary                     | hidden |
      | Custom user report 1 | report_custom_user_report_1 | user   | User report 1 abstract text | 0      |
      | Custom user report 2 | report_custom_user_report_2 | user   | User report 2 abstract text | 0      |
      | Custom user report 3 | report_custom_user_report_3 | user   | User report 3 abstract text | 1      |
    And I log in as "admin"

  Scenario: The my reports page displays reports allowing users to navigate to the report
    Given I click on "Reports" in the totara menu
    Then I should see "Custom user report 1"
    And  I should see "Custom user report 2"
    And "Custom user report 1" "text" should appear before "Custom user report 2" "text"
    When I follow "Custom user report 1"
    Then I should see "Custom user report 1"
    And I should see "2 records shown" in the ".rb-record-count" "css_element"
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 2"
    Then I should see "Custom user report 2"
    And I should see "2 records shown" in the ".rb-record-count" "css_element"
    When I click on "Reports" in the totara menu
    And I click on "//div/a/span[contains(@data-flex-icon, 'view-list')]" "xpath_element"
    Then I should see "Custom user report 1"
    And  I should see "Custom user report 2"
    And "Custom user report 1" "text" should appear before "Custom user report 2" "text"
    When I click on "//div/a/span[contains(@data-flex-icon, 'view-grid')]" "xpath_element"
    Then I should see "Custom user report 1"
    And  I should see "Custom user report 2"
    And "Custom user report 1" "text" should appear before "Custom user report 2" "text"

  Scenario: Reports can be hidden on the my reports page
    When I click on "Reports" in the totara menu
    Then I should see "Custom user report 1"
    And  I should see "Custom user report 2"
    And  I should not see "Custom user report 3"
    And "Custom user report 1" "text" should appear before "Custom user report 2" "text"

  Scenario: The display of the abstract text can be turned on or off
    Given I click on "Reports" in the totara menu
    Then I should not see "User report 1 abstract text"
    And  I should not see "User report 2 abstract text"
    When I navigate to "General settings" node in "Site administration > Reports"
    And I set the following fields to these values:
      | Show report abstract | 1 |
    And I press "Save changes"
    And I should see "Changes saved"
    And I click on "Reports" in the totara menu
    Then I should see "User report 1 abstract text"
    And  I should see "User report 2 abstract text"

  Scenario: Disabling the create user reports feature
    When I click on "Reports" in the totara menu
    Then I should see "Create report"
    When I navigate to "Manage user reports" node in "Site administration > Reports"
    Then "input[value='Create report']" "css_element" should be visible
    When I disable the "user_reports" advanced feature
    And I click on "Reports" in the totara menu
    Then I should not see "Create report"
    When I navigate to "Manage user reports" node in "Site administration > Reports"
    Then "input[value='Create report']" "css_element" should not be visible
