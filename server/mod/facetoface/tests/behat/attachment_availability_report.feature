@mod @mod_facetoface @totara @totara_reportbuilder @javascript
Feature: Seminar asset/facilitator availability related to reports
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

  Scenario Outline: Reportbuilder seminar item availability filter
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
    And I click on "<name> 1" "text" in the "Choose <collection_type>" "totaradialogue"
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
      | timestart[hour]    | 13   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select <item_type>" "link"
    And I click on "<name> 1" "text" in the "Choose <collection_type>" "totaradialogue"
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
      | timestart[hour]    | 15   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select <item_type>" "link"
    And I click on "<name> 2" "text" in the "Choose <collection_type>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    And I press "Save changes"
    And I navigate to "<column_or_node>" node in "Site administration > Seminars"

    # NOTE: We cannot use "Search" button because there is already "Search by" aria button above.

    When I set the following fields to these values:
      | <item_type>-<item_type>available_enable        | Free between the following times |
      | <item_type>-<item_type>available_start[day]    | 1                                |
      | <item_type>-<item_type>available_start[month]  | January                          |
      | <item_type>-<item_type>available_start[year]   | ## next year ## Y ##             |
      | <item_type>-<item_type>available_start[hour]   | 10                               |
      | <item_type>-<item_type>available_start[minute] | 00                               |
      | <item_type>-<item_type>available_end[day]      | 1                                |
      | <item_type>-<item_type>available_end[month]    | January                          |
      | <item_type>-<item_type>available_end[year]     | ## next year ## Y ##             |
      | <item_type>-<item_type>available_end[hour]     | 11                               |
      | <item_type>-<item_type>available_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should see "<name> 1"
    And I should see "<name> 2"
    And I should see "<name> 3"

    When I set the following fields to these values:
      | <item_type>-<item_type>available_start[day]    | 1                                |
      | <item_type>-<item_type>available_start[month]  | January                          |
      | <item_type>-<item_type>available_start[year]   | ## next year ## Y ##             |
      | <item_type>-<item_type>available_start[hour]   | 10                               |
      | <item_type>-<item_type>available_start[minute] | 00                               |
      | <item_type>-<item_type>available_end[day]      | 1                                |
      | <item_type>-<item_type>available_end[month]    | January                          |
      | <item_type>-<item_type>available_end[year]     | ## next year ## Y ##             |
      | <item_type>-<item_type>available_end[hour]     | 11                               |
      | <item_type>-<item_type>available_end[minute]   | 01                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "<name> 1"
    And I should see "<name> 2"
    And I should see "<name> 3"

    When I set the following fields to these values:
      | <item_type>-<item_type>available_start[day]    | 1                                |
      | <item_type>-<item_type>available_start[month]  | January                          |
      | <item_type>-<item_type>available_start[year]   | ## next year ## Y ##             |
      | <item_type>-<item_type>available_start[hour]   | 11                               |
      | <item_type>-<item_type>available_start[minute] | 30                               |
      | <item_type>-<item_type>available_end[day]      | 1                                |
      | <item_type>-<item_type>available_end[month]    | January                          |
      | <item_type>-<item_type>available_end[year]     | ## next year ## Y ##             |
      | <item_type>-<item_type>available_end[hour]     | 12                               |
      | <item_type>-<item_type>available_end[minute]   | 30                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "<name> 1"
    And I should see "<name> 2"
    And I should see "<name> 3"

    When I set the following fields to these values:
      | <item_type>-<item_type>available_start[day]    | 1                                |
      | <item_type>-<item_type>available_start[month]  | January                          |
      | <item_type>-<item_type>available_start[year]   | ## next year ## Y ##             |
      | <item_type>-<item_type>available_start[hour]   | 12                               |
      | <item_type>-<item_type>available_start[minute] | 59                               |
      | <item_type>-<item_type>available_end[day]      | 1                                |
      | <item_type>-<item_type>available_end[month]    | January                          |
      | <item_type>-<item_type>available_end[year]     | ## next year ## Y ##             |
      | <item_type>-<item_type>available_end[hour]     | 14                               |
      | <item_type>-<item_type>available_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "<name> 1"
    And I should see "<name> 2"
    And I should see "<name> 3"

    When I set the following fields to these values:
      | <item_type>-<item_type>available_start[day]    | 1                                |
      | <item_type>-<item_type>available_start[month]  | January                          |
      | <item_type>-<item_type>available_start[year]   | ## next year ## Y ##             |
      | <item_type>-<item_type>available_start[hour]   | 10                               |
      | <item_type>-<item_type>available_start[minute] | 00                               |
      | <item_type>-<item_type>available_end[day]      | 1                                |
      | <item_type>-<item_type>available_end[month]    | January                          |
      | <item_type>-<item_type>available_end[year]     | ## next year ## Y ##             |
      | <item_type>-<item_type>available_end[hour]     | 14                               |
      | <item_type>-<item_type>available_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "<name> 1"
    And I should see "<name> 2"
    And I should see "<name> 3"

    When I set the following fields to these values:
      | <item_type>-<item_type>available_start[day]    | 1                                |
      | <item_type>-<item_type>available_start[month]  | January                          |
      | <item_type>-<item_type>available_start[year]   | ## next year ## Y ##             |
      | <item_type>-<item_type>available_start[hour]   | 14                               |
      | <item_type>-<item_type>available_start[minute] | 00                               |
      | <item_type>-<item_type>available_end[day]      | 1                                |
      | <item_type>-<item_type>available_end[month]    | January                          |
      | <item_type>-<item_type>available_end[year]     | ## next year ## Y ##             |
      | <item_type>-<item_type>available_end[hour]     | 15                               |
      | <item_type>-<item_type>available_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should see "<name> 1"
    And I should see "<name> 2"
    And I should see "<name> 3"

    When I set the following fields to these values:
      | <item_type>-<item_type>available_start[day]    | 1                                |
      | <item_type>-<item_type>available_start[month]  | January                          |
      | <item_type>-<item_type>available_start[year]   | ## 2 years ago ## Y ##           |
      | <item_type>-<item_type>available_start[hour]   | 10                               |
      | <item_type>-<item_type>available_start[minute] | 00                               |
      | <item_type>-<item_type>available_end[day]      | 1                                |
      | <item_type>-<item_type>available_end[month]    | January                          |
      | <item_type>-<item_type>available_end[year]     | ## 2 years ## Y ##               |
      | <item_type>-<item_type>available_end[hour]     | 14                               |
      | <item_type>-<item_type>available_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "<name> 1"
    And I should see "<name> 2"
    And I should see "<name> 3"

    Examples:
      | name        | item_type   | collection_type | an_item_type  | column_or_node |
      | Asset       | asset       | assets          | an asset      | Assets         |
      | Facilitator | facilitator | facilitators    | a facilitator | Facilitators   |
