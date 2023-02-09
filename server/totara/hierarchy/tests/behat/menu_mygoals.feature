@totara @totara_hierarchy @totara_core_menu
Feature: Test Goals menu item
  In order to use Goals menu item
  As an admin
  I must be able to cofigure it

  Scenario: Make sure Goals is available in totara menu
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Goals" in the "#totaramenutable" "css_element"
    And I should see "Goals" in the totara menu

  Scenario: Make sure Goals is available in totara menu even if everything else is disabled in Appraisals
    Given I am on a totara site
    And I log in as "admin"
    And I disable the "appraisals" advanced feature
    And I disable the "feedback360" advanced feature
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Goals" in the "#totaramenutable" "css_element"
    And I should see "Goals" in the totara menu

  Scenario: Make sure Goals is not in totara menu if feature disabled
    Given I am on a totara site
    And I log in as "admin"
    And I disable the "goals" advanced feature
    And I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Goals" in the "#totaramenutable" "css_element"
    And I should see "Feature disabled" in the "Goals" "table_row"
