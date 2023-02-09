@totara @core @core_course
Feature: Course enrolment navigation
  In order to test enrolment navigation
  As an admin
  I will configure multiple courses to use it

  @javascript
  Scenario: Ensure the session wantsurl is ignored when leanrner has no enrolment
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username  | firstname | lastname | email             |
      | user1     | User      | 1        | user1@example.com |
    And the following "courses" exist:
      | fullname      | shortname        |
      | Course 1      | Course1          |
      | Course 2      | Course2          |
    And I am on "Course 1" course homepage
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    And I log out
    And I log in as "user1"
    And I click on "Find Learning" in the totara menu
    And I click on "Course 2" "text"
    And I should see "You can not enrol yourself in this course."
    And I press "Continue"
    And I click on "Course 1" "text"
    And I should see "Self enrolment (Learner)"
    When I press "Enrol me"
    Then I should not see "You can not enrol yourself in this course."
    And I should see "Course1"
