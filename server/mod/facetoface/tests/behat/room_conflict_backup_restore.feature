@mod @mod_facetoface @totara
Feature: Test room conflicts through backup/restore
  In order to test Face to face room conflicts
  As a site manager
  I need to create facetoface, add sessions, add room to each session with different room conflict settings

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name               | course | idnumber |
      | facetoface | Facetoface TL12734 | C1     | TL12734  |

    And I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 1          |
      | Building          | Building 123    |
      | Address           | 123 Tory street |
      | Capacity          | 10              |
      | Allow booking conflicts      | 0    |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 2          |
      | Building          | Building 234    |
      | Address           | 234 Tory street |
      | Capacity          | 10              |
      | Allow booking conflicts      | 1    |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

  @javascript
  Scenario: Add sessions with different rooms and duplicate facetoface
    And I am on "Course 1" course homepage
    And I follow "Facetoface TL12734"

    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select rooms" "link"
    And I click on "Room 1, Building 123, 123 Tory street (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"

    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 2    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 2    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select rooms" "link"
    And I click on "Room 2, Building 234, 234 Tory street (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"

    And I am on "Course 1" course homepage with editing mode on
    And I open "Facetoface TL12734" actions menu

    When I click on "Duplicate" "link" in the "Facetoface TL12734" activity
    And I turn editing mode off
    Then I should see "Room 2" in the ".activity.facetoface:first-child" "css_element"
    And I should see "Room 1" in the ".activity.facetoface:first-child" "css_element"
    # The room with prevent conflict should not appear.
    And I should see "Room 2" in the ".activity.facetoface:last-child" "css_element"
    And I should not see "Room 1" in the ".activity.facetoface:last-child" "css_element"

