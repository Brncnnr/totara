@mod @mod_assign
Feature: Assign group override
  In order to grant a group special access to an assignment
  As a teacher
  I need to create an override for that group.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Tina | Teacher1 | teacher1@example.com |
      | student1 | Sam1 | Student1 | student1@example.com |
      | student2 | Sam2 | Student2 | student2@example.com |
      | student3 | Sam3 | Student3 | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    Given the following "group members" exist:
      | user     | group   |
      | student1 | G1 |
      | student2 | G2 |
      | student3 | G1 |
    And the following "activities" exist:
      | activity | name                 | intro                   | course | idnumber | assignsubmission_onlinetext_enabled |
      | assign   | Test assignment name | Submit your online text | C1     | assign1  | 1                                   |

  Scenario: Add, modify then delete a group override
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I follow "Test assignment name"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I set the following fields to these values:
      | Override group     | Group 1 |
      | id_duedate_enabled | 1 |
      | duedate[day]       | 1 |
      | duedate[month]     | January |
      | duedate[year]      | ## +2 years ## Y ## |
      | duedate[hour]      | 08 |
      | duedate[minute]    | 00 |
    And I press "Save"
    And I should see date "1 Jan +2 years" formatted "%A, %d %B %Y, 8:00"
    Then I click on "Edit" "link" in the "Group 1" "table_row"
    And I set the following fields to these values:
      | duedate[year] | ## +5 years ## Y ## |
    And I press "Save"
    And I should see date "1 Jan +5 years" formatted "%A, %d %B %Y, 8:00"
    And I click on "Delete" "link"
    And I press "Continue"
    And I should not see "Group 1"

  Scenario: Duplicate a user override
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I follow "Test assignment name"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I set the following fields to these values:
      | Override group     | Group 1 |
      | id_duedate_enabled | 1 |
      | duedate[day]       | 1 |
      | duedate[month]     | January |
      | duedate[year]      | ## +2 years ## Y ## |
      | duedate[hour]      | 08 |
      | duedate[minute]    | 00 |
    And I press "Save"
    And I should see date "1 Jan +2 years" formatted "%A, %d %B %Y, 8:00"
    Then I click on "copy" "link"
    And I set the following fields to these values:
      | Override group | Group 2  |
      | duedate[year]  | ## +5 years ## Y ## |
    And I press "Save"
    And I should see date "1 Jan +5 years" formatted "%A, %d %B %Y, 8:00"
    And I should see "Group 2"

  Scenario: Allow a group to have a different due date
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I follow "Test assignment name"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | id_duedate_enabled | 1 |
      | id_allowsubmissionsfromdate_enabled | 0 |
      | id_cutoffdate_enabled | 0 |
      | duedate[day]       | 1 |
      | duedate[month]     | January |
      | duedate[year]      | ## -3 years ## Y ## |
      | duedate[hour]      | 08 |
      | duedate[minute]    | 00 |
    And I press "Save and display"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I set the following fields to these values:
      | Override group     | Group 1 |
      | id_duedate_enabled | 1 |
      | duedate[day]       | 1 |
      | duedate[month]     | January |
      | duedate[year]      | ## +2 years ## Y ## |
      | duedate[hour]      | 08 |
      | duedate[minute]    | 00 |
    And I press "Save"
    And I should see date "1 Jan +2 years" formatted "%A, %d %B %Y, 8:00"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    Then I should see date "-3 years 1 Jan" formatted "%A, %d %B %Y, 8:00"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I should see date "1 Jan +2 years" formatted "%A, %d %B %Y, 8:00"

  Scenario: Allow a group to have a different cut off date
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I follow "Test assignment name"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | id_duedate_enabled | 0 |
      | id_allowsubmissionsfromdate_enabled | 0 |
      | id_cutoffdate_enabled | 1 |
      | cutoffdate[day]       | 1 |
      | cutoffdate[month]     | January |
      | cutoffdate[year]      | ## -3 years ## Y ## |
      | cutoffdate[hour]      | 08 |
      | cutoffdate[minute]    | 00 |
    And I press "Save and display"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I set the following fields to these values:
      | Override group     | Group 1 |
      | id_cutoffdate_enabled | 1 |
      | cutoffdate[day]       | 1 |
      | cutoffdate[month]     | January |
      | cutoffdate[year]      | ## +2 years ## Y ## |
      | cutoffdate[hour]      | 08 |
      | cutoffdate[minute]    | 00 |
    And I press "Save"
    And I should see date "1 Jan +2 years" formatted "%A, %d %B %Y, 8:00"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    Then I should not see "Make changes to your submission"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I should see "Make changes to your submission"

  Scenario: Allow a group to have a different start date
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I follow "Test assignment name"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | id_duedate_enabled | 0 |
      | id_allowsubmissionsfromdate_enabled | 1 |
      | id_cutoffdate_enabled | 0 |
      | allowsubmissionsfromdate[day]       | 1 |
      | allowsubmissionsfromdate[month]     | January |
      | allowsubmissionsfromdate[year]      | ## +2 years ## Y ## |
      | allowsubmissionsfromdate[hour]      | 08 |
      | allowsubmissionsfromdate[minute]    | 00 |
    And I press "Save and display"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I set the following fields to these values:
      | Override group       | Group 1 |
      | id_allowsubmissionsfromdate_enabled | 1 |
      | allowsubmissionsfromdate[day]       | 1 |
      | allowsubmissionsfromdate[month]     | January |
      | allowsubmissionsfromdate[year]      | ## last year ## Y ## |
      | allowsubmissionsfromdate[hour]      | 08 |
      | allowsubmissionsfromdate[minute]    | 00 |
    And I press "Save"
    And I should see date "1 Jan last year" formatted "%A, %d %B %Y, 8:00"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    Then I should see date "1 Jan +2 years" formatted "This assignment will accept submissions from %A, %d %B %Y, 8:00"
    And I should not see "Add submission"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I should not see "This assignment will accept submissions from"

  @javascript
  Scenario: Add both a user and group override and verify that both are applied correctly
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I follow "Test assignment name"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | id_duedate_enabled | 0 |
      | id_allowsubmissionsfromdate_enabled | 1 |
      | id_cutoffdate_enabled | 0 |
      | allowsubmissionsfromdate[day]       | 1 |
      | allowsubmissionsfromdate[month]     | January |
      | allowsubmissionsfromdate[year]      | ## +5 years ## Y ## |
      | allowsubmissionsfromdate[hour]      | 08 |
      | allowsubmissionsfromdate[minute]    | 00 |
    And I press "Save and display"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I set the following fields to these values:
      | Override group       | Group 1 |
      | id_allowsubmissionsfromdate_enabled | 1 |
      | allowsubmissionsfromdate[day]       | 1 |
      | allowsubmissionsfromdate[month]     | January |
      | allowsubmissionsfromdate[year]      | ## +2 years ## Y ## |
      | allowsubmissionsfromdate[hour]      | 08 |
      | allowsubmissionsfromdate[minute]    | 00 |
    And I press "Save"
    And I should see date "1 Jan +2 years" formatted "%A, %d %B %Y, 8:00"
    And I follow "Test assignment name"
    And I navigate to "User overrides" in current page administration
    And I press "Add user override"
    And I set the following fields to these values:
      | Override user        | Student1 |
      | id_allowsubmissionsfromdate_enabled | 1 |
      | allowsubmissionsfromdate[day]       | 1 |
      | allowsubmissionsfromdate[month]     | January |
      | allowsubmissionsfromdate[year]      | ## +3 years ## Y ## |
      | allowsubmissionsfromdate[hour]      | 08 |
      | allowsubmissionsfromdate[minute]    | 00 |
    And I press "Save"
    And I should see date "1 Jan +3 years" formatted "%A, %d %B %Y, 8:00"
    And I log out
    Then I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I should see date "1 Jan +3 years" formatted "This assignment will accept submissions from %A, %d %B %Y, 8:00"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I should see date "1 Jan +5 years" formatted "This assignment will accept submissions from %A, %d %B %Y, 8:00"
    And I log out
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I should see date "1 Jan +2 years" formatted "This assignment will accept submissions from %A, %d %B %Y, 8:00"

  Scenario: Check correct ordering is made when overriding group with same due date
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I follow "Test assignment name"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | id_duedate_enabled | 1 |
      | id_allowsubmissionsfromdate_enabled | 0 |
      | id_cutoffdate_enabled | 0 |
      | duedate[day]       | 1 |
      | duedate[month]     | January |
      | duedate[year]      | ## -3 years ## Y ## |
      | duedate[hour]      | 08 |
      | duedate[minute]    | 00 |
    And I press "Save and display"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I set the following fields to these values:
      | Override group     | Group 1 |
      | id_duedate_enabled | 1       |
      | duedate[day]       | 1       |
      | duedate[month]     | January |
      | duedate[year]      | ## +2 years ## Y ## |
      | duedate[hour]      | 08      |
      | duedate[minute]    | 00      |
    And I press "Save"
    And I should see date "1 Jan +2 years" formatted "%A, %d %B %Y, 8:00"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I set the following fields to these values:
      | Override group     | Group 2 |
      | id_duedate_enabled | 1       |
      | duedate[day]       | 1       |
      | duedate[month]     | January |
      | duedate[year]      | ## +2 years ## Y ## |
      | duedate[hour]      | 08      |
      | duedate[minute]    | 00      |
    And I press "Save"
    And I should see "Group 2" in the ".lastrow" "css_element"
    And "Move up" "link" should exist in the "Group 2" "table_row"
    And "Move down" "link" should exist in the "Group 1" "table_row"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I set the following fields to these values:
      | Override group     | Group 1 |
      | id_duedate_enabled | 1       |
      | duedate[day]       | 1       |
      | duedate[month]     | January |
      | duedate[year]      | ## +2 years ## Y ## |
      | duedate[hour]      | 08      |
      | duedate[minute]    | 00      |
    And I press "Save"
    And I should see "Group 1" in the ".lastrow" "css_element"
    And "Move up" "link" should exist in the "Group 1" "table_row"
    And "Move down" "link" should exist in the "Group 2" "table_row"
    And I log out

  Scenario: Override a group when teacher is in no group, and does not have accessallgroups permission, and the activity's group mode is "separate groups"
    Given the following "permission overrides" exist:
      | capability                  | permission | role           | contextlevel | reference |
      | moodle/site:accessallgroups | Prevent    | editingteacher | Course       | C1        |
    And the following "activities" exist:
      | activity | name         | intro                    | course | idnumber | groupmode |
      | assign   | Assignment 2 | Assignment 2 description | C1     | assign2  | 1         |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Assignment 2"
    And I navigate to "Group overrides" in current page administration
    Then I should see "No groups you can access."
    And the "Add group override" "button" should be disabled

  Scenario: A teacher without accessallgroups permission should only be able to add group override for groups that he/she is a member of,
  when the activity's group mode is "separate groups"
    Given the following "permission overrides" exist:
      | capability                  | permission | role           | contextlevel | reference |
      | moodle/site:accessallgroups | Prevent    | editingteacher | Course       | C1        |
    And the following "activities" exist:
      | activity | name         | intro                    | course | idnumber | groupmode |
      | assign   | Assignment 2 | Assignment 2 description | C1     | assign2  | 1         |
    And the following "group members" exist:
      | user     | group |
      | teacher1 | G1    |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Assignment 2"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    Then the "Override group" select box should contain "Group 1"
    And the "Override group" select box should not contain "Group 2"

  Scenario: A teacher without accessallgroups permission should only be able to see the group overrides for groups that he/she is a member of,
  when the activity's group mode is "separate groups"
    Given the following "permission overrides" exist:
      | capability                  | permission | role           | contextlevel | reference |
      | moodle/site:accessallgroups | Prevent    | editingteacher | Course       | C1        |
    And the following "activities" exist:
      | activity | name         | intro                    | course | idnumber | groupmode |
      | assign   | Assignment 2 | Assignment 2 description | C1     | assign2  | 1         |
    And the following "group members" exist:
      | user     | group |
      | teacher1 | G1    |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Assignment 2"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I set the following fields to these values:
      | Override group                      | Group 1 |
      | id_allowsubmissionsfromdate_enabled | 1       |
      | allowsubmissionsfromdate[day]       | 1       |
      | allowsubmissionsfromdate[month]     | January |
      | allowsubmissionsfromdate[year]      | 2020    |
      | allowsubmissionsfromdate[hour]      | 08      |
      | allowsubmissionsfromdate[minute]    | 00      |
    And I press "Save and enter another override"
    And I set the following fields to these values:
      | Override group                      | Group 2 |
      | id_allowsubmissionsfromdate_enabled | 1       |
      | allowsubmissionsfromdate[day]       | 1       |
      | allowsubmissionsfromdate[month]     | January |
      | allowsubmissionsfromdate[year]      | 2020    |
      | allowsubmissionsfromdate[hour]      | 08      |
      | allowsubmissionsfromdate[minute]    | 00      |
    And I press "Save"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Assignment 2"
    And I navigate to "Group overrides" in current page administration
    Then I should see "Group 1" in the ".generaltable" "css_element"
    And I should not see "Group 2" in the ".generaltable" "css_element"
