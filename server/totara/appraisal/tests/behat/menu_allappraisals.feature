@totara @totara_appraisal @totara_core_menu
Feature: Test All Appraisals menu item
  In order to use All Appraisals menu item
  As an admin
  I must be able to cofigure it

  Scenario: Make sure All Appraisals is available in totara menu
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "All Appraisals (legacy)" in the "#totaramenutable" "css_element"
    And I should not see "All Appraisals (legacy)" in the totara menu

  Scenario: Make sure All Appraisals is available in totara menu even if other things disabled
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Perform settings" node in "Site administration > System information > Configure features"
    And I set the field "Enable Goals" to "0"
    And I press "Save changes"
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "All Appraisals (legacy)" in the "#totaramenutable" "css_element"
    And I should not see "All Appraisals (legacy)" in the totara menu

  Scenario: Make sure All Appraisals is not in totara menu if feature disabled
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Perform settings" node in "Site administration > System information > Configure features"
    And I press "Save changes"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "All Appraisals (legacy)" in the "#totaramenutable" "css_element"
    And I should see "Unused" in the "All Appraisals (legacy)" "table_row"
