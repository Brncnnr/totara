@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Manage performance activity date picker elements

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name | create_section | create_track | activity_status |
      | Activity one  | true           | true         | Draft           |

  Scenario: Save date picker elements to activity content
    Given I log in as "admin"
    And I navigate to the manage perform activities page

    # Add multiple elements
    And I click on "Activity one" "link"
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Date picker" activity content element

    When I save the activity content element
    Then I should see "rawTitle" form field has the tui validation error "Required"

    When I set the following fields to these values:
      | rawTitle       | Single fixed year date picker |
      | identifier     | Identifier 1                  |
      | yearRangeStart | 999                           |
      | yearRangeEnd   | 3000                          |
    And I save the activity content element
    Then I should see "yearRangeStart" form field has the tui validation error "Number must be 1900 or more"
    And I should see "yearRangeEnd" form field has the tui validation error "Number must be## +50 years ## Y## or less"

    When I set the following fields to these values:
      | yearRangeStart | 3001 |
    And I save the activity content element
    Then I should see "yearRangeStart" form field has the tui validation error "Year must be equal to or earlier than the year range ends"

    When I set the following fields to these values:
      | yearRangeStart | 2999 |
    And I save the activity content element
    Then I should see "yearRangeStart" form field has the tui validation error "Number must be## +50 years ## Y## or less"

    When I set the following fields to these values:
      | yearRangeStart | 100 |
      | yearRangeEnd   | 100 |
    And I save the activity content element
    Then I should see "yearRangeStart" form field has the tui validation error "Number must be 1900 or more"
    And I should see "yearRangeEnd" form field has the tui validation error "Number must be 1900 or more"

    When I set the following fields to these values:
      | yearRangeStart | 1000 |
      | yearRangeEnd   | 100  |
    And I save the activity content element
    Then I should see "yearRangeEnd" form field has the tui validation error "Year must be equal to or later than the year range begins"

    # Setting the year range values to things clearly outside the default range.
    When I set the following fields to these values:
      | yearRangeStart | 1900 |
      | yearRangeEnd   | 2071 |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast
    # Note that child 2 (is the first element)
    And I set the ".tui-performSectionContent__draggableItem:nth-child(2)" tui date selector to "1900-01-01"
    And I set the ".tui-performSectionContent__draggableItem:nth-child(2)" tui date selector to "2071-01-01"

    When I add a "Date picker" activity content element
    And I set the following fields to these values:
      | rawTitle | Floating year range date picker |
    And I save the activity content element
    Then I should see "Element saved" in the tui success notification toast
    # Note that child 3 (is the second element)
    And I set the ".tui-performSectionContent__draggableItem:nth-child(3)" tui date selector to "-50 years"
    And I set the ".tui-performSectionContent__draggableItem:nth-child(3)" tui date selector to "+ 50 years"

    Then I manually activate the perform activity "Activity one"
    And I reload the page
    And I click on "Element settings: Single fixed year date picker" "button"
    Then the perform element summary should contain:
      | Question text        | Single fixed year date picker |
      | Year range begins at | 1900                          |
      | Year range ends at   | 2071                          |
      | Reporting ID         | Identifier 1                  |

    When I click on "Close" "button"
    And I click on "Element settings: Floating year range date picker" "button"
    Then the perform element summary should contain:
      | Question text        | Floating year range date picker |
      | Year range begins at | ## -50 years ## Y##             |
      | Year range ends at   | ## +50 years ## Y##             |

  Scenario: Save required and optional date picker elements
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Activity one" "link"
    Then I should see "0" in the "required" element summary of the activity section
    And I should see "0" in the "optional" element summary of the activity section
    And I should see "0" in the "other" element summary of the activity section
    # Add multiple elements
    And I click the add responding participant button
    And I select "Appraiser" in the responding participants popover
    Then I should see "Activity saved" in the tui success notification toast and close it
    And I navigate to manage perform activity content page
    And I add a "Date picker" activity content element
    When I set the following fields to these values:
      | rawTitle | Question 1 |
    And I click on the "responseRequired" tui checkbox
    And I save the activity content element
    Then I should see "Required"
    And I add a "Date picker" activity content element
    When I set the following fields to these values:
      | rawTitle | Question 2 |
    And I save the activity content element
    When I close the tui notification toast
    And I follow "Content (Activity one)"
    Then I should see "1" in the "required" element summary of the activity section
    And I should see "1" in the "optional" element summary of the activity section
    And I should see "0" in the "other" element summary of the activity section
