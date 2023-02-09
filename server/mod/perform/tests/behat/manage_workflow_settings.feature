@totara @perform @mod_perform @javascript @vuejs
Feature: Manage activity workflow settings

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name    | description      | activity_type | create_track | activity_status |
      | My Test Activity | My Test Activity | feedback      | true         | Draft           |

  Scenario: Test 'On completion' setting
    # Activity not activated
    When I navigate to the manage perform activities page
    And I click on "My Test Activity" "link"
    And I click on "Content" "link"
    Then the "On completion" tui toggle switch should be "off"
    And I should see "Sections and instances will close once they have progressed to \"Complete\""
    When I click on the "On completion" tui toggle button
    When I reload the page
    Then the "On completion" tui toggle switch should be "on"

    # Activity activated
    When I manually activate the perform activity "My Test Activity"
    And I reload the page
    And I click on the "On completion" tui toggle button
    Then I should see "Only future instances and those that are not yet complete will remain open on completion. Already closed instances will remain that way."
    When I confirm the tui confirmation modal
    Then the "On completion" tui toggle switch should be "off"
    When I click on the "On completion" tui toggle button
    Then I should see "Only future instances and those that are not yet complete will be automatically closed on completion. Already completed instances will not be affected."
    When I click on "Cancel" "button" in the ".tui-modal" "css_element"
    Then the "On completion" tui toggle switch should be "off"

  Scenario: Test 'On due date' setting
    # Activity not activated
    When I navigate to the manage perform activities page
    And I click on "My Test Activity" "link"
    And I click on "Content" "link"
    Then the "On due date (no due date set)" tui toggle switch should be "off"
    And I should see "All active instances of the activity will close on the due date"
    And I should see "To enable this option you must set a due date on the \"Instance creation\" tab"
    When I click on the "On due date (no due date set)" tui toggle button
    And I reload the page
    # Should still be off as it should be disabled
    Then the "On due date (no due date set)" tui toggle switch should be "off"

    # Enable due date and ensure the 'On due date' toggle can be enabled
    And I click on "Instance creation" "link"
    Then I should not see "All active instances of the activity will close on the due date" in the ".tui-assignmentScheduleDueDate__description" "css_element"
    And the "Due date" tui toggle switch should be "off"
    Then I click on the "Due date" tui toggle button
    And the "Due date" tui toggle switch should be "on"
    And I click on "Update instance creation" "button"
    And I confirm the tui confirmation modal
    And I click on "Content" "link"
    Then I should not see "To enable this option you must set a due date on the \"Instance creation\" tab"
    And the "On due date" tui toggle switch should be "off"
    And I click on the "On due date" tui toggle button
    And I reload the page
    Then the "On due date" tui toggle switch should be "on"
    When I click on "Instance creation" "link"
    Then I should see "All active instances of the activity will close on the due date" in the ".tui-assignmentScheduleDueDate__description" "css_element"

    When the "Due date" tui toggle switch should be "on"
    And I click on the "Due date" tui toggle button
    Then I should see "Disable due date?"
    When I click on "Disable" "button" in the ".tui-modal" "css_element"
    And I click on "Update instance creation" "button"
    And I confirm the tui confirmation modal
    And I click on "Content" "link"
    Then the "On due date (no due date set)" tui toggle switch should be "off"
    And I click on "Instance creation" "link"
    And the "Due date" tui toggle switch should be "off"
    Then I click on the "Due date" tui toggle button
    And I click on "Update instance creation" "button"
    And I confirm the tui confirmation modal
    And I click on "Content" "link"

    # Activity activated
    When I manually activate the perform activity "My Test Activity"
    And I reload the page
    When I click on the "On due date" tui toggle button
    Then I should see "All open and overdue activity instances, and any activity instances created in the future, will automatically close on the activity due date."
    When I confirm the tui confirmation modal
    Then the "On due date" tui toggle switch should be "on"
    When I click on the "On due date" tui toggle button
    Then I should see "All open activity instances and any activity instances created in the future will no longer automatically close on the activity due date."
    When I click on "Cancel" "button" in the ".tui-modal" "css_element"
    Then the "On due date" tui toggle switch should be "on"