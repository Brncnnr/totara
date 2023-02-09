@totara @perform @mod_perform @perform_element @performelement_linked_review @totara_competency @javascript @vuejs @editor_weka @weka
Feature: Responding to competency assignments linked to a performance review

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
      | section  | parent_element | element_plugin      | element_title   | after_element   | is_required | data                                                                                                                                                                                                                   |
      | section1 | review1        | static_content      | static child    |                 | false       | {"docFormat":"FORMAT_JSON_EDITOR","draftId":0,"format":"HTML","wekaDoc":"{\"type\":\"doc\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Static content text\"}]}]}","element_id":0} |
      | section1 | review1        | long_text           | long text child | static child    | false       |                                                                                                                                                                                                                        |
      | section1 | review1        | multi_choice_single | radio child     | long text child | true        | {"options":[{"name":"option_a","value":"Option A"},{"name":"option_b","value":"Option B"},{"name":"option_c","value":"Option C"}]}                                                                                     |
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

  Scenario: Make a response to a linked competency in a performance activity
    When I log in as "user1"
    And I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"

    # No responses from Manager yet
    And I click show others responses
    Then I should see "Manager response"
    And I should see "No response submitted"

    # First selected competency: Doing paperwork
    When I click on "Doing paperwork" "link" in the 1st selected content item for the "review1" linked review element
    Then I should see "Competency profile"
    And I should see "Competency Details - Doing paperwork"
    And I should see "Ways to achieve proficiency in this competency"
    When I press the "back" button in the browser
    Then I should see "Doing paperwork description" in the 1st selected content item for the "review1" linked review element
    And I should see the current date in format "j/m/Y" in the ".tui-linkedReviewParticipantForm__item:nth-child(1) .tui-linkedReviewViewCompetency__timestamp" "css_element"
    And I should see "Reason assigned" in the 1st selected content item for the "review1" linked review element
    And I should see "Cohort 1 (Audience)" in the 1st selected content item for the "review1" linked review element
    And I should see "Achievement level" in the 1st selected content item for the "review1" linked review element
    And I should see "Competent" in the 1st selected content item for the "review1" linked review element
    And I should see "Proficient" in the 1st selected content item for the "review1" linked review element
    When I click on "Show help for Rating scale" "button" in the 1st selected content item for the "review1" linked review element
    Then I should see "Rating scale" in the ".tui-competencyRatingScaleOverview" "css_element" for the 1st selected content item in the "review1" linked review element
    And I should see "Competent" in the ".tui-competencyRatingScaleOverview" "css_element" for the 1st selected content item in the "review1" linked review element
    And I should see "Competent with supervision" in the ".tui-competencyRatingScaleOverview" "css_element" for the 1st selected content item in the "review1" linked review element
    And I should see "Not competent" in the ".tui-competencyRatingScaleOverview" "css_element" for the 1st selected content item in the "review1" linked review element
    And I should see "This value is proficient." in the ".tui-competencyRatingScaleOverview" "css_element" for the 1st selected content item in the "review1" linked review element
    And I should see "This value is not proficient." in the ".tui-competencyRatingScaleOverview" "css_element" for the 1st selected content item in the "review1" linked review element

    # Second selected competency: Managing people
    When I click on "Managing people" "link" in the 2nd selected content item for the "review1" linked review element
    Then I should see "Competency profile"
    And I should see "Competency Details - Managing people"
    And I should see "Ways to achieve proficiency in this competency"
    When I press the "back" button in the browser
    Then I should see "Managing people description" in the 2nd selected content item for the "review1" linked review element
    And I should see the current date in format "j/m/Y" in the ".tui-linkedReviewParticipantForm__item:nth-child(2) .tui-linkedReviewViewCompetency__timestamp" "css_element"
    And I should see "Reason assigned" in the 2nd selected content item for the "review1" linked review element
    And I should see "Test Position 1 (Position)" in the 2nd selected content item for the "review1" linked review element
    And I should see "Achievement level" in the 2nd selected content item for the "review1" linked review element
    And I should see "Not competent" in the 2nd selected content item for the "review1" linked review element
    And I should see "Not proficient" in the 2nd selected content item for the "review1" linked review element
    When I click on "Show help for Rating scale" "button" in the 2nd selected content item for the "review1" linked review element
    Then I should see "Rating scale" in the ".tui-competencyRatingScaleOverview" "css_element" for the 2nd selected content item in the "review1" linked review element
    And I should see "Competent" in the ".tui-competencyRatingScaleOverview" "css_element" for the 2nd selected content item in the "review1" linked review element
    And I should see "Competent with supervision" in the ".tui-competencyRatingScaleOverview" "css_element" for the 2nd selected content item in the "review1" linked review element
    And I should see "Not competent" in the ".tui-competencyRatingScaleOverview" "css_element" for the 2nd selected content item in the "review1" linked review element
    And I should see "This value is proficient." in the ".tui-competencyRatingScaleOverview" "css_element" for the 2nd selected content item in the "review1" linked review element
    And I should see "This value is not proficient." in the ".tui-competencyRatingScaleOverview" "css_element" for the 2nd selected content item in the "review1" linked review element

    # Third selected competency: Locating stuff
    When I click on "Locating stuff" "link" in the 3rd selected content item for the "review1" linked review element
    Then I should see "Competency profile"
    And I should see "Competency Details - Locating stuff"
    And I should see "Ways to achieve proficiency in this competency"
    When I press the "back" button in the browser
    Then I should see "Locating stuff description" in the 3rd selected content item for the "review1" linked review element
    And I should see the current date in format "j/m/Y" in the ".tui-linkedReviewParticipantForm__item:nth-child(3) .tui-linkedReviewViewCompetency__timestamp" "css_element"
    And I should see "Reason assigned" in the 3rd selected content item for the "review1" linked review element
    And I should see "Test Organisation 1 (Organisation)" in the 3rd selected content item for the "review1" linked review element
    And I should see "Achievement level" in the 3rd selected content item for the "review1" linked review element
    And I should see "No value achieved" in the 3rd selected content item for the "review1" linked review element
    And I should see "Not proficient" in the 3rd selected content item for the "review1" linked review element
    When I click on "Show help for Rating scale" "button" in the 3rd selected content item for the "review1" linked review element
    Then I should see "Rating scale" in the ".tui-competencyRatingScaleOverview" "css_element" for the 3rd selected content item in the "review1" linked review element
    And I should see "Competent" in the ".tui-competencyRatingScaleOverview" "css_element" for the 3rd selected content item in the "review1" linked review element
    And I should see "Competent with supervision" in the ".tui-competencyRatingScaleOverview" "css_element" for the 3rd selected content item in the "review1" linked review element
    And I should see "Not competent" in the ".tui-competencyRatingScaleOverview" "css_element" for the 3rd selected content item in the "review1" linked review element
    And I should see "This value is proficient." in the ".tui-competencyRatingScaleOverview" "css_element" for the 3rd selected content item in the "review1" linked review element
    And I should see "This value is not proficient." in the ".tui-competencyRatingScaleOverview" "css_element" for the 3rd selected content item in the "review1" linked review element

    # Respond to first competency
    When I activate the weka editor with css ".tui-linkedReviewParticipantForm__item:nth-child(1)"
    And I type "Doing paperwork long text response" in the weka editor
    And I upload embedded media to the weka editor using the file "mod/perform/tests/behat/fixtures/blue.png"
    And I click on the "Option A" tui radio in the ".tui-linkedReviewParticipantForm__item:nth-child(1)" "css_element"
    Then I should see "Static content text" in the 1st selected content item for the "review1" linked review element

    # Respond to the second competency
    When I activate the weka editor with css ".tui-linkedReviewParticipantForm__item:nth-child(2)"
    And I type "Managing people long text response" in the weka editor
    And I upload embedded media to the weka editor using the file "mod/perform/tests/behat/fixtures/blue.png"
    And I click on the "Option B" tui radio in the ".tui-linkedReviewParticipantForm__item:nth-child(2)" "css_element"
    Then I should see "Static content text" in the 2nd selected content item for the "review1" linked review element

    # Don't respond to third competency
    And I should see "Static content text" in the 3rd selected content item for the "review1" linked review element

    # Save as draft
    When I click on "Save as draft" "button"
    Then I should see "Draft saved" in the tui success notification toast
    When I click on "Cancel" "button"
    And I click on "activity1" "link"
    And I activate the weka editor with css ".tui-linkedReviewParticipantForm__item:nth-child(1)"
    Then I should see "Doing paperwork long text response" in the weka editor
    When I activate the weka editor with css ".tui-linkedReviewParticipantForm__item:nth-child(2)"
    Then I should see "Managing people long text response" in the weka editor

    # Submit
    When I click on "Submit" "button"
    Then I should see "Required" in the ".tui-linkedReviewParticipantForm__questions > div:nth-child(3)" "css_element" for the 3rd selected content item in the "review1" linked review element
    When I activate the weka editor with css ".tui-linkedReviewParticipantForm__item:nth-child(3)"
    And I click on the "Option C" tui radio in the ".tui-linkedReviewParticipantForm__item:nth-child(3)" "css_element"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Section submitted" in the tui success notification toast

    # View and complete form as manager
    When I log out
    And I log in as "user2"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link_or_button"
    And I click on "activity1" "link"
    And I click show others responses

    # Viewing subject's responses to 1st competency
    Then I should see "Subject response" in the 1st selected content item for the "review1" linked review element
    And I should see "User One" in the 1st selected content item for the "review1" linked review element
    And I should see "Doing paperwork long text response" in the 1st selected content item for the "review1" linked review element
    And I should see a weka embedded image with the name "blue.png" in the ".tui-linkedReviewParticipantForm__item:nth-child(1)" "css_element"
    And I should see "Option A" in the 1st selected content item for the "review1" linked review element
    # Viewing subject's responses to 2nd competency
    And I should see "Subject response" in the 2nd selected content item for the "review1" linked review element
    And I should see "User One" in the 2nd selected content item for the "review1" linked review element
    And I should see "Managing people long text response" in the 2nd selected content item for the "review1" linked review element
    And I should see a weka embedded image with the name "blue (1).png" in the ".tui-linkedReviewParticipantForm__item:nth-child(2)" "css_element"
    And I should see "Option B" in the 2nd selected content item for the "review1" linked review element
    # Viewing subject's responses to 3rd competency
    And I should see "Subject response" in the 3rd selected content item for the "review1" linked review element
    And I should see "User One" in the 3rd selected content item for the "review1" linked review element
    And I should see "No response submitted" in the 3rd selected content item for the "review1" linked review element
    And I should see "Option C" in the 3rd selected content item for the "review1" linked review element

    # Respond to competencies as manager and submit
    When I activate the weka editor with css ".tui-linkedReviewParticipantForm__item:nth-child(1)"
    And I type "Doing paperwork manager response" in the weka editor
    And I upload embedded media to the weka editor using the file "mod/perform/tests/behat/fixtures/green.png"
    And I click on the "Option C" tui radio in the ".tui-linkedReviewParticipantForm__item:nth-child(1)" "css_element"
    And I click on the "Option B" tui radio in the ".tui-linkedReviewParticipantForm__item:nth-child(2)" "css_element"
    And I click on the "Option A" tui radio in the ".tui-linkedReviewParticipantForm__item:nth-child(3)" "css_element"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Section submitted" in the tui success notification toast

    # View the print view
    When I navigate to the "print" user activity page for performance activity "activity1" where "user1" is the subject and "user2" is the participant

    # Print view: first selected competency: Doing paperwork
    And I should see "Doing paperwork" in the 1st selected content item for the "review1" linked review print element
    And I should see "Doing paperwork description" in the 1st selected content item for the "review1" linked review print element
    And I should see the current date in format "j/m/Y" in the ".tui-linkedReviewParticipantForm__item:nth-child(1) .tui-linkedReviewViewCompetency__timestamp" "css_element"
    And I should see "Reason assigned" in the 1st selected content item for the "review1" linked review print element
    And I should see "Cohort 1 (Audience)" in the 1st selected content item for the "review1" linked review print element
    And I should see "Achievement level" in the 1st selected content item for the "review1" linked review print element
    And I should see "Competent" in the 1st selected content item for the "review1" linked review print element
    And I should see "Proficient" in the 1st selected content item for the "review1" linked review print element
    And I should see "Static content text" in the 1st selected content item for the "review1" linked review print element
    And I should see "Your response" in the 1st selected content item for the "review1" linked review print element
    And I should see "Doing paperwork manager response" in the 1st selected content item for the "review1" linked review print element
    And I should see a weka embedded image with the name "green.png" in the ".tui-linkedReviewParticipantForm__item:nth-child(1)" "css_element"
    And I should see "Subject response" in the 1st selected content item for the "review1" linked review print element
    And I should see "User One" in the 1st selected content item for the "review1" linked review print element
    And I should see "Doing paperwork long text response" in the 1st selected content item for the "review1" linked review print element
    And I should see a weka embedded image with the name "blue.png" in the ".tui-linkedReviewParticipantForm__item:nth-child(1)" "css_element"
    And I should see "Option A" in the 1st selected content item for the "review1" linked review print element
    And I should see "Option B" in the 1st selected content item for the "review1" linked review print element
    And I should see "Option C" in the 1st selected content item for the "review1" linked review print element

    # Print view: second selected competency: Managing people
    And I should see "Managing people" in the 2nd selected content item for the "review1" linked review print element
    And I should see "Managing people description" in the 2nd selected content item for the "review1" linked review print element
    And I should see the current date in format "j/m/Y" in the ".tui-linkedReviewParticipantForm__item:nth-child(2) .tui-linkedReviewViewCompetency__timestamp" "css_element"
    And I should see "Reason assigned" in the 2nd selected content item for the "review1" linked review print element
    And I should see "Test Position 1 (Position)" in the 2nd selected content item for the "review1" linked review print element
    And I should see "Achievement level" in the 2nd selected content item for the "review1" linked review print element
    And I should see "Not competent" in the 2nd selected content item for the "review1" linked review print element
    And I should see "Not proficient" in the 2nd selected content item for the "review1" linked review print element
    And I should see "Static content text" in the 2nd selected content item for the "review1" linked review print element
    And I should see "Your response" in the 2nd selected content item for the "review1" linked review print element
    And ".tui-linkedReviewParticipantForm__item:nth-child(2) .tui-longTextParticipantPrint .tui-notepadLines" "css_element" should exist
    And I should see "Subject response" in the 2nd selected content item for the "review1" linked review print element
    And I should see "User One" in the 2nd selected content item for the "review1" linked review print element
    And I should see "Managing people long text response" in the 2nd selected content item for the "review1" linked review print element
    And I should see a weka embedded image with the name "blue (1).png" in the ".tui-linkedReviewParticipantForm__item:nth-child(2)" "css_element"
    And I should see "Option A" in the 2nd selected content item for the "review1" linked review print element
    And I should see "Option B" in the 2nd selected content item for the "review1" linked review print element
    And I should see "Option C" in the 2nd selected content item for the "review1" linked review print element

    # Print view: third selected competency: Locating stuff
    And I should see "Locating stuff" in the 3rd selected content item for the "review1" linked review print element
    And I should see "Locating stuff description" in the 3rd selected content item for the "review1" linked review print element
    And I should see the current date in format "j/m/Y" in the ".tui-linkedReviewParticipantForm__item:nth-child(3) .tui-linkedReviewViewCompetency__timestamp" "css_element"
    And I should see "Reason assigned" in the 3rd selected content item for the "review1" linked review print element
    And I should see "Test Organisation 1 (Organisation)" in the 3rd selected content item for the "review1" linked review print element
    And I should see "Achievement level" in the 3rd selected content item for the "review1" linked review print element
    And I should see "No value achieved" in the 3rd selected content item for the "review1" linked review print element
    And I should see "Not proficient" in the 3rd selected content item for the "review1" linked review print element
    And I should see "Static content text" in the 3rd selected content item for the "review1" linked review print element
    And I should see "Your response" in the 3rd selected content item for the "review1" linked review print element
    And ".tui-linkedReviewParticipantForm__item:nth-child(3) .tui-longTextParticipantPrint .tui-notepadLines" "css_element" should exist
    And I should see "Subject response" in the 3rd selected content item for the "review1" linked review print element
    And I should see "User One" in the 3rd selected content item for the "review1" linked review print element
    And I should see "No response submitted" in the 3rd selected content item for the "review1" linked review print element
    And I should see "Option A" in the 3rd selected content item for the "review1" linked review print element
    And I should see "Option B" in the 3rd selected content item for the "review1" linked review print element
    And I should see "Option C" in the 3rd selected content item for the "review1" linked review print element

    # View the view only participant view
    When I log out
    And I log in as "user3"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link_or_button"
    And I click on "activity1" "link"

    # View only participant view: first selected competency: Doing paperwork
    And I should see "Doing paperwork" in the 1st selected content item for the "review1" linked review element
    And I should see "Doing paperwork description" in the 1st selected content item for the "review1" linked review element
    And I should see the current date in format "j/m/Y" in the ".tui-linkedReviewParticipantForm__item:nth-child(1) .tui-linkedReviewViewCompetency__timestamp" "css_element"
    And I should see "Reason assigned" in the 1st selected content item for the "review1" linked review element
    And I should see "Cohort 1 (Audience)" in the 1st selected content item for the "review1" linked review element
    And I should see "Achievement level" in the 1st selected content item for the "review1" linked review element
    And I should see "Competent" in the 1st selected content item for the "review1" linked review element
    And I should see "Proficient" in the 1st selected content item for the "review1" linked review element
    And I should see "Static content text" in the 1st selected content item for the "review1" linked review element
    And I should not see "Your response" in the 1st selected content item for the "review1" linked review element
    And I should see "Doing paperwork manager response" in the 1st selected content item for the "review1" linked review element
    And I should see a weka embedded image with the name "green.png" in the ".tui-linkedReviewParticipantForm__item:nth-child(1)" "css_element"
    And I should see "Subject response" in the 1st selected content item for the "review1" linked review element
    And I should see "Manager response" in the 1st selected content item for the "review1" linked review element
    And I should see "User One" in the 1st selected content item for the "review1" linked review element
    And I should see "User Two" in the 1st selected content item for the "review1" linked review element
    And I should see "Doing paperwork long text response" in the 1st selected content item for the "review1" linked review element
    And I should see a weka embedded image with the name "blue.png" in the ".tui-linkedReviewParticipantForm__item:nth-child(1)" "css_element"
    And I should see "Option A" in the 1st selected content item for the "review1" linked review element
    And I should not see "Option B" in the 1st selected content item for the "review1" linked review element
    And I should see "Option C" in the 1st selected content item for the "review1" linked review element

    # View only participant view: second selected competency: Managing people
    And I should see "Managing people" in the 2nd selected content item for the "review1" linked review element
    And I should see "Managing people description" in the 2nd selected content item for the "review1" linked review element
    And I should see the current date in format "j/m/Y" in the ".tui-linkedReviewParticipantForm__item:nth-child(2) .tui-linkedReviewViewCompetency__timestamp" "css_element"
    And I should see "Reason assigned" in the 2nd selected content item for the "review1" linked review element
    And I should see "Test Position 1 (Position)" in the 2nd selected content item for the "review1" linked review element
    And I should see "Achievement level" in the 2nd selected content item for the "review1" linked review element
    And I should see "Not competent" in the 2nd selected content item for the "review1" linked review element
    And I should see "Not proficient" in the 2nd selected content item for the "review1" linked review element
    And I should see "Static content text" in the 2nd selected content item for the "review1" linked review element
    And I should not see "Your response" in the 2nd selected content item for the "review1" linked review element
    And I should see "Subject response" in the 2nd selected content item for the "review1" linked review element
    And I should see "Manager response" in the 2nd selected content item for the "review1" linked review element
    And I should see "User One" in the 2nd selected content item for the "review1" linked review element
    And I should see "User Two" in the 2nd selected content item for the "review1" linked review element
    And I should see "Managing people long text response" in the 2nd selected content item for the "review1" linked review element
    And I should see a weka embedded image with the name "blue (1).png" in the ".tui-linkedReviewParticipantForm__item:nth-child(2)" "css_element"
    And I should not see "Option A" in the 2nd selected content item for the "review1" linked review element
    And I should see "Option B" in the 2nd selected content item for the "review1" linked review element
    And I should not see "Option C" in the 2nd selected content item for the "review1" linked review element

    # View only participant view: third selected competency: Locating stuff
    And I should see "Locating stuff" in the 3rd selected content item for the "review1" linked review element
    And I should see "Locating stuff description" in the 3rd selected content item for the "review1" linked review element
    And I should see the current date in format "j/m/Y" in the ".tui-linkedReviewParticipantForm__item:nth-child(3) .tui-linkedReviewViewCompetency__timestamp" "css_element"
    And I should see "Reason assigned" in the 3rd selected content item for the "review1" linked review element
    And I should see "Test Organisation 1 (Organisation)" in the 3rd selected content item for the "review1" linked review element
    And I should see "Achievement level" in the 3rd selected content item for the "review1" linked review element
    And I should see "No value achieved" in the 3rd selected content item for the "review1" linked review element
    And I should see "Not proficient" in the 3rd selected content item for the "review1" linked review element
    And I should see "Static content text" in the 3rd selected content item for the "review1" linked review element
    And I should not see "Your response" in the 3rd selected content item for the "review1" linked review element
    And I should see "Subject response" in the 3rd selected content item for the "review1" linked review element
    And I should see "Manager response" in the 3rd selected content item for the "review1" linked review element
    And I should see "User One" in the 3rd selected content item for the "review1" linked review element
    And I should see "User Two" in the 3rd selected content item for the "review1" linked review element
    And I should see "No response submitted" in the 3rd selected content item for the "review1" linked review element
    And I should see "Option A" in the 3rd selected content item for the "review1" linked review element
    And I should not see "Option B" in the 3rd selected content item for the "review1" linked review element
    And I should see "Option C" in the 3rd selected content item for the "review1" linked review element
