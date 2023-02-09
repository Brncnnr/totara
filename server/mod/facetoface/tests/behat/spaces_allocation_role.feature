@javascript @mod @mod_facetoface @totara
Feature: Reserve and allocate spaces by staff manager with job assignment
  In order to test seminar allocations
  As a staff manager
  I need to allocate spaces for user

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Define roles" node in "Site administration > Permissions"
    And I follow "Staff Manager"
    And I press "Edit"
    And I set the following fields to these values:
      | contextlevel50 | 1            |
      | contextlevel70 | 1            |
    And I press "Save changes"
    And the following "users" exist:
      | username     | firstname | lastname | email                    |
      | staffmanager | Staff     | Manager  | staffmanager@example.com |
      | student1     | Sam1      | Student1 | student1@example.com     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user         | course | role         |
      | staffmanager | C1     | staffmanager |
      | student1     | C1     | student      |
    And the following "activities" exist:
      | activity   | name          | course | idnumber | managerreserve | allowcancellationsdefault | cancellationscutoffdefault |
      | facetoface | Seminar 23071 | C1     | S23071   | 1              | 2                         | 1209600                    |
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Sam1 Student1" "link"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Staff Manager |
      | ID Number | JA1           |
    And I press "Choose manager"
    And I click on "Staff Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I wait "1" seconds
    And I click on "Add job assignment" "button"

    And I am on "Course 1" course homepage
    And I follow "Seminar 23071"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | 0                |
      | timestart[month]   | +1               |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | 0                |
      | timefinish[month]  | +1               |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 0                |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I click on the link "Go to event" in row 1
    And I click on "Reserve for another manager" "link"
    And I set the following fields to these values:
       | managerid | 3 |
    And I press "Select manager"
    And I log out

  Scenario: Reserve and allocate spaces for user by staff manager with job assignment
    Given I log in as "staffmanager"
    And I am on "Course 1" course homepage
    And I follow "Seminar 23071"
    And I click on the link "Go to event" in row 1
    And I click on "Reserve spaces for team" "link"
    And I set the following fields to these values:
      | reserve | 1 |
    And I press "Update"
    And I click on "Allocate spaces for team" "link"
    And I set the field "Available team members" to "Sam1 Student1"
    When I press "Add"
    Then I should see "Allocate spaces for team (1/1)"
    And I click on "Allocate spaces for team" "link"
    And I set the field "Allocated team members" to "Sam1 Student1"
    When I press "Remove"
    Then I should see "Allocate spaces for team (0/1)"