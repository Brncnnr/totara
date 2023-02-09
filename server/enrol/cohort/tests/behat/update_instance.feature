@core @core_cohort @core_enrol @totara_cohort
Feature: Update Audience sync method in course enrolment method
  In order to update Audience sync method in course enrolment method
  As an admin
  I need to create course, cohort, add cohort to course through
  Audience sync method in in course enrolment method,
  update Audience sync method

  @javascript
  Scenario: Update Audience sync method in course enrolment method
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
      | student4 | Student   | 4        | student4@example.com |
      | student5 | Student   | 5        | student5@example.com |
      | student6 | Student   | 6        | student6@example.com |
    And the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | AUD001   |
      | Audience 2 | AUD002   |
    And the following "cohort members" exist:
      | user     | cohort |
      | student1 | AUD001 |
      | student2 | AUD001 |
      | student3 | AUD001 |
      | student4 | AUD002 |
      | student5 | AUD002 |
      | student6 | AUD002 |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I add "Audience sync" enrolment method with:
      | Audience | Audience 1 |
    And I run all adhoc tasks
    And I wait "1" seconds
    And I am on "Course 1" course homepage
    When I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should see "Student 1"
    And I should see "Student 2"
    And I should see "Student 3"
    And I should not see "Student 4"
    And I should not see "Student 5"
    And I should not see "Student 6"
    And I should see "Learner" in the "Student 1" "table_row"
    And I should see "Learner" in the "Student 2" "table_row"
    And I should see "Learner" in the "Student 3" "table_row"

    # Test before we update "Audience sync" instance with a new "Trainer" role
    And I should not see "Trainer" in the "Student 1" "table_row"
    And I should not see "Trainer" in the "Student 2" "table_row"
    And I should not see "Trainer" in the "Student 3" "table_row"

    And I navigate to "Users > Enrolment methods" in current page administration
    And I click on "Edit" "link" in the "Audience sync" "table_row"
    And I set the field "Assign role" to "Trainer"
    And I press "Save changes"
    And I run all adhoc tasks
    And I wait "1" seconds
    And I am on "Course 1" course homepage
    When I navigate to "Enrolled users" node in "Course administration > Users"

    # Test we updated "Audience sync" instance with the new "Trainer" role
    Then I should see "Trainer" in the "Student 1" "table_row"
    And I should see "Trainer" in the "Student 2" "table_row"
    And I should see "Trainer" in the "Student 3" "table_row"
