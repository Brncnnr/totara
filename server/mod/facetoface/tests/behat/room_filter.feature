@mod @mod_facetoface @totara @totara_customfield
Feature: Filter session by pre-defined rooms
  In order to test seminar rooms
  As a site manager
  I need to create rooms

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name              | course | idnumber |
      | facetoface | Test seminar name | C1     | S9103    |
    And the following "global rooms" exist in "mod_facetoface" plugin:
      | name   | capacity | custom:building | custom:location |
      | Room 1 | 10       | Building 123    | {"address":"123 Tory street","size":"medium","view":"satellite","display":"map","zoom":12,"location":{"latitude":-31.95,"longitude":115.85}} |
      | Room 2 | 10       | Building 234    | {"address":"234 Tory street","size":"medium","view":"satellite","display":"map","zoom":12,"location":{"latitude":-31.95,"longitude":115.85}} |
      | Room 3 | 10       | Building 345    | {"address":"345 Tory street","size":"medium","view":"satellite","display":"map","zoom":12,"location":{"latitude":-31.95,"longitude":115.85}} |
      | Room 4 | 10       | Building 456    | {"address":"456 Tory street","size":"medium","view":"satellite","display":"map","zoom":12,"location":{"latitude":-31.95,"longitude":115.85}} |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details |
      | Test seminar name | event 1 |
      | Test seminar name | event 2 |
      | Test seminar name | event 3 |
      | Test seminar name | event 4 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish               | rooms  |
      | event 1      | 1 Jan next year 11am | 1 Jan next year 12pm | Room 1 |
      | event 2      | 2 Jan next year 11am | 2 Jan next year 12pm | Room 2 |
      | event 3      | 3 Jan next year 11am | 3 Jan next year 12pm | Room 3 |
      | event 4      | 4 Jan next year 11am | 4 Jan next year 12pm | Room 4 |

    And I log in as "admin"

  @javascript
  Scenario: Add sessions with different rooms and filter sessions by rooms
    And I am on "Test seminar name" seminar homepage

    When I set the field "roomid" to "Room 1"
    Then I should see "Room 1" in the "1 January" "table_row"
    And I should not see "Room 2" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "Room 3" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "Room 4" in the ".mod_facetoface__sessionlist" "css_element"

    When I set the field "roomid" to "Room 2"
    Then I should see "Room 2" in the "2 January" "table_row"
    And I should not see "Room 1" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "Room 3" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "Room 4" in the ".mod_facetoface__sessionlist" "css_element"

    When I set the field "roomid" to "Room 3"
    Then I should see "Room 3" in the "3 January" "table_row"
    And I should not see "Room 2" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "Room 1" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "Room 4" in the ".mod_facetoface__sessionlist" "css_element"

    When I set the field "roomid" to "Room 4"
    Then I should see "Room 4" in the "4 January" "table_row"
    And I should not see "Room 2" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "Room 3" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "Room 1" in the ".mod_facetoface__sessionlist" "css_element"

    When I set the field "roomid" to "All"
    Then I should see "Room 1" in the "1 January" "table_row"
    And I should see "Room 2" in the "2 January" "table_row"
    And I should see "Room 3" in the "3 January" "table_row"
    And I should see "Room 4" in the "4 January" "table_row"
