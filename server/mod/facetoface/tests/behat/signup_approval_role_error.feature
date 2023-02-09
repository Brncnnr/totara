@mod @mod_facetoface @totara
Feature: Seminar Signup Role Approval after creating an event
  In order to signup to classroom connect
  As an admin
  I need to make sure that approval role is setup

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname | lastname | email              |
      | teacher     | Freddy    | Fred     | freddy@example.com |
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
      | jimmy   | CCC    | student        |
      | timmy   | CCC    | student        |
      | sammy   | CCC    | student        |
      | sally   | CCC    | student        |
    And the following "activities" exist:
      | activity   | name              | course | idnumber |
      | facetoface | Classroom Connect | CCC    | S10784   |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details | capacity |
      | Classroom Connect | event 1 | 10       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish        |
      | event 1      | tomorrow 9am | tomorrow 10am |

  @javascript
  Scenario: Learner is trying to sing-up when there is approval role and no trainer appointed.
    Given I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_session_roles[3]" "checkbox"
    And I press "Save changes"
    And I click on "s__facetoface_approvaloptions[approval_role_3]" "checkbox"
    And I press "Save changes"
    And I am on "Classroom Connect" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    And I click on "#id_approvaloptions_approval_role_3" "css_element"
    And I press "Save and display"
    And I log out

    When I log in as "sally"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should see "This seminar requires role approval. There are no users assigned to this role. Please contact the site administrator."
    And I log out

    When I log in as "admin"
    And I am on "Classroom Connect" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Freddy Fred" "checkbox" in the "#id_trainerroles" "css_element"
    And I press "Save changes"
    Then I should see "Booking open"
    And I log out

    When I log in as "sally"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should see "Editing Trainer"

    When I press "Request approval"
    Then I should see "Your request was sent for approval to the following user(s): Freddy Fred"
