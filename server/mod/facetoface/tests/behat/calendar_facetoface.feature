@mod @mod_facetoface @totara @core_calendar @javascript
Feature: Seminar calendar publishing to course, site, and user calendars

  Background:
    Given the following "categories" exist:
      | name             | category | idnumber | visible |
      | Open Top Level   | 0        | CAT1     | 1       |
      | Hidden Top Level | 0        | CAT2     | 0       |
      | Open Nested      | CAT1     | CAT3     | 1       |
      | Hidden Nested    | CAT1     | CAT4     | 0       |
    Given the following "courses" exist:
      | fullname           | shortname | category | visible | audiencevisible |
      | OTL Course         | course1   | CAT1     | 1       | 2               |
      | OTL Hidden Course  | course2   | CAT1     | 0       | 1               |
      | HTL Course         | course3   | CAT2     | 0       | 0               |
      | ON Course          | course4   | CAT3     | 1       | 2               |
      | ON Hidden Course   | course5   | CAT3     | 0       | 1               |
      | HN Course          | course6   | CAT4     | 0       | 3               |
    And the following "users" exist:
      | username  | firstname | lastname | email         | idnumber |
      | kbomba    | kian      | bomba    | k@example.com | 101      |
      | tedison   | thomas    | edison   | t@example.com | 102      |
    And the following "cohorts" exist:
      | name      | idnumber | contextlevel | reference |
      | Inventors | aud1     | System       |           |
    And the following "cohort members" exist:
      | user     | cohort |
      | tedison  | aud1   |
    And the following "course enrolments" exist:
      | user      | course  | role    |
      | kbomba    | course1 | student |
      | kbomba    | course3 | student |
      | kbomba    | course4 | student |
      | kbomba    | course6 | student |
    And the following "activities" exist:
      | activity   | name                     | shortname                | course  | idnumber  |
      | quiz       | OTL Quiz                 | OTL Quiz                 | course1 | quiz1     |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name               | shortname          | course  | showoncalendar | usercalentry |
      | OTL Seminar        | OTL Seminar        | course1 | 2              | 0            |
      | OTL Hidden Seminar | OTL Hidden Seminar | course2 | 2              | 0            |
      | HTL Seminar        | HTL Seminar        | course3 | 2              | 0            |
      | ON Seminar         | ON Seminar         | course4 | 2              | 0            |
      | ON Hidden Seminar  | ON Hidden Seminar  | course5 | 2              | 0            |
      | HN Seminar         | HN Seminar         | course6 | 2              | 0            |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface         | details |
      | OTL Seminar        | event 1 |
      | OTL Hidden Seminar | event 2 |
      | HTL Seminar        | event 3 |
      | ON Seminar         | event 4 |
      | ON Hidden Seminar  | event 5 |
      | HN Seminar         | event 6 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                   | finish                  |
      | event 1      | now +1 days             | now +1 days +60 minutes |
      | event 2      | now +2 days             | now +2 days +60 minutes |
      | event 3      | now +3 days             | now +3 days +60 minutes |
      | event 4      | now +4 days             | now +4 days +60 minutes |
      | event 5      | now +5 days             | now +5 days +60 minutes |
      | event 6      | now +6 days             | now +6 days +60 minutes |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user   | eventdetails |
      | kbomba | event 1      |
      | kbomba | event 3      |
      | kbomba | event 4      |
      | kbomba | event 6      |
    And I log in as "admin"
    And I am on "OTL Course" course homepage with editing mode on
    And I delete "OTL Quiz" activity
    And I turn editing mode off

  Scenario: Normal visibility, seminars set to site calendar, check calendar event view
    Given I click on "Dashboard" "link"
    And I click on "Go to calendar" "link"
    Then I should see "OTL Seminar" exactly "1" times
    And I should see "OTL Hidden Seminar"
    And I should see "HTL Seminar"
    And I should see "ON Seminar"
    And I should see "ON Hidden Seminar"
    And I should see "HN Seminar"
    And I log out

    When I log in as "kbomba"
    And I follow "Go to calendar"
    Then I should see "OTL Seminar" exactly "1" times
    And I should not see "OTL Hidden Seminar"
    And I should not see "HTL Seminar"
    And I should see "ON Seminar"
    And I should not see "ON Hidden Seminar"
    And I should not see "HN Seminar"
    When I follow "Hide global events"
    Then I should not see "OTL Seminar"
    And I should not see "ON Seminar"
    And I log out

    When I log in as "tedison"
    And I follow "Go to calendar"
    Then I should see "OTL Seminar" exactly "1" times
    And I should not see "OTL Hidden Seminar"
    And I should not see "HTL Seminar"
    And I should see "ON Seminar"
    And I should not see "ON Hidden Seminar"
    And I should not see "HN Seminar"

  Scenario: Normal visibility, seminars set to course calendar, check calendar event view
    Given I am on "OTL Course" course homepage
    And I follow "OTL Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   1 |
    And I click on "Save and return to course" "button"
    And I am on "OTL Hidden Course" course homepage
    And I follow "OTL Hidden Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   1 |
    And I click on "Save and return to course" "button"
    And I am on "HTL Course" course homepage
    And I follow "HTL Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   1 |
    And I click on "Save and return to course" "button"
    And I am on "ON Course" course homepage
    And I follow "ON Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   1 |
    And I click on "Save and return to course" "button"
    And I am on "ON Hidden Course" course homepage
    And I follow "ON Hidden Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   1 |
    And I click on "Save and return to course" "button"
    And I am on "HN Course" course homepage
    And I follow "HN Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   1 |
    And I click on "Save and return to course" "button"

    Given I click on "Dashboard" "link"
    When I follow "Go to calendar"
    Then I should not see "OTL Seminar"
    And I should not see "OTL Hidden Seminar"
    And I should not see "HTL Seminar"
    And I should not see "ON Seminar"
    And I should not see "ON Hidden Seminar"
    And I should not see "HN Seminar"
    And I log out

    When I log in as "kbomba"
    And I follow "Go to calendar"
    Then I should see "OTL Seminar" exactly "1" times
    And I should not see "OTL Hidden Seminar"
    And I should not see "HTL Seminar"
    And I should see "ON Seminar"
    And I should not see "ON Hidden Seminar"
    And I should not see "HN Seminar"
    When I follow "Hide course events"
    Then I should not see "OTL Seminar"
    And I should not see "ON Seminar"
    And I log out

    When I log in as "tedison"
    And I follow "Go to calendar"
    Then I should not see "OTL Seminar"
    And I should not see "OTL Hidden Seminar"
    And I should not see "HTL Seminar"
    And I should not see "ON Seminar"
    And I should not see "ON Hidden Seminar"
    And I should not see "HN Seminar"

  Scenario: Normal visibility, seminars set to user calendar only, check calendar event view
    Given I am on "OTL Course" course homepage
    And I follow "OTL Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   0 |
      | usercalentry   |   1 |
    And I click on "Save and return to course" "button"
    And I am on "OTL Hidden Course" course homepage
    And I follow "OTL Hidden Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   0 |
      | usercalentry   |   1 |
    And I click on "Save and return to course" "button"
    And I am on "HTL Course" course homepage
    And I follow "HTL Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   0 |
      | usercalentry   |   1 |
    And I click on "Save and return to course" "button"
    And I am on "ON Course" course homepage
    And I follow "ON Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   0 |
      | usercalentry   |   1 |
    And I click on "Save and return to course" "button"
    And I am on "ON Hidden Course" course homepage
    And I follow "ON Hidden Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   0 |
      | usercalentry   |   1 |
    And I click on "Save and return to course" "button"
    And I am on "HN Course" course homepage
    And I follow "HN Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   0 |
      | usercalentry   |   1 |
    And I click on "Save and return to course" "button"

    Given I click on "Dashboard" "link"
    When I follow "Go to calendar"
    Then I should see "OTL Seminar" exactly "1" times
    And I should see "OTL Hidden Seminar"
    And I should see "HTL Seminar"
    And I should see "ON Seminar"
    And I should see "ON Hidden Seminar"
    And I should see "HN Seminar"
    And I log out

    When I log in as "kbomba"
    And I follow "Go to calendar"
    Then I should see "OTL Seminar" exactly "1" times
    And I should not see "OTL Hidden Seminar"
    And I should not see "HTL Seminar"
    And I should see "ON Seminar"
    And I should not see "ON Hidden Seminar"
    And I should not see "HN Seminar"
    When I follow "Hide user events"
    Then I should not see "OTL Seminar"
    And I should not see "ON Seminar"
    And I log out

    When I log in as "tedison"
    And I follow "Go to calendar"
    Then I should not see "OTL Seminar"
    And I should not see "OTL Hidden Seminar"
    And I should not see "HTL Seminar"
    And I should not see "ON Seminar"
    And I should not see "ON Hidden Seminar"
    And I should not see "HN Seminar"

  Scenario: Audience based visibility, seminars set to site calendar, check calendar event view
    And I set the following administration settings values:
      | audiencevisibility | 1 |
    And I am on "OTL Hidden Course" course homepage
    And I follow "Edit settings"
    And I click on "Add visible audiences" "button"
    And I click on "Inventors" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "OK" "button" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I wait "1" seconds
    And I press "Save and display"
    And I am on "ON Hidden Course" course homepage
    And I follow "Edit settings"
    And I click on "Add visible audiences" "button"
    And I click on "Inventors" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "OK" "button" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I wait "1" seconds
    And I press "Save and display"
    And I log out

    When I log in as "kbomba"
    And I follow "Go to calendar"
    Then I should see "OTL Seminar" exactly "1" times
    And I should not see "OTL Hidden Seminar"
    And I should see "HTL Seminar"
    And I should see "ON Seminar"
    And I should not see "ON Hidden Seminar"
    And I should not see "HN Seminar"
    When I follow "Hide global events"
    Then I should not see "OTL Seminar"
    And I should not see "HTL Seminar"
    And I should not see "ON Seminar"
    And I log out

    When I log in as "tedison"
    And I follow "Go to calendar"
    Then I should see "OTL Seminar" exactly "1" times
    And I should see "OTL Hidden Seminar"
    And I should not see "HTL Seminar"
    And I should see "ON Seminar"
    And I should see "ON Hidden Seminar"
    And I should not see "HN Seminar"

  Scenario: Audience based visibility, seminars set to site calendar, check calendar event view
    And I set the following administration settings values:
      | audiencevisibility | 1 |
    And I am on "OTL Hidden Course" course homepage
    And I follow "Edit settings"
    And I click on "Add visible audiences" "button"
    And I click on "Inventors" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "OK" "button" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I wait "1" seconds
    And I press "Save and display"
    And I am on "ON Hidden Course" course homepage
    And I follow "Edit settings"
    And I click on "Add visible audiences" "button"
    And I click on "Inventors" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "OK" "button" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I wait "1" seconds
    And I press "Save and display"

    Given I am on "OTL Course" course homepage
    And I follow "OTL Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   1 |
    And I click on "Save and return to course" "button"
    And I am on "OTL Hidden Course" course homepage
    And I follow "OTL Hidden Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   1 |
    And I click on "Save and return to course" "button"
    And I am on "HTL Course" course homepage
    And I follow "HTL Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   1 |
    And I click on "Save and return to course" "button"
    And I am on "ON Course" course homepage
    And I follow "ON Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   1 |
    And I click on "Save and return to course" "button"
    And I am on "ON Hidden Course" course homepage
    And I follow "ON Hidden Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   1 |
    And I click on "Save and return to course" "button"
    And I am on "HN Course" course homepage
    And I follow "HN Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   1 |
    And I click on "Save and return to course" "button"
    And I log out

    When I log in as "kbomba"
    And I follow "Go to calendar"
    Then I should see "OTL Seminar" exactly "1" times
    And I should not see "OTL Hidden Seminar"
    And I should see "HTL Seminar"
    And I should see "ON Seminar"
    And I should not see "ON Hidden Seminar"
    And I should not see "HN Seminar"
    When I follow "Hide course events"
    Then I should not see "OTL Seminar"
    And I should not see "HTL Seminar"
    And I should not see "ON Seminar"
    And I log out

    When I log in as "tedison"
    And I follow "Go to calendar"
    Then I should not see "OTL Seminar"
    And I should not see "OTL Hidden Seminar"
    And I should not see "HTL Seminar"
    And I should not see "ON Seminar"
    And I should not see "ON Hidden Seminar"
    And I should not see "HN Seminar"

  Scenario: Audience based visibility, seminars set to user calendar, check calendar event view
    Given I set the following administration settings values:
      | audiencevisibility | 1 |
    And I am on "OTL Hidden Course" course homepage
    And I follow "Edit settings"
    And I click on "Add visible audiences" "button"
    And I click on "Inventors" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "OK" "button" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I wait "1" seconds
    And I press "Save and display"
    And I am on "ON Hidden Course" course homepage
    And I follow "Edit settings"
    And I click on "Add visible audiences" "button"
    And I click on "Inventors" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "OK" "button" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I wait "1" seconds
    And I press "Save and display"

    Given I am on "OTL Course" course homepage
    And I follow "OTL Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   0 |
      | usercalentry   |   1 |
    And I click on "Save and return to course" "button"
    And I am on "OTL Hidden Course" course homepage
    And I follow "OTL Hidden Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   0 |
      | usercalentry   |   1 |
    And I click on "Save and return to course" "button"
    And I am on "HTL Course" course homepage
    And I follow "HTL Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   0 |
      | usercalentry   |   1 |
    And I click on "Save and return to course" "button"
    And I am on "ON Course" course homepage
    And I follow "ON Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   0 |
      | usercalentry   |   1 |
    And I click on "Save and return to course" "button"
    And I am on "ON Hidden Course" course homepage
    And I follow "ON Hidden Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   0 |
      | usercalentry   |   1 |
    And I click on "Save and return to course" "button"
    And I am on "HN Course" course homepage
    And I follow "HN Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | showoncalendar |   0 |
      | usercalentry   |   1 |
    And I click on "Save and return to course" "button"
    And I log out

    When I log in as "kbomba"
    And I follow "Go to calendar"
    Then I should see "OTL Seminar" exactly "1" times
    And I should not see "OTL Hidden Seminar"
    And I should see "HTL Seminar"
    And I should see "ON Seminar"
    And I should not see "ON Hidden Seminar"
    And I should not see "HN Seminar"
    When I follow "Hide user events"
    Then I should not see "OTL Seminar"
    And I should not see "HTL Seminar"
    And I should not see "ON Seminar"
    And I log out

    When I log in as "tedison"
    And I follow "Go to calendar"
    Then I should not see "OTL Seminar"
    And I should not see "OTL Hidden Seminar"
    And I should not see "HTL Seminar"
    And I should not see "ON Seminar"
    And I should not see "ON Hidden Seminar"
    And I should not see "HN Seminar"