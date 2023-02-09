@totara @totara_competency @javascript @perform
Feature: Test lists component

  Background:
    Given I am on a totara site
    And I log in as "admin"

  Scenario: The list is displayed correctly
    When I navigate to the "lists" fixture in the "totara/competency" plugin
    Then I should see "\totara_competency\output\lists testing page"
    And I should see "Column 1"
    And I should see "Column 2"
    And I should see "Column 3"
    And I should see "Column 4"
    And "Select all" "checkbox" should exist

    # row 1
    And "tw-list__select_1" "checkbox" should exist
    And I should see "Expand trigger on different column"
    And "Expand trigger on different column" "link" should not exist
    # trigger on different column
    And "22" "link" should exist
    And ".tw-list__row[data-tw-list-row=1] .flex-icon[title=show]" "css_element" should exist
    And "up" "link" should exist
    And ".tw-list__row[data-tw-list-row=1] .flex-icon[title=down]" "css_element" should exist
    And ".tw-list__row[data-tw-list-row=1] .flex-icon[title=View children]" "css_element" should not exist

    # row 2
    And "tw-list__select_2" "checkbox" should exist
    And "Two action icons" "link" should exist
    And ".tw-list__row[data-tw-list-row=2] .flex-icon[title=hide]" "css_element" should exist
    And ".tw-list__row[data-tw-list-row=2] .flex-icon[title=delete]" "css_element" should exist
    And ".tw-list__row[data-tw-list-row=2] .tw-list__cell_hierarchy_btn" "css_element" should not exist

    # row 3
    And "tw-list__select_3" "checkbox" should exist
    And "Standard row without actions and hierarchy" "link" should exist
    And ".tw-list__row[data-tw-list-row=3] .tw-list__cell_action_btn" "css_element" should not exist
    And ".tw-list__row[data-tw-list-row=3] .tw-list__cell_hierarchy_btn" "css_element" should not exist

    # row 4
    And "tw-list__select_4" "checkbox" should exist
    And "Row with hierarchy enabled" "link" should exist
    And ".tw-list__row[data-tw-list-row=4] .tw-list__cell_action_btn" "css_element" should not exist
    And ".tw-list__row[data-tw-list-row=4] .tw-list__cell_hierarchy_btn" "css_element" should exist

    # row 5
    And "tw-list__select_5" "checkbox" should exist
    And "Row with text action link" "link" should exist
    And "link" "link" should exist
    And ".tw-list__row[data-tw-list-row=5] .tw-list__cell_hierarchy_btn" "css_element" should not exist

    # row 6
    And "tw-list__select_6" "checkbox" should exist
    And "Row with hidden action icon" "link" should exist
    And ".tw-list__row[data-tw-list-row=6] .tw-list__cell_action_btn_hidden" "css_element" should exist
    And ".tw-list__row[data-tw-list-row=6] .tw-list__cell_hierarchy_btn" "css_element" should not exist

    # row 7
    And "tw-list__select_7" "checkbox" should exist
    And I should see "Non-expandable row"
    And "Non-expandable row" "link" should not exist
    And ".tw-list__row[data-tw-list-row=7] .tw-list__cell_action_btn" "css_element" should not exist
    And ".tw-list__row[data-tw-list-row=7] .tw-list__cell_hierarchy_btn" "css_element" should not exist

    # row 8
    And "tw-list__select_8" "checkbox" should exist
    And "Row with disabled action icon" "link" should exist
    And ".tw-list__row[data-tw-list-row=8] .tw-list__cell_action_btn" "css_element" should exist
    And ".tw-list__row[data-tw-list-row=8] .tw-list__cell_hierarchy_btn" "css_element" should exist

  Scenario: The list shows correctly without checkboxes, actions and hierarchy
    When I navigate to the "lists" fixture in the "totara/competency" plugin with the following settings
      | selectable | 0 |
    Then "Select all" "checkbox" should not exist
    And "Select" "checkbox" should not exist
    When I navigate to the "lists" fixture in the "totara/competency" plugin with the following settings
      | has_hierarchy | 0 |
    Then ".tw-list__cell_hierarchy_btn" "css_element" should not exist
    When I navigate to the "lists" fixture in the "totara/competency" plugin with the following settings
      | has_actions | 0 |
    Then ".tw-list__cell_action_btn" "css_element" should not exist

  Scenario: The list events are fired correctly
    When I navigate to the "lists" fixture in the "totara/competency" plugin
    Then I should not see "Expanded item"
    # opening expanded view
    When I click on "22" "link"
    Then I wait until "[data-tw-list-expandedtarget]" "css_element" exists
    Then I should see "Expanded item"
    # closing expanded view by clicking the close icon
    When I click on "[data-tw-list-expandedclose]" "css_element"
    Then I should not see "Expanded item"
    # opening expanded view
    When I click on "22" "link"
    Then I wait until "[data-tw-list-expandedtarget]" "css_element" exists
    Then I should see "Expanded item"
      # closing expanded view by clicking the link again
    When I click on "22" "link"
    Then I should not see "Expanded item"
    # opening expanded view on name link
    When I click on "Row with hierarchy enabled" "link"
    Then I wait until "[data-tw-list-expandedtarget]" "css_element" exists
    Then I should see "Expanded item"
    # Event on hierarchy icon
    When I click on ".tw-list__row[data-tw-list-row=4] .tw-list__cell_hierarchy_btn" "css_element"
    Then I should see "hierarchy icon clicked for row with id '4'"
    # Event on hierarchy icon
    When I click on ".tw-list__row[data-tw-list-row=4] .tw-list__cell_hierarchy_btn" "css_element"
    Then I should see "hierarchy icon clicked for row with id '4'"
    # Event on action icon
    When I click on ".tw-list__row[data-tw-list-row=1] .flex-icon[title=show]" "css_element"
    Then I should see "'show' action clicked for row with id '1'"
    # Event on action link
    When I click on "a[data-tw-list-actiontrigger=link]" "css_element"
    Then I should see "'link' action clicked for row with id '5'"
    # Event on delete action
    Given I should see "Two action icons"
    When I click on ".tw-list__row[data-tw-list-row=2] .flex-icon[title=delete]" "css_element"
    Then I should see "'delete' action clicked for row with id '2'"
    # Row got removed
    And I should not see "Two action icons"

  Scenario: The list checkboxes work as expected
    When I navigate to the "lists" fixture in the "totara/competency" plugin
    # Select all checkboxes
    # first row is already preselected
    Then the field "tw-list__select_1" matches value "1"
    When I click on "Select all" "checkbox"
    Then I should not see "selected row with id '1'"
    And I should see "selected row with id '3'"
    And I should see "selected row with id '4'"
    And I should see "selected row with id '5'"
    And I should not see "selected row with id '6'"
    And I should see "selected row with id '7'"
    And I should see "selected row with id '8'"
    And the following fields match these values:
      | tw-list__selectAll | 1 |
      | tw-list__select_1 | 1 |
      | tw-list__select_3 | 1 |
      | tw-list__select_4 | 1 |
      | tw-list__select_5 | 1 |
      | tw-list__select_6 | 0 |
      | tw-list__select_7 | 1 |
      | tw-list__select_8 | 1 |
    # Deselect all checkboxes
    When I click on "Select all" "checkbox"
    Then I should see "deselected row with id '1'"
    And I should see "deselected row with id '3'"
    And I should see "deselected row with id '4'"
    And I should see "deselected row with id '5'"
    And I should not see "deselected row with id '6'"
    And I should see "deselected row with id '7'"
    And I should see "deselected row with id '8'"
    And the following fields match these values:
      | tw-list__selectAll | 0 |
      | tw-list__select_1 | 0 |
      | tw-list__select_3 | 0 |
      | tw-list__select_4 | 0 |
      | tw-list__select_5 | 0 |
      | tw-list__select_6 | 0 |
      | tw-list__select_7 | 0 |
      | tw-list__select_8 | 0 |
