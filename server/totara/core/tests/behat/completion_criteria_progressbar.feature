@totara @totara_core @totara_courseprogressbar
Feature: Test progress bar percentange is displayed according to criteria completion for a course
  In order to test the progress bar for completion of a course
  As admin
  I need to set criteria for course completion, enrol users and make them complete some of the criteria and
  see the record of learning report

  @javascript
  Scenario: course completion criteria
    Given I am on a totara site
    # Create users, courses and enrolments.
    And the following "users" exist:
    | username | firstname | lastname | email          |
    | user1    | user      | one      | u1@example.com |
    | user2    | user      | two      | u2@example.com |
    | user3    | user      | three    | u3@example.com |
    And the following "courses" exist:
    | fullname | shortname | summary          | format | enablecompletion |
    | Course 1 | C1        | Course summary 1 | topics | 1                |
    And the following "course enrolments" exist:
    | user  | course | role    |
    | user1 | C1     | student |
    | user2 | C1     | student |
    | user3 | C1     | student |
#     Create Courses 1 Assignment 1.
    Then I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I wait until the page is ready
    And I add a "Certificate" to section "1" and I fill the form with:
    | Name                | Certificate 1 |
    | Completion tracking | Show activity as complete when conditions are met |
    | Require view        | 1                                                 |
    And I add a "Certificate" to section "1" and I fill the form with:
      | Name                | Certificate 2 |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require view        | 1                                                 |
    # Set completion for Course 1 to Assignment 1 AND Manual self completion (will delete and remove self completion).
    Then I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Activity completion" "link"
    And I click on "Certificate 1" "checkbox"
    And I click on "Certificate 2" "checkbox"
    And I press "Save changes"

    # Complete Certificate 1 as user 1 but don't access Certificate 2.
    Then I log out
    And I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Certificate 1"
    # Confirm the status of the courses for user1.
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 50%      |
    # Complete Certificate 2 as user 2 but don't access Certificate 1.
    Then I log out
    And I log in as "user2"
    And I am on "Course 1" course homepage
    And I follow "Certificate 2"
    # Confirm the status of the courses for user1.
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 50%      |
    And I log out
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname           | shortname                 | source    |
      | Record of Learning | report_record_of_learning | dp_course |
    And I log in as "admin"
    And I navigate to my "Record of Learning" report
    And I press "Edit this report"
    And I switch to "Columns" tab
    And I delete the "Course Name (linked to course page)" column from the report
    And I delete the "Plan name (linked to plan page)" column from the report
    And I delete the "Plan course due date" column from the report
    And I delete the "Progress (and approval status)" column from the report
    And I add the "User's Fullname" column to the report
    And I add the "Course Name" column to the report
    And I add the "Progress (%)" column to the report
    And I add the "Progress" column to the report
    When I follow "View This Report"
    Then the "reportbuilder-table" table should contain the following:
      | User's Fullname | Course Name | Progress  | Progress  |
      | user one        | Course 1    | 50%       | 50%       |
      | user two        | Course 1    | 50%       | 50%       |
    And I log out
