@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Manage performance activity multiple choice-answers elements

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name | create_section | create_track | activity_status |
      | Activity one  | true           | true         | Draft           |

  Scenario: Save multiple choice multiple answers elements to activity content
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    #Add multiple elements
    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Multiple choice: multi-select" activity content element
    And I click multiple answers question add new option
    And I set the following fields to these values:
      | rawTitle          | Question 1   |
      | options[0][value] | Option one   |
      | options[1][value] | Option two   |
      | identifier        | Identifier 1 |
    And I save the activity content element
    And I add a "Multiple choice: multi-select" activity content element
    And I click multiple answers question add new option
    And I set the following fields to these values:
      | rawTitle          | Question 2   |
      | options[0][value] | Option three |
      | options[1][value] | Option four  |
    And I save the activity content element
    And I wait "5" seconds
    And I add a "Multiple choice: multi-select" activity content element
    And I wait "5" seconds
    And I click multiple answers question add new option
    And I set the following fields to these values:
      | rawTitle          | Question 3  |
      | options[0][value] | Option five |
      | options[1][value] | Option six  |
    And I save the activity content element
    When I close the tui notification toast
    And I follow "Content (Activity one)"
    When I navigate to manage perform activity content page
    Then I should see perform multiple answers question "Question 1" is saved with options "Option one,Option two"
    And I should see perform multiple answers question "Question 2" is saved with options "Option three,Option four"
    And I should see perform multiple answers question "Question 3" is saved with options "Option five,Option six"
    And I should see "Identifier 1" in the "Question 1" tui "card"

  Scenario: Save multiple choice multiple answers elements with more options
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Multiple choice: multi-select" activity content element
    And I click multiple answers question add new option
    And I set the following fields to these values:
      | rawTitle          | Question 1 |
      | options[0][value] | Option one |
      | options[1][value] | Option two |
    And I click multiple answers question add new option
    And I set the following fields to these values:
      | options[2][value] | Option three |
    And I save the activity content element
    And I close the tui notification toast
    And I follow "Content (Activity one)"
    And I navigate to manage perform activity content page
    Then I should see perform multiple answers question "Question 1" is saved with options "Option one,Option two,Option three"

  Scenario: Delete multiple answers elements options
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Multiple choice: multi-select" activity content element
    And I click multiple answers question add new option
    And I set the following fields to these values:
      | rawTitle          | Question 1 |
      | options[0][value] | Option one |
      | options[1][value] | Option two |
    And I click multiple answers question add new option
    And I set the following fields to these values:
      | options[2][value] | Option three |
    And I delete multiple answers question option
    And I save the activity content element
    And I close the tui notification toast
    And I follow "Content (Activity one)"
    And I navigate to manage perform activity content page
    Then I should see perform multiple answers question "Question 1" is saved with options "Option one,Option three"

  Scenario: Save multiple answers elements shows validation
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Multiple choice: multi-select" activity content element
    And I set the following fields to these values:
      | rawTitle | Question 1 |
    And I save the activity content element
    And I should see "Required"
    And I click on "Cancel" "button"
