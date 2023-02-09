@mod @mod_facetoface @totara @totara_reportbuilder @javascript
Feature: Seminar asset/facilitator availability related to switching
  In order to prevent asset/facilitators conflicts
  As an editing trainer
  I need to see only available assets/facilitators

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | teacher2 | Teacher   | Two      | teacher2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name           | course  | intro       |
      | Test Seminar 1 | C1      | <p>test</p> |
      | Test Seminar 2 | C1      | <p>test</p> |
    And the following "global assets" exist in "mod_facetoface" plugin:
      | name           | allowconflicts | hidden | description |
      | Asset 1        | 0              | 0      |             |
      | Asset 2        | 1              | 0      |             |
      | Asset 3        | 0              | 1      |             |
    And the following "global facilitators" exist in "mod_facetoface" plugin:
      | name           | allowconflicts | hidden | description |
      | Facilitator 1  | 0              | 0      |             |
      | Facilitator 2  | 1              | 0      |             |
      | Facilitator 3  | 0              | 1      |             |

  Scenario Outline: Seminar switch site item to not allow conflicts
    Given I log in as "admin"
    And I am on "Test Seminar 1" seminar homepage
    And I follow "Add event"
    And I set the following fields to these values:
      | Maximum bookings | 20 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select <item_type>" "link"
    And I click on "<name> 2" "text" in the "Choose <collection_type>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    And I press "Save changes"
    And I follow "Add event"
    And I set the following fields to these values:
      | Maximum bookings | 30 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select <item_type>" "link"
    And I click on "<name> 2" "text" in the "Choose <collection_type>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    When I press "Save changes"

    When I navigate to "<column_or_node>" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "<name> 2" "table_row"
    And I set the following fields to these values:
      | Allow booking conflicts | 0 |
    And I press "Save changes"
    Then I should see "<name> has conflicting usage"
    And I press "Cancel"

    When I am on "Test Seminar 1" seminar homepage
    And I click on the seminar event action "Edit event" in row "0 / 30"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 12   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I navigate to "<column_or_node>" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "<name> 2" "table_row"
    And I set the following fields to these values:
      | Allow booking conflicts | 0 |
    And I press "Save changes"
    Then I should not see "<name> has conflicting usage"

    Examples:
      | name        | item_type   | collection_type | an_item_type  | column_or_node |
      | Asset       | asset       | assets          | an asset      | Assets         |
      | Facilitator | facilitator | facilitators    | a facilitator | Facilitators   |

  Scenario Outline: Seminar switch custom item to not allow conflicts
    Given I log in as "teacher1"
    And I am on "Test Seminar 1" seminar homepage
    And I follow "Add event"
    And I set the following fields to these values:
      | Maximum bookings | 40 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select <item_type>" "link"
    And I click on "Create" "link" in the "Choose <collection_type>" "totaradialogue"
    And I set the following fields to these values:
      | Name                    | Etwas 1 |
      | Allow booking conflicts | 1       |
    And I click on "OK" "button" in the "Create new <item_type>" "totaradialogue"
    And I press "Save changes"
    And I follow "Add event"
    And I set the following fields to these values:
      | Maximum bookings | 50 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select <item_type>" "link"
    And I click on "Etwas 1 (Seminar: Test Seminar 1)" "link"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    And I press "Save changes"

    When I click on the seminar event action "Edit event" in row "0 / 50"
    And I click on "Edit custom <item_type> Etwas 1 in session" "link" in the "Etwas 1" "table_row"
    And I set the following fields to these values:
      | Allow booking conflicts | 0 |
    And I click on "OK" "button" in the "Edit <item_type>" "totaradialogue"
    Then I should see "<name> has conflicting usage" in the "Edit <item_type>" "totaradialogue"
    And I click on "Cancel" "button" in the "Edit <item_type>" "totaradialogue"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 12   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    When I click on the seminar event action "Edit event" in row "0 / 50"
    And I click on "Edit custom <item_type> Etwas 1 in session" "link" in the "Etwas 1" "table_row"
    And I set the following fields to these values:
      | Allow booking conflicts | 0 |
    And I click on "OK" "button" in the "Edit <item_type>" "totaradialogue"
    Then I should not see "<name> has conflicting usage"
    And I press "Save changes"

    Examples:
      | name        | item_type   | collection_type | an_item_type  | column_or_node |
      | Asset       | asset       | assets          | an asset      | Assets         |
      | Facilitator | facilitator | facilitators    | a facilitator | Facilitators   |
