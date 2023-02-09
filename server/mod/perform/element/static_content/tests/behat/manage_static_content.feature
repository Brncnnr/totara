@totara @perform @mod_perform @perform_element @javascript @vuejs @editor_weka @weka
Feature: Manage performance activity static content

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name        | create_section | create_track | activity_status |
      | Add Element Activity | true           | true         | Draft           |

  Scenario: Save static content elements
    Given I log in as "admin"

    # Add a static content element
    When I navigate to the edit perform activities page for activity "Add Element Activity"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I click on "Edit content elements" "link_or_button"
    And I add a "Static content" activity content element
    And I set the following fields to these values:
      | rawTitle | Static content 1 |
    And I activate the weka editor with css ".tui-weka"
    And I type "I see trees of green, red roses too. I see them bloom for me and you. And I think to myself what a wonderful world." in the weka editor
    And I wait for the next second
    And I save the activity content element
    Then I should see "Element saved"
    And I wait for pending js
    And I should see "I see them bloom for me and you."
    When I follow "Content (Add Element Activity)"
    Then I should see "1" in the "other" element summary of the activity section

    # Edit a static content element
    When I click on "Edit content elements" "link_or_button"
    And I click on "Edit element" "button"
    And I activate the weka editor with css ".tui-weka"
    And I type "Changed static content" in the weka editor
    And I wait for the next second
    And I save the activity content element
    Then I should see "Element saved"
    And I should see "Changed static content"
    When I follow "Content (Add Element Activity)"

  Scenario: Save static content elements with no title
    Given I log in as "admin"

     # Add a static content element with no title
    When I navigate to the edit perform activities page for activity "Add Element Activity"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I click on "Edit content elements" "link_or_button"
    And I add a "Static content" activity content element
    And I activate the weka editor with css ".tui-weka"
    And I type "I see trees of green, red roses too. I see them bloom for me and you. And I think to myself what a wonderful world." in the weka editor
    And I wait for the next second
    And I save the activity content element
    Then I should see "Element saved"
    And I wait for pending js
    And I should see "I see them bloom for me and you."
    When I follow "Content (Add Element Activity)"
    Then I should see "1" in the "other" element summary of the activity section

  Scenario: Save static content elements with no content
    Given I log in as "admin"
    # Add a static content element with no content and no title
    When I navigate to the edit perform activities page for activity "Add Element Activity"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I click on "Edit content elements" "link_or_button"
    And I add a "Static content" activity content element
    And I wait for the next second
    And I save the activity content element
    Then I should see "Required"
    And I cancel saving the activity content element

    # Add a static content element with no content
    And I add a "Static content" activity content element
    And I set the following fields to these values:
      | rawTitle | Static content 1 |
    And I wait for the next second
    And I save the activity content element
    Then I should see "Required"
    And I click on "Cancel" "button"
