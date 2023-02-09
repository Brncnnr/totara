@totara @perform @mod_perform @perform_element @performelement_linked_review @totara_hierarchy  @totara_hierarchy_goals @javascript @vuejs
Feature: Manage performance activity review goal element.

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name  | activity_type | create_section | create_track | activity_status | anonymous_responses |
      | First Activity | appraisal     | false          | true         | Draft           | false               |
    And the following "activity settings" exist in "mod_perform" plugin:
      | activity_name  | multisection |
      | First Activity | no           |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name  | section_name   |
      | First Activity | Single section |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name   | relationship        | can_view | can_answer |
      | Single section | subject             | no       | yes        |
      | Single section | manager             | yes      | yes        |
      | Single section | appraiser           | yes      | no         |
      | Single section | external respondent | yes      | no         |

  Scenario: I can create & update company and personal goal review content elements.
    Given I log in as "admin"
    And I navigate to the edit perform activities page for activity "First Activity"
    Then I should see "First Activity"
    And I should see "Subject" in the ".tui-performActivitySection__participant-items" "css_element"

    # Add a company_goal review element and verify form validations
    When I click on "Edit content elements" "link_or_button"
    And I add a "Review items" activity content element

    # Title not entered
    And I save the activity content element
    Then I should see "Required"

    When I set the following fields to these values:
      | rawTitle     | Review company goal |
      | content_type | company_goal        |
    Then I should see "Selection participant"
    And I should see "Subject"
    And I should see "Manager"
    And I should see "Appraiser"
    And I should see "Ability to change goal status during activity"
    And I should not see "Change of goal status participant"
    When I click on "Show help for Selection participant" "button"
    And I should see "Only 'Responding participants' or 'View-only participants' added for this section are available as participants to select items to review. External participants are not able to select items." in the tui popover
    And I close the tui popover

    # Selection participant not yet selected
    When I save the activity content element
    Then I should see "Required"

    When I click on the "Subject" tui radio in the "Selection participant" tui radio group
    And I click on "Ability to change goal status during activity" tui "checkbox"
    Then I should see "Change of goal status participant"
    When I click on "Show help for Change of goal status participant" "button"
    And I should see "Only one goal status change will be submitted to the goal. If there are multiple people in the participant role, the first change submitted will be applied." in the tui popover
    And I close the tui popover

    # Change of goal participant not yet selected
    When I save the activity content element
    Then I should see "Required"

    And I click on "Ability to change goal status during activity" tui "checkbox"
    And I save the activity content element
    And I wait for pending js
    Then I should see "Element saved" in the tui success notification toast
    And I should see "Review company goal"

    # Add a personal_goal review element on the same activity
    When I add a "Review items" activity content element
    And I set the following fields to these values:
      | rawTitle     | Review personal goal |
      | content_type | personal_goal        |
    Then I should see "Selection participant"
    And I should see "Subject"
    And I should see "Manager"
    And I should see "Appraiser"
    And I should see "Ability to change goal status during activity"
    And I should not see "Change of goal status participant"
    When I click on "Ability to change goal status during activity" tui "checkbox"
    Then I should see "Change of goal status participant"
    When I click on the "Subject" tui radio in the "Selection participant" tui radio group
    And I click on the "Manager" tui radio in the "Change of goal status participant" tui radio group
    And I save the activity content element
    And I wait for pending js
    Then I should see "Element saved" in the tui success notification toast
    And I should see "Review personal goal"

    # Refresh the page to ensure all are stored and refetched correctly
    When I am on homepage
    And I navigate to the edit perform activities page for activity "First Activity"
    And I click on "Edit content elements" "link_or_button"

    Then I should see "Company goal" in the ".tui-linkedReviewViewGoal__contentType" "css_element" of perform admin element "Review company goal"
    And I should see "Goals example" in the ".tui-linkedReviewViewGoal__title" "css_element" of perform admin element "Review company goal"
    And I should see "This is an example" in the ".tui-linkedReviewViewGoal__description" "css_element" of perform admin element "Review company goal"

    And I should see "Personal goal" in the ".tui-linkedReviewViewGoal__contentType" "css_element" of perform admin element "Review personal goal"
    And I should see "Goals example" in the ".tui-linkedReviewViewGoal__title" "css_element" of perform admin element "Review personal goal"
    And I should see "This is an example" in the ".tui-linkedReviewViewGoal__description" "css_element" of perform admin element "Review personal goal"

    # Now edit an element
    When I click on the Edit element button for question "Review company goal"
    Then I should not see "Change of goal status participant"
    When I click on "Ability to change goal status during activity" tui "checkbox"
    Then I should see "Change of goal status participant"
    When I click on the "Appraiser" tui radio in the "Change of goal status participant" tui radio group
    And I save the activity content element
    And I wait for pending js
    Then I should see "Element saved" in the tui success notification toast
    And I should see "Review company goal"
    And I should see "Review personal goal"

    When I click on the Actions button for question "Review company goal"
    And I click on "Delete" option in the dropdown menu
    Then I should see "Confirm delete element"
    When I confirm the tui confirmation modal
    And I wait for pending js
    Then I should see "Element deleted." in the tui success notification toast
    And I should see "Review personal goal"
    And I should not see "Review company goal"