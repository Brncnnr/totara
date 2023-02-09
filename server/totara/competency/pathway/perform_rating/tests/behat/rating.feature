@totara @perform @mod_perform @totara_competency @perform_element @performelement_linked_review @pathway_perform_rating @javascript @vuejs
Feature: Rating competencies via performance activities.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
    And the following job assignments exist:
      | user  | manager |
      | user1 | user2   |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name | create_track | create_section | activity_status |
      | activity1     | false        | false          | Draft           |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name | track_description |
      | activity1     | track1            |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name | section_name |
      | activity1     | section1     |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship | can_view | can_answer |
      | section1     | Subject      | yes      | yes        |
      | section1     | Manager      | yes      | yes        |
    And the following "competency assignments" exist in "performelement_linked_review" plugin:
      | competency_name | user  | reason       |
      | Doing paperwork | user1 | cohort       |
      | Managing people | user1 | position     |
      | Locating stuff  | user1 | organisation |
      | Locating stuff  | user1 | user         |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name     |
      | track1            | cohort          | Cohort 1            |
      | track1            | position        | Test Position 1     |
      | track1            | organisation    | Test Organisation 1 |
    And the following "pathways" exist in "totara_competency" plugin:
      | pathway        | competency      |
      | perform_rating | Doing paperwork |
      | perform_rating | Managing people |
      | perform_rating | Locating stuff  |

  Scenario: I can create a competency linked review element and make a rating in an activity which is then shown in the competency profile
    When I log in as "admin"
    And I navigate to the edit perform activities page for activity "activity1"
    And I click on "Edit content elements" "link_or_button"
    And I add a "Review items" activity content element
    And I set the following fields to these values:
      | rawTitle     | review1           |
      | content_type | totara_competency |
    And I click on the "Subject" tui radio in the "Selection participant" tui radio group
    And I click on "Help" "button"
    And I should see "Any competencies which participants can select must have 'Performance activity rating' set as an achievement path" in the tui popover
    When I close the tui popover
    And I click on "Enable Performance activity rating" tui "checkbox"
    And I save the activity content element
    Then I should see "Required"
    When I click on "Show help for Rating participant" "button"
    Then I should see "If there are multiple people in the participant role" in the tui popover
    When I close the tui popover
    Then I should see "Subject" in the "Rating participant" tui radio group
    And I should see "Manager" in the "Rating participant" tui radio group
    And I should not see "Appraiser" in the "Rating participant" tui radio group
    When I click on the "Manager" tui radio in the "Rating participant" tui radio group
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast
    And I should see "Final rating to be submitted for the competency" in the ".tui-linkedReviewRatingFormPreview" "css_element"
    And I should see "Select rating value" in the ".tui-linkedReviewRatingFormPreview" "css_element"
    When I click on "Help" "button" in the ".tui-linkedReviewRatingFormPreview" "css_element"
    Then I should see "Only one performance activity rating will be collected" in the tui popover

    # Activate the activity and see the read only element view
    When I navigate to the edit perform activities page for activity "activity1"
    And I click on "Activate" "button"
    And I confirm the tui confirmation modal
    And I click on "View content elements" "link_or_button"
    Then I should see "Final rating to be submitted for the competency" in the ".tui-linkedReviewRatingFormPreview" "css_element"
    And I should see "Select rating value" in the ".tui-linkedReviewRatingFormPreview" "css_element"
    When I click on "Help" "button" in the ".tui-linkedReviewRatingFormPreview" "css_element"
    Then I should see "Only one performance activity rating will be collected" in the tui popover
    When I close the tui popover
    And I click on "Element settings: review1" "button"
    Then the perform element summary should contain:
      | Question text                      | review1      |
      | Review type                        | Competencies |
      | Selection participant              | Subject      |
      | Enable Performance activity rating | Yes          |
      | Rating participant                 | Manager      |

    # Make a rating in the activity
    # Ratings haven't been made yet - view activity as subject
    When I log out
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"
    And I run the scheduled task "mod_perform\task\create_manual_participant_progress_task"
    And I wait for the next second
    And the following "selected content" exist in "performelement_linked_review" plugin:
      | element | subject_user | selector_user | content_name    | assignment_reason |
      | review1 | user1        | user1         | Doing paperwork |                   |
      | review1 | user1        | user1         | Managing people |                   |
      | review1 | user1        | user1         | Locating stuff  | organisation      |
      | review1 | user1        | user1         | Locating stuff  | user              |
    And I log in as "user1"
    And I navigate to the competency profile details page for the "Doing paperwork" competency and user "user1"
    Then "Performance activity" "link" should exist
    When I click on "Performance activity" "link"
    Then I should see "No rating given" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    When I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"
    Then I should see "Final rating to be submitted for the competency"
    And I should see "This will be answered by a Manager."
    When I click on "Help" "button" in the ".tui-competencyLinkedReviewRating" "css_element"
    Then I should see "Only one performance activity rating will be collected" in the tui popover

    # Make a rating in the activity as the manager user
    When I log out
    And I log in as "user2"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    And I click on "activity1" "link"
    Then I should see "Final rating to be submitted for the competency"
    And I should not see "This will be answered by a Manager."
    When I click on "Help" "button" in the ".tui-competencyLinkedReviewRating" "css_element"
    Then I should see "Only one performance activity rating will be collected" in the tui popover
    # Rate "Doing paperwork" as "No rating"
    And I set the field with css ".tui-linkedReviewParticipantForm__item:nth-child(1) select[name=scaleValue]" to "Set to 'No rating'"
    And I click on "Submit rating" "button" in the 1st selected content item for the "review1" linked review element
    Then I should see "Submit final rating?" in the tui modal
    And I should see "You've rated User One as No rating." in the tui modal
    And I should see "Once you've saved this rating, it will be submitted to the competency and cannot be changed." in the tui modal
    When I confirm the tui confirmation modal
    Then I should see "Rating by: User Two" in the 1st selected content item for the "review1" linked review element
    And I should see "Final rating: No rating" in the 1st selected content item for the "review1" linked review element
    And I should see the current date in format "j F Y" in the ".tui-linkedReviewParticipantForm__item:nth-child(1) .tui-competencyLinkedReviewRating" "css_element"
    # Rate "Managing people" as "Competent"
    When I set the field with css ".tui-linkedReviewParticipantForm__item:nth-child(2) select[name=scaleValue]" to "Competent"
    And I click on "Submit rating" "button" in the 2nd selected content item for the "review1" linked review element
    Then I should see "You've rated User One as Competent." in the tui modal
    When I confirm the tui confirmation modal
    Then I should see "Rating by: User Two" in the 2nd selected content item for the "review1" linked review element
    And I should see "Final rating: Competent" in the 2nd selected content item for the "review1" linked review element
    And I should see the current date in format "j F Y" in the ".tui-linkedReviewParticipantForm__item:nth-child(2) .tui-competencyLinkedReviewRating" "css_element"
    # Rate "Locating stuff" (Org assignment) as "Competent with supervision"
    When I set the field with css ".tui-linkedReviewParticipantForm__item:nth-child(3) select[name=scaleValue]" to "Competent with supervision"
    And I click on "Submit rating" "button" in the 3rd selected content item for the "review1" linked review element
    Then I should see "You've rated User One as Competent with supervision." in the tui modal
    When I confirm the tui confirmation modal
    Then I should see "Rating by: User Two" in the 3rd selected content item for the "review1" linked review element
    And I should see "Final rating: Competent with supervision" in the 3rd selected content item for the "review1" linked review element
    And I should see the current date in format "j F Y" in the ".tui-linkedReviewParticipantForm__item:nth-child(3) .tui-competencyLinkedReviewRating" "css_element"
    # Attempt to rate "Locating stuff" (User assignment) as "Competent"
    # This rating won't be saved since the same competency was just rated - so a message is shown as to why
    When I set the field with css ".tui-linkedReviewParticipantForm__item:nth-child(4) select[name=scaleValue]" to "Competent"
    And I click on "Submit rating" "button" in the 4th selected content item for the "review1" linked review element
    Then I should see "You've rated User One as Competent." in the tui modal
    When I confirm the tui confirmation modal
    Then I should see "Your rating has not been saved, as this competency has already been rated by you or by someone in the same role as you." in the tui info notification banner
    And I should see "Rating by: User Two" in the 4th selected content item for the "review1" linked review element
    And I should see "Final rating: Competent with supervision" in the 4th selected content item for the "review1" linked review element
    And I should see the current date in format "j F Y" in the ".tui-linkedReviewParticipantForm__item:nth-child(4) .tui-competencyLinkedReviewRating" "css_element"

    # Run aggregation
    When I run the scheduled task "totara_competency\task\competency_aggregation_queue"
    And I wait for the next second

    # Check the rating displays in the competency profile
    # "Doing paperwork" was set to no rating
    Given I log out
    And I log in as "user1"
    When I navigate to the competency profile details page for the "Doing paperwork" competency and user "user1"
    Then "Performance activity" "link" should exist
    When I click on "Performance activity" "link"
    Then I should see "activity1" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    And I should see "No rating" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    And I should see "User Two" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    And I should see the current date in format "j F Y" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    # This user is already proficient so it shouldn't change anything
    And I should see "Not proficient" in the ".tui-competencyDetailAssignment__status" "css_element"
    And I should see "No value achieved" in the ".tui-competencyDetailAssignment__level" "css_element"
    When I click on "Activity log" "button"
    Then I should not see "Criteria met: Performance Activity rating for 'activity1' by User Two (Manager)" in the tui modal

    # "Managing people" was set to "Competent"
    When I navigate to the competency profile details page for the "Managing people" competency and user "user1"
    Then "Performance activity" "link" should exist
    When I click on "Performance activity" "link"
    Then I should see "activity1" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    And I should see "Competent" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    And I should see "User Two" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    And I should see the current date in format "j F Y" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    # This user is already proficient so it shouldn't change anything
    And I should see "Proficient" in the ".tui-competencyDetailAssignment__status" "css_element"
    And I should see "Competent" in the ".tui-competencyDetailAssignment__level" "css_element"
    When I click on "Activity log" "button"
    Then I should see "Criteria met: Performance Activity rating for 'activity1' by User Two (Manager). Achieved 'Competent' rating." in the tui modal

    # "Locating stuff" was set to "Competent with supervision"
    When I navigate to the competency profile details page for the "Locating stuff" competency and user "user1"
    Then "Performance activity" "link" should exist
    When I click on "Performance activity" "link"
    Then I should see "activity1" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    And I should see "Competent with supervision" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    And I should see "User Two" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    And I should see the current date in format "j F Y" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    # This user is already proficient so it shouldn't change anything
    And I should see "Not proficient" in the ".tui-competencyDetailAssignment__status" "css_element"
    And I should see "Competent with supervision" in the ".tui-competencyDetailAssignment__level" "css_element"
    When I click on "Activity log" "button"
    Then I should see "Criteria met: Performance Activity rating for 'activity1' by User Two (Manager). Achieved 'Competent with supervision' rating." in the tui modal

    # Now delete the activity and check that it shows correctly on the competency details page
    Given I log out
    And I log in as "admin"
    When I navigate to the manage perform activities page
    And I open the dropdown menu in the tui datatable row with "activity1" "Name"
    And I click on "Delete" "link"
    And I confirm the tui confirmation modal

    Given I log out
    And I log in as "user1"
    When I navigate to the competency profile details page for the "Locating stuff" competency and user "user1"
    Then "Performance activity" "link" should exist
    When I click on "Performance activity" "link"
    Then I should see "This activity no longer exists" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    And I should see "Competent with supervision" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    And I should see "User Two" in the ".tui-pathwayPerformRatingAchievement" "css_element"
    When I click on "Activity log" "button"
    Then I should see "Criteria met: This activity no longer exists. Performance Activity rating by User Two (Manager)" in the tui modal