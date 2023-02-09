@mod @mod_assign @core_grades @javascript
Feature: Assign activity completion with passing grade
  As an admin/course creator/editing trainer
  I would like to set the the activity completion criteria to have the option to set "require passing grade" like quizzes
  So that there is flexibility within activity completion criteria

  Background:
    Given the following config values are set as admin:
      | grade_decimalpoints | 2 |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | course1  | course1   | 0        | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | One       | Uno      | user1@example.com |
      | user2    | Two       | Duex     | user2@example.com |
      | user3    | Three     | Toru     | user3@example.com |
      | user4    | Four      | Wha      | user4@example.com |
      | user5    | Five      | Cinq     | user5@example.com |
    And the following "course enrolments" exist:
     | user     | course   | role    |
     | user1    | course1  | student |
     | user2    | course1  | student |
     | user3    | course1  | student |
     | user4    | course1  | student |
     | user5    | course1  | student |
    And the following "activities" exist:
      | activity | course  | idnumber | name     | intro |
      | assign   | course1 | assign1  | assign 1 |       |

    And I log in as "admin"

  Scenario: Require grade is on, require passing grade is on, passing grade is 0
    Given I am on "course1" course homepage
    And I follow "assign 1"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I set the following fields to these values:
      | Grade to pass | 0 |
      | Completion tracking | Show activity as complete when conditions are met |
      | Learner must receive a grade to complete this activity | 1 |
      | Require passing grade | 1 |
    When I click on "Save and return to course" "button"
    Then I should see "Updating: Assignment"
    And I should see "'Grade to pass' must be greater than 0 when 'Require passing grade' activity completion setting is enabled"
    And I should see "This assignment does not have a grade to pass set so you cannot use this option"

  Scenario Outline: Test the combination of require passing grade and passing grade
    Given I am on "course1" course homepage
    And I follow "assign 1"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I set the following fields to these values:
      | Grade to pass | <gradetopass> |
      | Completion tracking | Show activity as complete when conditions are met |
      | Learner must receive a grade to complete this activity | 1 |
      | Require passing grade | <completiongrade> |
    When I click on "Save and return to course" "button"
    Then I should not see "Updating: Assignment"

    When I expand "Reports" node
    And I follow "Activity completion"
    Then I should see "One Uno, assign 1: Not completed" in the "One Uno" "table_row"
    And I should see "Two Duex, assign 1: Not completed" in the "Two Duex" "table_row"
    And I should see "Three Toru, assign 1: Not completed" in the "Three Toru" "table_row"
    And I should see "Four Wha, assign 1: Not completed" in the "Four Wha" "table_row"
    And I should see "Five Cinq, assign 1: Not completed" in the "Five Cinq" "table_row"

    # Grade assignments
    And I am on "course1" course homepage

    And I follow "assign 1"
    And I follow "View all submissions"
    And I click on "Grade" "link" in the "Two Duex" "table_row"
    And I set the field "Grade out of 100" to "0"
    And I press "Save changes"
    And I press "Ok"

    And I follow "assign 1"
    And I follow "View all submissions"
    And I click on "Grade" "link" in the "Three Toru" "table_row"
    And I set the field "Grade out of 100" to "25"
    And I press "Save changes"
    And I press "Ok"

    And I follow "assign 1"
    And I follow "View all submissions"
    And I click on "Grade" "link" in the "Four Wha" "table_row"
    And I set the field "Grade out of 100" to "50"
    And I press "Save changes"
    And I press "Ok"

    And I follow "assign 1"
    And I follow "View all submissions"
    And I click on "Grade" "link" in the "Five Cinq" "table_row"
    And I set the field "Grade out of 100" to "100"
    And I press "Save changes"
    And I press "Ok"

    When I am on "course1" course homepage
    And I expand "Reports" node
    And I follow "Activity completion"

    Then I should see "One Uno, assign 1: <user1_see_1st>" in the "One Uno" "table_row"
    And I should see "Two Duex, assign 1: <user2_see_1st>" in the "Two Duex" "table_row"
    And I should see "Three Toru, assign 1: <user3_see_1st>" in the "Three Toru" "table_row"
    And I should see "Four Wha, assign 1: <user4_see_1st>" in the "Four Wha" "table_row"
    And I should see "Five Cinq, assign 1: <user5_see_1st>" in the "Five Cinq" "table_row"

    But I should not see "<user1_notsee_1st>" in the "One Uno" "table_row"
    And I should not see "<user2_notsee_1st>" in the "Two Duex" "table_row"
    And I should not see "<user3_notsee_1st>" in the "Three Toru" "table_row"
    And I should not see "<user4_notsee_1st>" in the "Four Wha" "table_row"
    And I should not see "<user5_notsee_1st>" in the "Five Cinq" "table_row"

    # Re-grade assignments
    And I am on "course1" course homepage

    And I follow "assign 1"
    And I follow "View all submissions"
    And I click on "Grade" "link" in the "One Uno" "table_row"
    And I set the field "Grade out of 100" to "55"
    And I press "Save changes"
    And I press "Ok"

    And I follow "assign 1"
    And I follow "View all submissions"
    And I click on "Grade" "link" in the "Two Duex" "table_row"
    And I set the field "Grade out of 100" to ""
    And I press "Save changes"
    And I press "Ok"

    And I follow "assign 1"
    And I follow "View all submissions"
    And I click on "Grade" "link" in the "Three Toru" "table_row"
    And I set the field "Grade out of 100" to "22"
    And I press "Save changes"
    And I press "Ok"

    And I follow "assign 1"
    And I follow "View all submissions"
    And I click on "Grade" "link" in the "Four Wha" "table_row"
    And I set the field "Grade out of 100" to ""
    And I press "Save changes"
    And I press "Ok"

    And I follow "assign 1"
    And I follow "View all submissions"
    And I click on "Grade" "link" in the "Five Cinq" "table_row"
    And I set the field "Grade out of 100" to "0"
    And I press "Save changes"
    And I press "Ok"

    When I am on "course1" course homepage
    And I expand "Reports" node
    And I follow "Activity completion"

    Then I should see "One Uno, assign 1: <user1_see_2nd>" in the "One Uno" "table_row"
    And I should see "Two Duex, assign 1: <user2_see_2nd>" in the "Two Duex" "table_row"
    And I should see "Three Toru, assign 1: <user3_see_2nd>" in the "Three Toru" "table_row"
    And I should see "Four Wha, assign 1: <user4_see_2nd>" in the "Four Wha" "table_row"
    And I should see "Five Cinq, assign 1: <user5_see_2nd>" in the "Five Cinq" "table_row"

    But I should not see "<user1_notsee_2nd>" in the "One Uno" "table_row"
    And I should not see "<user2_notsee_2nd>" in the "Two Duex" "table_row"
    And I should not see "<user3_notsee_2nd>" in the "Three Toru" "table_row"
    And I should not see "<user4_notsee_2nd>" in the "Four Wha" "table_row"
    And I should not see "<user5_notsee_2nd>" in the "Five Cinq" "table_row"

    Examples:
      | completiongrade | gradetopass | user1_see_1st | user1_notsee_1st | user2_see_1st                          | user2_notsee_1st | user3_see_1st                          | user3_notsee_1st | user4_see_1st                   | user4_notsee_1st | user5_see_1st                   | user5_notsee_1st | user1_see_2nd                   | user1_notsee_2nd | user2_see_2nd | user2_notsee_2nd | user3_see_2nd                          | user3_notsee_2nd | user4_see_2nd | user4_notsee_2nd | user5_see_2nd                          | user5_notsee_2nd |
      | 0               | 42          | Not completed |------------------| Completed (did not achieve pass grade) |------------------| Completed (did not achieve pass grade) |------------------| Completed (achieved pass grade) |------------------| Completed (achieved pass grade) |------------------| Completed (achieved pass grade) |------------------| Not completed |------------------| Completed (did not achieve pass grade) |------------------| Not completed |------------------| Completed (did not achieve pass grade) |------------------|
      | 1               | 42          | Not completed |------------------| Not completed                          |------------------| Not completed                          |------------------| Completed (achieved pass grade) |------------------| Completed (achieved pass grade) |------------------| Completed (achieved pass grade) |------------------| Not completed |------------------| Not completed                          |------------------| Not completed |------------------| Not completed                          |------------------|
      | 0               | 0           | Not completed | pass grade       | Completed                              | pass grade       | Completed                              | pass grade       | Completed                       | pass grade       | Completed                       | pass grade       | Completed                       | pass grade       | Not completed |------------------| Completed                              | pass grade       | Not completed |------------------| Completed                              | pass grade       |
