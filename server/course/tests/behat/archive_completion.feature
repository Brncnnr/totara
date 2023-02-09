@totara @core_course @javascript
Feature: Users can manually archive course completion for themselves and others.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | email                | firstname | lastname |
      | user1       | user1@example.com    | User      | One      |
      | user2       | user2@example.com    | User      | Two      |
      | teacher1    | teacher1@example.com | Teacher   | One      |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | c1        | 1                |
      | Course 2 | c2        | 1                |
    And the following "programs" exist in "totara_program" plugin:
      | fullname  | shortname |
      | Program 1 | P1        |
    And the following "program assignments" exist in "totara_program" plugin:
      | user     | program  |
      | user1    | P1       |
      | user2    | P1       |
      | teacher1 | P1       |
    And the following "activities" exist:
      | activity | name   | intro                    | course | idnumber | completion |
      | label    | label1 | Click to complete course | c1     | label1   | 1          |
      | label    | label2 | Click to complete course | c2     | label2   | 1          |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | c1     | student        |
      | user2    | c1     | student        |
      | teacher1 | c1     | editingteacher |
      | teacher1 | c2     | editingteacher |
    And I log in as "admin"

      # Add Course 2 to Program 1.
    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Program 1" "table_row"
    And I switch to "Content" tab
    And I press "Add"
    And I follow "Miscellaneous"
    And I follow "Course 2"
    And I click on "Ok" "button" in the "Add course set" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I press "Save all changes"

    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Completion requirements" to "Course is complete when ANY of the conditions are met"
    And I set the field "Label - Click to complete course" to "1"
    And I press "Save changes"

    And the following "permission overrides" exist:
      | capability                                   | permission | role           | contextlevel | reference |
      | totara/core:archivemycourseprogress          | Allow      | student        | Course       |        c1 |
      | totara/core:archiveenrolledcourseprogress    | Allow      | editingteacher | Course       |        c1 |
      | totara/core:archiveusercourseprogress        | Allow      | editingteacher | Course       |        c1 |
      | moodle/site:viewreports                      | Allow      | editingteacher | Course       |        c1 |
      | totara/completioneditor:editcoursecompletion | Allow      | editingteacher | Course       |        c1 |
      | totara/core:archivemycourseprogress          | Allow      | student        | Course       |        c2 |
      | totara/core:archiveenrolledcourseprogress    | Allow      | editingteacher | Course       |        c2 |
      | totara/core:archiveusercourseprogress        | Allow      | editingteacher | Course       |        c2 |
      | moodle/site:viewreports                      | Allow      | editingteacher | Course       |        c2 |
      | totara/completioneditor:editcoursecompletion | Allow      | editingteacher | Course       |        c2 |
    And I log out

  Scenario: I can archive my own course progress.
    Given I log in as "user1"
    And I am on "Course 1" course homepage
    And I set the field "Manual completion of Click to complete course" to "1"
    When I navigate to "Reset this course" node in "Course administration"
    And I click on "Continue" "button"
    Then I should see "Your progress in this course and completion state have been archived and reset"
    And the field "Manual completion of Click to complete course" matches value "0"

    # Try resetting progress again.
    When I navigate to "Reset this course" node in "Course administration"
    Then I should see "You have not completed this course"
    And "Continue" "button" should not be visible
    And I click on "Ok" "button"

    # Cannot archive course 2 linked to program
    Given I am on "Course 2" course homepage
    And I set the field "Manual completion of Click to complete course" to "1"
    When I navigate to "Reset this course" node in "Course administration"
    Then I should see "Courses which are a part of a Program or Certification can not be manually archived."
    And "Continue" "button" should not be visible

  Scenario: I can archive course completion for all enrolled users.
    # User One completes course.
    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I set the field "Manual completion of Click to complete course" to "1"
    And I log out

    # User Two completes course.
    When I log in as "user2"
    And I am on "Course 1" course homepage
    And I set the field "Manual completion of Click to complete course" to "1"
    And I log out

    # Teacher One can reset all users progress.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I navigate to "Reset completions" node in "Course administration"
    Then I should see "This action will affect 2 learner(s)"
    And I click on "Continue" "button"
    Then I should see "2 users have had their progress and completion archived and reset in this course"

    # Try resetting progress again.
    When I navigate to "Reset completions" node in "Course administration"
    Then I should see "There are no users that have completed this course"
    And "Continue" "button" should not be visible
    And I click on "Ok" "button"

    # Cannot archive course 2 linked to program
    Given I am on "Course 2" course homepage
    When I navigate to "Reset completions" node in "Course administration"
    Then I should see "Courses which are a part of a Program or Certification can not be manually archived."
    And "Continue" "button" should not be visible

  Scenario: I can archive course progress for a specific user.
    # User One completes course.
    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I set the field "Manual completion of Click to complete course" to "1"
    And I log out

    # Teacher One can reset User One's progress.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I follow "Reset course completion for User One"
    And I click on "Continue" "button"
    Then I should see "Progress and completion state have been archived and reset for User One in this course."

    # Try resetting progress again.
    When I follow "Reset course completion for User One"
    Then I should see "User One"
    And I should see "has not completed this course"
    And "Continue" "button" should not be visible
    And I click on "Ok" "button"
