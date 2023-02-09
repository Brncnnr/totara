@javascript @mod @mod_facetoface @mod_facetoface_virtual_room @totara @totara_core_virtualmeeting
Feature: Validation in a seminar virtual room meeting
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username     | firstname | lastname | email                | alternatename |
      | trainer1     | Trainer   | First    | trainer1@example.com |               |
      | manager1     | Manager   | First    | manager1@example.com |               |
      | manager2     | Manager   | First    | manager1@example.com |               |
      | creator      | Creator   | Host     | creator@example.com  | Bobby         |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "custom rooms" exist in "mod_facetoface" plugin:
      | name         |
      | Virtual Room |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name            | intro | course |
      | Virtual seminar |       | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface      | details       |
      | Virtual seminar | Virtual event |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | trainer1 | C1     | teacher |
      | manager2 | C1     | manager |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails  | start      | finish     | rooms        | sessiontimezone  |
      | Virtual event | +3 day 6am | +3 day 9am | Virtual Room | Indian/Christmas |
    # Disable custom fields so behat will not poke Google Maps
    Given I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I switch to "Room" tab
    And I click on "Hide" "link" in the "Building" "table_row"
    And I click on "Hide" "link" in the "Location" "table_row"
    And I log out

  Scenario: mod_facetoface_virtualmeeting_101: Connect button of custom virtualmeeting
    Given I log in as "trainer1"
    And I am on "Virtual seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "Christmas"

    When I click on "Virtual Room" "link" in the "Christmas" "table_row"
    Then the "Connect" "button_exact" should be disabled
    And I click on "OK" "button_exact" in the "Edit room" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"

    Given I click on the seminar event action "Edit event" in row "Christmas"
    When I click on "Virtual Room" "link" in the "Christmas" "table_row"
    And I set the field "Add virtual room link" to "Custom virtual room link"
    Then the "Connect" "button_exact" should be disabled
    And I set the field "Virtual room link" to local url "/mod/facetoface/tests/fixtures/bph4svcr.php"
    And I click on "OK" "button_exact" in the "Edit room" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"

  Scenario: mod_facetoface_virtualmeeting_102: Connect button of app virtualmeeting
    Given I log in as "trainer1"
    And I am on "Virtual seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "Christmas"

    When I click on "Virtual Room" "link" in the "Christmas" "table_row"
    And I set the field "Add virtual room link" to "Fake Dev App"
    Then the "Connect" "button_exact" should be disabled
    And I click on "OK" "button_exact" in the "Edit room" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"

  Scenario: mod_facetoface_virtualmeeting_103: Connect button of user virtualmeeting
    Given I log in as "trainer1"
    And I am on "Virtual seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "Christmas"

    When I click on "Virtual Room" "link" in the "Christmas" "table_row"
    And I set the field "Add virtual room link" to "Fake Dev User"
    Then the "Connect" "button_exact" should be enabled
    When I click on "OK" "button_exact" in the "Edit room" "totaradialogue"
    Then I should see "Authorisation required"

    And I click on "Connect" "button" in the "Edit room" "totaradialogue"
    And I switch to "virtualmeeting_connect" window
    And I set the following fields to these values:
      | Username | creator |
      | Password | creator |
    And I click on "Log in" "button"
    And I switch to the main window
    And I wait for pending js
    Then I should see "Connected as Bobby (creator@example.com)"
    And I click on "OK" "button_exact" in the "Edit room" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"

  Scenario: mod_facetoface_virtualmeeting_104: Virtual room links
    Given the following "global rooms" exist in "mod_facetoface" plugin:
      | name           |
      | Site-wide room |

    Given I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I click on "Edit room" "link" in the "Site-wide room" "table_row"
    When I set the field "Add virtual room link" to "Custom virtual room link"
    And I press "Save changes"
    Then I should see "You must supply a value here"
    When I set the field "Virtual room link" to "invalid.url/format"
    And I press "Save changes"
    Then I should see "Invalid URL format"
    When I set the field "Virtual room link" to "http://example.com"
    And I press "Save changes"
    Then I should see "Manage rooms" in the page title
    And I log out

    Given I log in as "trainer1"
    And I am on "Virtual seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "Christmas"
    And I click on "Virtual Room" "link" in the "Christmas" "table_row"
    When I set the field "Add virtual room link" to "Custom virtual room link"
    And I click on "OK" "button_exact" in the "Edit room" "totaradialogue"
    Then I should see "You must supply a value here"
    When I set the field "Virtual room link" to "invalid.url/format"
    And I click on "OK" "button_exact" in the "Edit room" "totaradialogue"
    Then I should see "Invalid URL format"
    When I set the field "Virtual room link" to "http://example.com"
    And I click on "OK" "button_exact" in the "Edit room" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"

  Scenario: mod_facetoface_virtualmeeting_105: Ad-hoc virtual room changeability
    Given the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro | course |
      | Virtual seminar 2 |       | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details         |
      | Virtual seminar 2 | Virtual event 2 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails    | start      | finish     | sessiontimezone  |
      | Virtual event 2 | +3 day 6am | +3 day 9am | Indian/Christmas |

    And I log in as "manager1"
    And I am on "Virtual seminar 2" seminar homepage
    And I click on the seminar event action "Edit event" in row "Christmas"

    Given I click on "Select rooms" "link" in the "Christmas" "table_row"
    And I click on "Create" "link" in the "Choose rooms" "totaradialogue"
    Then the "Add to sitewide list" "checkbox" should be enabled
    And I set the following fields to these values:
      | Name     | Virtual Meeting |
      | Capacity | 11              |
    And I click on "OK" "button_exact" in the "Create new room" "totaradialogue"

    When I click on "Virtual Meeting" "link" in the "Christmas" "table_row"
    Then the "Add virtual room link" "select" should be enabled

    Given I set the field "Add virtual room link" to "Custom virtual room link"
    Then the "Add to sitewide list" "checkbox" should be enabled
    And I set the field "Virtual room link" to "http://example.com"
    And I click on "OK" "button_exact" in the "Edit room" "totaradialogue"

    When I click on "Virtual Meeting" "link" in the "Christmas" "table_row"
    Then the "Add virtual room link" "select" should be enabled

    Given I set the field "Add virtual room link" to "Fake Dev App"
    Then the "Add to sitewide list" "checkbox" should be disabled
    And I click on "OK" "button_exact" in the "Edit room" "totaradialogue"

    When I click on "Virtual Meeting" "link" in the "Christmas" "table_row"
    Then the "Add virtual room link" "select" should be disabled
    And I should not see "Add to sitewide list"

  Scenario: mod_facetoface_virtualmeeting_106: Site-wide virtual room changeability
    Given the following "global rooms" exist in "mod_facetoface" plugin:
      | name                    | url                |
      | Physical site-wide room |                    |
      | Virtual site-wide room  | http://example.com |

    And I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"

    Given I click on "Edit room" "link" in the "Physical site-wide room" "table_row"
    And I set the following fields to these values:
      | Name                  | Another virtual site-wide room              |
      | Add virtual room link | Custom virtual room link                    |
    And I set the field "Virtual room link" to local url "/mod/facetoface/tests/fixtures/bph4svcr.php"
    And I press "Save changes"

    Given I click on "Edit room" "link" in the "Virtual site-wide room" "table_row"
    And I set the following fields to these values:
      | Name                  | Another physical site-wide room |
      | Add virtual room link | None                            |
    And I press "Save changes"

    When I click on "Another virtual site-wide room" "link"
    Then I should see "Virtual room"
    And I follow "Go to room"
    And I switch to "totara_bph4svcr" window
    Then I should see "Behat: Virtual Room Placeholder Page" in the page title
    And I click on "Window close" "button_exact"
    And I switch to the main window
    And I click on "Back to rooms" "link"

    When I click on "Another physical site-wide room" "link"
    Then I should not see "Virtual room"
    And "Go to room" "link" should not exist

  Scenario: mod_facetoface_virtualmeeting_107: Ad-hoc virtual room promoted to sitewide changeability
    Given I log in as "admin"
    And I am on "Virtual seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "Christmas"

    When I click on "Virtual Room" "link" in the "Christmas" "table_row"
    And "None" "option" should exist in the "Add virtual room link" "select"
    And "Custom virtual room link" "option" should exist in the "Add virtual room link" "select"
    And "Fake Dev App" "option" should exist in the "Add virtual room link" "select"
    And "Fake Dev User" "option" should exist in the "Add virtual room link" "select"
    And I set the following fields to these values:
      | Add virtual room link | Custom virtual room link    |
      | Virtual room link     | http://example.com?id=12345 |
      | Add to sitewide list  | 1                           |
    And I click on "OK" "button_exact" in the "Edit room" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"

    When I follow "Virtual Room"
    Then I should see "Go to room"

    When I press "Edit room"
    Then the "Add virtual room link" "select" should be enabled
    And "None" "option" should exist in the "Add virtual room link" "select"
    And "Custom virtual room link" "option" should exist in the "Add virtual room link" "select"
    And "Fake Dev App" "option" should not exist in the "Add virtual room link" "select"
    And "Fake Dev User" "option" should not exist in the "Add virtual room link" "select"
    And I set the field "Add virtual room link" to "None"
    And I press "Save changes"
    Then I should not see "Go to room"

    When I press "Edit room"
    And I set the field "Add virtual room link" to "Custom virtual room link"
    And I press "Save changes"
    Then I should see "You must supply a value here"
    And I set the field "Virtual room link" to "http://example.com?id=12345"
    And I press "Save changes"
    Then I should see "Go to room"

  Scenario: mod_facetoface_virtualmeeting_108: Virtualmeeting plugin availability
    Given I log in as "trainer1"
    And I am on "Virtual seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "Christmas"

    When I click on "Virtual Room" "link" in the "Christmas" "table_row"
    And I set the field "Add virtual room link" to "Fake Dev App"
    And I click on "OK" "button_exact" in the "Edit room" "totaradialogue"
    And I press "Save changes"

    Given I run all adhoc tasks
    When I click on "Virtual Room" "link" in the "Christmas" "table_row"
    Then "Host meeting" "link_exact" should exist
    And "Join as attendee" "link_exact" should exist
    And I log out

    Given I log in as "admin"
    And I navigate to "Fake Dev App" node in "Site administration > Plugins > Virtual meetings"
    And I set the following fields to these values:
      | Enabled | 0 |
    And I press "Save changes"
    And I log out

    Given I log in as "trainer1"
    And I am on "Virtual seminar" seminar homepage

    When I click on "Virtual Room" "link" in the "Christmas" "table_row"
    Then I should see "Virtual room is unavailable"
    And I press the "back" button in the browser
    And I click on the seminar event action "Edit event" in row "Christmas"

    When I click on "Virtual Room" "link" in the "Christmas" "table_row"
    Then the field "Add virtual room link" matches value "(Unavailable)"
    And the "Add virtual room link" "select" should be disabled
    And "Add to sitewide list" "checkbox" should not exist

    When I click on "OK" "button_exact" in the "Edit room" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"

  Scenario: mod_facetoface_virtualmeeting_109: Only one virtual room per event
    Given I log in as "trainer1"
    And I am on "Virtual seminar" seminar homepage
    When I click on the seminar event action "Edit event" in row "#1"
    And I click on "Select rooms" "link"
    And I click on "Create" "link" in the "Choose rooms" "totaradialogue"
    And I set the following fields to these values:
      | Name                  | Room 1        |
      | Capacity              | 10            |
      | Add virtual room link | Fake Dev User |
    And I click on "Connect" "button"
    And I switch to "virtualmeeting_connect" window
    And I set the following fields to these values:
      | username | creator |
      | password | creator |
    And I click on "Log in" "button"
    And I switch to the main window
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Create new room" "totaradialogue"
    Then I should not see " Too many virtual meetings. Please ensure that only one virtual meeting is assigned per session"

    # Create second room (which should throw an error)
    And I click on "Select rooms" "link"
    And I click on "Create" "link" in the "Choose rooms" "totaradialogue"
    And I set the following fields to these values:
      | Name                  | Room 2        |
      | Capacity              | 5             |
      | Add virtual room link | Fake Dev User |
    And I click on "Connect" "button"
    And I switch to "virtualmeeting_connect" window
    And I set the following fields to these values:
      | username | creator |
      | password | creator |
    And I click on "Log in" "button"
    And I switch to the main window
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Create new room" "totaradialogue"
    Then I should see "Too many virtual meetings. Please ensure that only one virtual meeting is assigned per session"

    # Remove the second room and the error should disappear
    When I click on "Remove room Room 2 from session" "link"
    Then I should not see " Too many virtual meetings. Please ensure that only one virtual meeting is assigned per session"

  Scenario: mod_facetoface_virtualmeeting_110: A warning banner appears if updating a meeting could be a potential data loss
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name                    | intro | course |
      | Another virtual seminar |       | C1     |

    Given I log in as "admin"
    And I navigate to "Fake Dev App" node in "Site administration > Plugins > Virtual meetings"
    And I set the following fields to these values:
      | Lossy update | No |
    And I press "Save changes"
    And I log out

    Given I log in as "trainer1"
    And I am on "Another virtual seminar" seminar homepage
    And I press "Add event"

    When I click on "Select rooms" "link"
    And I click on "Create" "link_exact" in the "Choose rooms" "totaradialogue"
    And I set the following fields to these values:
      | Name                  | Virtual Meeting Uno |
      | Capacity              | 99                  |
      | Add virtual room link | Fake Dev App        |
    And I click on "OK" "button" in the "Create new room" "totaradialogue"
    Then ".alert.alert-warning" "css_element" should not exist
    And I click on "Remove room Virtual Meeting Uno from session" "link"

    When I click on "Select rooms" "link"
    And I click on "Create" "link_exact" in the "Choose rooms" "totaradialogue"
    And I set the following fields to these values:
      | Name                  | Virtual Meeting Dos |
      | Capacity              | 88                  |
      | Add virtual room link | Fake Dev User       |
    And I click on "Connect" "button"
    And I switch to "virtualmeeting_connect" window
    And I set the following fields to these values:
      | username | creator |
      | password | creator |
    And I click on "Log in" "button"
    And I switch to the main window
    And I click on "OK" "button" in the "Create new room" "totaradialogue"
    Then ".alert.alert-warning" "css_element" should not exist

    When I press "Save changes"
    Then I should not see "Editing event in"

    When I click on the seminar event action "Edit event" in row "#1"
    Then I should see "Editing this session may reset virtual room"
    And I click on "Remove room Virtual Meeting Dos from session" "link"

    When I click on "Select rooms" "link"
    And I click on "Virtual Meeting Uno" "text"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    Then ".alert.alert-warning" "css_element" should not exist

    And I click on "Remove room Virtual Meeting Uno from session" "link"
    When I click on "Select rooms" "link"
    And I click on "Virtual Meeting Dos" "text"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    Then I should see "Editing this session may reset virtual room"

    When I press "Save changes"
    Then I should not see "Editing event in"

    When I click on the seminar event action "Copy event" in row "#1"
    Then ".alert.alert-warning" "css_element" should not exist
    And I click on "Remove room Virtual Meeting Dos from session" "link"

    When I click on "Select rooms" "link"
    And I click on "Virtual Meeting Uno" "text"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    Then ".alert.alert-warning" "css_element" should not exist

    And I click on "Remove room Virtual Meeting Uno from session" "link"
    When I click on "Select rooms" "link"
    And I click on "Virtual Meeting Dos" "text"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    Then ".alert.alert-warning" "css_element" should not exist

    When I press "Save changes"
    Then I should not see "Editing event in"
