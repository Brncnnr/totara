@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Manage performance activity short text elements

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name        | create_section | create_track | activity_status |
      | Add Element Activity | true           | true         | Draft           |

  Scenario: Save required and optional short text elements
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    # Add multiple elements
    When I click on "Add Element Activity" "link"
    And I click on "Content" "link" in the ".tui-tabs__tabs" "css_element"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I click on "Edit content elements" "link_or_button"
    And I add a "Text: Short response" activity content element
    When I set the following fields to these values:
      | rawTitle   | Question 1   |
      | identifier | Identifier 1 |
    And I click on the "responseRequired" tui checkbox
    And I save the activity content element
    And I click on "Add element" "button"
    And I click on "Text: Short response" "button"
    When I set the following fields to these values:
      | rawTitle   | Question 2   |
    And I save the activity content element
    When I close the tui notification toast
    And I follow "Content (Add Element Activity)"
    Then I should see "1" in the "required" element summary of the activity section
    And I should see "1" in the "optional" element summary of the activity section
    And I should see "0" in the "other" element summary of the activity section
    When I click on "Edit content elements" "link_or_button"
    And I should see "Identifier 1" in the "Question 1" tui "card"
