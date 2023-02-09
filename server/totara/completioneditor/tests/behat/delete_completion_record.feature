@totara @totara_completioneditor @javascript
Feature: Course completion records can be deleted
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username | firstname  | lastname  | email               |
      | user001  | FirstName1 | LastName1 | user001@example.com |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion |
      | Course One | course1   | topics | 1                |
    And the following "course enrolments" exist:
      | user    | course  | role    |
      | user001 | course1 | student |
    And I am on "Course One" course homepage with editing mode on
    And I add a "Feedback" to section "1" and I fill the form with:
      | Name                | Test feedback 1           |
      | Description         | Test feedback description |
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Feedback - Test feedback 1" to "1"
    And I press "Save changes"
    When I navigate to "Completion editor" node in "Course administration"
    Then I should see "FirstName1 LastName1"

  Scenario: User can see the dashboard after re-enrolled a course
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I click on "a.rpledit" "css_element"
    And I set the field "rplinput" to "done"
    And I press key "13" in the field "rplinput"
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "FirstName1 LastName1" "table_row"
    And I press "Continue"
    And I navigate to "Completion editor" node in "Course administration"
    And I click on "Edit course completion" "link" in the "FirstName1 LastName1" "table_row"
    And I follow "Delete the current course completion record"
    And I press "Yes"
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Enrol users" "button"
    And I click on "Enrol" "button" in the ".user-enroller-panel .user:first-child" "css_element"
    And I click on "Finish enrolling users" "button"
    And I log out
    # Make sure the user can see the dashboard.
    And I log in as "user001"
