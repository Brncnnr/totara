@totara @totara_appraisal @totara_core_menu
Feature: Test Latest Appraisal menu item
  In order to use Latest Appraisal menu item
  As an admin
  I must be able to cofigure it

  Scenario: Make sure Latest Appraisal is available in totara menu
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Latest Appraisal (legacy)" in the "#totaramenutable" "css_element"
    And I should not see "Latest Appraisal (legacy)" in the totara menu

  Scenario: Make sure Latest Appraisal is available in totara menu even if other things disabled
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Perform settings" node in "Site administration > System information > Configure features"
    And I set the field "Enable Goals" to "0"
    And I press "Save changes"
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Latest Appraisal (legacy)" in the "#totaramenutable" "css_element"
    And I should not see "Latest Appraisal (legacy)" in the totara menu
