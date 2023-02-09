@mod @mod_facetoface @totara @javascript
Feature: Seminar Event Registration Closure
  In order to test user's status code when Face-to-face registration closes
  As a manager
  I need to set up users in various states

  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | manager  | Terry     | Ter      | manager@example.com |
      | sally    | Sally     | Sal      | sally@example.com   |
      | jelly    | Jelly     | Jel      | jelly@example.com   |
      | minny    | Minny     | Min      | minny@example.com   |
      | moxxy    | Moxxy     | Mox      | moxxy@example.com   |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | manager | C1     | editingteacher |
      | sally   | C1     | student        |
      | jelly   | C1     | student        |
      | minny   | C1     | student        |
      | moxxy   | C1     | student        |
    And the following job assignments exist:
      | user  | manager |
      | sally | manager |
      | jelly | manager |
      | minny | manager |
      | moxxy | manager |
    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_approvaloptions[approval_none]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_self]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_manager]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_admin]" "checkbox"
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                     | Test facetoface name        |
      | Description              | Test facetoface description |
      | Use legacy notifications | 1                           |
    And I turn editing mode off
    And I click on "Test facetoface name" "link"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 31   |
      | timefinish[month]  | 12   |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity                        | 4    |
      | registrationtimestart[enabled]  | 1    |
      | registrationtimestart[day]      | 1    |
      | registrationtimestart[month]    | 1    |
      | registrationtimestart[year]     | ## last year ## Y ## |
      | registrationtimestart[hour]     | 05   |
      | registrationtimestart[minute]   | 00   |
      | registrationtimefinish[enabled] | 1    |
      | registrationtimefinish[day]     | 1    |
      | registrationtimefinish[month]   | 1    |
      | registrationtimefinish[year]    | ## next year ## Y ## |
      | registrationtimefinish[hour]    | 09   |
      | registrationtimefinish[minute]  | 00   |
    And I press "Save changes"
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Sally Sal, sally@example.com, Jelly Jel, jelly@example.com, Minny Min, minny@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "Approval required"
    And I set the field "Approve Sally Sal for this event" to "1"
    And I press "Update requests"
    And I log out
    And I log in as "manager"
    And I am on "Course 1" course homepage
    And I click on "Test facetoface name" "link"
    And I click on the seminar event action "Attendees" in row "#1"
    And I follow "Approval required"
    And I set the field "Approve Jelly Jel for this event" to "1"
    And I press "Update requests"
    And I log out
    And I log in as "admin"

  Scenario: Session registration closure denies all pending requests and stops updates
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname              | shortname                    | source              |
      | Global Session Status | report_global_session_status | facetoface_sessions |
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "Global Session Status"
    And I switch to "Columns" tab
    And I add the "Signup status" column to the report
    And I click on "View This Report" "link"
    Then I should not see "Moxxy Mox"
    And I should see "Requested (2step)" in the "Jelly Jel" "table_row"
    And I should see "Requested" in the "Minny Min" "table_row"
    And I should see "Booked" in the "Sally Sal" "table_row"

    When I am on "Course 1" course homepage
    And I click on "View all events" "link"
    And I click on the seminar event action "Edit event" in row "1 January"
    And I set the following fields to these values:
      | registrationtimefinish[day]     | 1    |
      | registrationtimefinish[month]   | 1    |
      | registrationtimefinish[year]    | ## last year ## Y ## |
      | registrationtimefinish[hour]    | 17   |
      | registrationtimefinish[minute]  | 00   |
    And I press "Save changes"
    And I run the scheduled task "\mod_facetoface\task\close_registrations_task"
    And I click on "Reports" in the totara menu
    And I click on "Global Session Status" "link" in the "#myreports_section" "css_element"
    Then I should not see "Moxxy Mox"
    And I should see "Declined" in the "Jelly Jel" "table_row"
    And I should see "Declined" in the "Minny Min" "table_row"
    And I should see "Booked" in the "Sally Sal" "table_row"
    And I run all adhoc tasks

    When I log out
    And I log in as "manager"
    And I am on "Dashboard" page
    And I should see "Seminar event registration closure" in the ".block_totara_alerts" "css_element"
    And I click on "#detailtask2-dialog" "css_element" in the ".block_totara_tasks" "css_element"
    And I click on "Attendees" "link" in the "#detailtask2" "css_element"
    Then I should see "No pending approvals"

    When I am on "Dashboard" page
    And I click on "#detailtask2-dialog" "css_element" in the ".block_totara_tasks" "css_element"
    And I click on "Accept" "button" in the "detailtask2" "totaradialogue"
    And I click on "#detailtask4-dialog" "css_element" in the ".block_totara_tasks" "css_element"
    And I click on "Accept" "button" in the "detailtask4" "totaradialogue"
    And I click on "#detailtask6-dialog" "css_element" in the ".block_totara_tasks" "css_element"
    And I click on "Accept" "button" in the "detailtask6" "totaradialogue"
    And I log out
    And I log in as "admin"
    And I click on "Reports" in the totara menu
    And I click on "Global Session Status" "link" in the "#myreports_section" "css_element"
    Then I should see "Declined" in the "Jelly Jel" "table_row"
    And I should see "Declined" in the "Minny Min" "table_row"
    And I should see "Booked" in the "Sally Sal" "table_row"

  Scenario: Students can not request manager approval for seminar events when the signup window is closed
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | funny    | Funny     | Fun      | funny@example.com   |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 2 | C2        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | funny   | C2     | student        |
    And the following job assignments exist:
      | user  | manager |
      | funny | manager |
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_approvaloptions[approval_none]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_self]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_manager]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_admin]" "checkbox"
    And I press "Save changes"
    And I am on "Course 2" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
    And I turn editing mode off
    And I click on "Test seminar name" "link"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 31   |
      | timefinish[month]  | 12   |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity                        | 4    |
      | registrationtimestart[enabled]  | 1    |
      | registrationtimestart[day]      | 1    |
      | registrationtimestart[month]    | 1    |
      | registrationtimestart[year]     | ## last year ## Y ## |
      | registrationtimestart[hour]     | 05   |
      | registrationtimestart[minute]   | 00   |
      | registrationtimefinish[enabled] | 1    |
      | registrationtimefinish[day]     | 1    |
      | registrationtimefinish[month]   | 1    |
      | registrationtimefinish[year]    | ## last year ## Y ## |
      | registrationtimefinish[hour]    | 09   |
      | registrationtimefinish[minute]  | 00   |
    And I press "Save changes"
    And I am on "Course 2" course homepage
    And I click on "Test seminar name" "link"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I click on "Sign-up Workflow" "link"
    And I click on "#id_approvaloptions_approval_manager" "css_element"
    And I press "Save and display"
    And I log out
    When I log in as "funny"
    And I am on "Test seminar name" seminar homepage
    And I click on the link "Go to event" in row 1
    Then I should see "Sign-up unavailable" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I log out
    And I log in as "admin"

  Scenario: Students can not request manager approval for seminar events when the User Select Manager option is enabled, and the signup window is closed
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | money    | Money     | man      | money@example.com   |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 3 | C3        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | money   | C3     | student        |
    And the following job assignments exist:
      | user  | manager |
      | money | manager |
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_approvaloptions[approval_none]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_self]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_manager]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_admin]" "checkbox"
    And I click on "s__facetoface_managerselect" "checkbox"
    And I press "Save changes"
    And I am on "Course 3" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar2 name        |
      | Description       | Test seminar description |
    And I turn editing mode off
    And I click on "Test seminar2 name" "link"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 31   |
      | timefinish[month]  | 12   |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity                        | 4    |
      | registrationtimestart[enabled]  | 1    |
      | registrationtimestart[day]      | 1    |
      | registrationtimestart[month]    | 1    |
      | registrationtimestart[year]     | ## last year ## Y ## |
      | registrationtimestart[hour]     | 05   |
      | registrationtimestart[minute]   | 00   |
      | registrationtimefinish[enabled] | 1    |
      | registrationtimefinish[day]     | 1    |
      | registrationtimefinish[month]   | 1    |
      | registrationtimefinish[year]    | ## last year ## Y ## |
      | registrationtimefinish[hour]    | 09   |
      | registrationtimefinish[minute]  | 00   |
    And I press "Save changes"
    And I am on "Course 3" course homepage
    And I click on "Test seminar2 name" "link"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I click on "Sign-up Workflow" "link"
    And I click on "#id_approvaloptions_approval_manager" "css_element"
    And I press "Save and display"
    And I log out
    When I log in as "money"
    And I am on "Test seminar2 name" seminar homepage
    And I click on the link "Go to event" in row 1
    Then I should see "Sign-up unavailable" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
