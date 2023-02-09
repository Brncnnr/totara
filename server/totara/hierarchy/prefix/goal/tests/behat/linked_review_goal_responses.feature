@totara @perform @mod_perform @perform_element @performelement_linked_review @totara_hierarchy  @totara_hierarchy_goals @javascript @vuejs
Feature: Responding to goals linked to a performance review

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
      | activity_name | section_title | element_title        | content_type  |
      | activity1     | section1      | Personal goal review | personal_goal |
      | activity1     | section1      | Company goal review  | company_goal  |
    And the following "child elements" exist in "mod_perform" plugin:
      | section  | parent_element       | element_plugin | element_title  |
      | section1 | Personal goal review | short_text     | child personal |
      | section1 | Company goal review  | short_text     | child company  |
    And the following "participants in section" exist in "performelement_linked_review" plugin:
      | section  | subject_user | user  | relationship     | can_answer |
      | section1 | user1        | user1 | subject          | true       |
      | section1 | user1        | user2 | manager          | true       |
      | section1 | user1        | user3 | managers_manager | false      |
    And the following "goal" frameworks exist:
      | fullname      | idnumber      |
      | Company goals | Company goals |
    And the following "goal" hierarchy exists:
      | fullname                   | idnumber                   | framework     | description                                              | targetdate |
      | Company goal A             | Company goals A            | Company goals | <ul><li>Complete part 1</li><li>Complete part 2</li><ul> | 04/12/2045 |
      | Company goal to be deleted | Company goals to be delete | Company goals | I'm a company goal and I'm going to be deleted           |            |

    And I log out
    And I log in as "user1"
    And I am on "Goals" page
    And I press "Add company goal"
    And I click on "Company goal A" "link"
    And I click on "Company goal to be deleted" "link"
    And I press "Save"

    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name                | Personal goal A             |
      | Description         | Complete your personal goal |
      | Scale               | Goal scale                  |
      | targetdate[enabled] | 1                           |
      | targetdate[day]     | 4                           |
      | targetdate[month]   | 12                          |
      | targetdate[year]    | 2040                        |
    And I press "Save changes"

    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name        | Personal goal to be deleted              |
      | Description | I'm a personal goal that will be deleted |
      | Scale       | Goal scale                               |
    And I press "Save changes"

    And the following "selected content" exist in "performelement_linked_review" plugin:
      | element              | subject_user | selector_user | content_name    | content_name2               |
      | Company goal review  | user1        | user1         | Company goal A  | Company goal to be deleted  |
      | Personal goal review | user1        | user1         | Personal goal A | Personal goal to be deleted |

  Scenario: Make a response to a linked goal in a performance activity
    When I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"

    # No responses from Manager yet
    And I click show others responses
    Then I should see "Manager response"
    And I should see "No response submitted"

    When I click on "Personal goal A" "link" in the 1st selected content item for the "Personal goal review" linked review element
    Then I should see "Personal goal" in the ".view_personal_goal h2" "css_element"
    And I should see "Personal goal A"
    And I should see "Goal scale"
    And I should see "Goal assigned"
    And I should see "Complete your personal goal"

    # Selected company goal: Company goal A
    When I press the "back" button in the browser
    And I click on "Company goal A" "link" in the 1st selected content item for the "Company goal review" linked review element
    Then I should see "Company goals - Company goal A" in the "#region-main h2" "css_element"
    And I should see "Goal scale"
    And I should see "Complete part 1"
    And I should see "Complete part 2"
    And I should see "04/12/2045"

    When I press the "back" button in the browser

    Then I should see "Personal goal A" in the 1st selected content item for the "Personal goal review" linked review element
    And I should see "##today##j/m/Y##" in the 1st selected content item for the "Personal goal review" linked review element
    And I should see "Complete your personal goal" in the 1st selected content item for the "Personal goal review" linked review element
    And I should see "Goal assigned" in the 1st selected content item for the "Personal goal review" linked review element
    And I should see "Target date" in the 1st selected content item for the "Personal goal review" linked review element
    And I should see "4 December 2040" in the 1st selected content item for the "Personal goal review" linked review element

    Then I should see "Personal goal to be deleted" in the 2nd selected content item for the "Personal goal review" linked review element
    And I should see "##today##j/m/Y##" in the 2nd selected content item for the "Personal goal review" linked review element
    And I should see "I'm a personal goal that will be deleted" in the 2nd selected content item for the "Personal goal review" linked review element
    And I should see "Goal assigned" in the 2nd selected content item for the "Personal goal review" linked review element
    And I should not see "Target date" in the 2nd selected content item for the "Personal goal review" linked review element

    # Custom html descriptions should be supported (ul/li) is user defined.
    And I should see "Complete part 1" in the ".tui-linkedReviewViewGoal__description ul" "css_element"
    And I should see "Complete part 2" in the ".tui-linkedReviewViewGoal__description ul" "css_element"
    And I should see "##today##j/m/Y##" in the 1st selected content item for the "Company goal review" linked review element
    And I should see "Goal assigned" in the 1st selected content item for the "Company goal review" linked review element
    And I should see "Target date" in the 1st selected content item for the "Company goal review" linked review element
    And I should see "4 December 2045" in the 1st selected content item for the "Company goal review" linked review element

    And I should see "Company goal to be deleted" in the 2nd selected content item for the "Company goal review" linked review element
    And I should see "##today##j/m/Y##" in the 2nd selected content item for the "Company goal review" linked review element
    And I should see "I'm a company goal and I'm going to be deleted" in the 2nd selected content item for the "Company goal review" linked review element
    And I should see "Goal assigned" in the 2nd selected content item for the "Company goal review" linked review element
    And I should not see "Target date" in the 2nd selected content item for the "Company goal review" linked review element

    # Respond to the some child elements
    When I set the following fields to these values:
      | sectionElements[1][response][contentItemResponses][3][childElementResponses][3][response_data][response] | Still working on this         |
      | sectionElements[2][response][contentItemResponses][2][childElementResponses][4][response_data][response] | Still working on this one too |
    And I click on "Save as draft" "button"
    Then I should see "Draft saved" in the tui success notification toast

    When I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Section submitted" in the tui success notification toast

    # Go and delete one of each goal type
    When I am on "Goals" page
    And I click on ".company_table .lastrow [title=Delete]" "css_element"
    And I click on "Continue" "link_or_button"
    And I click on ".personal_table .lastrow [title=Delete]" "css_element"
    And I click on "Continue" "link_or_button"
    And I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"

    Then I should see "The goal no longer exists" in the 2nd selected content item for the "Personal goal review" linked review element

    And I should see "Company goal to be deleted" in the 2nd selected content item for the "Company goal review" linked review element
    And I should see "I'm a company goal and I'm going to be deleted" in the 2nd selected content item for the "Company goal review" linked review element
    And I should see "Goal assigned" in the 2nd selected content item for the "Company goal review" linked review element

    When I log out
    And I log in as "admin"
    And I navigate to "Manage goals" node in "Site administration > Goals"
    And I click on "Company goals" "link"
    And I click on "#goal-framework-index-1_r1_c1 [title=Delete]" "css_element"
    And I click on "Yes" "link_or_button"
    And I log out
    And I log in as "user1"
    And I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"
    Then I should see "The goal no longer exists" in the 2nd selected content item for the "Company goal review" linked review element

    When I log out
    And I log in as "admin"
    And I navigate to the mod perform response data report for "activity1" activity
    Then I should see "4" in the ".tui-elementResponseReportingActionCard__record-count" "css_element"

    And I should see "Personal goal review" in the "#rb_2_r0 .element_title" "css_element"
    And I should see "Review items" in the "#rb_2_r0 .element_type" "css_element"

    And I should see "child personal" in the "#rb_2_r1 .element_title" "css_element"
    And I should see "Text: Short response" in the "#rb_2_r1 .element_type" "css_element"

    And I should see "Company goal review" in the "#rb_2_r2 .element_title" "css_element"
    And I should see "Review items" in the "#rb_2_r2 .element_type" "css_element"

    And I should see "child company" in the "#rb_2_r3 .element_title" "css_element"
    And I should see "Text: Short response" in the "#rb_2_r3 .element_type" "css_element"
