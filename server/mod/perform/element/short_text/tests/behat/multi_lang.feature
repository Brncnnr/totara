@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Text: Short response element supports multi-lang filters in titles

  Background:
    Given I am on a totara site
    And I log in as "admin"
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name        | create_section | create_track | activity_status |
      | Add Element Activity | true           | true         | Draft           |
    # Enabling multi-language filters for headings and content.
    And the multi-language content filter is enabled

  Scenario: Set multi-lang text as question title for short text element type and make sure it's displayed correctly
    Given I navigate to the manage perform activities page
    And I click on "Add Element Activity" "link"

    # Adding a new item
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I click on "Edit content elements" "link_or_button"
    And I add a "Text: Short response" activity content element
    And I set the following fields to these values:
      | rawTitle | <span lang="en" class="multilang">it's an English question</span><span lang="de" class="multilang">deutsche Frage</span> |
    # Currently a changed text won't be filtered until saved
    And I save the activity content element
    And I close the tui notification toast
    Then I should see "it's an English question"
    When I click on the Edit element button for question "it's an English question"
    Then the following fields match these values:
      | rawTitle | <span lang="en" class="multilang">it's an English question</span><span lang="de" class="multilang">deutsche Frage</span> |
    When I save the activity content element
    And I follow "Content (Add Element Activity)"
    And I click on "Edit content elements" "link_or_button"
    Then "rawTitle" "field" should not be visible
    And I should see "it's an English question"
    And I should not see "deutsche Frage"
    When I click on the Edit element button for question "it's an English question"
    Then "rawTitle" "field" should be visible
    And the following fields match these values:
      | rawTitle | <span lang="en" class="multilang">it's an English question</span><span lang="de" class="multilang">deutsche Frage</span> |
    When I set the following fields to these values:
      | rawTitle | <span lang="en" class="multilang">changed & updated</span><span lang="de" class="multilang">geaendert & gespeichert</span> |
    # Currently a changed text won't be filtered until saved
    And I save the activity content element
    And I close the tui notification toast
    Then I should see "changed & updated"
    When I click on the Edit element button for question "changed & updated"
    Then the following fields match these values:
      | rawTitle | <span lang="en" class="multilang">changed & updated</span><span lang="de" class="multilang">geaendert & gespeichert</span> |
    When I save the activity content element
    And I follow "Content (Add Element Activity)"
    # Going back to edit mode and saving without changes should not change anything
    And I click on "Edit content elements" "link_or_button"
    Then I should see "changed & updated"
    And I should not see "geaendert & gespeichert"
    And I follow "Content (Add Element Activity)"