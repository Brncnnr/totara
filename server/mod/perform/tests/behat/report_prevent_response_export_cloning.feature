@totara @perform @totara_reportbuilder @mod_perform @javascript @vuejs
Feature: Prevent cloning of certain embedded reports

  Scenario: Prevent cloning of the response export report
    Given I log in as "admin"
    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "Report Name value" to "Performance activity response export"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I click on "Clone report" "link" in the "Performance activity response export" "table_row"
    Then I should see "Clone report"
    And I should see "This report can not be cloned because it can only be used for the exporting of activity responses."
    When I click on "Back to manage embedded reports" "link"
    And I set the field "Report Name value" to "Performance activity participant instances"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I click on "Clone report" "link" in the "Performance activity participant instances" "table_row"
    Then I should see "Clone report"
    And I should see "Report content and access controls may change when copying an embedded report as content or access controls that are applied by the embedded page will be lost."
