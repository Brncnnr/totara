@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Manage performance activity redisplay element.

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name        | description           | activity_type | create_track | create_section | activity_status | anonymous_responses |
      | First Activity       | My First description  | check-in      | true         | false          | Draft           | true                |
      | Second Activity      | My Second description | check-in      | true         | false          | Draft           | false               |
      | Redisplay Activity   | My Third description  | check-in      | true         | true           | Draft           | false               |
      | Redisplay Activity-2 | My Fourth description | check-in      | true         | true           | Draft           | false               |
    And the following "activity settings" exist in "mod_perform" plugin:
      | activity_name   | multisection |
      | First Activity  | yes          |
      | Second Activity | yes          |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name      | section_name |
      | First Activity     | section 1-1  |
      | First Activity     | section 1-2  |
      | Second Activity    | section 2-1  |
      | Second Activity    | section 2-2  |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship | can_view | can_answer |
      | section 1-1  | subject      | yes      | no         |
      | section 1-1  | manager      | yes      | yes        |
      | section 1-2  | subject      | yes      | yes        |
      | section 1-2  | manager      | yes      | yes        |
      | section 2-1  | peer         | yes      | no         |
      | section 2-1  | appraiser    | yes      | no         |
      | section 2-2  | peer         | yes      | yes        |
      | section 2-2  | mentor       | yes      | yes        |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name | element_name | title                   |
      | section 1-1  | short_text   | 1-1 Favourite position? |
      | section 1-1  | long_text    | 1-1 Describe position?  |
      | section 1-2  | short_text   | 1-2 Best position?      |
      | section 1-2  | long_text    | 1-2 Explain position?   |
      | section 2-1  | short_text   | 2-1 Favourite job?      |
      | section 2-1  | long_text    | 2-1 Describe job?       |
      | section 2-2  | short_text   | 2-2 Best job?           |
      | section 2-2  | long_text    | 2-2 Explain job?        |

  Scenario: I can create & update a redisplay perform element.
    Given I log in as "admin"
    When I navigate to the edit perform activities page for activity "Redisplay Activity"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I click on "Edit content elements" "link_or_button"
    And I add a "Response redisplay" activity content element
    When I set the following fields to these values:
      | rawTitle   | Review what you did in the past |
      | activityId | First Activity (Draft)          |
    And I wait for pending js
    And I set the following fields to these values:
      | sourceSectionElementId   | 1-1 Favourite position? (Text: Short response) |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast
    And I wait for pending js
    And I should see "Review what you did in the past"
    And I should see "First Activity"
    And I should see "1-1 Favourite position?"
    And I should see "{Anonymous responses}"

    And I add a "Response redisplay" activity content element
    When I set the following fields to these values:
      | rawTitle   | Discussing previous job duties   |
      | activityId | Second Activity (Draft)          |
    And I wait for pending js
    And I set the following fields to these values:
      | sourceSectionElementId | 2-1 Favourite job? (Text: Short response) |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast
    And I wait for pending js
    And I should see "Discussing previous job duties"
    And I should see "Second Activity"
    And I should see "2-1 Favourite job?"
    And I should see "{No responding relationships added yet}"

    And I add a "Response redisplay" activity content element
    When I set the following fields to these values:
      | rawTitle   | Discussing best job duties |
      | activityId | Second Activity (Draft)    |
    And I wait for pending js
    And I set the following fields to these values:
      | sourceSectionElementId | 2-2 Best job? (Text: Short response) |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast
    And I wait for pending js
    And I should see "Discussing best job duties"
    And I should see "Second Activity"
    And I should see "2-2 Best job?"
    And I should see "{Responses from: Peer, Mentor}"

  Scenario: Removing a redisplay reference source is possible when deleting an activity but not when deleting a section or element
    # Assign an audience so we can activate the activity
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user      | 1        | user1@example.com |
      | user2    | user      | 2        | user2@example.com |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | aud1     |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | aud1   |
      | user2 | aud1   |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name        | track_description |
      | First Activity       | track 1           |
      | Redisplay Activity-2 | track 2           |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 1           | cohort          | aud1            |
      | track 2           | cohort          | aud1            |

    When I log in as "admin"
    And I navigate to the edit perform activities page for activity "Redisplay Activity"
    # Add section title
    And I click on the "Multiple sections" tui toggle button
    And I confirm the tui confirmation modal
    And I wait until the page is ready
    And I set the title of activity section "1" to "The first section"
    And I click the add responding participant button in "1" activity section
    And I click on the "Subject" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(1) .tui-popoverFrame__content" "css_element"
    And I click on "Done" "button" in the ".tui-popoverPositioner" "css_element" of the "1" activity section
    And I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "1" activity section
    # Add redisplay element
    And I click on "Edit content elements" "link_or_button"
    And I add a "Response redisplay" activity content element
    And I set the following fields to these values:
      | rawTitle   | Review what you did in the past |
      | activityId | First Activity (Draft)          |
    And I wait for pending js
    And I set the following fields to these values:
      | sourceSectionElementId   | 1-1 Favourite position? (Text: Short response) |
    And I save the activity content element

    # Add redisplay element in second activity
    And I navigate to the edit perform activities page for activity "Redisplay Activity-2"
    # Add section title
    And I click on the "Multiple sections" tui toggle button
    And I confirm the tui confirmation modal
    And I wait until the page is ready
    And I set the title of activity section "1" to "The second section"
    # Also add a relationship so we can activate this activity later
    And I click the add responding participant button in "1" activity section
    And I click on the "Subject" tui checkbox in the ".tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(1) .tui-popoverFrame__content" "css_element"
    And I click on "Done" "button" in the ".tui-popoverPositioner" "css_element" of the "1" activity section
    And I click on "Done" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "1" activity section
    # Add redisplay element
    And I click on "Edit content elements" "link_or_button"
    And I add a "Response redisplay" activity content element
    And I set the following fields to these values:
      | rawTitle   | Review what you did in the past |
      | activityId | First Activity (Draft)          |
    And I wait for pending js
    And I set the following fields to these values:
      | sourceSectionElementId   | 1-1 Favourite position? (Text: Short response) |
    And I save the activity content element
    # Also add a respondable question so we can activate this activity later
    And I add a "Text: Short response" activity content element
    And I set the following fields to these values:
      | rawTitle | dummy question |
    And I save the activity content element

    # Try to delete section
    When I navigate to the edit perform activities page for activity "First Activity"
    When I click on ".tui-dropdown" "css_element" in the "1" activity section
    And I click on "Delete" "link" in the "1" activity section
    Then I should see "Cannot delete section" in the tui modal
    And I should see "This section cannot be deleted, because it contains questions that are being referenced by other elements:" in the tui modal
    And I should see "Redisplay Activity" in the tui modal
    And I should see "Redisplay Activity-2" in the tui modal
    And I should see "The first section" in the tui modal
    And I should see "The second section" in the tui modal

    # Try to delete element
    When I close the tui modal
    And I click on "Edit content elements" "link_or_button"
    And I click on the Actions button for question "1-1 Favourite position?"
    And I click on "Delete" option in the dropdown menu
    Then I should see "Cannot delete question element" in the tui modal
    And I should see "This question cannot be deleted, because it is being referenced by other elements:" in the tui modal
    And I should see "Redisplay Activity" in the tui modal
    And I should see "Redisplay Activity-2" in the tui modal
    And I should see "The first section" in the tui modal
    And I should see "The second section" in the tui modal

    # Try to delete draft activity - it should be possible but a warning must be displayed
    When I navigate to the manage perform activities page
    And I open the dropdown menu in the tui datatable row with "First Activity" "Name"
    And I click on "Delete" "link" in the ".tui-dataTableRow:nth-child(4)" "css_element"
    Then I should see "Confirm draft activity deletion" in the tui modal
    And I should see "This activity contains questions that are being referenced by other elements:" in the tui modal
    And I should see "Redisplay Activity" in the tui modal
    And I should see "Redisplay Activity-2" in the tui modal
    And I should see "The first section" in the tui modal
    And I should see "The second section" in the tui modal

    # Cancel deletion and activate the activity
    When I press "Cancel"
    And I open the dropdown menu in the tui datatable row with "First Activity" "Name"
    And I click on "Activate" "link" in the ".tui-dataTableRow:nth-child(4)" "css_element"
    And I press "Activate"
    Then I should see "was successfully activated." in the tui success notification toast

    # Delete the active activity - the same warning should be displayed
    When I open the dropdown menu in the tui datatable row with "First Activity" "Name"
    And I click on "Delete" "link" in the ".tui-dataTableRow:nth-child(4)" "css_element"
    Then I should see "Confirm activity deletion" in the tui modal
    And I should see "This activity contains questions that are being referenced by other elements:" in the tui modal
    And I should see "Redisplay Activity" in the tui modal
    And I should see "Redisplay Activity-2" in the tui modal
    And I should see "The first section" in the tui modal
    And I should see "The second section" in the tui modal

    When I press "Delete"
    Then I should see "Activity and all associated user records successfully deleted." in the tui success notification toast
    And I should see "3" rows in the tui datatable
    And I should see the tui datatable contains:
      | Name                 | Type     | Status |
      | Redisplay Activity-2 | Check-in | Draft  |
      | Redisplay Activity   | Check-in | Draft  |
      | Second Activity      | Check-in | Draft  |

    # Check the message in the redisplay question that now has a reference pointing nowhere
    When I navigate to the edit perform activities page for activity "Redisplay Activity"
    And I click on "Edit content elements" "link_or_button"
    Then I should see "Review what you did in the past"
    And I should see "The activity containing the question that is being referenced by this response redisplay no longer exists."

    # Make sure we can still fix the redisplay question by editing it
    When I click on "Edit element: Review what you did in the past" "button"
    And I set the following fields to these values:
      | rawTitle   | Review what you did in the past - modified|
      | activityId | Second Activity (Draft)                   |
    And I wait for pending js
    And I set the following fields to these values:
      | sourceSectionElementId   | 2-1 Favourite job? (Text: Short response) |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast
    And I wait for pending js
    And I should see "Review what you did in the past - modified"
    And I should see "Second Activity"
    And I should see "2-1 Favourite job?"

    # Activate 'Redisplay Activity-2' and check the dead reference for it
    When I navigate to the manage perform activities page
    And I open the dropdown menu in the tui datatable row with "Redisplay Activity-2" "Name"
    And I click on "Activate" "link" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    And I press "Activate"
    Then I should see "was successfully activated." in the tui success notification toast
    When I navigate to the edit perform activities page for activity "Redisplay Activity-2"
    And I click on "View content elements" "link_or_button"
    Then I should see "Review what you did in the past"
    And I should see "The activity containing the question that is being referenced by this response redisplay no longer exists."
    When I click on "Element settings: Review what you did in the past" "button"
    Then I should see "Review what you did in the past"
    And I should see "The activity containing the question that is being referenced by this response redisplay no longer exists."

    # Check the dead reference from participant view
    When I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"
    And I log out
    And I log in as "user1"
    And I navigate to the outstanding perform activities list page
    And I click on "Redisplay Activity-2" "link"
    Then I should see "Review what you did in the past"
    And I should see "The activity containing the question that is being referenced by this response redisplay no longer exists."

    # Check print view
    When I navigate to the "print" user activity page for performance activity "Redisplay Activity-2" where "user1" is the subject and "user1" is the participant
    Then I should see "Review what you did in the past"
    And I should see "The activity containing the question that is being referenced by this response redisplay no longer exists."