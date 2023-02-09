@totara @perform @mod_perform @javascript @vuejs
Feature: Managing an activity with multiple sections

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name             | create_section | create_track | activity_status |
      | Participant set up test   | true           | true         | Draft           |
      | Multiple section Activity | true           | true         | Draft           |
      | Multiple section Activity2| true           | true         | Draft           |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name             | section_name |
      | Multiple section Activity | Section B    |
    And I log in as "admin"
    And I navigate to the manage perform activities page

  Scenario: Manage participants for an activity with multiple sections.
    # Note already in multi-section mode because the generator created multiple sections.
    When I navigate to the edit perform activities page for activity "Multiple section Activity"
    And I click on "button[aria-label='Edit section']" "css_element" in the "1" activity section
    Then the focused element is "Section title" "field"
    When I click the add responding participant button in "1" activity section
    Then the following fields match these values:
      | Subject   | 0 |
      | Manager   | 0 |
      | Appraiser | 0 |
    And I click on the "Subject" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(1) .tui-popoverFrame__content" "css_element"
    And I click on the "Appraiser" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(1) .tui-popoverFrame__content" "css_element"
    And I click on "Done" "button" in the ".tui-popoverPositioner" "css_element" of the "1" activity section
    And I click on ".tui-performActivitySectionRelationship:nth-of-type(2) .tui-checkbox__label" "css_element" in the "1" activity section
    And I click on "Done" "button" in the ".tui-formBtnGroup" "css_element" of the "1" activity section
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast

    # Edit relationships for second section.
    When I click on ".tui-performActivitySection__action-edit" "css_element" in the "2" activity section
    Then the focused element is "Section title" "field"
    When I click the add responding participant button in "2" activity section
    And I click on the "Manager" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(2) .tui-popoverFrame__content" "css_element"
    And I click on the "Appraiser" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(2) .tui-popoverFrame__content" "css_element"
    And I click on "Done" "button" in the ".tui-popoverPositioner" "css_element" of the "2" activity section
    And I click on ".tui-performActivitySectionRelationship:nth-child(2) .tui-checkbox__label" "css_element" in the "2" activity section
    And I click on "Done" "button" in the ".tui-formBtnGroup" "css_element" of the "2" activity section
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast

    # Subject added to first section and not second.
    And I should see "Subject" in the "1" activity section
    And I should not see "Subject" in the "2" activity section
    # Manager added to second section and not first.
    And I should see "Manager" in the "2" activity section
    And I should not see "Manager" in the "1" activity section
    # Appraiser is available for both sections
    And I should see "Appraiser*" in the "1" activity section
    And I should see "Appraiser*" in the "2" activity section

  Scenario: Manage activity - toggle multi-section states
    When I navigate to the edit perform activities page for activity "Participant set up test"
    And I click on the "Multiple sections" tui toggle button
    Then I should see "All existing content will be grouped into the first section, along with the existing participant settings" in the tui modal
    And I confirm the tui confirmation modal
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I should see "Done"
    And I should see "Cancel"

    # Activity section in read-only mode
    When I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element"
    Then I should see "(*Can view others' responses)"

    When I click on "button[aria-label='Edit section']" "css_element" in the "1" activity section
    And I set the title of activity section "1" to "Section #1"
    And I click the add responding participant button in "1" activity section
    And I click on the "Subject" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(1) .tui-popoverFrame__content" "css_element"
    And I click on the "Appraiser" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(1) .tui-popoverFrame__content" "css_element"
    And I click on "Done" "button" in the ".tui-popoverPositioner" "css_element" of the "1" activity section
    And I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element"
    And I close the tui notification toast
    And I navigate to manage perform activity content page of "1" activity section
    And I add a "Text: Short response" activity content element
    And I set the following fields to these values:
      | rawTitle | S1Q1 |
    And I save the activity content element
    And I close the tui notification toast
    And I follow "Content (Participant set up test)"
    Then I should see "0" in the "required" element summary of "1" activity section
    And I should see "1" in the "optional" element summary of "1" activity section
    And I should see "Subject"
    And I should see "Appraiser"
    And I should see "Section #1"

    When I click on ".tui-dropdown" "css_element" in the "1" activity section
    And I click on "Add section above" "link" in the "1" activity section
    Then the focused element is "Section title" "field"
    When I set the title of activity section "1" to "Section #2"
    And I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element"
    And I close the tui notification toast
    And I navigate to manage perform activity content page of "1" activity section
    Then I should see "At least one responding participant must be added in order to edit content elements." in the tui modal
    And I close the tui modal
    When I click on "button[aria-label='Edit section']" "css_element" in the "1" activity section
    And I click the add responding participant button in "1" activity section
    And I click on the "Subject" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(1) .tui-popoverFrame__content" "css_element"
    And I click on the "Appraiser" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(1) .tui-popoverFrame__content" "css_element"
    And I click on "Done" "button" in the ".tui-popoverPositioner" "css_element" of the "1" activity section
    And I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element"
    And I close the tui notification toast
    And I navigate to manage perform activity content page of "1" activity section
    And I add a "Text: Short response" activity content element
    And I set the following fields to these values:
      | rawTitle | S2Q1 |
    And I save the activity content element
    And I close the tui notification toast
    And I follow "Content (Participant set up test)"
    Then I should see "0" in the "required" element summary of "1" activity section
    And I should see "1" in the "optional" element summary of "1" activity section
    And I should see "Section #2"

    # multi-section off => all sections merged into 1
    When I click on the "Multiple sections" tui toggle button
    Then I should see "All sections' content will be merged and section headings removed. Participant settings will be removed. This cannot be undone." in the tui modal

    When I confirm the tui confirmation modal
    And I should see "Activity saved" in the tui success notification toast

    And I close the tui notification toast
    Then I should not see "Section #1"
    And I should not see "Section #2"
    And I should not see "(*Can view others' responses)"
    And I should not see "Subject"
    And I should not see "Manager"
    And I should not see "Appraiser"
    And I should see "0" in the "required" element summary of "1" activity section
    And I should see "2" in the "optional" element summary of "1" activity section

    When I click the add responding participant button in "1" activity section
    And I click on the "Subject" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(1) .tui-popoverFrame__content" "css_element"
    And I click on "Done" "button" in the ".tui-popoverPositioner" "css_element" of the "1" activity section

    When I navigate to manage perform activity content page of "1" activity section
    Then I should see "S2Q1"
    And I should see "S1Q1"

    # Reenable multi-section; test can save a section title without changing the title
    # for the first section to be created.
    When I follow "Content (Participant set up test)"
    And I click on the "Multiple sections" tui toggle button
    And I confirm the tui confirmation modal
    Then the focused element is "Section title" "field"
    When I close the tui notification toast
    And I remove "Subject" as a perform activity participant
    And I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element"
    And I close the tui notification toast
    Then I should see "Untitled section"
    And I should see "(*Can view others' responses)"
    And I should not see "Subject"
    And I should not see "Manager"
    And I should not see "Appraiser"
    And I should see "0" in the "required" element summary of "1" activity section
    And I should see "2" in the "optional" element summary of "1" activity section

  Scenario: Manage activity - Add section.
    When I navigate to the edit perform activities page for activity "Participant set up test"
    Then "Add section" "button" should not be visible
    When I click on the "Multiple sections" tui toggle button
    Then I should see "All existing content will be grouped into the first section, along with the existing participant settings" in the tui modal
    When I confirm the tui confirmation modal
    And I close the tui notification toast
    # Only one section should be there
    Then activity section "1" should exist
    And activity section "2" should not exist
    And "Add section" "button" should be visible
    When I click on "Add section" "button"
    # Now the second one exists and is in edit mode - form is displayed
    Then activity section "1" should exist
    And activity section "2" should exist
    And the focused element is ".tui-performActivitySection:nth-of-type(2) [aria-label='Section title']" "css_element"
    # Reload page
    When I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "1" activity section
    And I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "2" activity section
    And I navigate to the edit perform activities page for activity "Participant set up test"
    Then "Add section" "button" should be visible
    # Titles should be displayed
    And I should see "Untitled section" in the "1" activity section
    And I should see "Untitled section" in the "2" activity section
    # read-only mode - no form displayed
    And "Section title" "field" in the "1" activity section should not exist
    And "Section title" "field" in the "2" activity section should not exist
    When I click on "Add section" "button"
    # Now the third one exists and is in edit mode - form is displayed
    Then activity section "1" should exist
    And activity section "2" should exist
    And activity section "3" should exist
    And "Section title" "field" in the "1" activity section should not exist
    And "Section title" "field" in the "2" activity section should not exist
    And "Section title" "field" in the "3" activity section should exist
    And the focused element is "Section title" "field"
    # Titles should be displayed
    And I should see "Untitled section" in the "1" activity section
    And I should see "Untitled section" in the "2" activity section
    And I should not see "Untitled section" in the "3" activity section
    When I click on "Add section" "button"
    # Now the fourth one exists and is in edit mode - form is displayed
    Then activity section "1" should exist
    And activity section "2" should exist
    And activity section "3" should exist
    And activity section "4" should exist
    And "Section title" "field" in the "1" activity section should not exist
    And "Section title" "field" in the "2" activity section should not exist
    And "Section title" "field" in the "3" activity section should exist
    And "Section title" "field" in the "4" activity section should exist
    And "Add section" "button" should be visible
    # Titles should be displayed
    And I should see "Untitled section" in the "1" activity section
    And I should see "Untitled section" in the "2" activity section
    And I should not see "Untitled section" in the "3" activity section
    And I should not see "Untitled section" in the "4" activity section

    Then I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "3" activity section
    And I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "4" activity section

  Scenario: Manage activity - Add section above and below
    When I navigate to the edit perform activities page for activity "Participant set up test"
    Then "Add section" "button" should not be visible
    When I click on the "Multiple sections" tui toggle button
    Then I should see "All existing content will be grouped into the first section, along with the existing participant settings" in the tui modal
    When I confirm the tui confirmation modal
    And I close the tui notification toast
    # Let's add two more sections
    When I click on "Add section" "button"
    And I click on "Add section" "button"
    Then activity section "1" should exist
    And activity section "2" should exist
    And activity section "3" should exist
    # Reload to make sure we have readonly mode for all
    When I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "1" activity section
    And I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "2" activity section
    And I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "3" activity section
    And I navigate to the edit perform activities page for activity "Participant set up test"
    And I click on ".tui-dropdown" "css_element" in the "1" activity section
    And I click on "Add section above" "link" in the "1" activity section
    # One more section is there
    # The first is now editable which we take as proof that it was added above the previous first one
    Then activity section "4" should exist
    And the focused element is ".tui-performActivitySection:nth-of-type(1) [aria-label='Section title']" "css_element"
    And "Section title" "field" in the "1" activity section should exist
    When I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "1" activity section
    And I click on ".tui-dropdown" "css_element" in the "2" activity section
    And I click on "Add section below" "link" in the "2" activity section
    # One more section is there
    # The third is now editable which we take as proof that it was added below the previous second
    Then activity section "5" should exist
    And the focused element is ".tui-performActivitySection:nth-of-type(3) [aria-label='Section title']" "css_element"
    And "Section title" "field" in the "3" activity section should exist
    When I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "3" activity section
    # The last section should not have two dropdown options
    And I click on ".tui-dropdown" "css_element" in the "5" activity section
    Then "Add section above" "link" in the "5" activity section should exist
    Then "Add section below" "link" in the "5" activity section should not exist
    # Reload and check whether all added sections are there
    When I navigate to the edit perform activities page for activity "Participant set up test"
    Then activity section "1" should exist
    And activity section "2" should exist
    And activity section "3" should exist
    And activity section "4" should exist
    And activity section "5" should exist

  Scenario: Manage activity - Edit the title for sections
    Given the multi-language content filter is enabled
    And I navigate to the edit perform activities page for activity "Participant set up test"
    And I click on the "Multiple sections" tui toggle button
    Then I should see "All existing content will be grouped into the first section, along with the existing participant settings" in the tui modal
    And I confirm the tui confirmation modal
    And I wait until the page is ready

    # Test can save a section title without changing the title if it is the first section to be created
    When I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "1" activity section
    And I wait until the page is ready
    Then I should see "Activity saved" in the tui success notification toast
    And I should see "Untitled section" in the "1" activity section
    When I reload the page
    Then I should see "Untitled section" in the "1" activity section

    # Test multi-lang filter
    When I click on "Add section" "button"
    And I set the title of activity section "2" to '<span lang="en" class="multilang">English</span><span lang="de" class="multilang">German</span> Title'
    And I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "2" activity section
    And I wait until the page is ready
    Then I should see "Activity saved" in the tui success notification toast
    And I should see "English Title" in the "2" activity section
    When I reload the page
    Then I should see "Untitled section" in the "1" activity section
    And I should see "English Title" in the "2" activity section

    # Done button should be disabled unless there have been changes
    When I click on "button[aria-label='Edit section']" "css_element" in the "1" activity section
    And I wait for pending js
    Then "button.tui-formBtn.tui-formBtn--prim[disabled]" "css_element" in the "1" activity section should exist
    And "button.tui-formBtn:not(.tui-formBtn--prim)[disabled]" "css_element" in the "1" activity section should not exist
    When I set the title of activity section "1" to "The First Section!"
    Then "button.tui-formBtn.tui-formBtn--prim[disabled]" "css_element" in the "1" activity section should not exist
    And "button.tui-formBtn:not(.tui-formBtn--prim)[disabled]" "css_element" in the "1" activity section should not exist
    When I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "1" activity section
    And I wait until the page is ready
    And I click on "button[aria-label='Edit section']" "css_element" in the "1" activity section
    Then "button.tui-formBtn.tui-formBtn--prim[disabled]" "css_element" in the "1" activity section should exist
    And "button.tui-formBtn:not(.tui-formBtn--prim)[disabled]" "css_element" in the "1" activity section should not exist
    When I set the title of activity section "1" to ""
    Then "button.tui-formBtn.tui-formBtn--prim[disabled]" "css_element" in the "1" activity section should not exist
    And "button.tui-formBtn:not(.tui-formBtn--prim)[disabled]" "css_element" in the "1" activity section should not exist
    When I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "1" activity section
    And I wait until the page is ready
    And I reload the page
    Then I should see "The First Section!" in the "1" activity section
    And I should see "English Title" in the "2" activity section

  Scenario: Delete a section
    When I navigate to the edit perform activities page for activity "Multiple section Activity2"
    Then "Add section" "button" should not be visible
    When I click on the "Multiple sections" tui toggle button
    Then I should see "All existing content will be grouped into the first section, along with the existing participant settings" in the tui modal
    When I confirm the tui confirmation modal
    And I close the tui notification toast
    # Add one more section
    When I click on "Add section" "button"
    Then activity section "1" should exist
    And activity section "2" should exist
    When I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "1" activity section
    And I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "2" activity section
    # add section title
    When I click on "button[aria-label='Edit section']" "css_element" in the "1" activity section
    And I set the title of activity section "1" to "The first section"
    And I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "1" activity section
    When I click on "button[aria-label='Edit section']" "css_element" in the "2" activity section
    And I set the title of activity section "2" to "The second section"
    And I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "2" activity section
    Then I should see "The first section" in the "1" activity section
    And I should see "The second section" in the "2" activity section
    # delete a section
    When I click on ".tui-dropdown" "css_element" in the "1" activity section
    And I click on "Delete" "link" in the "1" activity section
    Then I should see "This will also delete all content elements contained in this section" in the tui modal
    When I confirm the tui confirmation modal
    And I close the tui notification toast
    # check section is deleted
    Then activity section "2" should not exist
    # check the correct section is deleted
    And I should see "The second section" in the "1" activity section
    # There should not have a Delete link if only has one section
    When I click on ".tui-dropdown" "css_element" in the "1" activity section
    Then "Delete" "link" in the "1" activity section should not exist