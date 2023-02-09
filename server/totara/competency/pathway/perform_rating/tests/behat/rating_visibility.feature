@totara @perform @mod_perform @totara_competency @perform_element @performelement_linked_review @pathway_perform_rating @javascript @vuejs
Feature: Competency ratings from performance activity.

  Background:
    Given the following "users" exist:
      | username   | firstname | lastname | email                  |
      | subject1   | User      | One      | subject1@example.com   |
      | manager1   | User      | Two      | manager1@example.com   |
      | appraiser1 | User      | Three    | appraiser1@example.com |
    And the following job assignments exist:
      | user     | manager  | appraiser  |
      | subject1 | manager1 | appraiser1 |
    And the following "activity with section and review element" exist in "performelement_linked_review" plugin:
      | activity_name | section_title | element_title | content_type      | content_type_settings                                  |
      | activity1     | section1      | review1       | totara_competency | {"enable_rating":true,"rating_relationship":"subject"} |
    And the following "child elements" exist in "mod_perform" plugin:
      | section  | parent_element | element_plugin | element_title        | after_element | is_required | data |
      | section1 | review1        | short_text     | describe experience? |               | false       | {}   |
    And the following "participants in section" exist in "performelement_linked_review" plugin:
      | section  | subject_user | user       | relationship | can_answer | can_view |
      | section1 | subject1     | subject1   | subject      | true       | true     |
      | section1 | subject1     | manager1   | manager      | true       | true     |
      | section1 | subject1     | appraiser1 | appraiser    | true       | false    |
    And the following "competency assignments" exist in "performelement_linked_review" plugin:
      | competency_name | user     | reason   |
      | Doing paperwork | subject1 | cohort   |
      | Managing people | subject1 | position |
    And the following "selected content" exist in "performelement_linked_review" plugin:
      | element | subject_user | selector_user | content_name    |
      | review1 | subject1     | subject1      | Doing paperwork |
      | review1 | subject1     | subject1      | Managing people |
    And the following "pathways" exist in "totara_competency" plugin:
      | pathway        | competency      |
      | perform_rating | Doing paperwork |
      | perform_rating | Managing people |

  Scenario: Show final rating for participants that can view responses.
    # Accessing final rating as subject
    When I log in as "subject1"
    And I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"

    # Add ratings
    And I set the field with css ".tui-linkedReviewParticipantForm__item:nth-child(1) select[name=scaleValue]" to "Set to 'No rating'"
    And I click on "Submit rating" "button" in the 1st selected content item for the "review1" linked review element
    And I confirm the tui confirmation modal
    And I set the field with css ".tui-linkedReviewParticipantForm__item:nth-child(2) select[name=scaleValue]" to "Competent"
    And I click on "Submit rating" "button" in the 2nd selected content item for the "review1" linked review element
    And I confirm the tui confirmation modal
    And I log out

    # Accessing final rating as manager
    When I log in as "manager1"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link_or_button"
    And I click on "activity1" "link"
    Then I should see "Rating by: User One" in the 1st selected content item for the "review1" linked review element
    And I should see "Final rating: No rating" in the 1st selected content item for the "review1" linked review element
    And I should see "Final rating: Competent" in the 2nd selected content item for the "review1" linked review element
    And I log out

    # Accessing final rating as appraiser
    When I log in as "appraiser1"
    And I navigate to the outstanding perform activities list page
    And I click on "As Appraiser" "link_or_button"
    And I click on "activity1" "link"
    Then I should not see "Rating by: User One" in the 1st selected content item for the "review1" linked review element
    And I should not see "Final rating: No rating" in the 1st selected content item for the "review1" linked review element
    And I should not see "Final rating: Competent" in the 2nd selected content item for the "review1" linked review element
    And I log out

    # Accessing final rating as admin
    When I log in as "admin"
    And I navigate to "Performance activities > Performance activity response data" in site administration
    And I click on "User One" "link"
    When I click on "activity1" "link"
    Then I should see "Rating by: User One" in the 1st selected content item for the "review1" linked review element
    And I should see "Final rating: No rating" in the 1st selected content item for the "review1" linked review element
    And I should see "Final rating: Competent" in the 2nd selected content item for the "review1" linked review element
    And I log out

  Scenario: Show final rating follows activity visibility settings.
    And the following "activity settings" exist in "mod_perform" plugin:
      | activity_name | visibility_condition | close_on_completion |
      | activity1     | 1                    | yes                 |

    # Accessing final rating as subject
    When I log in as "subject1"
    And I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"

    # Add ratings
    And I set the field with css ".tui-linkedReviewParticipantForm__item:nth-child(1) select[name=scaleValue]" to "Set to 'No rating'"
    And I click on "Submit rating" "button" in the 1st selected content item for the "review1" linked review element
    And I confirm the tui confirmation modal
    And I set the field with css ".tui-linkedReviewParticipantForm__item:nth-child(2) select[name=scaleValue]" to "Competent"
    And I click on "Submit rating" "button" in the 2nd selected content item for the "review1" linked review element
    And I confirm the tui confirmation modal
    And I log out

    # Accessing final rating as manager
    When I log in as "manager1"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link_or_button"
    And I click on "activity1" "link"
    And I wait for the next second
    Then I should not see "Rating by: User One" in the 1st selected content item for the "review1" linked review element
    And I should not see "Final rating: No rating" in the 1st selected content item for the "review1" linked review element
    And I should not see "Final rating: Competent" in the 2nd selected content item for the "review1" linked review element
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal

    # Final rating should be visible after submission.
    And I click on "As Manager" "link_or_button"
    And I click on "activity1" "link"
    Then I should see "Rating by: User One" in the 1st selected content item for the "review1" linked review element
    And I should see "Final rating: No rating" in the 1st selected content item for the "review1" linked review element
    And I should see "Final rating: Competent" in the 2nd selected content item for the "review1" linked review element
    And I log out

    # Accessing final rating as appraiser.
    When I log in as "appraiser1"
    And I navigate to the outstanding perform activities list page
    And I click on "As Appraiser" "link_or_button"
    And I click on "activity1" "link"
    Then I should not see "Rating by: User One" in the 1st selected content item for the "review1" linked review element
    And I should not see "Final rating: No rating" in the 1st selected content item for the "review1" linked review element
    And I should not see "Final rating: Competent" in the 2nd selected content item for the "review1" linked review element
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal

    # Final rating should not be visible after submission as well.
    And I click on "As Appraiser" "link_or_button"
    And I click on "activity1" "link"
    Then I should not see "Rating by: User One" in the 1st selected content item for the "review1" linked review element
    And I should not see "Final rating: No rating" in the 1st selected content item for the "review1" linked review element
    And I should not see "Final rating: Competent" in the 2nd selected content item for the "review1" linked review element
    And I log out

    # Accessing final rating as admin
    When I log in as "admin"
    And I navigate to "Performance activities > Performance activity response data" in site administration
    And I click on "User One" "link"
    When I click on "activity1" "link"
    Then I should see "Rating by: User One" in the 1st selected content item for the "review1" linked review element
    And I should see "Final rating: No rating" in the 1st selected content item for the "review1" linked review element
    And I should see "Final rating: Competent" in the 2nd selected content item for the "review1" linked review element
    And I log out