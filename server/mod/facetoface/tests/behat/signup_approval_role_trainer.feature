@mod @mod_facetoface @totara @javascript
Feature: Seminar Signup Role Approval Trainer Role
  In order to signup to classroom connect
  As a learner
  I need to request approval from a session role
  And role approver must see tasks block

  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username    | firstname | lastname | email              |
      | trainer     | Benny     | Ben      | benny@example.com  |
      | jimmy       | Jimmy     | Jim      | jimmy@example.com  |
    And the following "courses" exist:
      | fullname                 | shortname | category |
      | Classroom Connect Course | CCC       | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | trainer | CCC    | teacher        |
      | jimmy   | CCC    | student        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                          | course  | approvaltype | approvalrole |
      | Classroom Connect | <p>Classroom Connect Tests</p> | CCC     | 2            | 4            |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details | capacity |
      | Classroom Connect | event 1 | 10       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish        |
      | event 1      | tomorrow 9am | tomorrow 10am |
    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_session_roles[4]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_none]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_self]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_manager]" "checkbox"
    And I press "Save changes"
    And I click on "s__facetoface_approvaloptions[approval_role_4]" "checkbox"
    And I press "Save changes"
    And I am on "Classroom Connect" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Benny Ben" "checkbox" in the "#id_trainerroles" "css_element"
    And I press "Save changes"
    And I log out

  Scenario: Student gets approved through role approval
    When I log in as "jimmy"
    And I am on "Classroom Connect" seminar homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should see "Trainer"
    And I should see "Benny Ben"
    And I press "Request approval"
    And I run all adhoc tasks
    And I log out

    # Staying in same scenario to prevent re-load of data.
    When I log in as "trainer"
    And I am on "Dashboard" page
    Then I should see "View all tasks"
    And I should see "Seminar booking role request: Classroom Connect"
