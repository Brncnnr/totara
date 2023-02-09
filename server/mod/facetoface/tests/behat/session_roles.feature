@mod @mod_facetoface @totara @javascript
Feature: Use facetoface session roles
  In order to use session roles
  As a teacher
  I need to be able to setup session roles and see them in report

  Scenario: Setup and view facetoface session roles
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | middlename | email                |
      | teacher1 | Terry1    | Teacher1 | Midter1    | teacher1@example.com |
      | student1 | Sam1      | Student1 | Midsam1    | student1@example.com |
      | student2 | Sam2      | Student2 |            | student2@example.com |
      | student3 | Sam3      | Student3 |            | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name                 | course  |
      | Test facetoface name | C1      |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface            | details |
      | Test facetoface name  | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start     | finish           |
      | event 1      | tomorrow  | tomorrow +1 hour |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user      | eventdetails |
      | teacher1  | event 1      |
      | student1  | event 1      |
      | student2  | event 1      |
      | student3  | event 1      |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname     | shortname           | source             | accessmode |
      | F2F sessions | report_f2f_sessions | facetoface_summary | 0          |

    And I log in as "admin"
    And I set the following administration settings values:
      | fullnamedisplay           | lastname middlename firstname |
      | alternativefullnameformat | lastname middlename firstname |
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I set the field "id_s__facetoface_session_roles_5" to "1"
    And I press "Save changes"

    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "F2F sessions"
    And I switch to "Columns" tab
    And I add the "Event Learner" column to the report
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I set the field "Student1 Midsam1 Sam1" to "1"
    And I set the field "Student3 Sam3" to "1"
    And I press "Save changes"

    When I follow "Reports"
    And I follow "F2F sessions"
    Then I should see "Student3  Sam3" in the "Test facetoface name" "table_row"
    And I should see "Student1 Midsam1 Sam1" in the "Test facetoface name" "table_row"
    And I should not see "Student2" in the "Test facetoface name" "table_row"

  @mod_facetoface_notification
  Scenario: Add and remove facetoface session roles, including overlapping roles (Learner + Trainer)
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | middlename | email                |
      | teacher1 | Terry1    | Teacher1 | Midter1    | teacher1@example.com |
      | student1 | Sam1      | Student1 | Midsam1    | student1@example.com |
      | student2 | Sam2      | Student2 |            | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C1     | student        |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name      | course  |
      | seminar 1 | C1      |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | seminar 1  | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start        | finish                  |
      | event 1      | now +2 days  | now +2 days +60 minutes |
      | event 1      | now +3 days  | now +3 days +60 minutes |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user      | eventdetails |
      | teacher1  | event 1      |
      | student1  | event 1      |
      | student2  | event 1      |

    When I log in as "admin"
    And I set the following administration settings values:
      | fullnamedisplay           | lastname middlename firstname |
      | alternativefullnameformat | lastname middlename firstname |
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I set the field "id_s__facetoface_session_roles_3" to "1"
    And I set the field "id_s__facetoface_session_roles_5" to "1"
    And I press "Save changes"

    # Add overlapping roles
    And I am on "Course 1" course homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I set the field "Teacher1 Midter1 Terry1" to "1"
    And I set the field with xpath "(//label[contains(.,'Teacher1')])[2]/preceding::input[1]" to "1"
    And I set the field "Student1 Midsam1 Sam1" to "1"
    And I set the field "Student2 Sam2" to "1"
    And I press "Save changes"
    When I click on the seminar event action "Attendees" in row "#1"
    And I follow "Event details"
    Then I should see "Teacher1 Midter1 Terry1" exactly "2" times
    And I should see "Student1 Midsam1 Sam1"
    And I should see "Student2 Sam2"

    # Remove one overlapping role
    When I follow "seminar 1"
    And I click on the seminar event action "Edit event" in row "#1"
    And I set the field with xpath "(//label[contains(.,'Teacher1')])[2]/preceding::input[1]" to "0"
    And I press "Save changes"
    When I click on the seminar event action "Attendees" in row "#1"
    And I follow "Event details"
    Then I should see "Teacher1 Midter1 Terry1" exactly "1" times
    And I should see "Student1 Midsam1 Sam1"
    And I should see "Student2 Sam2"

    # Remove other overlapping role, and trigger a notification (TL-21049)
    When I follow "seminar 1"
    And I click on the seminar event action "Edit event" in row "#1"
    And I follow "Edit session"
    And I fill seminar session with relative date in form data:
      | timestart[day]    | +1               |
      | timefinish[day]   | +1               |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the field "Teacher1 Midter1 Terry1" to "0"
    And I press "Save changes"
    When I click on the seminar event action "Attendees" in row "#1"
    And I follow "Event details"
    Then I should not see "Teacher1 Midter1 Terry1"
    And I should see "Student1 Midsam1 Sam1"
    And I should see "Student2 Sam2"
