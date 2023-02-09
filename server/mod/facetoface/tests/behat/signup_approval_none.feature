@mod @mod_facetoface @totara @javascript
Feature: Seminar Signup No Approval
  In order to signup to classroom connect
  As a learner
  I need to sign click the signup button

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname | lastname | email              |
      | sysapprover | Terry     | Ter      | terry@example.com  |
      | actapprover | Larry     | Lar      | larry@example.com  |
      | teacher     | Freddy    | Fred     | freddy@example.com |
      | trainer     | Benny     | Ben      | benny@example.com  |
      | manager     | Cassy     | Cas      | cassy@example.com  |
      | jimmy       | Jimmy     | Jim      | jimmy@example.com  |
      | timmy       | Timmy     | Tim      | timmy@example.com  |
      | sammy       | Sammy     | Sam      | sammy@example.com  |
      | sally       | Sally     | Sal      | sally@example.com  |
    And the following "courses" exist:
      | fullname                 | shortname | category |
      | Classroom Connect Course | CCC       | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | CCC    | editingteacher |
      | trainer | CCC    | teacher        |
      | jimmy   | CCC    | student        |
      | timmy   | CCC    | student        |
      | sammy   | CCC    | student        |
      | sally   | CCC    | student        |
    And the following job assignments exist:
      | user  | manager |
      | jimmy | manager |
      | timmy | manager |
      | sammy | manager |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                          | course  | approvaltype |
      | Classroom Connect | <p>Classroom Connect Tests</p> | CCC     | 0            |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details | capacity |
      | Classroom Connect | event 1 | 10       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish        |
      | event 1      | tomorrow 9am | tomorrow 10am |
    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_approvaloptions[approval_manager]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_self]" "checkbox"
    And I press "Save changes"
    And I log out

  @mod_approval
  Scenario: Student signs up and is instantly booked
    When I log in as "jimmy"
    And I am on "Classroom Connect Course" course homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should not see "Approval" in the ".mod_facetoface__eventinfo" "css_element"
    And I press "Sign-up"
    When I am on "Classroom Connect Course" course homepage
    And I follow "View all events"
    Then I should see "Booked" in the "Upcoming" "table_row"
    When I click on "Go to event" "link" in the "Booked" "table_row"
    Then I should see "Cancel booking" "link_or_button" in the seminar event sidebar "Booked"
