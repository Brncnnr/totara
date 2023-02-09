@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Manage performance activity multiple choice elements

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name | create_section | create_track | activity_status |
      | Activity one  | true           | true         | Draft           |

  Scenario: Save multiple choice elements to activity content
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    #Add multiple elements
    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Multiple choice: single-select" activity content element
    And I set the following fields to these values:
      | rawTitle          | Question 1   |
      | options[0][value] | Option one   |
      | options[1][value] | Option two   |
      | identifier        | Identifier 1 |
    And I save the activity content element
    And I add a "Multiple choice: single-select" activity content element
    And I set the following fields to these values:
      | rawTitle          | Question 2   |
      | options[0][value] | Option three |
      | options[1][value] | Option four  |
    And I save the activity content element
    And I add a "Multiple choice: single-select" activity content element
    And I set the following fields to these values:
      | rawTitle          | Question 3  |
      | options[0][value] | Option five |
      | options[1][value] | Option six  |
    And I save the activity content element
    And I close the tui notification toast
    And I follow "Content (Activity one)"
    When I navigate to manage perform activity content page
    Then I should see perform multi choice single question "Question 1" is saved with options "Option one,Option two"
    And I should see perform multi choice single question "Question 2" is saved with options "Option three,Option four"
    And I should see perform multi choice single question "Question 3" is saved with options "Option five,Option six"
    And I should see "Identifier 1" in the "Question 1" tui "card"

  Scenario: Save multiple choice elements with more options
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Multiple choice: single-select" activity content element
    And I set the following fields to these values:
      | rawTitle   | Question 1 |
      | options[0][value] | Option one |
      | options[1][value] | Option two |
    And I click multi choice single question add new option
    And I set the following fields to these values:
      |options[2][value]        | Option three |
    And I save the activity content element
    And I close the tui notification toast
    And I follow "Content (Activity one)"
    And I navigate to manage perform activity content page
    Then I should see perform multi choice single question "Question 1" is saved with options "Option one,Option two,Option three"

  Scenario: Delete multiple choice elements options
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Multiple choice: single-select" activity content element
    And I set the following fields to these values:
      | rawTitle   | Question 1 |
      | options[0][value] | Option one |
      | options[1][value] | Option two |
    And I click multi choice single question add new option
    And I set the following fields to these values:
      | options[2][value] | Option three |
    And I delete multi choice single question option
    And I save the activity content element
    And I close the tui notification toast
    And I follow "Content (Activity one)"
    And I navigate to manage perform activity content page
    Then I should see perform multi choice single question "Question 1" is saved with options "Option one,Option two"

  Scenario: Save multiple choice elements shows validation
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Multiple choice: single-select" activity content element
    And I set the following fields to these values:
      | rawTitle | Question 1 |
    And I save the activity content element
    And I should see "Required"
    And I click on "Cancel" "button"

  Scenario: Save required and optional multiple choice elements
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Activity one" "link"
    Then I should see "0" in the "required" element summary of the activity section
    And I should see "0" in the "optional" element summary of the activity section
    And I should see "0" in the "other" element summary of the activity section
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    # Add multiple elements
    And I navigate to manage perform activity content page
    And I add a "Multiple choice: single-select" activity content element
    When I set the following fields to these values:
      | rawTitle   | Question 1 |
      | options[0][value] | Option one |
      | options[1][value] | Option two |
    And I click on the "responseRequired" tui checkbox
    And I save the activity content element
    Then I should see "Required"
    And I add a "Multiple choice: single-select" activity content element
    When I set the following fields to these values:
      | rawTitle   | Question 2 |
      | options[0][value] | Option one |
      | options[1][value] | Option two |
    And I save the activity content element
    When I close the tui notification toast
    And I follow "Content (Activity one)"
    Then I should see "1" in the "required" element summary of the activity section
    And I should see "1" in the "optional" element summary of the activity section
    And I should see "0" in the "other" element summary of the activity section
