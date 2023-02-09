@totara @totara_reportbuilder @javascript
Feature: Only enrolled users should see course membership report records when
  Audience Based Visibility is turned on at the site level and Enrolled Users Only from the course level.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | learner1 | C1     | student        |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname                 | shortname                       | source            |
      | Course Membership Report | report_course_membership_report | course_membership |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Edit settings" "link" in the "Administration" "block"
    When I navigate to "Shared services settings" node in "Site administration > System information > Configure features"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Course 1" "link"
    And I click on "Edit" "link" in the ".course-detail-listing-actions" "css_element"
    And I set the following fields to these values:
      | Visibility | Enrolled users only |
    And I press "Save and display"
    And I navigate to my "Course Membership Report" report
    And I press "Edit this report"
    And I switch to "Access" tab
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"
    And I log out

  Scenario: Enrolled users can see the course record in the Course Membership report.
    Given I log in as "learner1"
    When I navigate to my "Course Membership Report" report
    Then I should see "Course 1"
    And I log out

  Scenario: Not enrolled users can not see the course record in the Course Membership report.
    Given I log in as "learner2"
    When I navigate to my "Course Membership Report" report
    Then I should not see "Course 1"
