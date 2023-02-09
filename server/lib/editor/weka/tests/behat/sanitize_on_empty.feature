@totara @editor @editor_weka @totara @vuejs
Feature: Sanitize on empty json document
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"
    And I set the field "Text editor" to "Weka"
    And I press "Save changes"

  @javascript
  Scenario: Sanitize empty course description
    Given I am on a totara site
    When I click on "Find Learning" in the totara menu
    And I click on "Create" "button"
    And I click on "Course" "link" in the "li.tw-catalogManageBtns__group_options_item" "css_element"
    And ".tui-weka" "css_element" should exist
    And I set the field "Course full name" to "Course 101"
    And I set the field "Course short name" to "c101"
    And I click on "Save and display" "button"
    When I follow "Edit settings"
    Then ".tui-weka" "css_element" should exist

  @javascript
  Scenario: Sanitize empty program summary
    Given I am on a totara site
    And I set the following administration settings values:
      | catalogtype | enhanced |
    And I click on "Programs" in the totara menu
    When I press "Create Program"
    # We just make sure that the editor is rendered correctly.
    Then ".tui-weka" "css_element" should exist