@totara @perform @totara_competency @javascript
Feature: Edit minimum proficiency values of competency assignments

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | user     | Staff     | User     |
    And a competency scale called "scale1" exists with the following values:
      | name | description | idnumber | proficient | default | sortorder |
      | 100% | 100 percent | 100      | 1          | 0       | 1         |
      | 90%  | 90 percent  | 90       | 0          | 0       | 2         |
      | 10%  | 10 percent  | 10       | 0          | 1       | 3         |
    And a competency scale called "scale2" exists with the following values:
      | name                   | description                            | idnumber    | proficient | default | sortorder |
      | Super Competent        | <strong>Is great at doing it.</strong> | super       | 1          | 0       | 1         |
      | Just Barely Competent  | Is okay at doing it.                   | barely      | 0          | 0       | 2         |
      | Incredibly Incompetent | <em>Is rubbish at doing it.</em>       | incompetent | 0          | 1       | 3         |
    And the following "competency" frameworks exist:
      | fullname                   | idnumber | description                     | scale  |
      | Percentage based Framework | pbfw     | Framework that uses percentages | scale1 |
      | Competency Framework       | fw       | Framework for competencies      | scale2 |
    And the following "competency" hierarchy exists:
      | framework | fullname    | idnumber    | description                                  | assignavailability |
      | pbfw      | Math        | math        | Adding, subtracting, and other calculations. | any                |
      | fw        | Typing slow | typing_slow | The ability to type <em>slow.</em>           | any                |
      | fw        | Typing fast | typing_fast | The ability to type <em>fast.</em>           | any                |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency  | user_group_type | user_group |
      | math        | user            | user       |
      | typing_slow | user            | user       |
      | typing_fast | user            | user       |

  Scenario: I can use filters and update values
    When I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I follow "Manage competency assignments"
    Then I should see "Math"
    And I should see "Typing slow"
    And I should see "Typing fast"

    When I follow "Edit proficiency value by assignment"
    Then I should see "Percentage based Framework" in the ".tw-selectTree__current_label" "css_element"
    And I should see "Math" in the "[data-tw-list-row=1]" "css_element"
    And I should see "100%" in the "[data-tw-list-row=1]" "css_element"

    # Test tooltip.
    When I click on "[data-flex-icon=help]" "css_element"
    Then I should see "The Minimum required proficiency value is the lowest value" in the ".moodle-dialogue-base" "css_element"
    And I click on "Close" "button" in the ".moodle-dialogue-base" "css_element"

    # Test filters, and basket selection
    When I click on ".tw-list__cell_select_label" "css_element"
    Then I should see "1" in the ".tw-selectionBasket__count_num" "css_element"

    When I click on ".tw-selectSearchText__field" "css_element"
    And I set the following fields to these values:
      | text_input | Spelling |
    And I click on ".tw-selectSearchText__btn" "css_element"
    Then I should see "No items to display"

    When I click on ".tw-selectSearchText__field" "css_element"
    And I set the following fields to these values:
      | text_input | Math |
    And I click on ".tw-selectSearchText__btn" "css_element"
    Then I should see "Math" in the "[data-tw-list-row=1]" "css_element"
    And I should see "100%" in the "[data-tw-list-row=1]" "css_element"

    When I click on "[data-tw-selectmulti-optionkey=position]" "css_element"
    Then I should see "No items to display"

    When I click on "[data-tw-selectmulti-optionkey=admin]" "css_element"
    Then I should see "Math" in the "[data-tw-list-row=1]" "css_element"
    And I should see "100%" in the "[data-tw-list-row=1]" "css_element"

    When I click on "[data-tw-selectmulti-optionkey=admin]" "css_element"
    Then I should see "No items to display"

    # Ensure the special clear override does not clear the top level framework filter.
    When I click on ".tw-selectRegionPanel__heading_clear_link" "css_element"
    Then I should see "Math" in the "[data-tw-list-row=1]" "css_element"
    And I should see "100%" in the "[data-tw-list-row=1]" "css_element"
    And I should see "1 item"

    When I click on "View selected" "link_or_button"
    Then I should see "Math" in the "[data-tw-list-row=1]" "css_element"
    And I should see "100%" in the "[data-tw-list-row=1]" "css_element"
    Then I should see "1" in the ".tw-selectionBasket__count_num" "css_element"

    # Test top level filter change confirmation.
    When I click on "Back" "link_or_button" in the ".tw-selectionBasket__actions" "css_element"
    And I click on ".tw-selectTree__current_label" "css_element"
    And I click on "Competency Framework" "link"
    Then I should see "Select competency framework" in the ".modal-header" "css_element"
    And I should see "all competencies you have selected will be removed" in the ".show .modal-body" "css_element"
    And I should see "Competency Framework" in the ".tw-selectTree__current_label" "css_element"
    And I should see "Typing fast" in the "[data-tw-list-row=3]" "css_element"
    And I should see "Super Competent" in the "[data-tw-list-row=3]" "css_element"
    And I should see "Typing slow" in the "[data-tw-list-row=2]" "css_element"
    And I should see "Super Competent" in the "[data-tw-list-row=2]" "css_element"
    And I should see "1" in the ".tw-selectionBasket__count_num" "css_element"

    When I click on "Cancel" "button" in the ".show .modal-footer" "css_element"
    Then I should see "Math" in the "[data-tw-list-row=1]" "css_element"
    And I should see "100%" in the "[data-tw-list-row=1]" "css_element"
    And I should see "1" in the ".tw-selectionBasket__count_num" "css_element"
    And I should see "Percentage based Framework" in the ".tw-selectTree__current_label" "css_element"

    When I click on ".tw-selectTree__current_label" "css_element"
    And I click on "Competency Framework" "link"
    And I click on "Confirm" "button" in the ".show .modal-footer" "css_element"
    Then I should see "0" in the ".tw-selectionBasket__count_num" "css_element"
    And I should see "Typing fast" in the "[data-tw-list-row=3]" "css_element"
    And I should see "Super Competent" in the "[data-tw-list-row=3]" "css_element"
    And I should see "Typing slow" in the "[data-tw-list-row=2]" "css_element"
    And I should see "Super Competent" in the "[data-tw-list-row=2]" "css_element"
    And I should see "Competency Framework" in the ".tw-selectTree__current_label" "css_element"

    # Test value setting
    When I click on ".tw-list__cell_select_label" "css_element"
    Then I should see "2" in the ".tw-selectionBasket__count_num" "css_element"

    When I click on "Edit" "link_or_button" in the ".tw-selectionBasket__actions" "css_element"
    Then I should see "Edit proficiency value for 2 assignment(s)"
    # The first scale value should be the default.
    And I should see "(Default)" in the ".show .modal-body .tw-assignComp__editProficiencyValueModal_label:nth-child(1)" "css_element"
    # These should be in sort order.
    And I should see "Super Competent" in the ".show .modal-body .tw-assignComp__editProficiencyValueModal_label:nth-child(1)" "css_element"
    And I should see "Just Barely Competent" in the ".show .modal-body .tw-assignComp__editProficiencyValueModal_label:nth-child(2)" "css_element"
    And I should see "Incredibly Incompetent" in the ".show .modal-body .tw-assignComp__editProficiencyValueModal_label:nth-child(3)" "css_element"
    And I should see "Remove assignment-specific proficiency value" in the ".show .modal-body .tw-assignComp__editProficiencyValueModal_label:last-child" "css_element"
    And the "Save changes" "button" should be disabled in the ".show .modal-footer" "css_element"

    # Select Just Barely.
    When I click on ".show .modal-body .tw-assignComp__editProficiencyValueModal_label:nth-child(2)" "css_element"
    Then the "Save changes" "button" should be enabled in the ".show .modal-footer" "css_element"

    When I click on "Save changes" "button" in the ".show .modal-footer" "css_element"
    Then I should see "Typing fast" in the "[data-tw-list-row=3]" "css_element"
    And I should see "Just Barely Competent" in the "[data-tw-list-row=3]" "css_element"
    And I should see "Y" in the "[data-tw-list-row=3]" "css_element"
    And I should see "Typing slow" in the "[data-tw-list-row=2]" "css_element"
    And I should see "Just Barely Competent" in the "[data-tw-list-row=2]" "css_element"
    And I should see "Y" in the "[data-tw-list-row=2]" "css_element"
    And I should see "The proficiency value for 2 assignments has been updated." in the ".alert-success" "css_element"

    # Select typing fast and clear it's override.
    When I click on "[data-tw-list-row=3] .tw-list__cell_select_label" "css_element"
    And I click on "Edit" "link_or_button" in the ".tw-selectionBasket__actions" "css_element"
    Then I should see "Edit proficiency value for 1 assignment(s)"

    When I click on ".show .modal-body .tw-assignComp__editProficiencyValueModal_label:last-child" "css_element"
    Then the "Save changes" "button" should be enabled in the ".show .modal-footer" "css_element"

    When I click on "Save changes" "button" in the ".show .modal-footer" "css_element"
    Then I should see "Typing fast" in the "[data-tw-list-row=3]" "css_element"
    And I should see "Super Competent" in the "[data-tw-list-row=3]" "css_element"
    And I should not see "Y" in the "[data-tw-list-row=3]" "css_element"
    And I should see "Typing slow" in the "[data-tw-list-row=2]" "css_element"
    And I should see "Just Barely Competent" in the "[data-tw-list-row=2]" "css_element"
    And I should see "Y" in the "[data-tw-list-row=2]" "css_element"
    And I should see "The proficiency value for 1 assignment has been updated." in the ".alert-success" "css_element"