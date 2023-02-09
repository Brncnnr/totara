@mod @mod_facetoface @javascript
Feature: Verify which columns are removed from the seminar event dashboard
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                |
      | learner1  | lear      | ner1     | learner1@example.com |
      | teacher   | tea       | cher     | teacher@example.com  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user      | course  | role           |
      | learner1  | C1      | student        |
      | teacher   | C1      | editingteacher |

  Scenario: Test activity default setting
    Given I log in as "admin"
    And I navigate to "Activity defaults" node in "Site administration > Seminars"
    And I set the field "Events table - hide empty columns" to "1"
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1"
    And the field "Events table - hide empty columns" matches value "1"

  Scenario: Test columns of a waitlisted event
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name      | course | decluttersessiontable |
      | Seminar 1 | C1     | 0                     |
      | Seminar 2 | C1     | 1                     |
      | Seminar 3 | C1     | 1                     |
      | Seminar 4 | C1     | 1                     |
      | Seminar 5 | C1     | 1                     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details | registrationtimestart | registrationtimefinish |
      | Seminar 1  | event 1 | @0                    | @0                     |
      | Seminar 2  | event 2 | @0                    | @0                     |
      | Seminar 3  | event 3 | @0                    | 31 Mar next year       |
      | Seminar 4  | event 4 | 28 Feb next year      | @0                     |
      | Seminar 5  | event 5 | 28 Feb next year      | 31 Mar next year       |
    Given I log in as "teacher"
    And I am on "Course 1" course homepage

    When I follow "Seminar 1"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Booked | Event status | Sign-up period | Session times | Rooms | Facilitators | Session status | Actions   | Actions     |
      | 0 / 10 | Wait-listed  |                |               |       |              |                | Attendees | Go to event |

    When I follow "Seminar 2"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Booked | Event status | Actions   | Actions     |
      | 0 / 10 | Wait-listed  | Attendees | Go to event |
    But the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Booked | Sign-up period | Session times | Rooms | Facilitators | Session status |
      | 0 / 10 |                |               |       |              |                |

    When I follow "Seminar 3"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Booked | Event status | Sign-up period | Actions   | Actions     |
      | 0 / 10 | Wait-listed  | 31 March       | Attendees | Go to event |
    But the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Booked | Session times | Rooms | Facilitators | Session status |
      | 0 / 10 |               |       |              |                |

    When I follow "Seminar 4"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Booked | Event status | Sign-up period | Actions   | Actions     |
      | 0 / 10 | Wait-listed  | 28 February    | Attendees | Go to event |
    But the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Booked | Session times | Rooms | Facilitators | Session status |
      | 0 / 10 |               |       |              |                |

    When I follow "Seminar 5"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Booked | Event status | Sign-up period | Sign-up period | Actions   | Actions     |
      | 0 / 10 | Wait-listed  | 28 February    | 31 March       | Attendees | Go to event |
    But the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Booked | Session times | Rooms | Facilitators | Session status |
      | 0 / 10 |               |       |              |                |

    And I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage

    When I follow "Seminar 1"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Seats available | Event status | Sign-up period | Session times | Rooms | Facilitators | Session status | Actions   | Actions     |
      | 10              | Wait-listed  |                |               |       |              |                | Attendees | Go to event |

    When I follow "Seminar 2"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Seats available | Event status | Actions   | Actions     |
      | 10              | Wait-listed  | Attendees | Go to event |
    But the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Seats available | Sign-up period | Session times | Rooms | Facilitators | Session status |
      | 10              |                |               |       |              |                |

    When I follow "Seminar 3"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Seats available | Event status | Sign-up period | Actions   | Actions     |
      | 10              | Wait-listed  | 31 March       | Attendees | Go to event |
    But the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Seats available | Session times | Rooms | Facilitators | Session status |
      | 10              |               |       |              |                |

    When I follow "Seminar 4"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Seats available | Event status | Sign-up period | Actions   | Actions     |
      | 10              | Wait-listed  | 28 February    | Attendees | Go to event |
    But the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Seats available | Session times | Rooms | Facilitators | Session status |
      | 10              |               |       |              |                |

    When I follow "Seminar 5"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Seats available | Event status | Sign-up period | Sign-up period | Actions   | Actions     |
      | 10              | Wait-listed  | 31 March       | 28 February    | Attendees | Go to event |
    But the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Seats available | Session times | Rooms | Facilitators | Session status |
      | 10              |               |       |              |                |

  Scenario: Test columns of different event times
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name      | course | decluttersessiontable |
      | Seminar 1 | C1     | 0                     |
      | Seminar 2 | C1     | 0                     |
      | Seminar 3 | C1     | 0                     |
      | Seminar 4 | C1     | 0                     |
      | Seminar 5 | C1     | 0                     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details | cancelledstatus |
      | Seminar 1  | event 1 | 0               |
      | Seminar 2  | event 2 | 0               |
      | Seminar 3  | event 3 | 0               |
      | Seminar 4  | event 4 | 1               |
      | Seminar 5  | event 5 | 1               |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start           | finish          | sessiontimezone  |
      | event 1      | 1 Jan last year | 2 Feb last year | Pacific/Auckland |
      | event 2      | 3 Mar last year | 4 Apr next year | Pacific/Auckland |
      | event 3      | 5 May next year | 6 Jun next year | Pacific/Auckland |
      | event 4      | 7 Jul next year | 8 Aug next year | Pacific/Auckland |
    Given I log in as "teacher"
    And I am on "Course 1" course homepage

    When I follow "Seminar 1"
    Then "mod_facetoface_upcoming_events_table" "table" should not exist
    And the "mod_facetoface_past_events_table" table should contain the following:
      | Booked | Event status | Session times | Session times | Rooms | Facilitators | Session status | Actions   |
      | 0 / 10 | Over         | 1 January     | 2 February    |       |              | Session over   | Attendees |
    But the "mod_facetoface_past_events_table" table should not contain the following:
      | Booked | Sign-up period | Actions     |
      | 0 / 10 |                | Go to event |
    When I navigate to "Edit settings" node in "Seminar administration"
    And I set the field "decluttersessiontable" to "1"
    And I press "Save and display"
    Then the "mod_facetoface_past_events_table" table should not contain the following:
      | Booked | Sign-up period | Rooms | Facilitators |
      | 0 / 10 |                |       |              |

    When I follow "Seminar 2"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Booked | Event status | Session times | Session times | Rooms | Facilitators | Session status      | Actions   | Actions     |
      | 0 / 10 | In progress  | 3 March       | 4 April       |       |              | Session in progress | Attendees | Go to event |
    When I navigate to "Edit settings" node in "Seminar administration"
    And I set the field "decluttersessiontable" to "1"
    And I press "Save and display"
    Then the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Booked | Sign-up period | Rooms | Facilitators |
      | 0 / 10 |                |       |              |

    When I follow "Seminar 3"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Booked | Event status | Session times | Session times | Rooms | Facilitators | Session status | Actions   | Actions     |
      | 0 / 10 | Upcoming     | 5 May         | 6 June        |       |              | Upcoming       | Attendees | Go to event |
    When I navigate to "Edit settings" node in "Seminar administration"
    And I set the field "decluttersessiontable" to "1"
    And I press "Save and display"
    Then the "mod_facetoface_past_events_table" table should not contain the following:
      | Booked | Sign-up period | Rooms | Facilitators |
      | 0 / 10 |                |       |              |

    When I follow "Seminar 4"
    Then "mod_facetoface_upcoming_events_table" "table" should not exist
    And the "mod_facetoface_past_events_table" table should contain the following:
      | Booked | Event status | Session times | Session times | Rooms | Facilitators | Session status | Actions   |
      | 0 / 10 | Cancelled    | 7 July        | 8 August      |       |              | Cancelled      | Attendees |
    But the "mod_facetoface_past_events_table" table should not contain the following:
      | Booked | Sign-up period | Actions     |
      | 0 / 10 |                | Go to event |
    When I navigate to "Edit settings" node in "Seminar administration"
    And I set the field "decluttersessiontable" to "1"
    And I press "Save and display"
    Then the "mod_facetoface_past_events_table" table should not contain the following:
      | Booked | Sign-up period | Rooms | Facilitators |
      | 0 / 10 |                |       |              |

    When I follow "Seminar 5"
    Then "mod_facetoface_upcoming_events_table" "table" should not exist
    And the "mod_facetoface_past_events_table" table should contain the following:
      | Booked | Event status | Session times | Rooms | Facilitators | Session status | Actions   |
      | 0 / 10 | Cancelled    |               |       |              | Cancelled      | Attendees |
    But the "mod_facetoface_past_events_table" table should not contain the following:
      | Booked | Sign-up period | Actions     |
      | 0 / 10 |                | Go to event |
    When I navigate to "Edit settings" node in "Seminar administration"
    And I set the field "decluttersessiontable" to "1"
    And I press "Save and display"
    Then the "mod_facetoface_past_events_table" table should not contain the following:
      | Booked | Sign-up period | Session times | Rooms | Facilitators |
      | 0 / 10 |                |               |       |              |

    And I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage

    When I follow "Seminar 1"
    Then "mod_facetoface_upcoming_events_table" "table" should not exist
    And the "mod_facetoface_past_events_table" table should contain the following:
      | Seats available | Event status | Session times | Session times | Session status |
      | 10              | Over         | 1 January     | 2 February    | Session over   |
    But the "mod_facetoface_past_events_table" table should not contain the following:
      | Seats available | Sign-up period | Rooms | Facilitators | Actions |
      | 10              |                |       |              |         |

    When I follow "Seminar 2"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Seats available | Event status | Session times | Session times | Session status      | Actions     |
      | 10              | In progress  | 3 March       | 4 April       | Session in progress | Go to event |
    But the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Seats available | Sign-up period | Rooms | Facilitators |
      | 10              |                |       |              |

    When I follow "Seminar 3"
    Then "mod_facetoface_past_events_table" "table" should not exist
    And the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Seats available | Event status | Session times | Session times | Session status | Actions     |
      | 10              | Upcoming     | 5 May         | 6 June        | Upcoming       | Go to event |
    But the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Seats available | Sign-up period | Rooms | Facilitators |
      | 10              |                |       |              |

    When I follow "Seminar 4"
    Then "mod_facetoface_upcoming_events_table" "table" should not exist
    And the "mod_facetoface_past_events_table" table should contain the following:
      | Seats available | Event status | Session times | Session times | Session status |
      | 10              | Cancelled    | 7 July        | 8 August      | Cancelled      |
    But the "mod_facetoface_past_events_table" table should not contain the following:
      | Seats available | Sign-up period | Rooms | Facilitators | Actions |
      | 10              |                |       |              |         |

    When I follow "Seminar 5"
    Then "mod_facetoface_upcoming_events_table" "table" should not exist
    And the "mod_facetoface_past_events_table" table should contain the following:
      | Seats available | Event status | Session status |
      | 10              | Cancelled    | Cancelled      |
    But the "mod_facetoface_past_events_table" table should not contain the following:
      | Seats available | Sign-up period | Session times | Rooms | Facilitators | Actions |
      | 10              |                |               |       |              |         |

  Scenario: Test columns with custom fields
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name      | course | decluttersessiontable |
      | Seminar 1 | C1     | 0                     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | Seminar 1  | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start           | finish          | sessiontimezone  |
      | event 1      | 1 Jan next year | 2 Feb next year | Pacific/Auckland |

    Given I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I set the following fields to these values:
      | datatype  | Checkbox |
      | fullname  | CF check |
      | shortname | check    |
    And I press "Save changes"
    And I set the following fields to these values:
      | datatype  | Date/time |
      | fullname  | CF date   |
      | shortname | date      |
    And I press "Save changes"
    And I set the following fields to these values:
      | datatype  | File    |
      | fullname  | CF file |
      | shortname | file    |
    And I press "Save changes"
    And I set the following fields to these values:
      | datatype  | Location    |
      | fullname  | CF location |
      | shortname | location    |
    And I press "Save changes"
    And I set the following fields to these values:
      | datatype  | Menu of choices |
      | fullname  | CF menu         |
      | shortname | menu            |
    And I set the field "Menu options" to multiline:
      """
      foo
      bar
      qux
      """
    And I press "Save changes"
    And I set the following fields to these values:
      | datatype  | Multi-select |
      | fullname  | CF multi     |
      | shortname | multi        |
      | multiselectitem[0][option] | one  |
      | multiselectitem[1][option] | tree |
      | multiselectitem[2][option] | hill |
    And I press "Save changes"
    And I set the following fields to these values:
      | datatype  | Text area |
      | fullname  | CF memo   |
      | shortname | memo      |
    And I press "Save changes"
    And I set the following fields to these values:
      | datatype  | Text input |
      | fullname  | CF text    |
      | shortname | text       |
    And I press "Save changes"
    And I set the following fields to these values:
      | datatype  | URL    |
      | fullname  | CF url |
      | shortname | url    |
    And I press "Save changes"

    And I am on "Course 1" course homepage
    When I follow "Seminar 1"
    Then the "mod_facetoface_upcoming_events_table" table should contain the following:
      | Event status | CF check | CF date | CF file | CF location | CF menu | CF multi | CF memo | CF text | CF url |
      | Upcoming     |          |         |         |             |         |          |         |         |        |
    But "mod_facetoface_past_events_table" "table" should not exist

    When I navigate to "Edit settings" node in "Seminar administration"
    And I set the field "decluttersessiontable" to "1"
    And I press "Save and display"
    Then the "mod_facetoface_upcoming_events_table" table should not contain the following:
      | Event status | CF check | CF date | CF file | CF location | CF menu | CF multi | CF memo | CF text | CF url |
      | Upcoming     |          |         |         |             |         |          |         |         |        |
