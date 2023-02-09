@totara @totara_competency @javascript @perform
Feature: Test lists_manager component

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the "lists_manager" fixture in the "totara/competency" plugin

  Scenario: The list with list manager in use is displayed correctly
    Then I should see "\totara_competency\output\lists_manager testing page"
    And I should see "Column 1"
    And I should see "Column 2"
    And I should see "Column 3"
    And I should see "Column 4"
    And "Select all" "checkbox" should exist
    And I should see "20 items"
    And I should see "order by"
    And "order by column 1 ASC" "link" should exist

    # row 1 to 5
    And "tw-list__select_11" "checkbox" should exist
    And "tw-list__select_12" "checkbox" should exist
    And "tw-list__select_13" "checkbox" should exist
    And "tw-list__select_14" "checkbox" should exist
    And "tw-list__select_15" "checkbox" should exist

    # Column 1
    And I should see "a11"
    And I should see "a12"
    And I should see "a13"
    And I should see "a14"
    And I should see "a15"
    # Column 2
    And I should see "b11"
    And I should see "b12"
    And I should see "b13"
    And I should see "b14"
    And I should see "b15"
    # Column 3
    And I should see "c11"
    And I should see "c12"
    And I should see "c13"
    And I should see "c14"
    And I should see "c15"
    # Column 4
    And I should see "d11"
    And I should see "d12"
    And I should see "d13"
    And I should see "d14"
    And I should see "d15"

    And "Load more" "link" should exist

    # Expanded view
    When I click on "a11" "link"
    Then I wait until "Expanded item for row 11" "text" exists
    When I click on "a12" "link"
    Then I wait until "Expanded item for row 12" "text" exists
    When I click on "a13" "link"
    Then I wait until "Expanded item for row 13" "text" exists
    When I click on "a14" "link"
    Then I wait until "Expanded item for row 14" "text" exists
    When I click on "a15" "link"
    Then I wait until "Expanded item for row 15" "text" exists

    # Pagination
    When I click on "Load more" "link"
    Then I should see "a21"
    Then I should see "a22"
    Then I should see "a23"
    Then I should see "a24"
    Then I should see "a25"
    When I click on "Load more" "link"
    Then I should see "a31"
    Then I should see "a32"
    Then I should see "a33"
    Then I should see "a34"
    Then I should see "a35"
    When I click on "Load more" "link"
    Then I should see "a41"
    Then I should see "a42"
    Then I should see "a43"
    Then I should see "a44"
    Then I should see "a45"

    # Last page reached
    And "Load more" "link" should not exist

    # Expanded view (rest)
    When I click on "a21" "link"
    Then I wait until "Expanded item for row 21" "text" exists
    When I click on "a22" "link"
    Then I wait until "Expanded item for row 22" "text" exists
    When I click on "a23" "link"
    Then I wait until "Expanded item for row 23" "text" exists
    When I click on "a24" "link"
    Then I wait until "Expanded item for row 24" "text" exists
    When I click on "a25" "link"
    Then I wait until "Expanded item for row 25" "text" exists

    When I click on "a31" "link"
    Then I wait until "Expanded item for row 31" "text" exists
    When I click on "a32" "link"
    Then I wait until "Expanded item for row 32" "text" exists
    When I click on "a33" "link"
    Then I wait until "Expanded item for row 33" "text" exists
    When I click on "a34" "link"
    Then I wait until "Expanded item for row 34" "text" exists
    When I click on "a35" "link"
    Then I wait until "Expanded item for row 35" "text" exists

    When I click on "a41" "link"
    Then I wait until "Expanded item for row 41" "text" exists
    When I click on "a42" "link"
    Then I wait until "Expanded item for row 42" "text" exists
    When I click on "a43" "link"
    Then I wait until "Expanded item for row 43" "text" exists
    When I click on "a44" "link"
    Then I wait until "Expanded item for row 44" "text" exists
    When I click on "a45" "link"
    Then I wait until "Expanded item for row 45" "text" exists

  Scenario: The list order can be changed
    When I click on "order by column 1 ASC" "link"
    And I click on "order by column 1 DESC" "link"
    Then I wait until "a41" "link" exists
    And I should see "a42"
    And I should see "a43"
    And I should see "a44"
    And I should see "a45"

    # Pagination
    When I click on "Load more" "link"
    Then I should see "a31"
    Then I should see "a32"
    Then I should see "a33"
    Then I should see "a34"
    Then I should see "a35"
    When I click on "Load more" "link"
    Then I should see "a21"
    Then I should see "a22"
    Then I should see "a23"
    Then I should see "a24"
    Then I should see "a25"
    When I click on "Load more" "link"
    Then I should see "a11"
    Then I should see "a12"
    Then I should see "a13"
    Then I should see "a14"
    Then I should see "a15"

    # Last page reached
    And "Load more" "link" should not exist

  Scenario: The list manager events are fired
    When I click on ".tw-list__row[data-tw-list-row=11] .flex-icon[title=Delete]" "css_element"
    Then I should see "action 'deleteClicked' for id '11' triggered"
    When I click on ".tw-list__row[data-tw-list-row=11] .flex-icon[title=Archive]" "css_element"
    Then I should see "action 'archiveClicked' for id '11' triggered"
    When I click on ".tw-list__row[data-tw-list-row=11] .flex-icon[title=Activate]" "css_element"
    Then I should see "action 'activateClicked' for id '11' triggered"
    When I click on "tw-list__select_11" "checkbox"
    Then I should see "item with id '11' selected"
    When I click on "tw-list__select_12" "checkbox"
    Then I should see "item with id '12' selected"
    When I click on "tw-list__select_13" "checkbox"
    Then I should see "item with id '13' selected"
    When I click on "tw-list__select_14" "checkbox"
    Then I should see "item with id '14' selected"
    When I click on "tw-list__select_15" "checkbox"
    Then I should see "item with id '15' selected"
    When I click on "tw-list__select_11" "checkbox"
    Then I should see "item with id '11' unselected"
    When I click on "tw-list__select_12" "checkbox"
    Then I should see "item with id '12' unselected"
    When I click on "tw-list__select_13" "checkbox"
    Then I should see "item with id '13' unselected"
    When I click on "tw-list__select_14" "checkbox"
    Then I should see "item with id '14' unselected"
    When I click on "tw-list__select_15" "checkbox"
    Then I should see "item with id '15' unselected"
    When I click on "Select all" "checkbox"
    Then I should see "item with id '11' selected"
    Then I should see "item with id '12' selected"
    Then I should see "item with id '13' selected"
    Then I should see "item with id '14' selected"
    Then I should see "item with id '15' selected"
    When I click on "Select all" "checkbox"
    Then I should see "item with id '11' unselected"
    Then I should see "item with id '12' unselected"
    Then I should see "item with id '13' unselected"
    Then I should see "item with id '14' unselected"
    Then I should see "item with id '15' unselected"
