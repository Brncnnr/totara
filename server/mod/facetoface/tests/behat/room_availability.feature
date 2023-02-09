@mod @mod_facetoface @totara @javascript
Feature: Seminar room availability
  In order to prevent room conflicts
  As an editing trainer
  I need to see only available rooms

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
    And the following "global rooms" exist in "mod_facetoface" plugin:
      | name   | allowconflicts | hidden | capacity |
      | Room 1 | 0              | 0      | 10       |
      | Room 2 | 1              | 0      | 10       |
      | Room 3 | 0              | 1      | 10       |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name           | course | intro       |
      | Test Seminar 1 | C1     | <p>test</p> |
      | Test Seminar 2 | C1     | <p>test</p> |

  Scenario: Time based seminar room conflicts
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
    And I click on "Select rooms" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I click on "Select rooms" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
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
    And I click on "Select rooms" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 2 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
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
    And I click on "Select rooms" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I should see "Room 1" in the "1:00 PM" "table_row"
    And I should see "Room 1" in the "11:00 AM -1 January" "table_row"
    And I should see "Room 2" in the "1 February" "table_row"
    And I press "Save changes"

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
    And I click on "Select rooms" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"
    Then I should see "Room 1" in the "0 / 20" "table_row"

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
    And I click on "Select rooms" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"
    Then I should see "Room 1" in the "0 / 30" "table_row"

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
    And I click on "Select rooms" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 2 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"
    Then I should see "Room 2" in the "0 / 40" "table_row"

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
    And I click on "Select rooms" "link"
    Then I should see "Room 1 (Capacity: 10) (Room unavailable)"
    When I click on "Cancel" "button" in the "Choose rooms" "totaradialogue"
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
    And I should see "Room 1 is already booked"
    When I click on "Cancel" "button" in the "Select date" "totaradialogue"
    And I press "Cancel"

  Scenario: Hiding related seminar room availability
    Given I log in as "teacher1"
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
    And I click on "Select rooms" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I click on "Select rooms" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"
    And I log out
    And I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I click on "Hide from users when choosing a room on the Add/Edit event page" "link" in the "Room 1" "table_row"
    And I log out
    And I log in as "teacher1"
    And I am on "Test Seminar 1" seminar homepage

    When I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 2    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select rooms" "link"
    Then I should not see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Cancel" "button" in the "Choose rooms" "totaradialogue"
    And I press "Cancel"

    When I click on the seminar event action "Edit event" in row "0 / 20"
    And I click on "Select rooms" "link"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Cancel" "button" in the "Choose rooms" "totaradialogue"
    And I press "Add a new session"
    # The UI is not usable much here, we just save this and go back and the last added session will be listed first.
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "0 / 20"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 2    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select rooms" "link"
    Then I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"
    And I should see "Upcoming events"

  Scenario: Custom seminar room availability
    Given I log in as "teacher1"
    And I am on "Test Seminar 1" seminar homepage
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
    And I click on "Select rooms" "link"
    And I click on "Create" "link" in the "Choose rooms" "totaradialogue"
    And I set the following fields to these values:
      | Name                         | Zimmer 1 |
      | roomcapacity                 | 30       |
      | Allow booking conflicts      | 0        |
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"

    When  I press "Add a new session"
    # The UI is not usable much here, we just save this and go back and the last added session will be listed first.
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "0 / 30"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 12   |
      | timestart[minute]  | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select rooms" "link"
    Then I should see "Zimmer 1 (Capacity: 30) (Seminar: Test Seminar 1)"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Zimmer 1 (Capacity: 30) (Seminar: Test Seminar 1)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"
    And I should see "Upcoming events"

    When I follow "Add event"
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
    And I click on "Select rooms" "link"
    Then I should see "Zimmer 1 (Capacity: 30) (Room unavailable) (Seminar: Test Seminar 1)"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Create" "link" in the "Choose rooms" "totaradialogue"
    And I set the following fields to these values:
      | Name                         | Zimmer 2 |
      | roomcapacity                 | 40       |
      | Allow booking conflicts      | 0        |
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"
    And I click on "Remove room Zimmer 2 from session" "link"
    And I press "Save changes"
    And I should not see "Zimmer 2" in the "0 / 40" "table_row"

    When I click on the seminar event action "Edit event" in row "0 / 40"
    And I click on "Select rooms" "link"
    Then I should see "Zimmer 1 (Capacity: 30) (Room unavailable) (Seminar: Test Seminar 1)"
    And I should see "Zimmer 2 (Capacity: 40) (Seminar: Test Seminar 1)"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Cancel" "button" in the "Choose rooms" "totaradialogue"
    And I press "Cancel"

    When I am on "Test Seminar 2" seminar homepage
    And I follow "Add event"
    And I click on "Select rooms" "link"
    Then I should not see "Zimmer 1"
    And I should see "Zimmer 2 (Capacity: 40) (Seminar: Test Seminar 2)"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Cancel" "button" in the "Choose rooms" "totaradialogue"
    And I press "Cancel"
    And I log out

    When I log in as "teacher2"
    And I am on "Test Seminar 2" seminar homepage
    And I follow "Add event"
    And I click on "Select rooms" "link"
    Then I should not see "Zimmer 1"
    And I should not see "Zimmer 2"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Cancel" "button" in the "Choose rooms" "totaradialogue"
    And I press "Cancel"

    When I am on "Test Seminar 1" seminar homepage
    And I follow "Add event"
    And I click on "Select rooms" "link"
    Then I should see "Zimmer 1 (Capacity: 30) (Seminar: Test Seminar 1)"
    And I should not see "Zimmer 2"
    And I should see "Room 1 (Capacity: 10)"
    And I should see "Room 2 (Capacity: 10)"
    And I should not see "Room 3 (Capacity: 10)"
    And I click on "Cancel" "button" in the "Choose rooms" "totaradialogue"
    And I press "Cancel"

  Scenario: Seminar switch site room to not allow conflicts
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
    And I click on "Select rooms" "link"
    And I click on "Room 2 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
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
    And I click on "Select rooms" "link"
    And I click on "Room 2 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    When I press "Save changes"
    Then I should see "Room 2" in the "0 / 20" "table_row"
    And I should see "Room 2" in the "0 / 30" "table_row"

    When I navigate to "Rooms" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Room 2" "table_row"
    And I set the following fields to these values:
      | Allow booking conflicts | 0 |
    And I press "Save changes"
    Then I should see "Room has conflicting usage"
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
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Room 2" "table_row"
    And I set the following fields to these values:
      | Allow booking conflicts | 0 |
    And I press "Save changes"
    Then I should not see "Room has conflicting usage"

  Scenario: Seminar switch custom room to not allow conflicts
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
    And I click on "Select rooms" "link"
    And I click on "Create" "link" in the "Choose rooms" "totaradialogue"
    And I set the following fields to these values:
      | Name                         | Zimmer 1 |
      | roomcapacity                 | 40       |
      | Allow booking conflicts      | 1        |
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"
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
    And I click on "Select rooms" "link"
    And I click on "Zimmer 1 (Capacity: 40) (Seminar: Test Seminar 1)" "link"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"
    Then I should see "Zimmer 1" in the "0 / 40" "table_row"
    And I should see "Zimmer 1" in the "0 / 50" "table_row"

    When I click on the seminar event action "Edit event" in row "0 / 50"
    And I click on "Edit custom room Zimmer 1 in session" "link" in the "Zimmer 1" "table_row"
    And I set the following fields to these values:
      | Allow booking conflicts | 0 |
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"
    Then I should see "Room has conflicting usage" in the "Edit room" "totaradialogue"
    And I click on "Cancel" "button" in the "Edit room" "totaradialogue"
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
    And I click on "Edit custom room Zimmer 1 in session" "link" in the "Zimmer 1" "table_row"
    And I set the following fields to these values:
      | Allow booking conflicts | 0 |
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"
    Then I should not see "Room has conflicting usage"
    And I press "Save changes"

  Scenario: Reportbuilder seminar room availability filter
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
    And I click on "Select rooms" "link"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
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
    And I click on "Select rooms" "link"
    And I click on "Room 1 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
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
    And I click on "Select rooms" "link"
    And I click on "Room 2 (Capacity: 10)" "text" in the "Choose rooms" "totaradialogue"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"
    And I navigate to "Rooms" node in "Site administration > Seminars"

    # NOTE: We cannot use "Search" button because there is already "Search by" aria button above.

    When I set the following fields to these values:
      | room-roomavailable_enable        | Free between the following times |
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | ## next year ## Y ##             |
      | room-roomavailable_start[hour]   | 10                               |
      | room-roomavailable_start[minute] | 00                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | ## next year ## Y ##             |
      | room-roomavailable_end[hour]     | 11                               |
      | room-roomavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"

    When I set the following fields to these values:
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | ## next year ## Y ##             |
      | room-roomavailable_start[hour]   | 10                               |
      | room-roomavailable_start[minute] | 00                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | ## next year ## Y ##             |
      | room-roomavailable_end[hour]     | 11                               |
      | room-roomavailable_end[minute]   | 01                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"

    When I set the following fields to these values:
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | ## next year ## Y ##             |
      | room-roomavailable_start[hour]   | 11                               |
      | room-roomavailable_start[minute] | 30                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | ## next year ## Y ##             |
      | room-roomavailable_end[hour]     | 12                               |
      | room-roomavailable_end[minute]   | 30                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"

    When I set the following fields to these values:
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | ## next year ## Y ##             |
      | room-roomavailable_start[hour]   | 12                               |
      | room-roomavailable_start[minute] | 59                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | ## next year ## Y ##             |
      | room-roomavailable_end[hour]     | 14                               |
      | room-roomavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"

    When I set the following fields to these values:
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | ## next year ## Y ##             |
      | room-roomavailable_start[hour]   | 10                               |
      | room-roomavailable_start[minute] | 00                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | ## next year ## Y ##             |
      | room-roomavailable_end[hour]     | 14                               |
      | room-roomavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"

    When I set the following fields to these values:
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | ## next year ## Y ##             |
      | room-roomavailable_start[hour]   | 14                               |
      | room-roomavailable_start[minute] | 00                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | ## next year ## Y ##             |
      | room-roomavailable_end[hour]     | 15                               |
      | room-roomavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"

    When I set the following fields to these values:
      | room-roomavailable_start[day]    | 1                                |
      | room-roomavailable_start[month]  | January                          |
      | room-roomavailable_start[year]   | ## 2 years ago ## Y ##           |
      | room-roomavailable_start[hour]   | 10                               |
      | room-roomavailable_start[minute] | 00                               |
      | room-roomavailable_end[day]      | 1                                |
      | room-roomavailable_end[month]    | January                          |
      | room-roomavailable_end[year]     | ## 2 years ## Y ##               |
      | room-roomavailable_end[hour]     | 14                               |
      | room-roomavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Room 1"
    And I should see "Room 2"
    And I should see "Room 3"
