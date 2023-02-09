@totara @totara_reportbuilder @javascript
Feature: Check that course completion reports don't show multiple enrolment types per course when only one has been selected.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | trainer1 | Trainer   | One      | trainer1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |
      | learner4 | Learner   | four     | learner4@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
      | Course 2 | C2        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | trainer1 | C1     | editingteacher |
      | learner1 | C1     | student        |
      | learner2 | C1     | student        |
      | learner3 | C1     | student        |
      | learner4 | C1     | student        |
    And the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | A1       |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | A1     |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname                             | shortname                                  | source            |
      | Course Completion Report             | report_course_completion_report            | course_completion |
      | Course Completion Visibility Report  | report_course_completion_visibility_report | course_completion |

  Scenario: User is enrolled in a second course using a different method
    Given I log in as "admin"
    When I am on "Course 2" course homepage
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I press "Add enrolled audiences"
    And I click on "Audience 1" "link"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I press "Save and display"
    And I run the scheduled task "\enrol_cohort\task\sync_members"
    When I navigate to my "Course Completion Report" report
    And I press "Edit this report"
    And I switch to "Columns" tab
    And I add the "Enrolment Types" column to the report
    And I follow "View This Report"
    Then I should see "Manual enrolments"
    And I should see "Audience sync"
    And I should not see "Audience sync, Manual enrolments"

  Scenario: User is enrolled in the same course using a different method
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I press "Add enrolled audiences"
    And I click on "Audience 1" "link"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I press "Save and display"
    And I run the scheduled task "\enrol_cohort\task\sync_members"
    When I navigate to my "Course Completion Report" report
    And I press "Edit this report"
    And I switch to "Columns" tab
    And I add the "Enrolment Types" column to the report
    And I follow "View This Report"
    Then I should see "Manual enrolments"
    And I should see "Audience sync"
    And I should see "Audience sync, Manual enrolments"

  Scenario: User with RPL completion is reported correctly
    Given I log in as "trainer1"
    And I am on "Course 1" course homepage
    # Set up completion
    And I follow "Course completion"
    And I expand all fieldsets
    And I set the field "Editing Trainer" to "1"
    And I press "Save changes"
    # Mark the learner as complete
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I mark "Learner One" complete by "Editing Trainer" in the course completion report
    And I mark "Learner Two" complete by RPL with "You completed it!" in the course completion report
    And I log out
    And I log in as "admin"
    When I navigate to my "Course Completion Report" report
    And I press "Edit this report"
    And I switch to "Columns" tab
    And I add the "RPL note" column to the report
    When I follow "View This Report"
    Then I should see "" in the "course_completion_rplnote" report column for "Learner One"
    And I should see "You completed it!" in the "course_completion_rplnote" report column for "Learner Two"
    And I log out

  Scenario: Enrolled users can see the course record in the Course Completion report
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner5 | Learner   | five     | learner5@example.com |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Edit settings" "link" in the "Administration" "block"
    When I navigate to "Shared services settings" node in "Site administration > System information > Configure features"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Course 1" "link"
    And I click on "Edit" "link" in the ".course-detail-listing-actions" "css_element"
    And I set the following fields to these values:
      | Visibility | Enrolled users only |
    And I press "Save and display"
    And I navigate to my "Course Completion Visibility Report" report
    And I press "Edit this report"
    And I switch to "Access" tab
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"
    And I log out
    And I log in as "learner4"
    When I navigate to my "Course Completion Visibility Report" report
    And I should see "Course 1" in the "course_courselink" report column for "Learner four"
    And I log out

  Scenario: Not enrolled users can not see the course record in the Course Completion report
    Given I log in as "learner5"
    When I navigate to my "Course Completion Visibility Report" report
    Then I should not see "Course 1"
