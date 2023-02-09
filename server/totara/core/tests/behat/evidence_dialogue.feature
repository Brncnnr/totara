@totara @totara_core @totara_evidence @totara_customfield
Feature: Test evidence dialogue search
  In order to test the evidence dialog search
  As an admin
  I set up data and then use the evidence dialog search


  @javascript
  Scenario: I can search for evidence in the evidence dialog
    # Setup.
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | format | summary |
      | Course 1 | C1        | topics | x       |
    And the following "types" exist in "totara_evidence" plugin:
      | name          | fields |
      | Evidence_Type | 1      |
    When I log in as "admin"
    # Create evidence.
    And I navigate to my evidence bank
    And I click on "Add evidence" "link"
    And I expand the evidence type selector
    And I select type "Evidence_Type" from the evidence type selector
    And I click on "Use this type" "link"
    And I set the following fields to these values:
      | Evidence name   | Test Evidence itemid1  |
      | Custom Field #1 | Test Evidence fieldid1 |
    And I click on "Save evidence item" "button"
    And I click on "Add evidence" "link"
    And I expand the evidence type selector
    And I select type "Evidence_Type" from the evidence type selector
    And I click on "Use this type" "link"
    And I set the following fields to these values:
      | Evidence name   | Test Evidence itemid2  |
      | Custom Field #1 | Test Evidence fieldid2 |
    And I click on "Save evidence item" "button"
    # Create plan.
    When I click on "Record of Learning" in the totara menu
    And I click on "Manage plans" "link"
    When I press "Create new learning plan"
    And I set the field "Plan name" to "Test Learning Plan"
    When I press "Create plan"
    Then I should see "Plan creation successful"
    # Add course.
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I follow "Miscellaneous"
    And I follow "Course 1"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"
    And I wait "1" seconds
    # Go to the evidence dialog.
    And I follow "Course 1"
    And I press "Add linked evidence"
    And I click on "Search" "link" in the "Add linked evidence" "totaradialogue"
    # Search for both.
    And I search for "id" in the "Add linked evidence" totara dialogue
    Then I should see "Test Evidence itemid1" in the "Add linked evidence" "totaradialogue"
    And I should see "Test Evidence itemid2" in the "Add linked evidence" "totaradialogue"
    # Search for evidence item #1 using name.
    And I search for "itemid1" in the "Add linked evidence" totara dialogue
    Then I should see "Test Evidence itemid1" in the "Add linked evidence" "totaradialogue"
    And I should not see "Test Evidence itemid2" in the "Add linked evidence" "totaradialogue"
    # Search for evidence item #2 using custom field content.
    When I search for "fieldid2" in the "Add linked evidence" totara dialogue
    Then I should not see "Test Evidence itemid1" in the "Add linked evidence" "totaradialogue"
    And I should see "Test Evidence itemid2" in the "Add linked evidence" "totaradialogue"
    # Change the evidence field content.
    Then I click on "Cancel" "button" in the "Add linked evidence" "totaradialogue"
    And I wait "1" seconds
    And I navigate to my evidence bank
    And I click on "Edit" "link" in the "Test Evidence itemid1" "table_row"
    And I set the following fields to these values:
      | Custom Field #1 | foobar |
    And I click on "Save changes" "button"
    And I click on "Edit" "link" in the "Test Evidence itemid2" "table_row"
    And I set the following fields to these values:
      | Custom Field #1 | foobar |
    And I click on "Save changes" "button"
    # Go to the evidence dialog.
    And I click on "Record of Learning" in the totara menu
    And I click on "Test Learning Plan" "link"
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I follow "Course 1"
    And I press "Add linked evidence"
    And I click on "Search" "link" in the "Add linked evidence" "totaradialogue"
    # Search for evidence item #1 using name.
    When I search for "itemid1" in the "Add linked evidence" totara dialogue
    Then I should see "Test Evidence itemid1" in the "Add linked evidence" "totaradialogue"
    And I should not see "Test Evidence itemid2" in the "Add linked evidence" "totaradialogue"
    # Search for any evidence using the old field content.
    When I search for "field" in the "Add linked evidence" totara dialogue
    Then I should not see "Test Evidence itemid1" in the "Add linked evidence" "totaradialogue"
    And I should not see "Test Evidence itemid2" in the "Add linked evidence" "totaradialogue"
