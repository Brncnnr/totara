@totara @editor @editor_weka @weka @javascript @editor @vuejs
Feature: Moodle form weka editor test
  Render the weka editor as the default editor for a user
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I am on a totara site
    And I log in as "admin"

  Scenario: Create course and test that plain text area is rendered
    Given I open my profile in edit mode
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"
    And I set the field "Text editor" to "Plain text area"
    And I press "Save changes"

    When I click on "Find Learning" in the totara menu
    And I click on "Create" "button"
    And I click on "Course" "link" in the "li.tw-catalogManageBtns__group_options_item" "css_element"
    Then ".tui-weka" "css_element" should not exist

  Scenario: Create course and test that weka editor is rendered
    Given I open my profile in edit mode
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"
    And I set the field "Text editor" to "Weka"
    And I press "Save changes"

    When I click on "Find Learning" in the totara menu
    And I click on "Create" "button"
    And I click on "Course" "link" in the "li.tw-catalogManageBtns__group_options_item" "css_element"
    Then ".tui-weka" "css_element" should exist

  Scenario: Create course with weka editor on and not provide description
    Given I open my profile in edit mode
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"
    And I set the field "Text editor" to "Weka"
    And I press "Save changes"
    When I click on "Find Learning" in the totara menu
    And I click on "Create" "button"
    And I click on "Course" "link" in the "li.tw-catalogManageBtns__group_options_item" "css_element"
    And I set the field "Course full name" to "Course 101"
    And I set the field "Course short name" to "c101"
    When I click on "Save and display" "button"
    Then I should not see "Edit course settings"