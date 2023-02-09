@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Manage performance activity aggregation element.

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name        | description                         | activity_type | create_track | create_section | activity_status | anonymous_responses |
      | Aggregation Activity | We will average and sum some values | check-in      | true         | false          | Draft           | true                |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name        | section_name |
      | Aggregation Activity | Section one  |

  Scenario: I can create and update an aggregation perform element.
    Given I log in as "admin"
    When I navigate to the edit perform activities page for activity "Aggregation Activity"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I click on "Edit content elements" "link_or_button"
    And I add a "Response aggregation" activity content element
    Then the following fields match these values:
      | sourceSectionElementIds[0][value] | No available questions to select |
      | sourceSectionElementIds[1][value] | No available questions to select |

    When I click on "Save" "button"
    Then I should see "Required" in the ".tui-aggregationAdminEdit" "css_element"

    When I click on "Cancel" "button"
    And I add a "Rating scale: Numeric" activity content element
    And I set the following fields to these values:
      | rawTitle     | Scale one |
      | lowValue     | 1         |
      | highValue    | 10        |
      | defaultValue | 5         |
    And I click on "Save" "button"
    And I add a "Response aggregation" activity content element
    Then the following fields match these values:
      | sourceSectionElementIds[0][value] | Select question element... |
      | sourceSectionElementIds[1][value] | Select question element... |

    When I set the following fields to these values:
      | sourceSectionElementIds[0][value] | Scale one |
      | sourceSectionElementIds[1][value] | Scale one |
    And I click on "Save" "button"
    Then I should see "question can only be added once" in the ".tui-aggregationAdminEdit" "css_element"
    And I should see "Required" in the ".tui-aggregationAdminEdit" "css_element"

    When I click on "Cancel" "button"
    And I add a "Rating scale: Numeric" activity content element
    And I set the following fields to these values:
      | rawTitle     | Scale two |
      | lowValue     | 1         |
      | highValue    | 10        |
      | defaultValue | 5         |
    And I click on "Save" "button"
    And I add a "Response aggregation" activity content element
    And I set the following fields to these values:
      | rawTitle                          | Reviewing your scores |
      | sourceSectionElementIds[0][value] | Scale one             |
      | sourceSectionElementIds[1][value] | Scale two             |
      | excludedValues[0][value]          | 1                     |
    And I click on the "average" tui checkbox in the "calculations" tui checkbox group
    And I click on the "median" tui checkbox in the "calculations" tui checkbox group
    And I click on the "minimum" tui checkbox in the "calculations" tui checkbox group
    And I click on the "maximum" tui checkbox in the "calculations" tui checkbox group
    And I click on "Add an excluded value" "button"
    And I click on "Add an excluded value" "button"
    And I set the following fields to these values:
      | excludedValues[2][value] | 2    |
      | identifier               | 9000 |
    And I click on "Save" "button"
    Then I should see "Element saved" in the tui success notification toast
    And I should see "and the following values are excluded from calculation: 1, 2" in the ".tui-aggregationAdminView" "css_element"
    And I should see "Average: {calculated value}" in the ".tui-aggregationAdminView" "css_element"
    And I should see "Median: {calculated value}" in the ".tui-aggregationAdminView" "css_element"
    And I should see "Minimum: {calculated value}" in the ".tui-aggregationAdminView" "css_element"
    And I should see "Maximum: {calculated value}" in the ".tui-aggregationAdminView" "css_element"

    When I click on "Edit element: Reviewing your scores" "button"
    And I add a "Rating scale: Custom" activity content element
    And I set the field with css ".tui-customRatingScaleAdminEdit [name='rawTitle']" to "Custom scale"
    And I set the field with css ".tui-customRatingScaleAdminEdit [name='options[0][value][text]']" to "One"
    And I set the field with css ".tui-customRatingScaleAdminEdit [name='options[0][value][score]']" to "1"
    And I set the field with css ".tui-customRatingScaleAdminEdit [name='options[1][value][text]']" to "Two"
    And I set the field with css ".tui-customRatingScaleAdminEdit [name='options[1][value][score]']" to "2"
    And I click on ".tui-customRatingScaleAdminEdit button[type='submit']" "css_element"
    And I click on "Add question" "button"
    And I set the following fields to these values:
      | rawTitle                          | Reviewing your scores |
      | sourceSectionElementIds[2][value] | Custom scale          |
      | excludedValues[0][value]          |                       |
      | excludedValues[1][value]          |                       |
      | excludedValues[2][value]          |                       |
    And I click on "Save" "button"
    Then I should see "Element saved" in the tui success notification toast
    And I should see "Unanswered questions are excluded from calculation." in the ".tui-aggregationAdminView" "css_element"
