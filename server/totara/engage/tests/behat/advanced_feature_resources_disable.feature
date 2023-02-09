@totara_engage @totara @engage @javascript
Feature: Don't show engage features when resources are disabled

  Scenario: Resource/Survey/Playlist cards should not show when resources advanced feature is disabled
    Given I am on a totara site
    And I set the site theme to "ventura"
    And I log in as "admin"

    When I disable the "engage_resources" advanced feature
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I press "Reset menu to default configuration"
    And I click on "permanently deleted" "radio"
    And I press "Reset"
    Then I should not see "Your Library" in the totara menu

    When I enable the "engage_resources" advanced feature
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I press "Reset menu to default configuration"
    And I click on "permanently deleted" "radio"
    And I press "Reset"
    Then I should see "Your Library" in the totara menu