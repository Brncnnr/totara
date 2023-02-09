@mod @mod_assign @core_grades @javascript
Feature: Assignment activity settings
  As an admin/course creator/editing trainer
  I need to be able to add, edit and clone assignment activities without loosing setting values

  Scenario: Add and edit an assignment with completion criteria requiring a passing grade
    Given the following config values are set as admin:
      | enablecompletion | 1 |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |

    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I wait until the page is ready
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name       | Test Assignment require passing grade             |
      | Description           | -                                                 |
      | Grade to pass         | 50                                                |
      | Completion tracking   | Show activity as complete when conditions are met |
      | Learner must receive a grade to complete this activity | 1                |
      | Require passing grade | 1                                                 |
    And I add a "Assignment" to section "2" and I fill the form with:
      | Assignment name       | Test Assignment without passing grade             |
      | Description           | -                                                 |
      | Grade to pass         | 75                                                |
      | Completion tracking   | Show activity as complete when conditions are met |
      | Learner must receive a grade to complete this activity | 1                |
      | Require passing grade | 0                                                 |
    Then I should see "Test Assignment require passing grade"
    And I should see "Test Assignment without passing grade"

    # Require passing grade value should stay the same
    When I follow "Test Assignment require passing grade"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I expand all fieldsets
    Then the field "Require passing grade" matches value "1"

    When I am on "Course 1" course homepage with editing mode on
    And I follow "Test Assignment without passing grade"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I expand all fieldsets
    Then the field "Require passing grade" matches value "0"
    And I log out
