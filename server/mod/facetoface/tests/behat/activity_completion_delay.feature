@mod @mod_facetoface @javascript
Feature: Seminar activity completion with a delay until after the end of the event
  As an admin/course creator/editing trainer
  I would like to set the the activity completion criteria to have the option to delay activtity completion
  In order to prevent accidental activity and course completions during the attendance and grading process

  Background:
    Given the following "courses" exist:
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
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name      | course  |
      | seminar 1 | course1 |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | seminar 1  | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start    | finish               |
      | event 1      | -48 hour | -46 hour |
      | event 1      | -24 hour | -22 hour |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user  | eventdetails |
      | user1 | event 1      |
      | user2 | event 1      |
      | user3 | event 1      |
      | user4 | event 1      |
      | user5 | event 1      |
    And I log in as "admin"

  Scenario: Check form validation for the 'Require event over for' setting.
    Given I am on "seminar 1" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Completion tracking           | Show activity as complete when conditions are met |
      | completiondelayenabled        | 1                                                 |
      | completiondelay               | 1.5                                               |
    And I click on "Save and display" "button"
    Then I should see "\"Require event over for\" must be a whole number between 0 and 999 days."
    When I set the following fields to these values:
      | completiondelay               | 9999                                              |
    And I click on "Save and display" "button"
    Then I should see "\"Require event over for\" must be a whole number between 0 and 999 days."
    When I set the following fields to these values:
      | completiondelay               | -2                                                |
    And I click on "Save and display" "button"
    Then I should see "\"Require event over for\" must be a whole number between 0 and 999 days."
    When I set the following fields to these values:
      | completiondelay               | 0                                                 |
    And I click on "Save and display" "button"
    Then I should not see "\"Require event over for\" must be a whole number between 0 and 999 days."
    And ".mod_facetoface__event-dashboard" "css_element" should exist
    When I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | completiondelay               | 0                                                 |
    And I click on "Save and display" "button"
    Then I should not see "\"Require event over for\" must be a whole number between 0 and 999 days."
    And ".mod_facetoface__event-dashboard" "css_element" should exist

  Scenario: Require attendance state for activity completion, delay completion.
    Given I am on "seminar 1" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Completion tracking           | Show activity as complete when conditions are met |
      | Require grade                 | No                                                |
      | completionstatusrequired[90]  | 1                                                 |
      | completionstatusrequired[100] | 1                                                 |
      | completiondelayenabled        | 1                                                 |
      | completiondelay               | 1                                                 |
    And I click on "Save and display" "button"
    And I click on "Take event attendance" "link"
    And I set the following fields to these values:
      | One Uno's attendance    | Fully attended     |
      | Two Duex's attendance   | Partially attended |
      | Three Toru's attendance | Unable to attend   |
      | Four Wha's attendance   | No show            |
    When I click on "Save attendance" "button"
    Then I should see "Successfully updated attendance" in the ".alert-success" "css_element"

    When I navigate to "Activity completion" node in "Course administration > Reports"
    Then I should see "One Uno, seminar 1: Not completed" in the "One Uno" "table_row"
    And I should see "Two Duex, seminar 1: Not completed" in the "Two Duex" "table_row"
    And I should see "Three Toru, seminar 1: Not completed" in the "Three Toru" "table_row"
    And I should see "Four Wha, seminar 1: Not completed" in the "Four Wha" "table_row"
    And I should see "Five Cinq, seminar 1: Not completed" in the "Five Cinq" "table_row"

    When I am on "seminar 1" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | completiondelay               | 0 |
    And I click on "Save and display" "button"
    And I run the scheduled task "mod_facetoface\task\activity_completion_task"
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then I should see "One Uno, seminar 1: Completed" in the "One Uno" "table_row"
    And I should see "Two Duex, seminar 1: Completed" in the "Two Duex" "table_row"
    And I should see "Three Toru, seminar 1: Not completed" in the "Three Toru" "table_row"
    And I should see "Four Wha, seminar 1: Not completed" in the "Four Wha" "table_row"
    And I should see "Five Cinq, seminar 1: Not completed" in the "Five Cinq" "table_row"

    When I am on "seminar 1" seminar homepage
    And I click on "Take event attendance" "link"
    And I set the following fields to these values:
      | Two Duex's attendance  | Unable to attend   |
      | Four Wha's attendance  | Partially attended |
      | Five Cinq's attendance | Fully attended     |
    When I click on "Save attendance" "button"
    Then I should see "Successfully updated attendance" in the ".alert-success" "css_element"
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then I should see "One Uno, seminar 1: Completed" in the "One Uno" "table_row"
    And I should see "Two Duex, seminar 1: Not completed" in the "Two Duex" "table_row"
    And I should see "Three Toru, seminar 1: Not completed" in the "Three Toru" "table_row"
    And I should see "Four Wha, seminar 1: Completed" in the "Four Wha" "table_row"
    And I should see "Five Cinq, seminar 1: Completed" in the "Five Cinq" "table_row"

  Scenario: Require grade for activity completion, delay completion.
    Given I am on "seminar 1" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Passing grade                 | 42                                                |
      | Manual event grading          | 1                                                 |
      | Completion tracking           | Show activity as complete when conditions are met |
      | Require grade                 | Yes, any grade (0–100)                            |
      | completionstatusrequired[90]  | 0                                                 |
      | completionstatusrequired[100] | 0                                                 |
      | completiondelayenabled        | 1                                                 |
      | completiondelay               | 1                                                 |
    And I click on "Save and display" "button"
    And I click on "Take event attendance" "link"
    And I set the following fields to these values:
      | One Uno's attendance     | Fully attended     |
      | One Uno's event grade    | 100                |
      | Two Duex's attendance    | Partially attended |
      | Two Duex's event grade   | 42                 |
      | Three Toru's attendance  | Unable to attend   |
      | Three Toru's event grade | 30                 |
      | Four Wha's attendance    | No show            |
      | Four Wha's event grade   | 0                  |
    When I click on "Save attendance" "button"
    Then I should see "Successfully updated attendance" in the ".alert-success" "css_element"

    When I navigate to "Activity completion" node in "Course administration > Reports"
    Then I should see "One Uno, seminar 1: Not completed" in the "One Uno" "table_row"
    And I should see "Two Duex, seminar 1: Not completed" in the "Two Duex" "table_row"
    And I should see "Three Toru, seminar 1: Not completed" in the "Three Toru" "table_row"
    And I should see "Four Wha, seminar 1: Not completed" in the "Four Wha" "table_row"
    And I should see "Five Cinq, seminar 1: Not completed" in the "Five Cinq" "table_row"

    When I am on "seminar 1" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | completiondelay               | 0 |
    And I click on "Save and display" "button"
    And I run the scheduled task "mod_facetoface\task\activity_completion_task"
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then I should see "One Uno, seminar 1: Completed (achieved pass grade)" in the "One Uno" "table_row"
    And I should see "Two Duex, seminar 1: Completed (achieved pass grade)" in the "Two Duex" "table_row"
    And I should see "Three Toru, seminar 1: Completed (did not achieve pass grade)" in the "Three Toru" "table_row"
    And I should see "Four Wha, seminar 1: Completed (did not achieve pass grade)" in the "Four Wha" "table_row"
    And I should see "Five Cinq, seminar 1: Not completed" in the "Five Cinq" "table_row"

    When I am on "seminar 1" seminar homepage
    And I click on "Take event attendance" "link"
    And I set the following fields to these values:
      | Two Duex's event grade  | 30  |
      | Four Wha's event grade  | 64  |
      | Five Cinq's event grade | 100 |
    When I click on "Save attendance" "button"
    Then I should see "Successfully updated attendance" in the ".alert-success" "css_element"
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then I should see "One Uno, seminar 1: Completed (achieved pass grade)" in the "One Uno" "table_row"
    And I should see "Two Duex, seminar 1: Completed (did not achieve pass grade)" in the "Two Duex" "table_row"
    And I should see "Three Toru, seminar 1: Completed (did not achieve pass grade)" in the "Three Toru" "table_row"
    And I should see "Four Wha, seminar 1: Completed (achieved pass grade)" in the "Four Wha" "table_row"
    And I should see "Five Cinq, seminar 1: Completed (achieved pass grade)" in the "Five Cinq" "table_row"

  Scenario: Require attendance and grade for activity completion, and activity completion for course completion.
    Given I am on "seminar 1" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Passing grade                 | 42                                                |
      | Manual event grading          | 1                                                 |
      | Completion tracking           | Show activity as complete when conditions are met |
      | Require grade                 | Yes, any grade (0–100)                            |
      | completionstatusrequired[90]  | 1                                                 |
      | completionstatusrequired[100] | 1                                                 |
      | completiondelayenabled        | 1                                                 |
      | completiondelay               | 1                                                 |
    And I click on "Save and return to course" "button"
    And I follow "Course completion"
    And I set the following fields to these values:
      | Seminar - seminar 1 | 1 |
    And I click on "Save changes" "button"
    Then I am on "seminar 1" seminar homepage
    And I click on "Take event attendance" "link"
    And I set the following fields to these values:
      | One Uno's attendance    | Fully attended     |
      | Two Duex's attendance   | Partially attended |
      | Three Toru's attendance | Unable to attend   |
      | Four Wha's attendance   | No show            |
    When I click on "Save attendance" "button"
    Then I should see "Successfully updated attendance" in the ".alert-success" "css_element"

    When I run the scheduled task "mod_facetoface\task\activity_completion_task"
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then I should see "One Uno, seminar 1: Not completed" in the "One Uno" "table_row"
    And I should see "Two Duex, seminar 1: Not completed" in the "Two Duex" "table_row"
    And I should see "Three Toru, seminar 1: Not completed" in the "Three Toru" "table_row"
    And I should see "Four Wha, seminar 1: Not completed" in the "Four Wha" "table_row"
    And I should see "Five Cinq, seminar 1: Not completed" in the "Five Cinq" "table_row"
    When I navigate to "Course completion" node in "Course administration > Reports"
    Then I should see "Not completed" in the "One Uno" "table_row"
    But I should not see "Completed" in the "One Uno" "table_row"
    And I should see "Not completed" in the "Two Duex" "table_row"
    But I should not see "Completed" in the "Two Duex" "table_row"
    And I should see "Not completed" in the "Three Toru" "table_row"
    But I should not see "Completed" in the "Three Toru" "table_row"
    And I should see "Not completed" in the "Four Wha" "table_row"
    But I should not see "Completed" in the "Four Wha" "table_row"
    And I should see "Not completed" in the "Five Cinq" "table_row"
    But I should not see "Completed" in the "Five Cinq" "table_row"

    When I am on "seminar 1" seminar homepage
    And I click on "Take event attendance" "link"
    And I set the following fields to these values:
      | One Uno's event grade    | 100 |
      | Two Duex's event grade   | 42  |
      | Three Toru's event grade | 30  |
      | Four Wha's event grade   | 0   |
    When I click on "Save attendance" "button"
    Then I should see "Successfully updated attendance" in the ".alert-success" "css_element"

    When I run the scheduled task "mod_facetoface\task\activity_completion_task"
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then I should see "One Uno, seminar 1: Not completed" in the "One Uno" "table_row"
    And I should see "Two Duex, seminar 1: Not completed" in the "Two Duex" "table_row"
    And I should see "Three Toru, seminar 1: Not completed" in the "Three Toru" "table_row"
    And I should see "Four Wha, seminar 1: Not completed" in the "Four Wha" "table_row"
    And I should see "Five Cinq, seminar 1: Not completed" in the "Five Cinq" "table_row"
    When I navigate to "Course completion" node in "Course administration > Reports"
    Then I should see "Not completed" in the "One Uno" "table_row"
    But I should not see "Completed" in the "One Uno" "table_row"
    And I should see "Not completed" in the "Two Duex" "table_row"
    But I should not see "Completed" in the "Two Duex" "table_row"
    And I should see "Not completed" in the "Three Toru" "table_row"
    But I should not see "Completed" in the "Three Toru" "table_row"
    And I should see "Not completed" in the "Four Wha" "table_row"
    But I should not see "Completed" in the "Four Wha" "table_row"
    And I should see "Not completed" in the "Five Cinq" "table_row"
    But I should not see "Completed" in the "Five Cinq" "table_row"

    When I am on "seminar 1" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | completiondelay               | 0 |
    And I click on "Save and display" "button"
    And I run the scheduled task "mod_facetoface\task\activity_completion_task"
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then I should see "One Uno, seminar 1: Completed (achieved pass grade)" in the "One Uno" "table_row"
    And I should see "Two Duex, seminar 1: Completed (achieved pass grade)" in the "Two Duex" "table_row"
    And I should see "Three Toru, seminar 1: Not completed" in the "Three Toru" "table_row"
    And I should see "Four Wha, seminar 1: Not completed" in the "Four Wha" "table_row"
    And I should see "Five Cinq, seminar 1: Not completed" in the "Five Cinq" "table_row"
    When I navigate to "Course completion" node in "Course administration > Reports"
    Then I should see "Completed" in the "One Uno" "table_row"
    But I should not see "Not completed" in the "One Uno" "table_row"
    And I should see "Completed" in the "Two Duex" "table_row"
    But I should not see "Not completed" in the "Two Duex" "table_row"
    And I should see "Not completed" in the "Three Toru" "table_row"
    But I should not see "Completed" in the "Three Toru" "table_row"
    And I should see "Not completed" in the "Four Wha" "table_row"
    But I should not see "Completed" in the "Four Wha" "table_row"
    And I should see "Not completed" in the "Five Cinq" "table_row"
    But I should not see "Completed" in the "Five Cinq" "table_row"

    When I am on "seminar 1" seminar homepage
    And I click on "Take event attendance" "link"
    And I set the following fields to these values:
      | Four Wha's attendance   | Partially attended |
      | Four Wha's event grade  | 30                 |
      | Five Cinq's attendance  | Fully attended     |
      | Five Cinq's event grade | 100                |
    When I click on "Save attendance" "button"
    Then I should see "Successfully updated attendance" in the ".alert-success" "css_element"
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then I should see "One Uno, seminar 1: Completed (achieved pass grade)" in the "One Uno" "table_row"
    And I should see "Two Duex, seminar 1: Completed (achieved pass grade)" in the "Two Duex" "table_row"
    And I should see "Three Toru, seminar 1: Not completed" in the "Three Toru" "table_row"
    And I should see "Four Wha, seminar 1: Completed (did not achieve pass grade)" in the "Four Wha" "table_row"
    And I should see "Five Cinq, seminar 1: Completed (achieved pass grade)" in the "Five Cinq" "table_row"
    When I navigate to "Course completion" node in "Course administration > Reports"
    Then I should see "Completed" in the "One Uno" "table_row"
    But I should not see "Not completed" in the "One Uno" "table_row"
    And I should see "Completed" in the "Two Duex" "table_row"
    But I should not see "Not completed" in the "Two Duex" "table_row"
    And I should see "Not completed" in the "Three Toru" "table_row"
    But I should not see "Completed" in the "Three Toru" "table_row"
    And I should see "Completed" in the "Four Wha" "table_row"
    But I should not see "Not completed" in the "Four Wha" "table_row"
    And I should see "Completed" in the "Five Cinq" "table_row"
    But I should not see "Not completed" in the "Five Cinq" "table_row"
