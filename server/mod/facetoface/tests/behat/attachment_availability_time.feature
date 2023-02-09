@mod @mod_facetoface @totara @totara_reportbuilder @javascript
Feature: Seminar asset/facilitator availability related to time
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

  Scenario Outline: Time based seminar item conflicts
    Given I log in as "teacher1"
    And I am on "Test Seminar 1" seminar homepage
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select <item_type>" "link"
    And I should see "<name> 1"
    And I should see "<name> 2"
    And I should not see "<name> 3"
    And I click on "<name> 1" "text" in the "Choose <collection_type>" "totaradialogue"
    And I click on "<name> 2" "text" in the "Choose <collection_type>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    And I click on "Select <item_type>" "link"
    And I should see "<name> 1"
    And I should see "<name> 2"
    And I should not see "<name> 3"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    And I press "Add a new session"
    # The UI is not usable much here, we just save this and go back and the last added session will be listed first.
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "0 / 10"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 2    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select <item_type>" "link"
    And I should see "<name> 1"
    And I should see "<name> 2"
    And I should not see "<name> 3"
    And I click on "<name> 2" "text" in the "Choose <collection_type>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    And I press "Add a new session"
    # The UI is not usable much here, we just save this and go back and the last added session will be listed first.
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "0 / 10"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 12   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select <item_type>" "link"
    And I should see "<name> 1"
    And I should see "<name> 2"
    And I should not see "<name> 3"
    And I click on "<name> 1" "text" in the "Choose <collection_type>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "0 / 10"
    And I should see "<name> 1" in the "1:00 PM" "table_row"
    And I should not see "<name> 2" in the "1:00 PM" "table_row"
    And I should see "<name> 1" in the "11:00 AM -1 January" "table_row"
    And I should see "<name> 2" in the "11:00 AM -1 January" "table_row"
    And I should not see "<name> 1" in the "1 February" "table_row"
    And I should see "<name> 2" in the "1 February" "table_row"
    And I press "Cancel"

    When I follow "Add event"
    And I set the following fields to these values:
      | Maximum bookings | 20 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 10   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select <item_type>" "link"
    And I should see "<name> 1"
    And I should see "<name> 2"
    And I should not see "<name> 3"
    And I click on "<name> 1" "text" in the "Choose <collection_type>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "0 / 20"
    And I should see "<name> 1" in the "10:00 AM" "table_row"
    And I should not see "<name> 2" in the "10:00 AM" "table_row"
    And I press "Cancel"

    When I follow "Add event"
    And I set the following fields to these values:
      | Maximum bookings | 30 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 13   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select <item_type>" "link"
    And I should see "<name> 1"
    And I should see "<name> 2"
    And I should not see "<name> 3"
    And I click on "<name> 1" "text" in the "Choose <collection_type>" "totaradialogue"
    And I click on "<name> 2" "text" in the "Choose <collection_type>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "0 / 30"
    And I should see "<name> 1" in the "1:00 PM -1 January" "table_row"
    And I should see "<name> 2" in the "1:00 PM -1 January" "table_row"
    And I press "Cancel"

    When I follow "Add event"
    And I set the following fields to these values:
      | Maximum bookings | 40 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 2    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select <item_type>" "link"
    And I should see "<name> 1"
    And I should see "<name> 2"
    And I should not see "<name> 3"
    And I click on "<name> 2" "text" in the "Choose <collection_type>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "0 / 40"
    And I should not see "<name> 1" in the "1 February" "table_row"
    And I should see "<name> 2" in the "1 February" "table_row"
    And I press "Cancel"

    When I follow "Add event"
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
    Then I should see "<name> 1 (<item_type> unavailable on selected dates)"
    Then I should not see "<name> 2 (<item_type> unavailable on selected dates)"
    When I click on "Cancel" "button" in the "Choose <collection_type>" "totaradialogue"
    And I press "Cancel"

    And I click on the seminar event action "Edit event" in row "0 / 20"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I should see "<name> 1 is already booked"
    When I click on "Cancel" "button" in the "Select date" "totaradialogue"
    And I press "Cancel"

    Examples:
      | name        | item_type   | collection_type | an_item_type  | column_or_node |
      | Asset       | asset       | assets          | an asset      | Assets         |
      | Facilitator | facilitator | facilitators    | a facilitator | Facilitators   |
