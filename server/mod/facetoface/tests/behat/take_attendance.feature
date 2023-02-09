@javascript @mod @mod_facetoface @totara
Feature: Take attendance for seminar sessions
  In order to take attendance in a seminar session
  As a teacher
  I need to set attendance status for attendees

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                |
      | teacher1  | Terry3    | Teacher  | teacher@example.com  |
      | student1  | Sam1      | Student1 | student1@example.com |
      | student2  | Sam2      | Student2 | student2@example.com |
      | student3  | Sam3      | Student3 | student3@example.com |
      | student4  | Sam4      | Student4 | student4@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                           | course  |
      | Test seminar name | <p>Test seminar description</p> | C1      |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details |
      | Test seminar name | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start  | finish      | sessiontimezone  |
      | event 1      | -1 day | -30 minutes | Pacific/Auckland |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | student1 | event 1      | booked |
      | student2 | event 1      | booked |
      | student3 | event 1      | booked |
      | student4 | event 1      | booked |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable restricted access | 1 |
    And I log out
    And I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 1                                                 |
    And I click on "Save and display" "button"
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Seminar - Test seminar name | 1 |
    And I press "Save changes"
    And I log out

  Scenario: Set attendance for individual users
    Given I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    And I click on "Take event attendance" "link"
    And I set the field "Sam1 Student1's attendance" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"
    And I switch to "Attendees" tab
    Then the "facetoface_sessions" table should contain the following:
      | Name          | Signup status  |
      | Sam1 Student1 | Fully attended |
      | Sam2 Student2 | Booked         |
      | Sam3 Student3 | Booked         |
      | Sam4 Student4 | Booked         |
    When I navigate to "Course completion" node in "Course administration > Reports"
    And I click on "Sam1 Student1" "link"
    Then I should see "Completed" in the "#criteriastatus" "css_element"
    And I click on "C1" "link"
    When I navigate to "Course completion" node in "Course administration > Reports"
    And I click on "Sam2 Student2" "link"
    Then I should not see "Completed" in the "#criteriastatus" "css_element"
    And I log out

  Scenario: Set attendance in bulk
    Given I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    And I click on "Take event attendance" "link"
    And I click on "Select Sam1 Student1" "checkbox"
    And I click on "Select Sam2 Student2" "checkbox"
    And I set the field "and mark as" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"
    When I navigate to "Course completion" node in "Course administration > Reports"
    And I click on "Sam1 Student1" "link"
    Then I should see "Completed" in the "#criteriastatus" "css_element"
    And I click on "C1" "link"
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I click on "Sam2 Student2" "link"
    Then I should see "Completed" in the "#criteriastatus" "css_element"
    And I click on "C1" "link"
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I click on "Sam3 Student3" "link"
    Then I should not see "Completed" in the "#criteriastatus" "css_element"
    And I log out

  Scenario: Reset attendance for user
    Given I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    And I click on "Take event attendance" "link"
    And I set the field "Sam1 Student1's attendance" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"
    And I switch to "Attendees" tab
    Then the "facetoface_sessions" table should contain the following:
      | Name          | Signup status  |
      | Sam1 Student1 | Fully attended |
      | Sam2 Student2 | Booked         |
    When I switch to "Take attendance" tab
    And I set the field "Sam1 Student1's attendance" to "Partially attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"
    And I switch to "Attendees" tab
    Then the "facetoface_sessions" table should contain the following:
      | Name          | Signup status      |
      | Sam1 Student1 | Partially attended |
      | Sam2 Student2 | Booked             |
    When I switch to "Take attendance" tab
    And I set the field "Sam1 Student1's attendance" to "Not set"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"
    And I switch to "Attendees" tab
    Then the "facetoface_sessions" table should contain the following:
      | Name          | Signup status |
      | Sam1 Student1 | Booked        |
      | Sam2 Student2 | Booked        |
    And I log out
