@mod @mod_feedback @javascript
Feature: Testing multichoicerated questions in feedback
  In order to create feedbacks
  As a teacher
  I need to be able to create different types of multichoicerated questions

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | 1        |
      | student1 | Student   | 1        |
      | student2 | Student   | 2        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity   | name                | course | idnumber    |
      | feedback   | Learning experience | C1     | feedback0   |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Learning experience"
    And I click on "Edit questions" "link" in the "[role=main]" "css_element"

  Scenario: Ensure < and > symbols display correctly in feedback multi choice rated
    When I add a "Multiple choice (rated)" question to the feedback with:
      | Question         | this is a multiple choice 1  |
      | Label            | multichoice1                 |
      | Multiple choice type | Multiple choice - single answer|
      | Multiple choice values | <6 Months\n>6 Months\n |
    Then I should see "<6 Months"
    And I should see ">6 Months"
    And I log out

    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Learning experience"
    And I follow "Answer the questions..."
    And I set the field "<6 Months" to "1"
    And I press "Submit your answers"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Learning experience"
    And I navigate to "Show responses" in current page administration
    Then I should see "<6 Months"

    When I follow "Response number: 1"
    Then I should see "<6 Months"
    And I log out
