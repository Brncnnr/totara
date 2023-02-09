@totara @perform @mod_perform @perform_element @performelement_linked_review @totara_competency @javascript @vuejs
Feature: Selecting competency assignments linked to a performance review

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
    And the following "activity with section and review element" exist in "performelement_linked_review" plugin:
      | activity_name | section_title | element_title | content_type      |
      | activity1     | section1      | review1       | totara_competency |
    And the following "child elements" exist in "mod_perform" plugin:
      | section  | parent_element | element_plugin | element_title   | after_element   | data                                           |
      | section1 | review1        | long_text      | long text child |                 | {}                                             |
      | section1 | review1        | date_picker    | date child      | long text child | {"yearRangeStart": 1900, "yearRangeEnd": 2050} |
    And the following "participants in section" exist in "performelement_linked_review" plugin:
      | section  | subject_user | user  | relationship |
      | section1 | user1        | user1 | subject      |
      | section1 | user1        | user2 | manager      |

  Scenario: When I have no competencies assigned, nothing can happen
    When I log in as "user1"
    And I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"
    And I click on "Add competencies" "link_or_button"
    Then I should see "Select competencies" in the tui modal
    And I should see "No items to display" in the tui modal
    And the "Add" "button" should be disabled in the ".tui-modalContent" "css_element"
    When I click on "Cancel" "button" in the ".tui-modal" "css_element"
    Then I should not see "Select competencies"

  Scenario: Waiting for another user to select the competencies
    When I log in as "user2"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link_or_button"
    And I click on "activity1" "link"
    Then I should see "Awaiting competency selection from a Subject."

  Scenario: View only participant can select competencies
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user3    | User      | Three    | user3@example.com |
    And the following "activity with section and review element" exist in "performelement_linked_review" plugin:
      | activity_name | section_title | element_title | content_type      |
      | activity2     | section2      | review2       | totara_competency |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship | can_view | can_answer |
      | section2     | subject      | yes      | no         |
    And the following "participants in section" exist in "performelement_linked_review" plugin:
      | section  | subject_user | user  | relationship |
      | section2 | user1        | user1 | subject      |
    And the following "competency assignments" exist in "performelement_linked_review" plugin:
      | competency_name | user  | reason | manual_rating |
      | Doing paperwork | user1 | user   | Competent     |
    When I log in as "user1"
    And I navigate to the outstanding perform activities list page
    And I click on "activity2" "link"
    And I click on "Add competencies" "link_or_button"
    And I toggle the adder picker entry with "Doing paperwork" for "Competency"
    And I click on "Add" "button" in the ".tui-modal" "css_element"
    And I click on "Confirm selection" "button"
    Then I should see "Doing paperwork description"
    When I reload the page
    Then I should see "Doing paperwork description"

  Scenario: Browse, filter and select competencies via the competency assignment adder
    Given the following "competency assignments" exist in "performelement_linked_review" plugin:
      | competency_name   | user  | reason       | manual_rating              |
      | Doing paperwork   | user1 | cohort       | Competent                  |
      | Managing people   | user1 | position     | Not competent              |
      | Locating stuff    | user1 | organisation |                            |
      | Talking to people | user1 | user         | Competent with supervision |
    When I log in as "user1"
    And I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"
    And I click on "Add competencies" "link_or_button"

    # Competency assignment adder - no filters applied
    Then I should see "Select competencies" in the tui modal
    And I should not see "No items to display" in the tui modal
    And I should see "Items selected: 0" in the tui modal
    And I should see the tui datatable contains:
      | Competency        | Reason assigned                    | Proficient | Achievement level          |
      | Doing paperwork   | Cohort 1 (Audience)                | Yes        | Competent                  |
      | Locating stuff    | Test Organisation 1 (Organisation) | - No       | No value achieved          |
      | Managing people   | Test Position 1 (Position)         | - No       | Not competent              |
      | Talking to people | Admin User (Admin)                 | - No       | Competent with supervision |

    # Try add different filters
    When I click on "Show filters" "link_or_button"
    And I set the field "Filter items by search" to "Stuff"
    Then I should see the tui datatable contains:
      | Competency     | Reason assigned                    | Proficient | Achievement level |
      | Locating stuff | Test Organisation 1 (Organisation) | - No       | No value achieved |
    When I set the field "Filter items by search" to ""
    And I set the field "Reason assigned" to "Test Position 1"
    Then I should see the tui datatable contains:
      | Competency      | Reason assigned            | Proficient | Achievement level |
      | Managing people | Test Position 1 (Position) | - No       | Not competent     |
    When I set the field "Reason assigned" to ""
    And I set the field "Proficiency status" to "Proficient"
    Then I should see the tui datatable contains:
      | Competency      | Reason assigned     | Proficient | Achievement level |
      | Doing paperwork | Cohort 1 (Audience) | Yes        | Competent         |

    # Select some assignments
    When I set the field "Proficiency status" to "All"
    And I toggle the adder picker entry with "Locating stuff" for "Competency"
    And I toggle the adder picker entry with "Talking to people" for "Competency"
    And I toggle the adder picker entry with "Managing people" for "Competency"
    Then I should see "Items selected: 3" in the tui modal
    When I switch to "Selected" tui tab
    And I should see the tui datatable contains:
      | Competency        | Reason assigned                    | Proficient | Achievement level          |
      | Locating stuff    | Test Organisation 1 (Organisation) | - No       | No value achieved          |
      | Managing people   | Test Position 1 (Position)         | - No       | Not competent              |
      | Talking to people | Admin User (Admin)                 | - No       | Competent with supervision |
    When I toggle the adder picker entry with "Managing people" for "Competency"
    Then I should see "Items selected: 2" in the tui modal
    When I click on "Add" "button" in the ".tui-modal" "css_element"

    # Viewing the "Locating stuff" competency after adding it
    Then I should see "Locating stuff" in the ".tui-linkedReviewSelectedContent__item:nth-child(1)" "css_element"
    And I should see "Locating stuff description" in the ".tui-linkedReviewSelectedContent__item:nth-child(1)" "css_element"
    And I should see "Reason assigned" in the ".tui-linkedReviewSelectedContent__item:nth-child(1)" "css_element"
    And I should see "Test Organisation 1 (Organisation)" in the ".tui-linkedReviewSelectedContent__item:nth-child(1)" "css_element"
    And I should see "Achievement level" in the ".tui-linkedReviewSelectedContent__item:nth-child(1)" "css_element"
    And I should see "No value achieved" in the ".tui-linkedReviewSelectedContent__item:nth-child(1)" "css_element"
    And I should see "Not proficient" in the ".tui-linkedReviewSelectedContent__item:nth-child(1)" "css_element"
    When I click on "Show help for Rating scale" "button" in the ".tui-linkedReviewSelectedContent__item:nth-child(1)" "css_element"
    Then I should see "Rating scale" in the ".tui-linkedReviewSelectedContent__item:nth-child(1) .tui-competencyRatingScaleOverview" "css_element"
    And I should see "Competent" in the ".tui-linkedReviewSelectedContent__item:nth-child(1) .tui-competencyRatingScaleOverview" "css_element"
    And I should see "Competent with supervision" in the ".tui-linkedReviewSelectedContent__item:nth-child(1) .tui-competencyRatingScaleOverview" "css_element"
    And I should see "Not competent" in the ".tui-linkedReviewSelectedContent__item:nth-child(1) .tui-competencyRatingScaleOverview" "css_element"

    # Viewing the "Talking to people" competency after adding it
    Then I should see "Talking to people" in the ".tui-linkedReviewSelectedContent__item:nth-child(2)" "css_element"
    And I should see "Talking to people description" in the ".tui-linkedReviewSelectedContent__item:nth-child(2)" "css_element"
    And I should see "Admin User (Admin)" in the ".tui-linkedReviewSelectedContent__item:nth-child(2)" "css_element"
    And I should see "Competent with supervision" in the ".tui-linkedReviewSelectedContent__item:nth-child(2)" "css_element"
    And I should see "Not proficient" in the ".tui-linkedReviewSelectedContent__item:nth-child(2)" "css_element"
    When I click on "Show help for Rating scale" "button" in the ".tui-linkedReviewSelectedContent__item:nth-child(2)" "css_element"
    Then I should see "Rating scale" in the ".tui-linkedReviewSelectedContent__item:nth-child(2) .tui-competencyRatingScaleOverview" "css_element"

    # Previously selected assignments should be disabled in the adder
    When I click on "Add competencies" "button"
    Then I should see "Locating stuff" in the ".tui-dataTableRow--disabled.tui-dataTableRow--selected:nth-child(2)" "css_element"
    And I should see "Talking to people" in the ".tui-dataTableRow--disabled.tui-dataTableRow--selected:nth-child(4)" "css_element"
    When I click on "Cancel" "button" in the ".tui-modal" "css_element"

    # Remove competencies
    Then I should see "Confirm selection"
    And I should see "Locating stuff"
    And I should see "Talking to people"
    When I click on "Remove" "button" in the ".tui-linkedReviewSelectedContent__item:last-child" "css_element"
    Then I should see "Confirm selection"
    And I should see "Locating stuff"
    And I should not see "Talking to people"
    When I click on "Remove" "button" in the ".tui-linkedReviewSelectedContent__item" "css_element"
    Then I should not see "Confirm selection"
    And I should not see "Locating stuff"
    And I should not see "Talking to people"

    # Select some and confirm the selection
    When I click on "Add competencies" "link_or_button"
    And I toggle the adder picker entry with "Locating stuff" for "Competency"
    And I toggle the adder picker entry with "Talking to people" for "Competency"
    And I click on "Add" "button" in the ".tui-modal" "css_element"
    And I click on "Confirm selection" "button"
    Then I should see "Locating stuff description"
    And I should see "Talking to people description"
    When I reload the page
    Then I should see "Locating stuff description"
    And I should see "Talking to people description"