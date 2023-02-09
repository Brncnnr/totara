@totara_engage @totara @engage @totara_reportedcontent @javascript
Feature: Inappropriate content report is not accessible without the correct advanced features

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And I log in as "admin"

  Scenario: Inappropriate content report is available with engage resources
    Given I enable the "engage_resources" advanced feature
    And I disable the "container_workspace" advanced feature

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Inappropriate content"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Inappropriate content" in the ".totara-table-container" "css_element"

  Scenario: Inappropriate content report is available with workspaces
    Given I disable the "engage_resources" advanced feature
    And I enable the "container_workspace" advanced feature

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Inappropriate content"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Inappropriate content" in the ".totara-table-container" "css_element"

  Scenario: Inappropriate content report is available with both workspaces & resources
    Given I enable the "engage_resources" advanced feature
    And I enable the "container_workspace" advanced feature

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Inappropriate content"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Inappropriate content" in the ".totara-table-container" "css_element"

  Scenario: Inappropriate content report is not available without workspaces or resources
    Given I disable the "engage_resources" advanced feature
    And I disable the "container_workspace" advanced feature

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Inappropriate content"
    And I press "id_submitgroupstandard_addfilter"
    Then I should not see "Inappropriate content" in the ".totara-table-container" "css_element"