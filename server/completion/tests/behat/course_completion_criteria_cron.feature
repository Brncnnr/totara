@core @core_completion @totara_completion_upload
Feature: Make sure course completion depending on completion of other course is checked on cron
  In order to ensure that course criteria are marked complete when dependent courses are completed
  I need to run the completion cron task and check that the courses were marked complete

  @javascript
  Scenario: Cron marks criteria complete based on completion of other course
    # Set up some data.
    Given the following "courses" exist:
      | fullname         | shortname | idnumber | category |
      | Dependent course | DC        | DC       | 0        |
      | Resulting course | RC        | RC       | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | First    | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | DC     | student        |
      | student1 | RC     | student        |
    And the following config values are set as admin:
      | enablecompletion | 1 |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable completion tracking | 1 |
    # Configure the dependent course's completion.
    And I am on "Dependent course" course homepage
    And completion tracking is "Enabled" in current course
    And I follow "Course completion"
    And I set the following fields to these values:
      | criteria_self_value | 1 |
    And I press "Save changes"
    # Configure the resulting course's completion.
    And I am on "Resulting course" course homepage
    And completion tracking is "Enabled" in current course
    And I follow "Course completion"
    And I set the following fields to these values:
      | Courses available | Miscellaneous / Dependent course |
    And I press "Save changes"
    # Run cron to make sure the reaggregate flag is set to 0.
    And I run the scheduled task "core\task\completion_regular_task"
    # Import course completion for the dependent course.
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I set the field "Override current course completions" to "1"
    And I upload "completion/tests/fixtures/completion_criteria_course_cron.csv" file to "CSV file to upload" filemanager
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "1 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"
    # Check that the resulting course is NOT marked complete.
    And I am on "Resulting course" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I should see "Not completed" in the "Student First" "table_row"
    # Run the function we're testing.
    And I run the scheduled task "core\task\completion_regular_task"
    # Check that the resulting course IS marked complete.
    And I am on "Resulting course" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I should see "Completed" in the "Student First" "table_row"
