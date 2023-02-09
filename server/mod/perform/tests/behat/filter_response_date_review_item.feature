@totara @perform @mod_perform @performelement_linked_review @totara_reportbuilder @javascript
Feature: Test view and filter performance response data report with Review type filter

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And the following "activity with section and review element" exist in "performelement_linked_review" plugin:
      | activity_name | section_title | element_title        | content_type  |
      | activity1     | section1      | Personal goal review | personal_goal |
      | activity1     | section1      | Company goal review  | company_goal  |
    And the following "child elements" exist in "mod_perform" plugin:
      | section  | parent_element       | element_plugin | element_title  |
      | section1 | Personal goal review | short_text     | child personal |
      | section1 | Company goal review  | short_text     | child company  |
    And the following "participants in section" exist in "performelement_linked_review" plugin:
      | section  | subject_user | user  | relationship     | can_answer |
      | section1 | user1        | user1 | subject          | true       |
    And the following "goal" frameworks exist:
      | fullname      | idnumber      |
      | Company goals | Company goals |
    And the following "goal" hierarchy exists:
      | fullname       | idnumber        | framework     |
      | Company goal A | Company goals A | Company goals |
      | Company goal B | Company goals B | Company goals |

    And I log out

  Scenario: I can filter review type response data report
    Given I log in as "user1"
    And I am on "Goals" page
    And I press "Add company goal"
    And I click on "Company goal A" "link"
    And I click on "Company goal B" "link"
    And I press "Save"
    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name                | Personal goal A |
      | Scale               | Goal scale      |
    And I press "Save changes"
    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name        | Personal goal B |
      | Scale       | Goal scale      |
    And I press "Save changes"
    And the following "selected content" exist in "performelement_linked_review" plugin:
      | element              | subject_user | selector_user | content_name    | content_name2   |
      | Company goal review  | user1        | user1         | Company goal A  | Company goal B  |
      | Personal goal review | user1        | user1         | Personal goal A | Personal goal B |
    And I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"
    # Respond to the some child elements
    And I set the following fields to these values:
      | sectionElements[1][response][contentItemResponses][3][childElementResponses][3][response_data][response] | Still working on this         |
      | sectionElements[2][response][contentItemResponses][2][childElementResponses][4][response_data][response] | Still working on this one too |
    And I click on "Save as draft" "button"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I log out

    And I log in as "admin"

    When I navigate to the mod perform response data report for "activity1" activity
    Then I should see "4" in the ".tui-elementResponseReportingActionCard__record-count" "css_element"
    When I click on "View as report" "button"
    And I click on "View" "button" in the ".tui-modal" "css_element"
    Then I should see "Performance data for activity1"
    And the following should exist in the "perform_response_data" table:
      | Activity name | Element type         | Element text   | Element response              | Parent element type         | Review type   | Review item name |
      | activity1     | Text: Short response | child personal | Still working on this         | Review items: Personal goal | Personal goal | Personal goal A  |
      | activity1     | Text: Short response | child personal |                               | Review items: Personal goal | Personal goal | Personal goal B  |
      | activity1     | Text: Short response | child company  |                               | Review items: Company goal  | Company goal  | Company goal A   |
      | activity1     | Text: Short response | child company  | Still working on this one too | Review items: Company goal  | Company goal  | Company goal B   |

    When I set the field "additional-linked_review_content_type_op" to "is equal to"
    And I click on "Choose review types" "button"
    And I click on "Personal goal" "link" in the "Choose review types" "totaradialogue"
    And I click on "Save" "button" in the "Choose review types" "totaradialogue"
    And I wait until ".fitem_actionbuttons" "css_element" exists
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    Then the following should exist in the "perform_response_data" table:
      | Activity name | Element text   | Parent element type         | Review type   | Review item name |
      | activity1     | child personal | Review items: Personal goal | Personal goal | Personal goal A  |
      | activity1     | child personal | Review items: Personal goal | Personal goal | Personal goal B  |

    And the following should not exist in the "perform_response_data" table:
      | Activity name | Element text   | Parent element type         | Review type   | Review item name |
      | activity1     | child company  | Review items: Company goal  | Company goal  | Company goal A   |
      | activity1     | child company  | Review items: Company goal  | Company goal  | Company goal B   |

    When I set the field "additional-linked_review_content_type_op" to "isn't equal to"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    Then the following should not exist in the "perform_response_data" table:
      | Activity name | Element text   | Parent element type         | Review type   | Review item name |
      | activity1     | child personal | Review items: Personal goal | Personal goal | Personal goal A  |
      | activity1     | child personal | Review items: Personal goal | Personal goal | Personal goal B  |

    And the following should exist in the "perform_response_data" table:
      | Activity name | Element text   | Parent element type         | Review type   | Review item name |
      | activity1     | child company  | Review items: Company goal  | Company goal  | Company goal A   |
      | activity1     | child company  | Review items: Company goal  | Company goal  | Company goal B   |

    And I click on "Clear" "button" in the ".fitem_actionbuttons" "css_element"

    When I set the field "additional-linked_review_content_type_op" to "is equal to"
    And I click on "Choose review types" "button"
    And I click on "Company goal" "link" in the "Choose review types" "totaradialogue"
    And I click on "Save" "button" in the "Choose review types" "totaradialogue"
    And I wait until ".fitem_actionbuttons" "css_element" exists
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    Then the following should not exist in the "perform_response_data" table:
      | Activity name | Element text   | Parent element type         | Review type   | Review item name |
      | activity1     | child personal | Review items: Personal goal | Personal goal | Personal goal A  |
      | activity1     | child personal | Review items: Personal goal | Personal goal | Personal goal B  |

    And the following should exist in the "perform_response_data" table:
      | Activity name | Element text   | Parent element type         | Review type   | Review item name |
      | activity1     | child company  | Review items: Company goal  | Company goal  | Company goal A   |
      | activity1     | child company  | Review items: Company goal  | Company goal  | Company goal B   |

    When I set the field "additional-linked_review_content_type_op" to "isn't equal to"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    Then the following should exist in the "perform_response_data" table:
      | Activity name | Element text   | Parent element type         | Review type   | Review item name |
      | activity1     | child personal | Review items: Personal goal | Personal goal | Personal goal A  |
      | activity1     | child personal | Review items: Personal goal | Personal goal | Personal goal B  |

    And the following should not exist in the "perform_response_data" table:
      | Activity name | Element text   | Parent element type         | Review type   | Review item name |
      | activity1     | child company  | Review items: Company goal  | Company goal  | Company goal A   |
      | activity1     | child company  | Review items: Company goal  | Company goal  | Company goal B   |

    And I click on "Clear" "button" in the ".fitem_actionbuttons" "css_element"

    When I set the field "additional-linked_review_content_type_op" to "is equal to"
    And I click on "Choose review types" "button"
    And I click on "Evidence" "link" in the "Choose review types" "totaradialogue"
    And I click on "Save" "button" in the "Choose review types" "totaradialogue"
    And I wait until ".fitem_actionbuttons" "css_element" exists
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "There are no records that match your selected criteria"

    And I click on "Clear" "button" in the ".fitem_actionbuttons" "css_element"

    When I set the field "additional-linked_review_content_type_op" to "is equal to"
    And I click on "Choose review types" "button"
    And I click on "Company goal" "link" in the "Choose review types" "totaradialogue"
    And I click on "Personal goal" "link" in the "Choose review types" "totaradialogue"
    And I click on "Save" "button" in the "Choose review types" "totaradialogue"
    And I wait until ".fitem_actionbuttons" "css_element" exists
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And the following should exist in the "perform_response_data" table:
      | Activity name | Element type         | Element text   | Element response              | Parent element type         | Review type   | Review item name |
      | activity1     | Text: Short response | child personal | Still working on this         | Review items: Personal goal | Personal goal | Personal goal A  |
      | activity1     | Text: Short response | child personal |                               | Review items: Personal goal | Personal goal | Personal goal B  |
      | activity1     | Text: Short response | child company  |                               | Review items: Company goal  | Company goal  | Company goal A   |
      | activity1     | Text: Short response | child company  | Still working on this one too | Review items: Company goal  | Company goal  | Company goal B   |
