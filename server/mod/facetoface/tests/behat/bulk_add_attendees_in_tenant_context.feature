@mod @mod_facetoface @mod_facetoface_attendees_add @totara @javascript @_file_upload @tenant @totara_tenant
Feature: Add seminar attendees in tenant context via bulk options
  In order to test tenant context in the bulk add attendees
  As admin
  I need to create an event, tenants, tenant participants and upload attendees through the bulk add attendees options.

  Background:
    Given I am on a totara site
    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name          | idnumber |
      | First Tenant  | ten1     |
      | Second Tenant | ten2     |
    And the following "users" exist:
      | username          | firstname | lastname    | tenantmember | tenantparticipant | tenantusermanager |
      | student1          | Student   | One         | ten1         |                   |                   |
      | student2          | Student   | Two         |              | ten1              |                   |
      | student3          | Student   | Three       | ten2         |                   |                   |
      | student4          | Student   | Four        |              |                   |                   |
      | student5          | Student   | Five        |              |                   |                   |
      | student6          | Student   | Six         |              | ten2              |                   |
      | usermanager       | User      | Manager     | ten1         |                   | ten1              |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
      | Course 2 | C2        | ten1     | 1                |
      | Course 3 | C3        | ten2     | 1                |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | course |
      | No tenant Seminar | C1     |
      | Tenant 1 Seminar  | C2     |
      | Tenant 2 Seminar  | C3     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details |
      | No tenant Seminar | event 1 |
      | Tenant 1 Seminar  | event 2 |
      | Tenant 2 Seminar  | event 3 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                   | finish                  |
      | event 1      | now +2 days             | now +2 days +60 minutes |
      | event 2      | now +3 days             | now +3 days +60 minutes |
      | event 3      | now +4 days             | now +4 days +60 minutes |

  Scenario: Check only tenant participants can be added as attendees via CSV in tenants context seminars
    Given I log in as "admin"
    # Course 1 not in tenant context. So all users allowed
    And I am on "Course 1" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_in_tenant_context.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Automatic"
    When I press "Continue"
    And I press "Confirm"
    Then I should see "Student One"
    And I should see "Student Two"
    And I should see "Student Three"
    And I should see "Student Four"
    And I should see "Student Six"

    # Course 2 is in tenant1 context. Only participants of Tenant1 allowed
    And I am on "Course 2" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_in_tenant_context.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Automatic"
    When I press "Continue"
    And I press "Confirm"
    Then I should see "3 problem(s) encountered during import"
    When I click on "View results" "link"
    Then I should see "The user is not a tenant participant" in the "Student Three" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Four" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Six" "table_row"

    # Course 3 is in tenant2 context. Only participants of Tenant2 allowed
    And I am on "Course 3" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_in_tenant_context.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Automatic"
    When I press "Continue"
    And I press "Confirm"
    Then I should see "3 problem(s) encountered during import"
    When I click on "View results" "link"
    Then I should see "The user is not a tenant participant" in the "Student One" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Two" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Four" "table_row"

  Scenario: Check added as attendees via CSV when tentant isolation is enabled
    Given tenant support is enabled with full tenant isolation
    And I log in as "admin"
    # Course 1 not in tenant context. No tenant members are allowed
    And I am on "Course 1" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_in_tenant_context.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Automatic"
    When I press "Continue"
    And I press "Confirm"
    Then I should see "2 problem(s) encountered during import"
    When I click on "View results" "link"
    Then I should see "Tenant isolation enabled. Members cannot attend events outside their tenant domain" in the "Student One" "table_row"
    And I should see "Tenant isolation enabled. Members cannot attend events outside their tenant domain" in the "Student Three" "table_row"

    # Course 2 is in tenant1 context. Only participants of Tenant1 allowed
    And I am on "Course 2" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_in_tenant_context.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Automatic"
    When I press "Continue"
    And I press "Confirm"
    Then I should see "3 problem(s) encountered during import"
    When I click on "View results" "link"
    Then I should see "The user is not a tenant participant" in the "Student Three" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Four" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Six" "table_row"

    # Course 3 is in tenant2 context. Only participants of Tenant2 allowed
    And I am on "Course 3" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_in_tenant_context.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Automatic"
    When I press "Continue"
    And I press "Confirm"
    Then I should see "3 problem(s) encountered during import"
    When I click on "View results" "link"
    Then I should see "The user is not a tenant participant" in the "Student One" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Two" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Four" "table_row"

  Scenario: Check only tenant participants can be added as attendees via List of IDs in tenants context seminars
    Given I log in as "admin"
  # Course 1 not in tenant context. So all users allowed
    And I am on "Course 1" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via list of IDs"
    And I set the field "idfield" to "Username"
    And I set the field "csvinput" to "student1,student2,student3,student4,student6"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Student One"
    And I should see "Student Two"
    And I should see "Student Three"
    And I should see "Student Four"
    And I should see "Student Six"

    # Course 2 is in tenant1 context. Only participants of Tenant1 allowed
    And I am on "Course 2" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via list of IDs"
    And I set the field "idfield" to "Username"
    And I set the field "csvinput" to "student1,student2,student3,student4,student6"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "3 problem(s) encountered during import"
    When I click on "View results" "link"
    Then I should see "The user is not a tenant participant" in the "Student Three" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Four" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Six" "table_row"

    # Course 3 is in tenant2 context. Only participants of Tenant2 allowed
    And I am on "Course 3" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via list of IDs"
    And I set the field "idfield" to "Username"
    And I set the field "csvinput" to "student1,student2,student3,student4,student6"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "3 problem(s) encountered during import"
    When I click on "View results" "link"
    Then I should see "The user is not a tenant participant" in the "Student One" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Two" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Four" "table_row"

  Scenario: Check added as attendees via List of IDs when tentant isolation is enabled
    Given tenant support is enabled with full tenant isolation
    And I log in as "admin"
    # Course 1 not in tenant context. No tenant members are allowed
    And I am on "Course 1" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via list of IDs"
    And I set the field "idfield" to "Username"
    And I set the field "csvinput" to "student1,student2,student3,student4,student6"
    When I press "Continue"
    And I press "Confirm"
    Then I should see "2 problem(s) encountered during import"
    When I click on "View results" "link"
    Then I should see "Tenant isolation enabled. Members cannot attend events outside their tenant domain" in the "Student One" "table_row"
    And I should see "Tenant isolation enabled. Members cannot attend events outside their tenant domain" in the "Student Three" "table_row"

    # Course 2 is in tenant1 context. Only participants of Tenant1 allowed
    And I am on "Course 2" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via list of IDs"
    And I set the field "idfield" to "Username"
    And I set the field "csvinput" to "student1,student2,student3,student4,student6"
    When I press "Continue"
    And I press "Confirm"
    Then I should see "3 problem(s) encountered during import"
    When I click on "View results" "link"
    Then I should see "The user is not a tenant participant" in the "Student Three" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Four" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Six" "table_row"

    # Course 3 is in tenant2 context. Only participants of Tenant2 allowed
    And I am on "Course 3" course homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via list of IDs"
    And I set the field "idfield" to "Username"
    And I set the field "csvinput" to "student1,student2,student3,student4,student6"
    When I press "Continue"
    And I press "Confirm"
    Then I should see "3 problem(s) encountered during import"
    When I click on "View results" "link"
    Then I should see "The user is not a tenant participant" in the "Student One" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Two" "table_row"
    And I should see "The user is not a tenant participant" in the "Student Four" "table_row"
