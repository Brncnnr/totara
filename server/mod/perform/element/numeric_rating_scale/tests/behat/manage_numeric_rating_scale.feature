@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Manage performance activity numeric rating scale elements

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name | create_section | create_track | activity_status |
      | Activity one  | true           | true         | Draft           |

  Scenario: Save numeric rating scale elements to activity content
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page

    And I add a "Rating scale: Numeric" activity content element
    And I set the following fields to these values:
      | rawTitle     | Simple scale |
      | lowValue     | 0            |
      | highValue    | 10           |
      | defaultValue | 5            |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast

    And I add a "Rating scale: Numeric" activity content element
    And I click on the "descriptionEnabled" tui checkbox
    And I click on the "responseRequired" tui checkbox
    And I set the following fields to these values:
      | rawTitle     | Complex scale  |
      | lowValue     | -10            |
      | highValue    | 10             |
      | defaultValue | 5              |
      | identifier   | reporting id 1 |
    And I activate the weka editor with css ".tui-weka"
    And I click on the "Bold" toolbar button in the weka editor
    And I type "A strong description" in the weka editor
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast

    And I add a "Rating scale: Numeric" activity content element
    And I click on the "descriptionEnabled" tui checkbox
    And I click on the "responseRequired" tui checkbox
    And I set the following fields to these values:
      | rawTitle     | Disabled description scale |
      | lowValue     | 0                          |
      | highValue    | 10                         |
      | defaultValue | 5                          |
      | identifier   | reporting id 2             |
    And I activate the weka editor with css ".tui-weka"
    And I set the weka editor to "A description that was disabled"
    And I click on the "descriptionEnabled" tui checkbox
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast

    When I click on "Edit element: Disabled description scale" "button"
    And I click on the "descriptionEnabled" tui checkbox
    Then I should see "A description that was disabled" in the weka editor

    When I click on "Cancel" "button"
    And I reload the page
    Then I should see "0" in the ".tui-range__lowLabel" "css_element" of perform admin element "Simple scale"
    And I should see "10" in the ".tui-range__highLabel" "css_element" of perform admin element "Simple scale"

    And I should see "*" in the ".tui-performAdminCustomElement__content-title" "css_element" of perform admin element "Complex scale"
    And I should see "-10" in the ".tui-range__lowLabel" "css_element" of perform admin element "Complex scale"
    And I should see "10" in the ".tui-range__highLabel" "css_element" of perform admin element "Complex scale"
    And I should see "reporting id 1" in the ".tui-performAdminCustomElement__lozenge" "css_element" of perform admin element "Complex scale"

    And I should see "*" in the ".tui-performAdminCustomElement__content-title" "css_element" of perform admin element "Disabled description scale"
    And I should see "0" in the ".tui-range__lowLabel" "css_element" of perform admin element "Disabled description scale"
    And I should see "10" in the ".tui-range__highLabel" "css_element" of perform admin element "Disabled description scale"
    And I should see "reporting id 2" in the ".tui-performAdminCustomElement__lozenge" "css_element" of perform admin element "Disabled description scale"

    When I manually activate the perform activity "Activity one"
    And I reload the page
    And I click on "Element settings: Simple scale" "button"
    Then the perform element summary should contain:
      | Question text         | Simple scale |
      | Minimum numeric value | 0            |
      | Maximum numeric value | 10           |
      | Default value         | 5            |

    When I click on "Close" "button"
    And I click on "Element settings: Complex scale" "button"
    Then the perform element summary should contain:
      | Question text         | Complex scale        |
      | Minimum numeric value | -10                  |
      | Maximum numeric value | 10                   |
      | Default value         | 5                    |
      | Description           | A strong description |
      | Reporting ID          | reporting id 1       |
    # Make sure we have rendered actual html (bold text) in the summary for description.
    And I should see "A strong description" in the ".tui-performAdminCustomElementSummary__section-htmlValue p strong" "css_element"

    When I click on "Close" "button"
    And I click on "Element settings: Disabled description scale" "button"
    Then the perform element summary should contain:
      | Question text         | Disabled description scale |
      | Minimum numeric value | 0                          |
      | Maximum numeric value | 10                         |
      | Default value         | 5                          |
      | Reporting ID          | reporting id 2             |

    When I follow "Content (Activity one)"
    Then I should see "2" in the "required" element summary of the activity section
    And I should see "1" in the "optional" element summary of the activity section
    And I should see "0" in the "other" element summary of the activity section

  Scenario: Save numeric rating scale elements shows validation
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Rating scale: Numeric" activity content element
    And I click on the "descriptionEnabled" tui checkbox
    And I save the activity content element
    Then I should see "rawTitle" form field has the tui validation error "Required"
    And I should see "lowValue" form field has the tui validation error "Required"
    And I should see "highValue" form field has the tui validation error "Required"
    And I should see "defaultValue" form field has the tui validation error "Required"
    And I should see "Weka editor" form field has the tui validation error "Required"

    When I set the following fields to these values:
      | lowValue     | 101 |
      | highValue    | 50  |
      | defaultValue | 50  |
    And I save the activity content element
    Then I should see "highValue" form field has the tui validation error "Number must be 103 or more"
    And I should see "defaultValue" form field has the tui validation error "Number must be 101 or more"
    And I should see "Weka editor" form field has the tui validation error "Required"

    When I set the following fields to these values:
      | rawTitle     | My scale |
      | highValue    | 103      |
      | defaultValue | 104      |
    And I save the activity content element
    Then I should see "defaultValue" form field has the tui validation error "Number must be 103 or less"

    When I set the following fields to these values:
      | rawTitle     | My scale |
      | defaultValue | 101      |
    And I click on the "descriptionEnabled" tui checkbox
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast