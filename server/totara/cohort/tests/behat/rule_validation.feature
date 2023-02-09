@totara @totara_cohort @javascript
Feature: Validation of Audience dynamic rules
  I need to be able to select an audience as a rule in a dynamic audience

  Background:
    Given I am on a totara site
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I switch to "Add new audience" tab
    And I set the following fields to these values:
      | Name | test audience |
      | Type | Dynamic       |
    And I click on "Save changes" "button"

  Scenario: Validate min max Rule
    Given I set the field "Add rule" to "Has direct reports"
    And I set the field "id_equal" to "None"
    Then the "id_listofvalues" "field" should be disabled

    When I set the field "id_equal" to "At least"
    Then the "id_listofvalues" "field" should be enabled

    When I set the field "id_listofvalues" to "abc"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "You must specify one valid number" in the "Add rule" "totaradialogue"

    When I set the field "id_listofvalues" to "123"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Total number of direct reports: At least \"123\" person(s)"

    When I click on "Edit" "link" in the ".cohort-editing_ruleset" "css_element"
    Then the following fields match these values:
        | id_equal        | At least |
        | id_listofvalues | 123      |

  Scenario: Validate list of values rule
    Given I set the field "Add rule" to "First name"
    And I set the field "id_equal" to "is empty"
    Then the "id_listofvalues" "field" should be disabled

    When I set the field "id_equal" to "contains"
    Then the "id_listofvalues" "field" should be enabled

    When I set the field "id_listofvalues" to ""
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "You must specify at least one value" in the "Add rule" "totaradialogue"

    When I set the field "id_listofvalues" to "abc"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "User's first name contains \"abc\""

    When I click on "Edit" "link" in the ".cohort-editing_ruleset" "css_element"
    Then the following fields match these values:
        | id_equal        | contains |
        | id_listofvalues | abc      |
