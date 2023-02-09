@totara @perform @mod_perform
Feature: Activation of activities
  As an activity administrator
  I need to be able to activate activities
  so they can be available to users.

  Background:
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
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name           | activity_type | activity_status | create_track | create_section |
      | Active activity         | check-in      | Active          | true         | true           |
      | Empty draft activity    | appraisal     | Draft           | false        | false          |
      | Complete draft activity | feedback      | Draft           | false        | false          |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name           | section_name |
      | Complete draft activity | section 1    |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship |
      | section 1    | subject      |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name | element_name |
      | section 1    | short_text   |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name           | track_description |
      | Empty draft activity    | track 1           |
      | Complete draft activity | track 2           |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 1           | cohort          | aud1            |
      | track 2           | cohort          | aud1            |
    When I log in as "admin"

  @javascript @vuejs
  Scenario: Activating activities in the activities list
    When I navigate to the manage perform activities page
    Then I should see the tui datatable contains:
      | Name                    | Type      | Status |
      | Complete draft activity | Feedback  | Draft  |
      | Empty draft activity    | Appraisal | Draft  |
      | Active activity         | Check-in  | Active |

    When I open the dropdown menu in the tui datatable row with "Empty draft activity" "Name"
    Then I should see "Activate" option in the dropdown menu
    When I click on "Activate" option in the dropdown menu
    Then I should see "Activity cannot be activated" in the tui modal
    And I should see "Activation of this draft activity will only be possible once all of the following criteria are met:" in the tui modal
    And I should see "At least one question element added per section" in the tui modal

    When I close the tui modal
    And I close any visible tui dropdowns
    And I open the dropdown menu in the tui datatable row with "Active activity" "Name"
    Then I should not see "Activate" option in the dropdown menu

    When I close any visible tui dropdowns
    And I open the dropdown menu in the tui datatable row with "Complete draft activity" "Name"
    Then I should see "Activate" option in the dropdown menu

    When I click on "Activate" option in the dropdown menu
    Then I should see "Confirm activity activation" in the tui modal
    And I should see "2 users will be assigned on activation" in the tui modal

    When I click on "Activate" "button"
    Then I should see " was successfully activated." in the tui success notification toast
    And I should see the tui datatable contains:
      | Name                    | Type      | Status |
      | Complete draft activity | Feedback  | Active |
      | Empty draft activity    | Appraisal | Draft  |
      | Active activity         | Check-in  | Active |

  @javascript @vuejs
  Scenario: Activating activities on the manage activity page
    When I navigate to the edit perform activities page for activity "Empty draft activity"
    Then I should see "This activity is currently in a draft state" in the tui action card

    When I click on the tui form help icon in the ".tui-actionCard" "css_element"
    Then I should see "It can be activated once all of the following criteria are met:" in the tui popover
    And I should see "At least one question element added per section" in the tui popover
    When I close the tui popover

    And I click on "Activate" "button" in the ".tui-actionCard" "css_element"
    Then I should see "Activity cannot be activated" in the tui modal
    And I should see "Activation of this draft activity will only be possible once all of the following criteria are met:" in the tui modal
    And I should see "At least one question element added per section" in the tui modal
    When I close the tui modal

    When I navigate to the edit perform activities page for activity "Complete draft activity"
    Then I should see "This activity is currently in a draft state" in the tui action card
    When I click on "Activate" "button" in the ".tui-actionCard" "css_element"
    Then I should see "Confirm activity activation" in the tui modal
    And I should see "2 users will be assigned on activation" in the tui modal
    When I close the tui modal
    Then I should see "This activity is currently in a draft state" in the tui action card
    When I click on "Activate" "button" in the ".tui-actionCard" "css_element"
    And I confirm the tui confirmation modal
    Then I should see " was successfully activated." in the tui success notification toast
    And I should see "This activity is active." in the tui action card
    And I should see "Changes can be applied that will affect future and, in some cases, existing instances." in the tui action card
    And I should not see "Activate" in the tui action card

  @javascript @vuejs
  Scenario: Editing of sections is disabled when activity is active
    When I navigate to the edit perform activities page for activity "Complete draft activity"
    # lets make it multisection
    And I click on the "Multiple sections" tui toggle button
    And I confirm the tui confirmation modal
    And I click on "Cancel" "button" in the ".tui-performActivitySection__saveButtons" "css_element" of the "1" activity section
    Then I should see "Multiple sections"
    Then "Edit section" "button" in the "1" activity section should exist
    And "Section dropdown menu" "button" in the "1" activity section should exist
    And "Edit content elements" "link_or_button" in the "1" activity section should exist
    And "View content elements" "link_or_button" in the "1" activity section should not exist

    When I click on the "On completion" tui toggle button
    Then I should see "Activity saved" in the tui success notification toast
    When I click on the "On completion" tui toggle button
    Then I should see "Activity saved" in the tui success notification toast
    When I close the tui notification toast

    When I click on "Activate" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    Then I should not see "Multiple sections"
    And "Edit section" "button" in the "1" activity section should not exist
    And "Section dropdown menu" "button" in the "1" activity section should not exist
    And "Edit content elements" "link_or_button" in the "1" activity section should not exist
    And "View content elements" "link_or_button" in the "1" activity section should exist
    And I should see "Subject" in the "1" activity section

    # On completion setting has a confirm modal when in archived state
    When I click on the "On completion" tui toggle button
    Then I should see "Confirm workflow change" in the tui modal
    When I confirm the tui confirmation modal
    Then I should see "Activity saved" in the tui success notification toast
