@javascript @mod @mod_facetoface @mod_facetoface_virtual_room @totara @totara_core_virtualmeeting
Feature: User sees a seminar virtual room meeting
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username     | firstname | lastname | email                |
      | learner1     | Learner   | One      | learner1@example.com |
      | learner2     | Learner   | Two      | learner2@example.com |
      | trainer1     | Trainer   | First    | trainer1@example.com |
      | trainer2     | Trainer   | Second   | trainer2@example.com |
      | creator      | Creator   | Host     | creator@example.com  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "custom rooms" exist in "mod_facetoface" plugin:
      | name      | url                                         |
      | Countdown | /mod/facetoface/tests/fixtures/bph4svcr.php |
    And the following "global facilitators" exist in "mod_facetoface" plugin:
      | name     | allowconflicts | hidden | description      |
      | Future 1 | 0              | 0      | <p>Near Future</p> |
      | Future 2 | 1              | 0      | <p>Late Future</p> |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro | course |
      | Virtual gathering |       | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details     |
      | Virtual gathering | Party       |
      | Virtual gathering | Countdown   |
      | Virtual gathering | Near Future |
      | Virtual gathering | Late Future |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
      | learner2 | C1     | student |
      | trainer1 | C1     | teacher |
      | trainer2 | C1     | teacher |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                 | finish                | rooms     | starttimezone    | finishtimezone   | sessiontimezone  | facilitators |
      | Party        | 24 Dec next year  6pm | 24 Dec next year 9pm  |           | Europe/Prague    | Europe/Prague    | Europe/Prague    |              |
      | Party        | 24 Dec next year 10pm | 25 Dec next year 12am |           | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |              |
      | Party        | 25 Dec next year  5pm | 25 Dec next year 11pm |           | America/Toronto  | America/Toronto  | America/Toronto  |              |
      | Countdown    | 31 Dec next year 11pm |  1 Jan +2 years 1am   | Countdown | Australia/Perth  | Australia/Perth  | Australia/Perth  |              |
      | Near Future  | +15 min               | +2 hours              |           | Pacific/Apia     | Pacific/Apia     | Pacific/Apia     | Future 1     |
      | Late Future  | +30 min               | +2 hours              |           | Pacific/Apia     | Pacific/Apia     | Pacific/Apia     | Future 2     |

    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | learner1 | Party        | booked |
      | learner1 | Countdown    | booked |
      | trainer1 | Party        | booked |
      | trainer2 | Party        | booked |
      | learner1 | Near Future  | booked |
      | learner2 | Late Future  | booked |
    # Disable custom fields so behat will not poke Google Maps
    Given I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I switch to "Room" tab
    And I click on "Hide" "link" in the "Building" "table_row"
    And I click on "Hide" "link" in the "Location" "table_row"
    And I log out

  Scenario: mod_facetoface_virtualmeeting_001: Create virtual meeting worldwide
    Given I log in as "trainer1"
    And I am on "Virtual gathering" seminar homepage
    And I click on the seminar event action "Edit event" in row "Pacific/Auckland"

    And I click on "Select rooms" "link" in the "Pacific/Auckland" "table_row"
    And I click on "Create" "link_exact" in the "[aria-describedby='selectrooms0-dialog']" "css_element"
    And I set the following fields to these values:
      | Name                  | Party night  |
      | Capacity              | 100          |
      | Add virtual room link | Fake Dev App |
    And I click on "OK" "button_exact" in the "[aria-describedby='editcustomroom0-dialog']" "css_element"

    And I click on "Select rooms" "link" in the "Europe/Prague" "table_row"
    And I click on "Create" "link_exact" in the "[aria-describedby='selectrooms1-dialog']" "css_element"
    And I set the following fields to these values:
      | Name                  | Live music    |
      | Capacity              | 200           |
      | Add virtual room link | Fake Dev User |
    And I click on "Connect" "button" in the "[aria-describedby='editcustomroom1-dialog']" "css_element"
    And I wait "1" seconds
    And I switch to "virtualmeeting_connect" window
    And I set the following fields to these values:
      | Username | creator |
      | Password | creator |
    And I click on "Log in" "button"
    And I switch to the main window
    And I wait "1" seconds
    And I click on "OK" "button_exact" in the "[aria-describedby='editcustomroom1-dialog']" "css_element"

    And I click on "Select rooms" "link" in the "America/Toronto" "table_row"
    And I click on "Party night (Capacity: 100)" "text"
    And I click on "OK" "button_exact" in the "[aria-describedby='selectrooms2-dialog']" "css_element"
    And I press "Save changes"

    When I click on "Party night" "link" in the "Pacific/Auckland" "table_row"
    Then I should see "Updating virtual room"
    And I press the "back" button in the browser

    When I click on "Live music" "link" in the "Europe/Prague" "table_row"
    Then I should see "Updating virtual room"
    And I press the "back" button in the browser

    When I click on "Party night" "link" in the "America/Toronto" "table_row"
    Then I should see "Updating virtual room"
    And I press the "back" button in the browser

    Given I run all adhoc tasks

    When I click on "Party night" "link" in the "Pacific/Auckland" "table_row"
    And I follow "Join as attendee"
    And I switch to "totara_virtualmeeting_poc_meet" window
    Then the following fields match these values:
      | Age    | 1                                                    |
      | Start  | ## 24 Dec next year  9am ## D, d M Y H:i:s \G\M\T ## |
      | Finish | ## 24 Dec next year 11am ## D, d M Y H:i:s \G\M\T ## |
    And I click on "Window close" "button_exact"
    And I switch to the main window
    And I press the "back" button in the browser

    When I click on "Live music" "link" in the "Europe/Prague" "table_row"
    And I follow "Join as attendee"
    And I switch to "totara_virtualmeeting_poc_meet" window
    Then the following fields match these values:
      | Age    | 1                                                    |
      | Start  | ## 24 Dec next year  5pm ## D, d M Y H:i:s \G\M\T ## |
      | Finish | ## 24 Dec next year  8pm ## D, d M Y H:i:s \G\M\T ## |
    And I click on "Window close" "button_exact"
    And I switch to the main window
    And I press the "back" button in the browser

    When I click on "Party night" "link" in the "America/Toronto" "table_row"
    And I follow "Join as attendee"
    And I switch to "totara_virtualmeeting_poc_meet" window
    Then the following fields match these values:
      | Age    | 1                                                    |
      | Start  | ## 25 Dec next year 10pm ## D, d M Y H:i:s \G\M\T ## |
      | Finish | ## 26 Dec next year  4am ## D, d M Y H:i:s \G\M\T ## |
    And I click on "Window close" "button_exact"
    And I switch to the main window
    And I press the "back" button in the browser

    When I click on "Countdown" "link" in the "Australia/Perth" "table_row"
    And I follow "Go to room"
    And I switch to "totara_bph4svcr" window
    Then I should see "Behat: Virtual Room Placeholder Page" in the page title
    And I click on "Window close" "button_exact"
    And I switch to the main window
    And I press the "back" button in the browser

  Scenario: mod_facetoface_virtualmeeting_004: Test using the same virtualmeeting room in two events
    Given I log in as "trainer1"
    When I am on "Virtual gathering" seminar homepage
    And I click on the seminar event action "Edit event" in row "Pacific/Auckland"
    And I click on the link "Select rooms" in row 1
    And I click on "Create" "link_exact" in the "[aria-describedby='selectrooms0-dialog']" "css_element"
    And I set the following fields to these values:
      | Name                  | Party night  |
      | Capacity              | 64           |
      | Add virtual room link | Fake Dev App |
    And I click on "OK" "button_exact" in the "[aria-describedby='editcustomroom0-dialog']" "css_element"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "Australia/Perth"
    And I click on the link "Select rooms" in row 1
    And I click on "Party night (Capacity: 64)" "link"
    And I click on "OK" "button" in the "Choose rooms" "totaradialogue"
    And I press "Save changes"
    And I click on the seminar event action "Event details" in row "Australia/Perth"
    And I follow "Party night"
    Then I should see "Updating virtual room"
    And I run all adhoc tasks
    And I reload the page
    Then I should see "Join as attendee"
    And I click on "Go back" "button"
    And I click on "View all events" "button"
    And I click on the seminar event action "Event details" in row "Pacific/Auckland"
    And I follow "Party night"
    Then I should see "Join as attendee"

  Scenario: mod_facetoface_virtualmeeting_005: One user may not edit session with another user's virtualmeeting room
    Given I log in as "trainer1"
    When I am on "Virtual gathering" seminar homepage
    And I click on the seminar event action "Edit event" in row "Pacific/Auckland"
    And I click on the link "Select rooms" in row 1
    And I click on "Create" "link_exact" in the "[aria-describedby='selectrooms0-dialog']" "css_element"
    And I set the following fields to these values:
      | Name                  | Party night   |
      | Capacity              | 64            |
      | Add virtual room link | Fake Dev User |
    And I click on "Connect" "button"
    And I switch to "virtualmeeting_connect" window
    And I set the following fields to these values:
      | Username | creator |
      | Password | creator |
    And I click on "Log in" "button"
    And I switch to the main window
    Then I should see "Connected as" in the "Create new room" "totaradialogue"
    And I click on "OK" "button_exact" in the "[aria-describedby='editcustomroom0-dialog']" "css_element"
    And I press "Save changes"
    And I click on the seminar event action "Edit event" in row "Pacific/Auckland"
    And I should see "Edit custom room Party night in session"
    And I log out
    When I log in as "trainer2"
    And I am on "Virtual gathering" seminar homepage
    And I click on the seminar event action "Edit event" in row "Pacific/Auckland"
    Then I should not see "Edit custom room Party night in session"
    And I should see "Remove room Party night from session"
    And I click on "Remove room Party night from session" "link"
    Then I should not see "Party night"
    And I press "Save changes"
    Then I should see "Upcoming events"
    And I should not see "Party night"

  Scenario: mod_facetoface_virtualmeeting_006: Enable and disable virtualmeeting plugins
    Given I log in as "admin"
    And I navigate to "Fake Dev User" node in "Site administration > Plugins > Virtual meetings"
    And I set the following fields to these values:
      | Enabled | 0 |
    And I press "Save changes"
    Then I should see "Changes saved"
    When I am on "Virtual gathering" seminar homepage
    And I click on the seminar event action "Edit event" in row "Pacific/Auckland"
    And I click on the link "Select rooms" in row 1
    And I click on "Create" "link" in the "Choose rooms" "totaradialogue"
    And I should not see "Fake Dev User" in the "Add virtual room link" "field"
    And I should see "Fake Dev App" in the "Add virtual room link" "field"
    And I click on "Cancel" "button" in the "Create new room" "totaradialogue"
    Then I navigate to "Fake Dev User" node in "Site administration > Plugins > Virtual meetings"
    And I set the following fields to these values:
      | Enabled | 1 |
    And I press "Save changes"
    And I follow "Fake Dev App"
    And I set the following fields to these values:
      | Enabled | 0 |
    And I press "Save changes"
    When I am on "Virtual gathering" seminar homepage
    And I click on the seminar event action "Edit event" in row "Pacific/Auckland"
    And I click on the link "Select rooms" in row 1
    And I click on "Create" "link" in the "Choose rooms" "totaradialogue"
    And I should see "Fake Dev User" in the "Add virtual room link" "field"
    And I should not see "Fake Dev App" in the "Add virtual room link" "field"

  Scenario: mod_facetoface_virtualmeeting_007: Valid users can see join now on the event details page for virtual meetings
    Given I log in as "admin"
    And I am on "Virtual gathering" seminar homepage
    And I click on the seminar event action "Edit event" in row "Future 1"
    And I click on "Select rooms" "link"
    And I click on "Create" "link_exact" in the "[aria-describedby='selectrooms0-dialog']" "css_element"
    And I set the following fields to these values:
      | Name                  | Future Sailor |
      | Capacity              | 100           |
      | Add virtual room link | Fake Dev App  |
    And I click on "OK" "button_exact" in the "[aria-describedby='editcustomroom0-dialog']" "css_element"
    And I press "Save changes"

    When I am on "Virtual gathering" seminar homepage
    And I click on the seminar event action "Edit event" in row "Future 2"
    And I click on "Select rooms" "link"
    And I click on "Create" "link_exact" in the "[aria-describedby='selectrooms0-dialog']" "css_element"
    And I set the following fields to these values:
      | Name                  | Electronic Castaway |
      | Capacity              | 100                 |
      | Add virtual room link | Fake Dev App        |
    And I click on "OK" "button_exact" in the "[aria-describedby='editcustomroom0-dialog']" "css_element"
    And I press "Save changes"

    # Check the button is not there before the adhoc task creates the rooms.
    When I am on "Course 1" course homepage
    Then I should not see "Join now" in the "Future 1" "table_row"
    And I should not see "Join now" in the "Future 2" "table_row"

    When I follow "View all events"
    Then I should not see "Join now" in the "Future 1" "table_row"
    And I should not see "Join now" in the "Future 2" "table_row"

    When I click on "Go to event" "link" in the "Future 1" "table_row"
    Then I should not see "Join now"
    And I should see "Future Sailor"
    And I click on "Future Sailor" "link"
    And I should not see "join room"
    And I should see "Updating virtual room"

    Given I run all adhoc tasks

    # Check the button is now there after the adhoc task has created the rooms.
    When I am on "Course 1" course homepage
    And I should not see "Join now" in the "Future 2" "table_row"

    When I follow "View all events"
    And I should not see "Join now" in the "Future 2" "table_row"

    When I click on "Go to event" "link" in the "Future 1" "table_row"
    Then I should see "Join now"
    And I click on "Join now" "button"
    And I switch to "totara_virtualmeeting_poc_meet" window
    And I should see "Join meeting" in the page title
    And I should see "Virtual gathering"
    And I click on "Window close" "button_exact"
    And I switch to the main window

    # Check the button is not there on the Late future event
    When I am on "Virtual gathering" seminar homepage
    And I click on "Go to event" "link" in the "Future 2" "table_row"
    Then I should not see "Join now"
    And I should see "Electronic Castaway"
    And I click on "Electronic Castaway" "link"
    And I should see "Host meeting"
    And I should see "Join as attendee"

    # Check the button shows on Near Future for learner1.
    When I log out
    And I log in as "learner1"
    And I am on "Virtual gathering" seminar homepage
    And I click on "Go to event" "link" in the "Future 1" "table_row"
    Then I should see "Join now"
    And I click on "Join now" "button"
    And I switch to "totara_virtualmeeting_poc_meet" window
    And I should see "Join meeting" in the page title
    And I should see "Virtual gathering"
    And I click on "Window close" "button_exact"
    And I switch to the main window

    # Check the button doesn't show on Late Future for learner1.
    When I am on "Virtual gathering" seminar homepage
    And I click on "Go to event" "link" in the "Future 2" "table_row"
    Then I should not see "Join now"
    And I should see "Electronic Castaway"
    And I click on "Electronic Castaway" "link"
    And I should not see "join room"
    And I should see "Virtual room is unavailable"

    # Check the button doesn't show on Near Future for learner2.
    When I log out
    And I log in as "learner2"
    And I am on "Virtual gathering" seminar homepage
    And I click on "Go to event" "link" in the "Future 1" "table_row"
    Then I should not see "Join now"
    And I should see "Future Sailor"
    And I click on "Future Sailor" "link"
    And I should not see "join room"
    And I should see "Virtual room is unavailable"

    # Check the button doesn't show on Late Future for learner2.
    When I am on "Virtual gathering" seminar homepage
    And I click on "Go to event" "link" in the "Future 2" "table_row"
    Then I should not see "Join now"
    And I should see "Electronic Castaway"
    And I click on "Electronic Castaway" "link"
    And I should not see "join room"
    And I should see "Virtual room will open 15 minutes before next session"
