@totara @perform @performelement_linked_review @totara_evidence @javascript
Feature: Selecting evidence linked to a performance review

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

    And the following "types" exist in "totara_evidence" plugin:
      | name                | user  | fields | description |
      | Evidence_Type_One   | admin | 1      | DESC_ONE    |
      | Evidence_Type_Two   | admin | 2      | DESC_TWO    |
      | Evidence_Type_Three | admin | 3      | DESC_THREE  |
    And the following "evidence" exist in "totara_evidence" plugin:
      | name           | user  | type                |
      | Evidence_One   | user1 | Evidence_Type_One   |
      | Evidence_Two   | user1 | Evidence_Type_Two   |
      | Evidence_Three | user2 | Evidence_Type_Three |

    And the following "activity with section and review element" exist in "performelement_linked_review" plugin:
      | activity_name | section_title | element_title   | content_type    | selection_relationships |
      | activity1     | section1      | Evidence review | totara_evidence | perform_peer            |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship | can_view | can_answer |
      | section1     | subject      | yes      | yes        |
      | section1     | manager      | yes      | yes        |
      | section1     | peer         | yes      | no         |
    And the following "participants in section" exist in "performelement_linked_review" plugin:
      | section  | subject_user | user  | relationship | can_answer |
      | section1 | user1        | user1 | subject      | true       |
      | section1 | user1        | user2 | manager      | true       |
      | section1 | user1        | user3 | perform_peer | false      |

  Scenario: Selecting participant can select evidence
    When I log in as "user3"
    And I navigate to the outstanding perform activities list page
    And I click on "As Peer" "link_or_button"
    And I click on "activity1" "link"
    And I click on "Add evidence" "link_or_button"
    Then I should not see "No items to display" in the tui modal
    And I should see "Items selected: 0" in the tui modal
    Then I should see the tui datatable contains:
      | Evidence     | Evidence type     |
      | Evidence_One | Evidence_Type_One |
      | Evidence_Two | Evidence_Type_Two |
    And I should not see "Evidence_Three"

    When I toggle the adder picker entry with "Evidence_Two" for "Evidence"
    And I should see "Items selected: 1" in the tui modal
    And I click on "Add" "button" in the ".tui-modal" "css_element"
    And I click on "Confirm selection" "button"
    Then I should see "Evidence review"
    And I should see "Custom Field #1"
    And I should see "Custom Field #2"
