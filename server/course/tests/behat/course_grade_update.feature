@core @core_course @totara @javascript
Feature: Adding course's activity should require cron to run.
  Background:
    Given I lower the async regrade thresholds
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user_et  | Editing   | Trainer  | et@example.com    |
      | user_1   | User      | Clone    | c_1@example.com   |
      | user_2   | User      | Clone    | c_2@example.com   |
      | user_3   | User      | Clone    | c_3@example.com   |
      | user_4   | User      | Clone    | c_4@example.com   |
      | user_5   | User      | Clone    | c_5@example.com   |
      | user_6   | User      | Clone    | c_6@example.com   |
      | user_7   | User      | Clone    | c_7@example.com   |
      | user_8   | User      | Clone    | c_8@example.com   |
      | user_9   | User      | Clone    | c_9@example.com   |
      | user_10  | User      | Clone    | c_10@example.com  |
      | user_11  | User      | Clone    | c_11@example.com  |
    And the following "courses" exist:
      | shortname | fullname   | idnumber | enablecompletion |
      | c101      | Course 101 | c101     | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user_et  | c101   | editingteacher |
      | user_1   | c101   | student        |
      | user_2   | c101   | student        |
      | user_3   | c101   | student        |
      | user_4   | c101   | student        |
      | user_5   | c101   | student        |
      | user_6   | c101   | student        |
      | user_7   | c101   | student        |
      | user_8   | c101   | student        |
      | user_9   | c101   | student        |

  Scenario: Adding course activity on a course with few users should not notify the regrade.
    And I am on a totara site
    And I log in as "admin"
    And I am on "Course 101" course homepage
    And I turn editing mode on
    And I should not see "Grades are now being re-aggregated due to the additional activity."
    When I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name     | Ass 1                                             |
      | Completion tracking | Show activity as complete when conditions are met |
      | completionusegrade  | 1                                                 |
    Then I should not see "Grades are now being re-aggregated due to the additional activity."
    And I turn editing mode off
    And I log out
    And I log in as "user_et"
    When I am on "Course 101" course homepage
    Then I should not see "Grades are now being re-aggregated due to the additional activity."

  Scenario: Adding course activity on a course with many users should notify the regrade for admin and editing trainer.
    Given the following "course enrolments" exist:
      | user     | course | role           |
      | user_10  | c101   | student        |
      | user_11  | c101   | student        |
    And I am on a totara site
    And I log in as "admin"
    And I am on "Course 101" course homepage
    And I turn editing mode on
    And I should not see "Grades are now being re-aggregated due to the additional activity."
    When I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name     | Ass 1                                             |
      | Completion tracking | Show activity as complete when conditions are met |
      | completionusegrade  | 1                                                 |
    Then I should see "Grades are now being re-aggregated due to the additional activity."
    And I turn editing mode off
    And I log out
    And I log in as "user_et"
    When I am on "Course 101" course homepage
    Then I should see "Grades are now being re-aggregated due to the additional activity."
    And I log out
    And I log in as "user_2"
    When I am on "Course 101" course homepage
    Then I should not see "Grades are now being re-aggregated due to the additional activity."
    And I log out
    And I run all adhoc tasks
    When I log in as "admin"
    And I am on "Course 101" course homepage
    Then I should not see "Grades are now being re-aggregated due to the additional activity."