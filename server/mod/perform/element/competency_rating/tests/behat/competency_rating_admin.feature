@totara @perform @mod_perform @perform_element @performelement_linked_review @performelement_competency_rating @totara_competency @javascript @vuejs
Feature: Manage performance activity competency rating element.

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name  | description          | activity_type | create_track | create_section | activity_status | anonymous_responses |
      | First Activity | My First description | check-in      | true         | false          | Draft           | true                |
    And the following "activity settings" exist in "mod_perform" plugin:
      | activity_name  | multisection |
      | First Activity | yes          |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name  | section_name |
      | First Activity | section 1-1  |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship | can_view | can_answer |
      | section 1-1  | subject      | yes      | no         |
      | section 1-1  | manager      | yes      | yes        |

  Scenario: Admin can add competency rating element as child element only.
    # Competency rating element not available as top level element.
    Given I log in as "admin"
    When I navigate to the edit perform activities page for activity "First Activity"
    And I wait for the next second
    And I click on "Edit content elements" "link_or_button"
    And I click on "Add element" "link_or_button"
    And I should not see "Rating scale: Competency" option in the dropdown menu
    And I click on "Add element" "link_or_button"

    # Add a review element.
    And I add a "Review items" activity content element
    And I set the following fields to these values:
      | rawTitle     | Review what you did in the past |
      | content_type | totara_competency               |
    Then ".tui-radioGroup" "css_element" should exist
    And I click on the "Subject" tui radio
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast
    And I wait for pending js
    Then I should see "Review what you did in the past"

    # Add a competency rating element.
    When I click on the Add sub-element button for question "Review what you did in the past"
    And I should see "Rating scale: Competency" option in the dropdown menu
    And I add a "Rating scale: Competency" element from dropdown list
    And I set the following fields to these values:
      | rawTitle   | Rate competency |
      | identifier | 200             |
    And I save the activity sub element
    Then I should see "Element saved" in the tui success notification toast
    And I should see "Rate competency"
    And I should not see "Scale description 1"
    And I should not see "Scale description 2"
    And I should not see "Scale description 3"

    When I click on "Edit element: Rate competency" "button"
    And I click on the "scaleDescriptionsEnabled" tui checkbox
    And I save the activity sub element
    Then I should see "Element saved" in the tui success notification toast

    When I click on "Show description" "button"
    Then I should see "Scale description 1"
    And I should not see "Scale description 2"
    And I should not see "Scale description 3"

    When I manually activate the perform activity "First Activity"
    And I reload the page
    Then I should see "Rate competency"

    When I click on "Element settings: Rate competency" "button"
    Then the perform element summary should contain:
      | Question text                     | Rate competency |
      | Include rating scale descriptions | Yes             |
      | Reporting ID                      | 200             |
