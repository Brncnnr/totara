@totara @totara_feedback360 @totara_core_menu
Feature: Test 360 Feedback Main menu item
  In order to use 360 Feedback menu item
  As an admin
  I must be able to cofigure it

  Scenario: Make sure 360 Feedback is available in totara menu
    And I log in as "admin"
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "360° Feedback (legacy)" in the "#totaramenutable" "css_element"
    And I should not see "360° Feedback (legacy)" in the totara menu

  Scenario: Make sure 360 Feedback is available in totara menu even if other things disabled
    And I log in as "admin"
    When I navigate to "Perform settings" node in "Site administration > System information > Configure features"
    And I set the field "Enable Goals" to "0"
    And I press "Save changes"
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "360° Feedback (legacy)" in the "#totaramenutable" "css_element"
    And I should not see "360° Feedback (legacy)" in the totara menu

  Scenario: Make sure 360 Feedback is not in totara menu if feature disabled
    And I log in as "admin"
    When I navigate to "Perform settings" node in "Site administration > System information > Configure features"
    And I press "Save changes"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "360° Feedback (legacy)" in the "#totaramenutable" "css_element"
    And I should see "Unused" in the "360° Feedback (legacy)" "table_row"
