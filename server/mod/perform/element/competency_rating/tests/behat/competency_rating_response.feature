@totara @perform @mod_perform @perform_element @performelement_linked_review @performelement_competency_rating @totara_competency @javascript @vuejs
Feature: Responding to competency rating sub-question in a linked review performance activity.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following job assignments exist:
      | user  | manager |
      | user1 | user2   |
      | user2 | user3   |
    And the following "activity with section and review element" exist in "performelement_linked_review" plugin:
      | activity_name | section_title | element_title | content_type      |
      | activity1     | section1      | review1       | totara_competency |
    And the following "child elements" exist in "mod_perform" plugin:
      | section  | parent_element | element_plugin    | element_title   | after_element | is_required | data                                 |
      | section1 | review1        | competency_rating | Rate competency |               | true        | { "scaleDescriptionsEnabled": true } |
    And the following "participants in section" exist in "performelement_linked_review" plugin:
      | section  | subject_user | user  | relationship     | can_answer |
      | section1 | user1        | user1 | subject          | true       |
      | section1 | user1        | user2 | manager          | true       |
      | section1 | user1        | user3 | managers_manager | false      |
    And the following "competency assignments" exist in "performelement_linked_review" plugin:
      | competency_name | user  | reason       | manual_rating |
      | Doing paperwork | user1 | cohort       | Competent     |
      | Managing people | user1 | position     | Not competent |
      | Locating stuff  | user1 | organisation |               |
    And the following "selected content" exist in "performelement_linked_review" plugin:
      | element | subject_user | selector_user | content_name    |
      | review1 | user1        | user1         | Doing paperwork |
      | review1 | user1        | user1         | Managing people |
      | review1 | user1        | user1         | Locating stuff  |
    And I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Competency scale" "link"
    And I click on "Edit" "link"
    And I set the field "Description" to "Fully competent without any supervision"
    And I select the text in the "Description" Atto editor
    When I click on "Bold" "button"
    And I click on "Save changes" "button"
    And I log out

  Scenario: Make a response to a competency rating question in a performance activity
    When I log in as "user1"
    And I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"

    # No responses from Manager yet
    And I click show others responses
    Then I should see "Manager response"
    And I should see "No response submitted"

    # Make sure we have rendered actual html (bold text) in the summary for description.
    When I click on "Show description" "button"
    Then I should see "Fully competent without any supervision" in the ".tui-hideShow__content--show b" "css_element"

    # Respond to first & second competency
    And I click on the "Competent" tui radio in the ".tui-linkedReviewParticipantForm__item:nth-child(1)" "css_element"
    And I click on the "Competent with supervision" tui radio in the ".tui-linkedReviewParticipantForm__item:nth-child(2)" "css_element"

    # Save as draft & submit
    When I click on "Save as draft" "button"
    Then I should see "Draft saved" in the tui success notification toast
    And I click on "Cancel" "button"

    # Submit response & validation
    When I click on "activity1" "link"
    And I click on "Submit" "button"
    Then I should see "Required" in the ".tui-linkedReviewParticipantForm__questions > div:nth-child(1)" "css_element" for the 3rd selected content item in the "review1" linked review element
    When I click on the "Not competent" tui radio in the ".tui-linkedReviewParticipantForm__item:nth-child(3)" "css_element"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Section submitted" in the tui success notification toast

    # Print view
    When I navigate to the "print" user activity page for performance activity "activity1" where "user1" is the subject and "user1" is the participant
    And I should see "Your response" in the 1st selected content item for the "review1" linked review print element
    And I should see "Competent" in the 1st selected content item for the "review1" linked review print element
    And I should see "Competent with supervision" in the 2nd selected content item for the "review1" linked review print element
    And I should see "Fully competent without any supervision" in the ".tui-competencyRatingParticipantForm__descriptionPrint b" "css_element"

    # Manager view other's responses & submit
    When I log out
    And I log in as "user2"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link_or_button"
    And I click on "activity1" "link"
    And I click show others responses
    Then I should see "Subject response" in the 1st selected content item for the "review1" linked review element
    And I should see "User One" in the 1st selected content item for the "review1" linked review element
    And I should see "Competent" in the 1st selected content item for the "review1" linked review element
    And I should see "Competent with supervision" in the 2nd selected content item for the "review1" linked review element
    And I should see "Not competent" in the 3rd selected content item for the "review1" linked review element
    And I click on the "Competent with supervision" tui radio in the ".tui-linkedReviewParticipantForm__item:nth-child(1)" "css_element"
    And I click on the "Not competent" tui radio in the ".tui-linkedReviewParticipantForm__item:nth-child(2)" "css_element"
    And I click on the "Competent" tui radio in the ".tui-linkedReviewParticipantForm__item:nth-child(3)" "css_element"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Section submitted" in the tui success notification toast
    And I log out

    # Admin response data view
    When I log in as "admin"
    And I navigate to "Performance activities > Performance activity response data" in site administration
    And I click on "User One" "link"
    When I click on "activity1" "link"
    Then I should see "Subject response" in the 1st selected content item for the "review1" linked review element
    Then I should see "Manager response" in the 1st selected content item for the "review1" linked review element

    # Subject & manager response for content 1
    Then I should see "Competent" in the 1st selected content item for the "review1" linked review element
    Then I should see "Competent with supervision" in the 1st selected content item for the "review1" linked review element

    # Subject & manager response for content 2
    And I should see "Competent with supervision" in the 2nd selected content item for the "review1" linked review element
    And I should see "Not competent" in the 2nd selected content item for the "review1" linked review element

    # Subject & manager response for content 3
    And I should see "Not competent" in the 3rd selected content item for the "review1" linked review element
    And I should see "Competent" in the 3rd selected content item for the "review1" linked review element
