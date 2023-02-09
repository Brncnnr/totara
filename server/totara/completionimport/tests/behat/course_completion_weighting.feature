@core @core_completion @totara_completion_upload @_file_upload
Feature: Make sure the grade column of CSV differs
  @javascript
  Scenario Outline: Course completion displayed correctly when weighted
    Given I am on a totara site
    And the following config values are set as admin:
      | enablecompletion | 1 |
    And the following "courses" exist:
      | fullname    | shortname | idnumber | category | enablecompletion |
      | Course Test | DC        | DC       | 0        | 1                |
    And the following "users" exist:
      | username | firstname | lastname | alternatename | email                |
      | student1 | Student   | First    |               | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | DC     | student        |
    And the following "activities" exist:
      | activity | name | course | idnumber |
      | assign   | Ass1 | DC     | Ass1     |
      | assign   | Ass2 | DC     | Ass2     |
      | assign   | Ass3 | DC     | Ass3     |
      | assign   | Ass4 | DC     | Ass4     |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname    | shortname   | source                | accessmode |
      | CCIH report | ccih_report | course_completion_all | 0          |
    And I log in as "admin"
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Record of Learning: Courses"
    And I press "id_submitgroupstandard_addfilter"
    And I click on "Record of Learning: Courses" "link"
    And I switch to "Columns" tab
    And I add the "Grade" column to the report
    And I press "Save changes"
    And I am on "Course Test" course homepage
    And I navigate to "Gradebook setup" node in "Course administration"
    And the field with xpath "//tr[contains(.,'Ass1')]//input[@type='text']" matches value "25.0"
    And the field with xpath "//tr[contains(.,'Ass2')]//input[@type='text']" matches value "25.0"
    And the field with xpath "//tr[contains(.,'Ass3')]//input[@type='text']" matches value "25.0"
    And the field with xpath "//tr[contains(.,'Ass4')]//input[@type='text']" matches value "25.0"

    # Run cron to make sure the reaggregate flag is set to 0.
    And I run the scheduled task "core\task\completion_regular_task"

    # Import course completion for the dependent course.
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I set the following fields to these values:
      | Upload course CSV Grade format | <grade_unit> |
      | Override current course completions | 0 |
    And I upload "totara/completionimport/tests/behat/fixtures/completion_upload_23158.csv" file to "CSV file to upload" filemanager
    And I click on "Save" "button" in the "#mform1" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "1 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"
    And I log out

    And I log in as "student1"
    When I click on "Record of Learning" in the totara menu
    Then "Course Test" row "Grade" column of "reportbuilder-table" table should contain "-"
    When I click on "1" "link" in the "Course Test" "table_row"
    Then "Course Test" row "Grade at time of completion" column of "reportbuilder-table" table should contain "<grade>"
    And I log out

    And I log in as "admin"
    And I am on "Course Test" course homepage with editing mode on
    And I add a "Quiz" to section "1" and I fill the form with:
      | name | Quiz |
    And I navigate to "Gradebook setup" node in "Course administration"
    And I click on "Edit" "link" in the "Quiz" "table_row"
    And I click on "Edit settings" "link" in the "Quiz" "table_row"
    And I set the field "weightoverride" to "1"
    And I set the field "aggregationcoef2" to "0.00"
    When I press "Save changes"
    Then the field with xpath "//tr[contains(.,'Quiz')]//input[@type='text']" matches value "0.0"
    And the field with xpath "//tr[contains(.,'Ass1')]//input[@type='text']" matches value "25.0"
    And the field with xpath "//tr[contains(.,'Ass2')]//input[@type='text']" matches value "25.0"
    And the field with xpath "//tr[contains(.,'Ass3')]//input[@type='text']" matches value "25.0"
    And the field with xpath "//tr[contains(.,'Ass4')]//input[@type='text']" matches value "25.0"

    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I set the following fields to these values:
      | Upload course CSV Grade format | <grade_unit> |
      | Override current course completions | 1 |
    And I upload "totara/completionimport/tests/behat/fixtures/completion_upload_23158.csv" file to "CSV file to upload" filemanager
    And I click on "Save" "button" in the "#mform1" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "1 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I navigate to my "CCIH report" report
    Then the "ccih_report" table should contain the following:
      | Is current record | Grade at time of completion |
      | No                | <grade>                     |
      | Yes               | <grade>                     |
    And I log out

    And I log in as "student1"
    When I click on "Record of Learning" in the totara menu
    Then "Course Test" row "Grade" column of "reportbuilder-table" table should contain "<grade>"
    When I click on "1" "link" in the "Course Test" "table_row"
    Then "Course Test" row "Grade at time of completion" column of "reportbuilder-table" table should contain "<grade>"
    And I log out

    Examples:
      | grade_unit | grade |
      | Real       | 20.0% |
      | Percentage | 80.0% |
