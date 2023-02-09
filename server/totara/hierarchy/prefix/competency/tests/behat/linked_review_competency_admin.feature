@totara @perform @mod_perform @perform_element @performelement_linked_review @javascript @vuejs
Feature: Manage performance activity review content element.

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name        | description           | activity_type | create_track | create_section | activity_status | anonymous_responses |
      | First Activity       | My First description  | check-in      | true         | false          | Draft           | true                |
    And the following "activity settings" exist in "mod_perform" plugin:
      | activity_name   | multisection |
      | First Activity  | yes          |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name      | section_name |
      | First Activity     | section 1-1  |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship | can_view | can_answer |
      | section 1-1  | subject      | yes      | no         |
      | section 1-1  | manager      | yes      | yes        |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name | element_name | title                   |
      | section 1-1  | short_text   | 1-1 Favourite position? |
      | section 1-1  | long_text    | 1-1 Describe position?  |


  Scenario: I can create & update a review content element.
    Given I log in as "admin"
    When I navigate to the edit perform activities page for activity "First Activity"
    # Add a review element
    And I click on "Edit content elements" "link_or_button"
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

    # add sub element
    When I click on the Add sub-element button for question "Review what you did in the past"
    And I add a "Text: Short response" element from dropdown list
    And I set the following fields to these values:
      | rawTitle     | Sub element 1 |
    And I save the activity sub element
    And I reload the page
    Then I should see "Sub element 1"

    # edit review element
    When I click on the Edit element button for question "Review what you did in the past"
    And I set the following fields to these values:
      | rawTitle     | Review what you did in last year |
    And I click on the "Manager" tui radio
    And I save the activity content element
    Then I should see "Review what you did in last year"

    # check if it got changed
    When I click on the Edit element button for question "Review what you did in last year"
    Then the following fields match these values:
      | rawTitle                | Review what you did in last year |
    And the "Manager" radio button is selected
    Given I click on "Cancel" "button"

    # can't delete section relationship if it is used in review element
    When I navigate to the edit perform activities page for activity "First Activity"
    And I click on "button[aria-label='Edit section']" "css_element" in the "1" activity section
    And I remove "Manager" as a perform activity participant
    And I click on "Done" "button" in the ".tui-formBtnGroup" "css_element" of the "1" activity section
    Then I should see "Cannot remove participant(s)" in the tui modal
    And I should see "One or more participant(s) cannot be removed from this section because they are referenced in the following question(s):" in the tui modal
    And I should see "Review what you did in last year" in the tui modal

    # delete review element
    When I close the tui modal
    And I click on "Edit content elements" "link_or_button"
    And I click on the Actions button for question "Review what you did in last year"
    And I click on "Delete" option in the dropdown menu
    Then I should see "Confirm delete element"
    When I confirm the tui confirmation modal
    Then I should see "Element deleted." in the tui success notification toast
    And I should not see "Review what you did in last year"