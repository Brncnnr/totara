@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Manage performance activity custom rating scale elements

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name | create_section | create_track | activity_status |
      | Activity one  | true           | true         | Draft           |

  Scenario: Save custom rating scale elements to activity content
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    #Add multiple elements
    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Rating scale: Custom" activity content element
    And I set the following fields to these values:
      | rawTitle                 | Question 1   |
      | options[0][value][text]  | Option one   |
      | options[0][value][score] | 1            |
      | options[1][value][text]  | Option two   |
      | options[1][value][score] | 2            |
      | identifier               | Identifier 1 |
    And I save the activity content element
    And I add a "Rating scale: Custom" activity content element
    And I set the following fields to these values:
      | rawTitle                 | Question 2   |
      | options[0][value][text]  | Option three |
      | options[0][value][score] | 3            |
      | options[1][value][text]  | Option four  |
      | options[1][value][score] | 4            |
    And I save the activity content element
    And I add a "Rating scale: Custom" activity content element
    And I set the following fields to these values:
      | rawTitle                 | Question 3  |
      | options[0][value][text]  | Option five |
      | options[0][value][score] | 5           |
      | options[1][value][text]  | Option six  |
      | options[1][value][score] | 6           |
    And I save the activity content element
    And I close the tui notification toast
    And I follow "Content (Activity one)"
    When I navigate to manage perform activity content page
    Then I should see perform custom rating scale question "Question 1" is saved with options "Option one (score: 1),Option two (score: 2)"
    And I should see perform custom rating scale question "Question 2" is saved with options "Option three (score: 3),Option four (score: 4)"
    And I should see perform custom rating scale question "Question 3" is saved with options "Option five (score: 5),Option six (score: 6)"
    Then I should see "Identifier 1" in the "Question 1" tui "card"

  Scenario: Save custom rating scale elements with more options
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Rating scale: Custom" activity content element
    And I set the following fields to these values:
      | rawTitle                 | Question 1 |
      | options[0][value][text]  | Option one |
      | options[0][value][score] | 1          |
      | options[1][value][text]  | Option two |
      | options[1][value][score] | 2          |

    And I click on the "options[0][descriptionEnabled]" tui checkbox
    And I activate the weka editor with css "[aria-label='Description 1']"
    And I set the weka editor to "A description that was disabled"
    And I click on the "options[0][descriptionEnabled]" tui checkbox

    And I click on the "options[1][descriptionEnabled]" tui checkbox
    And I activate the weka editor with css "[aria-label='Description 2']"
    And I click on the "Bold" toolbar button in the weka editor
    And I type "A strong description" in the weka editor

    And I click custom rating scale question add new option
    And I set the following fields to these values:
      | options[2][value][text]  | Option three |
      | options[2][value][score] | 3            |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast
    And I should see perform custom rating scale question "Question 1" is saved with options "Option one (score: 1),Option two (score: 2),Option three (score: 3)"
    And I should not see "A description that was disabled"
    # Make sure we have rendered actual html (bold text) in the summary for description.
    And I should see "A strong description" in the ".tui-hideShow__content--show p strong" "css_element"

    When I manually activate the perform activity "Activity one"
    And I reload the page
    And I click on "Element settings: Question 1" "button"
    Then the perform element summary should contain:
      | Question text | Question 1 |
    And I should not see "A description that was disabled"
    # Make sure we have rendered actual html (bold text) in the summary for description.
    And I should see "A strong description" in the ".tui-performAdminCustomElementSummary__section-valueDescription p strong" "css_element"
    And I should see "Option one (score: 1)" in the ".tui-performAdminCustomElementSummary__section-options" "css_element"
    And I should see "Option two (score: 2)" in the ".tui-performAdminCustomElementSummary__section-options" "css_element"
    And I should see "Option three (score: 3)" in the ".tui-performAdminCustomElementSummary__section-options" "css_element"

  Scenario: Delete custom rating scale elements options
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Rating scale: Custom" activity content element
    And I set the following fields to these values:
      | rawTitle                 | Question 1 |
      | options[0][value][text]  | Option one |
      | options[0][value][score] | 1          |
      | options[1][value][text]  | Option two |
      | options[1][value][score] | 2          |
    And I click custom rating scale question add new option
    And I set the following fields to these values:
      | options[2][value][text]  | Option three |
      | options[2][value][score] | 3            |
    And I delete custom rating scale question option
    And I save the activity content element
    And I close the tui notification toast
    And I follow "Content (Activity one)"
    And I navigate to manage perform activity content page
    Then I should see perform custom rating scale question "Question 1" is saved with options "Option one (score: 1),Option two (score: 2)"

  Scenario: Save custom rating scale elements shows validation
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    When I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Rating scale: Custom" activity content element
    And I click on the "options[0][descriptionEnabled]" tui checkbox
    And I save the activity content element
    Then I should see "rawTitle" form field has the tui validation error "Required"
    And I should see "options[0][value][text]" form field has the tui validation error "Required"
    And I should see "options[0][value][score]" form field has the tui validation error "Required"
    And I should see "options[1][value][text]" form field has the tui validation error "Required"
    And I should see "options[1][value][score]" form field has the tui validation error "Required"
    And I should see "Weka editor" form field has the tui validation error "Required"

    When I set the following fields to these values:
      | rawTitle                 | Question 1 |
      | options[0][value][text]  | Option one |
      | options[0][value][score] | 1          |
      | options[1][value][text]  | Option two |
      | options[1][value][score] | 2          |
    And I click on the "options[0][descriptionEnabled]" tui checkbox
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast

  Scenario: Save required and optional custom rating scale elements
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
    And I add a "Rating scale: Custom" activity content element
    When I set the following fields to these values:
      | rawTitle                 | Question 1 |
      | options[0][value][text]  | Option one |
      | options[0][value][score] | 1          |
      | options[1][value][text]  | Option two |
      | options[1][value][score] | 2          |
    And I click on the "responseRequired" tui checkbox
    And I save the activity content element
    Then I should see "Required"
    When I add a "Rating scale: Custom" activity content element
    And I set the following fields to these values:
      | rawTitle                 | Question 2 |
      | options[0][value][text]  | Option one |
      | options[0][value][score] | 1          |
      | options[1][value][text]  | Option two |
      | options[1][value][score] | 2          |
    And I save the activity content element
    When I close the tui notification toast
    And I follow "Content (Activity one)"
    Then I should see "1" in the "required" element summary of the activity section
    And I should see "1" in the "optional" element summary of the activity section
    And I should see "0" in the "other" element summary of the activity section