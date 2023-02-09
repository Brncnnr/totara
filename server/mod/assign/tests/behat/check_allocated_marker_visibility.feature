@mod @mod_assign @javascript
Feature: Check allocated marker visibility
  In order to check allocated marker visibility
  As a teacher
  I need to set allocated users to a trainer and check the trainer see only the participant allocated to him.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | trainer1 | Tina | Trainer1 | trainer1@example.com |
      | student1 | Sam1 | Student1 | student1@example.com |
      | student2 | Sam2 | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | trainer1 | C1 | teacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Submit your online text |
      | assignsubmission_onlinetext_enabled | 1 |
      | assignsubmission_file_enabled | 0 |
      | Use marking workflow | Yes |
      | Use marking allocation | Yes |
    And I follow "Test assignment name"
    And I navigate to "View all submissions" in current page administration
    And I click on "Select Sam1 Student1" "checkbox"
    And I select "Set allocated marker" from the "operation" singleselect
    And I click on "Go" "button" in the ".gradingbatchoperationsform" "css_element" confirming the dialogue
    And I should see "Tina Trainer1"
    And I press "Save changes"
    And I log out

  Scenario: Check visibility for allocated users
    Given I log in as "trainer1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    When I navigate to "View all submissions" in current page administration
    Then I should see "Sam1 Student1"
    And I should not see "Sam2 Student2"
    And I log out
