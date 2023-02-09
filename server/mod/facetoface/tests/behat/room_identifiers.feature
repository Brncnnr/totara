@javascript @mod @mod_facetoface @totara
Feature: Check seminar room identifiers setting
  In order to allow learners to find the correct room
  As an admin
  I need to configure which fields are used to identify rooms

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    And I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name                         | Room 1          |
      | Capacity                     | 10              |
      | Allow booking conflicts      | 0               |
      | Building                     | Some Building 1 |
      | Address                      | 123 Main Street |
    And I press "Add a room"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name                         | Room 2          |
      | Capacity                     | 10              |
      | Allow booking conflicts      | 0               |
      | Building                     | Some Building 2 |
      | Address                      |                 |
    And I press "Add a room"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name                         | Room 3          |
      | Capacity                     | 10              |
      | Allow booking conflicts      | 0               |
      | Building                     |                 |
      | Address                      | 321 Main Street |
    And I press "Add a room"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar 1 |
      | Description | test           |
    And I turn editing mode off
    And I follow "Seminar 1"
    And I follow "Add event"
    And I click on "Select rooms" "link"
    And I should see "Room 1, Some Building 1, 123 Main Street (Capacity: 10)"
    And I should see "Room 2, Some Building 2 (Capacity: 10)"
    And I should see "Room 3, 321 Main Street (Capacity: 10)"
    And I click on "Room 1, Some Building 1, 123 Main Street (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"
    And I follow "Add event"
    And I click on "Select rooms" "link"
    And I click on "Room 2, Some Building 2 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"
    And I follow "Add event"
    And I click on "Select rooms" "link"
    And I click on "Room 3, 321 Main Street (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"
    And I navigate to "Global settings" node in "Site administration > Seminars"

  Scenario: Test all three room identifier settings
    And I set the following administration settings values:
      | facetoface_roomidentifier | 0 |
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Seminar 1"
    Then I should see "Room 1" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "Some Building 1" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "123 Main Street" in the ".mod_facetoface__sessionlist" "css_element"
    And I should see "Room 2" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "Some Building 2" in the ".mod_facetoface__sessionlist" "css_element"
    And I should see "Room 3" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "321 Main Street" in the ".mod_facetoface__sessionlist" "css_element"
    When I set the field "roomid" to "Room 1, Some Building 1, 123 Main Street"
    Then I should not see "Room 2" in the ".mod_facetoface__sessionlist" "css_element"
    And I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | facetoface_roomidentifier | 1 |
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Seminar 1"
    Then I should see "Room 1" in the ".mod_facetoface__sessionlist" "css_element"
    And I should see "Some Building 1" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "123 Main Street" in the ".mod_facetoface__sessionlist" "css_element"
    And I should see "Room 2" in the ".mod_facetoface__sessionlist" "css_element"
    And I should see "Some Building 2" in the ".mod_facetoface__sessionlist" "css_element"
    And I should see "Room 3" in the ".mod_facetoface__sessionlist" "css_element"
    And I should not see "321 Main Street" in the ".mod_facetoface__sessionlist" "css_element"
    When I set the field "roomid" to "Room 1, Some Building 1, 123 Main Street"
    Then I should not see "Room 2" in the ".mod_facetoface__sessionlist" "css_element"
    And I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | facetoface_roomidentifier | 2 |
    And I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Seminar 1"
    Then I should see "Room 1" in the ".mod_facetoface__sessionlist" "css_element"
    And I should see "Some Building 1" in the ".mod_facetoface__sessionlist" "css_element"
    And I should see "123 Main Street" in the ".mod_facetoface__sessionlist" "css_element"
    And I should see "Room 2" in the ".mod_facetoface__sessionlist" "css_element"
    And I should see "Some Building 2" in the ".mod_facetoface__sessionlist" "css_element"
    And I should see "Room 3" in the ".mod_facetoface__sessionlist" "css_element"
    And I should see "321 Main Street" in the ".mod_facetoface__sessionlist" "css_element"
    When I set the field "roomid" to "Room 1, Some Building 1, 123 Main Street"
    Then I should not see "Room 2" in the ".mod_facetoface__sessionlist" "css_element"
