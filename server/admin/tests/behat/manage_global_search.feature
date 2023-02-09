@core @core_admin @javascript
Feature: Manage global search
  Scenario: View manage global search
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Solr" node in "Site administration > Plugins > Search"
    Then I should see "Manage global search"
    When I navigate to "Search areas" node in "Site administration > Plugins > Search"
    Then I should see "Manage global search"