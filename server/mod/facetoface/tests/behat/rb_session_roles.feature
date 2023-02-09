@mod @mod_facetoface @totara
Feature: Use facetoface session roles content restriction in facetoface session report
  In order to use session roles content restriction
  As an admin
  I need to be able to setup session roles content restriction

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry     | Teacher1 | teacher1@example.com |
      | teacher2 | Alex      | Teacher2 | teacher2@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
      | student3 | Sam3      | Student3 | student3@example.com |
      | student4 | Sam4      | Student4 | student4@example.com |
      | student5 | Sam5      | Student5 | student5@example.com |
      | student6 | Sam6      | Student6 | student6@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
      | Course 2 | C2        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      # Course 1
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
      | student5 | C1     | student        |
      | student6 | C1     | student        |
      # Course 2
      | teacher2 | C2     | teacher        |
      | teacher1 | C2     | teacher        |
      | student1 | C2     | student        |
      | student2 | C2     | student        |
      | student3 | C2     | student        |
      | student4 | C2     | student        |
      | student5 | C2     | student        |
      | student6 | C2     | student        |
    And the following "activities" exist:
      | activity   | name           | course | idnumber |
      | facetoface | Seminar 11187A | C1     | S11187A  |
      | facetoface | Seminar 11187B | C2     | S11187B  |

    And I log in as "admin"
    And I navigate to "Shared services settings" node in "Site administration > System information > Configure features"
    And I set the field "id_s__enableglobalrestrictions" to "1"
    And I press "Save changes"

    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I set the field "id_s__facetoface_session_roles_3" to "1"
    And I set the field "id_s__facetoface_session_roles_4" to "1"
    And I press "Save changes"

  @javascript
  Scenario: Setup session roles through report builder content restriction and the teachers can view only their attendees according to session role
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname         | shortname                  | source              | accessmode |
      | Seminar Sign-ups | report_facetoface_sessions | facetoface_sessions | 0          |
      | Seminar Events   | report_facetoface_events   | facetoface_events   | 0          |
      | Seminar Sessions | report_facetoface_summary  | facetoface_summary  | 0          |
    And I navigate to my "Seminar Sign-ups" report
    And I press "Edit this report"
    And I switch to "Columns" tab
    And I add the "Seminar Name" column to the report
    And I switch to "Content" tab
    And I set the field "id_globalrestriction" to "1"
    And I set the field "id_contentenabled_1" to "1"
    And I set the field "id_session_roles_enable" to "1"
    And I set the field "id_role_3" to "1"
    And I press "Save changes"

    And I navigate to my "Seminar Events" report
    And I press "Edit this report"
    And I switch to "Columns" tab
    And I add the "Number of Attendees" column to the report
    And I switch to "Content" tab
    And I set the field "id_globalrestriction" to "1"
    And I set the field "id_contentenabled_1" to "1"
    And I set the field "id_session_roles_enable" to "1"
    And I set the field "id_role_3" to "1"
    And I press "Save changes"

    And I navigate to my "Seminar Sessions" report
    And I press "Edit this report"
    And I switch to "Columns" tab
    And I add the "Number of Attendees" column to the report
    And I switch to "Content" tab
    And I set the field "id_globalrestriction" to "1"
    And I set the field "id_contentenabled_1" to "1"
    And I set the field "id_session_roles_enable" to "1"
    And I set the field "id_role_3" to "1"
    And I press "Save changes"

    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the field "Terry Teacher1" to "1"
    And I press "Save changes"
    And I click on the seminar event action "Attendees" in row "1 January"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam1 Student1, student1@example.com,Sam2 Student2, student2@example.com,Sam3 Student3, student3@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "View all events"

    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 2    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 2    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the field "Alex Teacher2" to "1"
    And I press "Save changes"
    And I click on the seminar event action "Attendees" in row "1 February"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam4 Student4, student4@example.com,Sam5 Student5, student5@example.com,Sam6 Student6, student6@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I log out

    When I log in as "teacher1"
    And I follow "Reports"
    And I follow "Seminar Sessions"
    Then I should see "3" in the "Seminar 11187A" "table_row"
    When I follow "Reports"
    And I follow "Seminar Sign-ups"
    Then I should see "Sam3 Student3"
    And I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I should not see "Sam4 Student4"
    And I should not see "Sam5 Student5"
    And I should not see "Sam6 Student6"
    When I follow "Reports"
    And I follow "Seminar Events"
    Then I should see "3" in the "Seminar 11187A" "table_row"
    And I log out

    When I log in as "teacher2"
    And I follow "Reports"
    And I follow "Seminar Sign-ups"
    Then I should not see "Sam3 Student3"
    And I should not see "Sam1 Student1"
    And I should not see "Sam2 Student2"
    And I should see "Sam4 Student4"
    And I should see "Sam5 Student5"
    And I should see "Sam6 Student6"
    When I follow "Reports"
    And I follow "Seminar Events"
    Then I should see "3" in the "Seminar 11187A" "table_row"
    When I follow "Reports"
    And I follow "Seminar Sessions"
    Then I should see "3" in the "Seminar 11187A" "table_row"
    And I log out

  @javascript
  Scenario: Setup multiple session roles through report builder content restriction and the teachers can view only their attendees according to mulitple session roles
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname         | shortname                  | source              | accessmode |
      | Seminar Sign-ups | report_facetoface_sessions | facetoface_sessions | 0          |
    And I navigate to my "Seminar Sign-ups" report
    And I press "Edit this report"
    And I switch to "Columns" tab
    And I add the "Seminar Name" column to the report
    And I switch to "Content" tab
    And I set the field "id_globalrestriction" to "1"
    And I set the field "id_contentenabled_1" to "1"
    And I set the field "id_session_roles_enable" to "1"
    And I set the field "id_role_3" to "1"
    And I set the field "id_role_4" to "1"
    And I press "Save changes"

    # Course 1 setup
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the field "Terry Teacher1" to "1"
    And I press "Save changes"
    And I click on the seminar event action "Attendees" in row "1 January"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam1 Student1, student1@example.com,Sam2 Student2, student2@example.com,Sam3 Student3, student3@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "View all events"

    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 2    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 2    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the field "Alex Teacher2" to "1"
    And I press "Save changes"
    And I click on the seminar event action "Attendees" in row "1 February"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam4 Student4, student4@example.com,Sam5 Student5, student5@example.com,Sam6 Student6, student6@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"

    # Course 2 setup
    And I am on "Course 2" course homepage
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 2    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 2    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the field "Terry Teacher1" to "1"
    And I press "Save changes"
    And I click on the seminar event action "Attendees" in row "2 January"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam1 Student1, student1@example.com,Sam2 Student2, student2@example.com,Sam3 Student3, student3@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"

    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 2    |
      | timestart[month]   | 2    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 2    |
      | timefinish[month]  | 2    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the field "Alex Teacher2" to "1"
    And I press "Save changes"
    And I click on the seminar event action "Attendees" in row "2 February"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sam4 Student4, student4@example.com,Sam5 Student5, student5@example.com,Sam6 Student6, student6@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I log out

    When I log in as "teacher1"
    And I follow "Reports"
    And I follow "Seminar Sign-ups"
    Then I should see "Sam3 Student3"
    And I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I should not see "Sam4 Student4"
    And I should not see "Sam5 Student5"
    And I should not see "Sam6 Student6"
    And I log out

    When I log in as "teacher2"
    And I follow "Reports"
    And I follow "Seminar Sign-ups"
    Then I should not see "Sam3 Student3"
    And I should not see "Sam1 Student1"
    And I should not see "Sam2 Student2"
    And I should see "Sam4 Student4"
    And I should see "Sam5 Student5"
    And I should see "Sam6 Student6"
