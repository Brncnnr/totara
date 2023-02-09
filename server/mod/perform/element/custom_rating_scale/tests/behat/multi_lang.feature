@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Rating scale: Custom element supports multi-lang filters in titles and options

  Background:
    Given I am on a totara site
    And I log in as "admin"
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name        | create_section | create_track | activity_status |
      | Add Element Activity | true           | true         | Draft           |

    # Enabling multi-language filters for headings and content.
    And the multi-language content filter is enabled

  Scenario: Set multi-lang text as question title and for options of the custom rating scale element type and make sure it's displayed correctly
    Given I navigate to the manage perform activities page
    And I click on "Add Element Activity" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it

    # Adding a new item
    And I navigate to manage perform activity content page
    And I add a "Rating scale: Custom" activity content element
    Then "rawTitle" "field" should be visible
    When I set the following fields to these values:
      | rawTitle                 | <span lang="en" class="multilang">it's an English question</span><span lang="de" class="multilang">deutsche Frage</span> |
      | options[0][value][text]  | <span lang="en" class="multilang">it's the first option</span><span lang="de" class="multilang">erste Option</span>      |
      | options[0][value][score] |   1                                                                                                                      |
      | options[1][value][text]  | <span lang="en" class="multilang">it's the second option</span><span lang="de" class="multilang">zweite Option</span>    |
      | options[1][value][score] |   2                                                                                                                     |
    # Currently a changed text won't be filtered until saved
    And I save the activity content element
    And I close the tui notification toast
    Then I should see "it's an English question"
    When I click on the Edit element button for question "it's an English question"
    Then the following fields match these values:
      | rawTitle | <span lang="en" class="multilang">it's an English question</span><span lang="de" class="multilang">deutsche Frage</span> |
    When I cancel saving the activity content element
    And I follow "Content (Add Element Activity)"
    And I click on "Edit content elements" "link_or_button"
    Then "rawTitle" "field" should not be visible
    And I should see "it's an English question"
    And I should not see "deutsche Frage"
    And I should see "it's the first option (score: 1)"
    And I should not see "erste Option (score: 1)"
    And I should see "it's the second option (score: 2)"
    And I should not see "zweite Option (score: 2)"
    When I click on the Edit element button for question "it's an English question"
    Then "rawTitle" "field" should be visible
    And the following fields match these values:
      | rawTitle   | <span lang="en" class="multilang">it's an English question</span><span lang="de" class="multilang">deutsche Frage</span> |
      | options[0][value][text] | <span lang="en" class="multilang">it's the first option</span><span lang="de" class="multilang">erste Option</span>                 |
      | options[1][value][text] | <span lang="en" class="multilang">it's the second option</span><span lang="de" class="multilang">zweite Option</span>               |
    When I set the following fields to these values:
      | rawTitle   | <span lang="en" class="multilang">changed & updated</span><span lang="de" class="multilang">geaendert & gespeichert</span>               |
      | options[0][value][text] | <span lang="en" class="multilang">it's the first changed option</span><span lang="de" class="multilang">erste geaenderte Option</span>   |
      | options[1][value][text] | <span lang="en" class="multilang">it's the second changed option</span><span lang="de" class="multilang">zweite geaenderte Option</span> |
    # Currently a changed text won't be filtered until saved
    And I save the activity content element
    And I close the tui notification toast
    Then I should see "changed & updated"
    When I click on the Edit element button for question "changed & updated"
    Then the following fields match these values:
      | rawTitle | <span lang="en" class="multilang">changed & updated</span><span lang="de" class="multilang">geaendert & gespeichert</span> |
    When I cancel saving the activity content element
    And I follow "Content (Add Element Activity)"
    And I click on "Edit content elements" "link_or_button"
    Then I should see "changed & updated"
    And I should not see "geaendert & gespeichert"
    And I should see "it's the first changed option (score: 1)"
    And I should not see "erste geaenderte Option (score: 1)"
    And I should see "it's the second changed option (score: 2)"
    And I should not see "zweite geaenderte Option (score: 2)"
    # Going back to edit mode and saving without changes should not change anything
    And I follow "Content (Add Element Activity)"
    And I click on "Edit content elements" "link_or_button"
    Then I should see "changed & updated"
    And I should not see "geaendert & gespeichert (score: 1)"
    And I should see "it's the first changed option (score: 1)"
    And I should not see "erste geaenderte Option (score: 1)"
    And I should see "it's the second changed option (score: 2)"
    And I should not see "zweite geaenderte Option (score: 2)"
    And I should not see "geaendert & gespeichert (score: 2)"
    And I follow "Content (Add Element Activity)"
