@totara @perform @mod_perform @javascript
Feature: Disable performance activities feature at site-level

  Background:
    When I log in as "admin"

  Scenario: Disable in the advanced features page
    When I toggle open the admin quick access menu
    Then I should see "Activity management" in the admin quick access menu

    When I navigate to "Perform settings" node in "Site administration > System information > Configure features"
    And I set the field "Enable Performance Activities" to "0"
    And I press "Save changes"

    When I toggle open the admin quick access menu
    Then I should not see "Activity management" in the admin quick access menu

  Scenario: Hide performance activities link in top menu
    Given I should see "Activities" in the totara menu
    When I navigate to "Perform settings" node in "Site administration > System information > Configure features"
    And I set the field "Enable Performance Activities" to "0"
    And I press "Save changes"
    Then I should not see "Activities" in the totara menu

  Scenario: Hide report source in user reports interface
    When I navigate to "Reports > Manage user reports" in site administration
    And I click on "Create report" "button"
    And I set the field with xpath "//input[@id='search_input']" to "Instance"
    And I click on "button.tw-selectSearchText__btn" "css_element"
    And I wait for pending js
    Then I should see "Performance Subject Instance" in the ".totara_reportbuilder__createreport_list" "css_element"
    And I should see "Participant Instance" in the ".totara_reportbuilder__createreport_list" "css_element"

    When I disable the "performance_activities" advanced feature
    And I reload the page
    And I set the field with xpath "//input[@id='search_input']" to "Perform"
    And I click on "button.tw-selectSearchText__btn" "css_element"
    And I wait for pending js
    Then I should not see "Performance Subject Instance" in the ".totara_reportbuilder__createreport_list" "css_element"
    And I should not see "Participant Instance" in the ".totara_reportbuilder__createreport_list" "css_element"
