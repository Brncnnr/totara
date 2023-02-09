@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Adding, Updating, Removing activity elements.

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name        | create_section | create_track | activity_status |
      | Add Element Activity | true           | true         | Draft           |

  Scenario: Save multiple elements to activity content.
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    # Add multiple elements
    When I click on "Add Element Activity" "link"
    And I click on "Content" "link" in the ".tui-tabs__tabs" "css_element"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I click on "Edit content elements" "link_or_button"
    And I add a "Text: Short response" activity content element
    Then the focused element is "[name=rawTitle]" "css_element"

    When I set the following fields to these values:
      | rawTitle   | Question 1   |
      | identifier | Identifier 1 |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast
    When I close the tui notification toast
    And I add a "Text: Short response" activity content element
    Then the focused element is "[name=rawTitle]" "css_element"

    When I set the following fields to these values:
      | rawTitle   | Question 2   |
      | identifier | Identifier 2 |
    And I save the activity content element
    And I add a "Text: Short response" activity content element
    Then the focused element is "[name=rawTitle]" "css_element"

    When I set the following fields to these values:
      | rawTitle | Question 3 |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast
    When I close the tui notification toast
    And I follow "Content (Add Element Activity)"
    And I click on "Edit content elements" "link_or_button"
    Then I should see "Question 1"
    And I should see "Question 2"
    And I should see "Question 3"
    And I should see "Identifier 1" in the "Question 1" tui "card"
    And I should not see "Identifier" in the "Question 3" tui "card"

    # Update multiple elements and save.
    When I click on the Edit element button for question "Question 1"
    Then the focused element is "[name=rawTitle]" "css_element"

    And the following fields match these values:
      | rawTitle   | Question 1   |
      | identifier | Identifier 1 |
    When I set the following fields to these values:
      | rawTitle   | Test 1       |
      | identifier | Identifier A |
    And I save the activity content element
    And I click on the Edit element button for question "Question 2"
    Then the focused element is "[name=rawTitle]" "css_element"

    When I set the following fields to these values:
      | rawTitle   | Test 2 |
      | identifier |        |
    And I save the activity content element
    And I click on the Edit element button for question "Question 3"
    Then the focused element is "[name=rawTitle]" "css_element"

    When I set the following fields to these values:
      | rawTitle   | Test 3       |
      | identifier | Identifier C |
    And I save the activity content element
    And I close the tui notification toast
    And I follow "Content (Add Element Activity)"
    And I click on "Edit content elements" "link_or_button"
    Then I should see "Test 1"
    And I should see "Test 2"
    And I should see "Test 3"
    And I should see "Identifier A" in the "Test 1" tui "card"
    And I should not see "Identifier" in the "Test 2" tui "card"

    # 'Move' option should not be shown for single section activity.
    When I click on the Actions button for question "Test 1"
    Then I should not see "Move to another section" option in the dropdown menu

    # Deletion confirmation modal.
    When I click on "Delete" option in the dropdown menu
    Then I should see "Confirm delete element" in the tui modal
    And I should see "This cannot be undone." in the tui modal
    When I close the tui modal
    Then I should not see "Element deleted."
    And I click on the Actions button for question "Test 1"
    And I click on "Delete" option in the dropdown menu
    And I confirm the tui confirmation modal
    Then I should see "Element deleted." in the tui success notification toast
    And I close the tui notification toast

    # Unsaved changes dialog should not be triggered
    And I follow "Content (Add Element Activity)"
    And I click on "Edit content elements" "link_or_button"
    Then I should not see "Test 1"
    And I should see "Test 2"
    And I should see "Test 3"

    # Delete using icon when not in edit mode
    When I click on the Actions button for question "Test 2"
    And I click on "Delete" option in the dropdown menu
    Then I should see "Confirm delete element" in the tui modal
    And I should see "This cannot be undone." in the tui modal
    And I confirm the tui confirmation modal
    Then I should see "Element deleted." in the tui success notification toast
    And I close the tui notification toast

    # Only one element should remain
    Then I should see "Test 3"
    And I should not see "Test 1"
    And I should not see "Test 2"

    # Changes should be permanent
    When I follow "Content (Add Element Activity)"
    And I click on "Edit content elements" "link_or_button"
    Then I should see "Test 3"
    And I should not see "Test 1"
    And I should not see "Test 2"

  Scenario: Reorder elements in a section
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    # Add multiple elements
    When I click on "Add Element Activity" "link"
    And I click on "Content" "link" in the ".tui-tabs__tabs" "css_element"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I click on "Edit content elements" "link_or_button"
    And I add a "Text: Short response" activity content element
    Then the focused element is "[name=rawTitle]" "css_element"
    When I set the following fields to these values:
      | rawTitle   | Question 1   |
      | identifier | Identifier 1 |
    And I save the activity content element
    Then I should not see drag icon visible in the question "Question 1"

    When I add a "Text: Short response" activity content element
    And the focused element is "[name=rawTitle]" "css_element"
    And I set the following fields to these values:
      | rawTitle   | Question 2   |
      | identifier | Identifier 2 |
    And I save the activity content element
    Then I should see drag icon visible in the question "Question 1"
    And I should see drag icon visible in the question "Question 2"

    When I add a "Text: Short response" activity content element
    And the focused element is "[name=rawTitle]" "css_element"

    And I set the following fields to these values:
      | rawTitle | Question 3 |
    And I save the activity content element
    Then I should see drag icon visible in the question "Question 1"
    And I should see drag icon visible in the question "Question 2"
    And I should see drag icon visible in the question "Question 3"

  Scenario: Can not move element to another section if only one section
    Given the following "activity sections" exist in "mod_perform" plugin:
      | activity_name        | section_name |
      | Add Element Activity | Section B    |
    When I log in as "admin"
    And I navigate to the edit perform activities page for activity "Add Element Activity"
    And I click on ".tui-dropdown" "css_element" in the "1" activity section
    And I click on "Delete" "link" in the "1" activity section
    And I confirm the tui confirmation modal
    When I click on "button[aria-label='Edit section']" "css_element" in the "1" activity section
    And I click the add responding participant button in "1" activity section
    And I click on the "Subject" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(1) .tui-popoverFrame__content" "css_element"
    And I click on "Done" "button" in the ".tui-popoverPositioner" "css_element" of the "1" activity section
    And I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element"
    And I close the tui notification toast
    And I navigate to manage perform activity content page of "1" activity section

    And I add a "Text: Short response" activity content element
    And I set the following fields to these values:
      | rawTitle | SectionB-Question1 |
    And I save the activity content element
    And I close the tui notification toast

    And I click on the Actions button for question "SectionB-Question1"
    Then I should not see "Move to another section"

  Scenario: Move element to another section
    # Note: adding another section will make the generator activate multi-section mode.
    Given the following "activity sections" exist in "mod_perform" plugin:
      | activity_name        | section_name |
      | Add Element Activity | Section B    |
      | Add Element Activity | Section C    |
    When I log in as "admin"
    And I navigate to the edit perform activities page for activity "Add Element Activity"
    When I click on "button[aria-label='Edit section']" "css_element" in the "1" activity section
    And I click the add responding participant button in "1" activity section
    And I click on the "Subject" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(1) .tui-popoverFrame__content" "css_element"
    And I click on "Done" "button" in the ".tui-popoverPositioner" "css_element" of the "1" activity section
    And I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element"
    And I close the tui notification toast
    And I navigate to manage perform activity content page of "1" activity section

    # Add two question elements.
    And I add a "Text: Short response" activity content element
    And I set the following fields to these values:
      | rawTitle | SectionB-Question1 |
    And I save the activity content element
    And I close the tui notification toast
    And I add a "Text: Short response" activity content element
    And I set the following fields to these values:
      | rawTitle | SectionB-Question2 |
    And I save the activity content element
    And I close the tui notification toast

    # Move one question element.
    And I click on the Actions button for question "SectionB-Question1"
    And I click on "Move to another section" option in the dropdown menu
    Then I should see "Move element to another section" in the tui modal
    And I should see "It will be added as the final element in the section it moves to." in the tui modal
    And I should see "Move from" in the tui modal
    And I should see "Untitled section" in the tui modal
    And the "Move to" select box should contain "Section B"
    And the "Move to" select box should contain "Section C"
    And the "Move to" select box should not contain "Untitled section"
    When I set the field "Move to" to "Section B"
    And I click on "Move" "button" in the ".tui-modal" "css_element"
    Then I should see "Element moved successfully" in the tui success notification toast
    When I close the tui notification toast
    Then I should not see "SectionB-Question1"
    And I should see "SectionB-Question2"

    # Go to the target section and make sure the question element is showing up there now.
    When I navigate to the edit perform activities page for activity "Add Element Activity"

    Then I click on "button[aria-label='Edit section']" "css_element" in the "2" activity section
    And I click the add responding participant button in "2" activity section
    And I click on the "Subject" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(2) .tui-popoverFrame__content" "css_element"
    And I click on "Done" "button" in the ".tui-popoverPositioner" "css_element" of the "2" activity section
    And I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element"
    And I close the tui notification toast

    And I navigate to manage perform activity content page of "2" activity section
    Then I should see "SectionB-Question1"
    And I should not see "SectionB-Question2"
