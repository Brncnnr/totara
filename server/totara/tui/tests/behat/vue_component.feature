@totara @totara_tui @javascript
Feature: Test vue component

  Scenario: Vue should be able to render a component regardless of theme
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the "vue_component" fixture in the "totara/tui" plugin
    And I set the site theme to "basis" for device type "default"
    Then I should see "Vue component displayed"
    Then I should see "Vue component displayed"
    When I set the site theme to "ventura"
    Then I should see "Vue component displayed"
