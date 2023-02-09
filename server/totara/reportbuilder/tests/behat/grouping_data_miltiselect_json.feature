@totara @totara_reportbuilder @javascript
Feature: Check that grouping is working as expected when using multi-select custom field json data

  Background:
    Given I am on a totara site
    And the following "custom course fields" exist in "totara_core" plugin:
      | datatype    | shortname  | fullname   | param1                                         |
      | multiselect | format     | format     | [{"option":"SCORM","icon":"","default":"1","delete":0},{"option":"BADGE","icon":"","default":"1","delete":0},{"option":"PDF","icon":"","default":"0","delete":0}] |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |
      | learner4 | Learner   | four     | learner4@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
      | Course 2 | C2        | 1                |
      | Course 3 | C3        | 1                |
      | Course 4 | C4        | 1                |
      | Course 5 | C5        | 1                |
      | Course 6 | C6        | 1                |
      | Course 7 | C7        | 1                |
      | Course 8 | C8        | 1                |
      | Course 9 | C9        | 1                |
      | Course 10 | C10      | 1                |
      | Course 11 | C11      | 1                |
      | Course 12 | C12      | 1                |
      | Course 13 | C13      | 1                |
      | Course 14 | C14      | 1                |
      | Course 15 | C15      | 1                |
      | Course 16 | C16      | 1                |
      | Course 17 | C17      | 1                |
    And the following "course enrolments" exist:
      | user     | course  | role           |
      | learner1 | C1      | student        |
      | learner2 | C1      | student        |
      | learner3 | C2      | student        |
      | learner4 | C2      | student        |
      | learner4 | C3      | student        |
      | learner4 | C4      | student        |
      | learner4 | C5      | student        |
      | learner4 | C6      | student        |
      | learner4 | C7      | student        |
      | learner4 | C8      | student        |
      | learner4 | C9      | student        |
      | learner4 | C10     | student        |
      | learner4 | C11     | student        |
      | learner4 | C12     | student        |
      | learner4 | C13     | student        |
      | learner4 | C14     | student        |
      | learner4 | C15     | student        |
      | learner4 | C16     | student        |
      | learner2 | C17     | student        |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname                             | shortname                                  | source            |
      | Course Completion Report             | report_course_completion_report            |course_completion  |

  Scenario: Grouping is working correctly when having multi-select custom field
    Given I log in as "admin"

    # Add customfield options to courses
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 2" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 3" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 4" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 5" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 6" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 7" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 8" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 9" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 10" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 11" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 12" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 13" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 14" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 15" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 16" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I press "Save and display"

    And I am on "Course 17" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | customfield_format[2]    | 1    |
    And I press "Save and display"

    # Make the report has 2 columns Count unique values of Course Shortname AND format.
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "Course Completion Report" "link"
    And I switch to "Columns" tab
    And I delete the "User's Fullname (linked to profile)" column from the report
    And I delete the "Course Name (linked to course page)" column from the report
    And I delete the "User's Organisation Name(s)" column from the report
    And I delete the "Completion Organisation Name" column from the report
    And I delete the "User's Position Name(s)" column from the report
    And I delete the "Completion Position Name" column from the report
    And I delete the "Completion Status" column from the report
    And I delete the "The completion date" column from the report
    And I add the "Course Shortname" column to the report
    And I set aggregation for the "Course Shortname" column to "Count unique" in the report
    And I add the "format (text)" column to the report
    And I press "Save changes"
    When I follow "View This Report"
    Then I should see "2 records shown"
    And the "reportbuilder-table" table should contain the following:
      | Count unique values of Course Shortname | format               |
      | 16                                      | SCORM, BADGE         |
      | 1                                       | PDF                  |
