@course @totara @container @container_course @javascript
Feature: Enrolment banner display for user in course
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email           |
      | user_one | user      | one      | one@example.com |
    And the following "courses" exist:
      | fullname   | shortname |
      | Course 101 | c101      |

    # we need this step in order to enable the course appearance at the find learning page.
    And I run all adhoc tasks

  Scenario: Non enrolled guest user should see a banner with the course guest access enabled
    Given I am on a totara site
    And I log in as "admin"
    And I am on "Course 101" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I click on "Enable" "link" in the "Guest access" "table_row"
    And I log out
    And I log in as "user_one"
    And I click on "Find Learning" in the totara menu
    And I should see "Course 101"
    When I click on "Course 101" "text"
    Then I should see "You are viewing as a ‘Guest’. Your progress will not be recorded."

  Scenario: Non enrolled guest user should see a banner with the course guest access and self enrol enabled (without password)
    Given I am on a totara site
    And I log in as "admin"
    And I am on "Course 101" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I click on "Enable" "link" in the "Guest access" "table_row"
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    And I log out
    And I log in as "user_one"
    And I click on "Find Learning" in the totara menu
    And I should see "Course 101"
    When I click on "Course 101" "text"
    Then I should not see "You are viewing as a ‘Guest’. Your progress will not be recorded."
    And I should see "You’re viewing this course as a ‘Guest’. You must enrol in the course for your learning to be recorded"
    And I should see "Enrol"
    When I follow "Enrol"
    Then I should not see "Self enrolment (Learner)"
    And I should see "You've been enrolled successfully"
    And I should not see "You’re viewing this course as a ‘Guest’. You must enrol in the course for your learning to be recorded"

  Scenario: Non enrolled user should not be able to see banner when only self enrolment enabled
    Given I am on a totara site
    And I log in as "admin"
    And I am on "Course 101" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    And I log out
    And I log in as "user_one"
    And I click on "Find Learning" in the totara menu
    And I should see "Course 101"
    When I click on "Course 101" "text"
    Then I should not see "You are viewing as a ‘Guest’. Your progress will not be recorded."
    And I should not see "You’re viewing this course as a ‘Guest’. You must enrol in the course for your learning to be recorded"
    And I should see "Self enrolment (Learner)"
    When I press "Enrol me"
    Then I should see "You've been enrolled successfully"
    And I should not see "You’re viewing this course as a ‘Guest’. You must enrol in the course for your learning to be recorded"

  Scenario: Admin user should see a banner without guest access and without self enrolled
    Given I am on a totara site
    And  I log in as "admin"
    When I am on "Course 101" course homepage
    Then I should see "You’re accessing this course as an administrator."

  Scenario: Admin user should see a banner with guest access enabled
    Given I am on a totara site
    And I log in as "admin"
    And I am on "Course 101" course homepage
    And I should see "You’re accessing this course as an administrator."
    And I navigate to "Users > Enrolment methods" in current page administration
    And I click on "Enable" "link" in the "Guest access" "table_row"
    When I am on "Course 101" course homepage
    Then I should not see "You’re accessing this course as an administrator. You must enrol in the course for your learning to be recorded."
    And I should see "You’re accessing this course as an administrator."

  Scenario: Admin user should see enrol option with self enrol enabled
    Given I am on a totara site
    And I log in as "admin"
    And I am on "Course 101" course homepage
    And I should not see "Enrol"
    And I navigate to "Users > Enrolment methods" in current page administration
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    And I am on "Course 101" course homepage
    And I should see "You’re accessing this course as an administrator. You must enrol in the course for your learning to be recorded."
    And I should see "Enrol"
    When I follow "Enrol"
    Then I should not see "Self enrolment (Learner)"
    And I should see "You've been enrolled successfully"
    And I should not see "You’re accessing this course as an administrator. You must enrol in the course for your learning to be recorded."