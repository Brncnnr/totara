@mod @mod_facetoface @totara
Feature: Delete a course with a seminar
  In order to delete a course
  As a teacher
  I need the seminar to not do silly things with completion during purging of course.

  @javascript
  Scenario: Delete a course with one seminar activity
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | course |
      | Test seminar name | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface         | details |
      | Test seminar name  | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                    | finish                   |
      | event 1      | 1 Jan next year 11:00:00 | 1 jan next year 12:00:00 |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails |
      | student1 | event 1      |
      | student2 | event 1      |

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Completion tracking | Show activity as complete when conditions are met |
      | Require grade       | 1                                                 |
    And I press "Save and display"
    And I log out

    And I log in as "admin"
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    And I should see "Course 1" in the "#course-listing" "css_element"
    And I click on "delete" action for "Course 1" in management course listing
    And I should see "Delete C1"
    And I should see "Course 1 (C1)"

    When I press "Delete"
    Then I should see "Deleting C1"
    And I should see "C1 has been completely deleted"
    And I press "Continue"
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    And I should not see "Course 1" in the "#course-listing" "css_element"

