@totara @totara_tui @javascript
Feature: Test vue apollo component

  @javascript
  Scenario: Apollo should request the ping graphql service and output 'ok' when it resolves
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the "vue_apollo" fixture in the "totara/tui" plugin
    Then I should see "ok"
