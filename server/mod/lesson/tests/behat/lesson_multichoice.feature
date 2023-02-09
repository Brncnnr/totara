@mod @mod_lesson
Feature: In a lesson activity with a multichoice question, learners should progress to the specified jump to page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner1 | C1     | student        |

    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Lesson" to section "1"
    And I set the following fields to these values:
      | Name                                   | Test lesson name            |
      | Description                            | Test lesson description     |
      | Provide option to try a question again | No                          |
      | Maximum number of attempts             | 1                           |
      | Action after correct answer            | Normal - follow lesson path |
    And I press "Save and display"
    And I follow "Add a question page"
    And I set the field "Select a question type" to "Multichoice"

    And I press "Add a question page"
    # Jump to values will be set when we have more pages
    And I set the following fields to these values:
      | Page title           | Question page name     |
      | Page contents        | Question page contents |
      | id_answer_editor_0   | Correct 1              |
      | id_score_0           | 1                      |
      | id_answer_editor_1   | Correct 2              |
      | id_score_1           | 1                      |
      | id_answer_editor_2   | Incorrect 1            |
      | id_score_2           | 0                      |
      | id_answer_editor_3   | Incorrect 2            |
      | id_score_3           | 0                      |
    And I press "Save page"
    And I select "Add a content page" from the "qtype" singleselect
    And I set the following fields to these values:
      | Page title         | First go to page name    |
      | Page contents      | First go to page content |
      | id_answer_editor_0 | Back                     |
      | id_jumpto_0        | Question page name       |
      | id_answer_editor_1 | End                      |
      | id_jumpto_1        | End of lesson            |
    And I press "Save page"
    And I select "Add a content page" from the "qtype" singleselect
    And I set the following fields to these values:
      | Page title         | Second go to page name    |
      | Page contents      | Second go to page content |
      | id_answer_editor_0 | Back                      |
      | id_jumpto_0        | Question page name        |
      | id_answer_editor_1 | End                       |
      | id_jumpto_1        | End of lesson             |
    And I press "Save page"
    And I select "Add a content page" from the "qtype" singleselect
    And I set the following fields to these values:
      | Page title         | Third go to page name    |
      | Page contents      | Third go to page content |
      | id_answer_editor_0 | Back                     |
      | id_jumpto_0        | Question page name       |
      | id_answer_editor_1 | End                      |
      | id_jumpto_1        | End of lesson            |
    And I press "Save page"
    And I select "Add a content page" from the "qtype" singleselect
    And I set the following fields to these values:
      | Page title         | Fourth go to page name    |
      | Page contents      | Fourth go to page content |
      | id_answer_editor_0 | Back                      |
      | id_jumpto_0        | Question page name        |
      | id_answer_editor_1 | End                       |
      | id_jumpto_1        | End of lesson             |
    And I press "Save page"
    Then I should see "Question page name"
    And I should see "First go to page name"
    And I should see "Second go to page name"
    And I should see "Third go to page name"
    And I should see "Fourth go to page name"

    # Now set the question page's correct 'jump to' values
    When I follow "Update page: Question page name"
    And I set the following fields to these values:
      | id_jumpto_0          | First go to page name  |
      | id_jumpto_1          | Second go to page name |
      | id_jumpto_2          | Third go to page name  |
      | id_jumpto_3          | Fourth go to page name |
    And I press "Save page"
    Then I should see "First go to page name" in the "Question page name" "table_row"
    And I should see "Second go to page name" in the "Question page name" "table_row"
    And I should see "Third go to page name" in the "Question page name" "table_row"
    And I should see "Fourth go to page name" in the "Question page name" "table_row"
    And I log out

  @javascript
  Scenario: Check user progress when answering a multi-choice single value question
    When I log in as "learner1"
    And I follow "Course 1"
    And I follow "Test lesson name"
    Then I should see "Question page contents"
    When I click on "Correct 1" "radio"
    And I press "Submit"
    Then I should see "First go to page content"
    When I press "Back"
    Then I should see "Question page contents"
    When I click on "Incorrect 1" "radio"
    And I press "Submit"
    Then I should see "Maximum number of attempts reached - Moving to next page"
    And I log out

  @javascript
  Scenario: Check user progress when answering a multi-choice single value question with multiple attempts
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test lesson name"
    And I navigate to "Edit settings" node in "Lesson administration"
    And I set the following fields to these values:
      | Maximum number of attempts | 4 |
    And I press "Save and return to course"
    And I log out

    When I log in as "learner1"
    And I follow "Course 1"
    And I follow "Test lesson name"
    Then I should see "Question page contents"
    When I click on "Correct 1" "radio"
    And I press "Submit"
    Then I should see "First go to page content"
    When I press "Back"
    Then I should see "Question page contents"
    When I click on "Correct 2" "radio"
    And I press "Submit"
    Then I should see "Second go to page content"
    When I press "Back"
    And I click on "Incorrect 1" "radio"
    And I press "Submit"
    Then I should see "Third go to page content"
    When I press "Back"
    And I click on "Incorrect 2" "radio"
    And I press "Submit"
    Then I should see "Fourth go to page content"
    When I press "Back"
    And I click on "Correct 1" "radio"
    And I press "Submit"
    Then I should see "Maximum number of attempts reached - Moving to next page"
    And I log out

  Scenario: Check user progress when answering a multi-choice question with multiple answers
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test lesson name"
    And I follow "Edit Test lesson name"
    And I follow "Update page: Question page name"
    And I set the following fields to these values:
      | Multiple-answer | 1 |
    And I press "Save page"
    And I log out

    When I log in as "learner1"
    And I follow "Course 1"
    And I follow "Test lesson name"
    Then I should see "Question page contents"
    When I set the following fields to these values:
      | Correct 1 | 1 |
    And I press "Submit"
    Then I should see "Third go to page content"
    When I press "Back"
    Then I should see "Question page contents"
    When I set the following fields to these values:
      | Correct 2   | 1 |
      | Incorrect 1 | 1 |
    And I press "Submit"
    Then I should see "Maximum number of attempts reached - Moving to next page"
    And I log out

  Scenario: Check user progress when answering a multi-choice question with multiple answers and multiple attempts
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test lesson name"
    And I navigate to "Edit settings" node in "Lesson administration"
    And I set the following fields to these values:
      | Maximum number of attempts | 7 |
    And I press "Save and display"
    And I follow "Edit Test lesson name"
    And I follow "Update page: Question page name"
    And I set the following fields to these values:
      | Multiple-answer | 1 |
    And I press "Save page"
    And I log out

    When I log in as "learner1"
    And I follow "Course 1"
    And I follow "Test lesson name"
    Then I should see "Question page contents"
    And I set the following fields to these values:
      | Correct 1 | 1 |
    And I press "Submit"
    Then I should see "Third go to page content"
    When I press "Back"
    Then I should see "Question page contents"
    When I set the following fields to these values:
      | Correct 2 | 1 |
    And I press "Submit"
    Then I should see "Third go to page content"
    When I press "Back"
    And I set the following fields to these values:
      | Incorrect 1 | 1 |
    And I press "Submit"
    Then I should see "Third go to page content"
    When I press "Back"
    And I set the following fields to these values:
      | Correct 1   | 1 |
      | Incorrect 1 | 1 |
    And I press "Submit"
    Then I should see "Third go to page content"
    When I press "Back"
    And I set the following fields to these values:
      | Correct 2  | 1 |
      | Incorrect 2 | 1 |
    And I press "Submit"
    Then I should see "Fourth go to page content"
    When I press "Back"
    And I set the following fields to these values:
      | Correct 1   | 1 |
      | Correct 2   | 1 |
      | Incorrect 1 | 1 |
    And I press "Submit"
    Then I should see "Third go to page content"
    When I press "Back"
    And I set the following fields to these values:
      | Correct 1 | 1 |
      | Correct 2 | 1 |
    And I press "Submit"
    Then I should see "First go to page content"
    When I press "Back"
    And I set the following fields to these values:
      | Correct 1 | 1 |
      | Correct 2 | 1 |
    And I press "Submit"
    Then I should see "Maximum number of attempts reached - Moving to next page"
    And I log out

  Scenario: Check user progress when answering a multi-choice question with multiple answers and no incorrect answer
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test lesson name"
    And I navigate to "Edit settings" node in "Lesson administration"
    And I set the following fields to these values:
      | Maximum number of attempts | 3 |
    And I press "Save and display"
    And I follow "Edit Test lesson name"
    And I follow "Update page: Question page name"
    And I set the following fields to these values:
      | Multiple-answer      | 1 |
      | id_answer_editor_2   |   |
      | id_answer_editor_3   |   |
    And I press "Save page"
    And I log out

    When I log in as "learner1"
    And I follow "Course 1"
    And I follow "Test lesson name"
    Then I should see "Question page contents"
    And I set the following fields to these values:
      | Correct 1 | 1 |
    And I press "Submit"
    # Staying on default (i.e. same page) as there is no incorrect answer with a 'Jump to' defined
    Then I should see "Question page contents"
    When I set the following fields to these values:
      | Correct 2 | 1 |
    And I press "Submit"
    Then I should see "Question page contents"
    When I set the following fields to these values:
      | Correct 1 | 1 |
      | Correct 2 | 1 |
    And I press "Submit"
    Then I should see "First go to page content"
