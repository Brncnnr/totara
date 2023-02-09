@totara @totara_cohort @javascript
Feature: Test the Multiselect cohort rule
  As an Admin
  I need to create and edit a multiselect cohort rule

  Background:
    Given I am on a totara site
    And the following "cohorts" exist:
      | name       | idnumber | cohorttype |
      | Audience 1 | AUD001   | 2          |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner1  | One      | learner1@example.com |
      | learner2 | Learner2  | Two      | learner2@example.com |
    And the following "position type" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber   |
      | Position type 1 | POSTYPE001 |
    And the following "position" frameworks exist:
      | fullname               | idnumber |
      | Position Framework 001 | PFW001   |
    And the following "position" hierarchy exists:
      | framework | fullname   | idnumber | type       |
      | PFW001    | Position 1 | POS001   | POSTYPE001 |
    And the following job assignments exist:
      | user     | position     |
      | learner1 | POS001       |

  Scenario: Create Multiselect cohort rule
    Given I log in as "admin"
    And I navigate to "Manage types" node in "Site administration > Positions"
    And I follow "Position type 1"
    When I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                   | Multiselect |
      | Short name (must be unique) | ms1         |
      | Option 1 text               | Option 1    |
      | Option 2 text               | Option 2    |
      | Option 3 text               | Option 3    |
    And I click on "Save changes" "button"
    And I navigate to "Manage positions" node in "Site administration > Positions"
    And I click on "Position Framework 001" "link"
    And I click on "Edit" "link" in the "Position 1" "table_row"
    And I click on "Option 3" "checkbox"
    And I click on "Save changes" "button"

    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I click on "Audience 1" "link"
    And I click on "Rule sets" "link" in the ".tabtree" "css_element"
    And I set the field "Add rule" to "Multiselect"
    And I set the following fields to these values:
      | id_equal | Is           |
      | id_exact | All selected |
    And I click on "Option 3" "checkbox" in the "Add rule" "totaradialogue"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I wait "1" seconds
    Then I should see "User's position \"Multiselect\" is all of the selected \"Option 3\""

    When I click on "Approve changes" "button"
    And I click on "Members" "link" in the ".tabtree" "css_element"
    Then I should see "Learner1 One"
    And I should not see "Learner2 Two"
