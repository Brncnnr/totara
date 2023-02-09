@totara @totara_plan @javascript
Feature: See that audience based visibility doesn't effect a course showing in a Learning Plan.

  Background:
    Given the "mylearning" user profile block exists
    And I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                     |
      | learner1 | Learner   | One      | learner.one@example.com   |
      | learner2 | Learner   | Two      | learner.two@example.com   |
      | manager1 | Manager   | One      | manager.one@example.com   |
    And the following "courses" exist:
      | fullname                       | shortname   |
      | CourseVisibility Test Course 1 | testcourse1 |
    And the following job assignments exist:
      | user     | fullname       | manager  |
      | learner1 | jobassignment1 | manager1 |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name            |
      | learner1 | Learning Plan 1 |

  Scenario: Add course to plan with no visibility restrictions.
    Given I log in as "learner1"
    When I am on "Dashboard" page
    And I click on "Learning Plans" "link"
    And I click on "Learning Plan 1" "link"
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "CourseVisibility Test Course 1" "link"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then I should see "CourseVisibility Test Course 1" in the "#dp-component-update-table" "css_element"

  Scenario: Audienced based visibility where learner can't see course.
    Given I log in as "admin"
    When I navigate to "Shared services settings" node in "Site administration > System information > Configure features"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "CourseVisibility Test Course 1" "link"
    And I click on "Edit" "link" in the ".course-detail-listing-actions" "css_element"
    And I set the following fields to these values:
      | Visibility | Enrolled users and members of the selected audiences |
    And I press "Save and display"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Learner One" "link"
    And I click on "Learning Plans" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    And I click on "Learning Plan 1" "link"
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "CourseVisibility Test Course 1" "link"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"

    # Check that the course is visible in the plan.
    Then I should see "CourseVisibility Test Course 1" in the "#dp-component-update-table" "css_element"

  Scenario: Audience visibility, enrolled users only, required learning
  Using the Plan enrolment plugin, when a 'enrolled users only' course is added to a plan, ensure the user can enrol.
    When I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 1 |

    When I am on "CourseVisibility Test Course 1" course homepage
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the field "Visibility" to "Enrolled users only"
    Then I press "Save and display"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Learner One" "link"
    And I click on "Learning Plans" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    And I click on "Learning Plan 1" "link"
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "CourseVisibility Test Course 1" "link"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"
    And I press "Approve"
    Then I should see "Plan \"Learning Plan 1\" has been approved by Admin User"
    And I log out

    When I log in as "learner1"
    Then I should see "CourseVisibility Test Course 1" in the "Current Learning" "block"

    When I click on "CourseVisibility Test Course 1" "link"
    Then I should see "This course is currently unavailable to learners"

    When I click on "Record of Learning" in the totara menu
    And I click on "Learning Plan 1" "link"
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    Then I should see "CourseVisibility Test Course 1" in the "#dp-component-update-table" "css_element"

    When I click on "Launch course" "link"
    Then I should see "This course is currently unavailable to learners"
    And I log out

    When I log in as "admin"
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Enable" "link" in the "Learning Plan" "table_row"
    And I am on "CourseVisibility Test Course 1" course homepage
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I set the field "Add method" to "Learning Plan"
    And I log out

    When I log in as "learner1"
    Then I should see "CourseVisibility Test Course 1" in the "Current Learning" "block"

    # User should now be able to access the course from the current learning block.
    When I click on "CourseVisibility Test Course 1" "link"
    Then I should see "You have been enrolled in course CourseVisibility Test Course 1 ."
    And I should see "Topic 1"
    And I log out

    # Now lets remove the enrolment so we can test accessing the course from the plan.
    When I log in as "admin"
    And I am on "CourseVisibility Test Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Learner One" "table_row"
    And I press "Continue"
    Then I should not see "Learner One"
    And I log out

    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I click on "Learning Plan 1" "link"
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    Then I should see "CourseVisibility Test Course 1" in the "#dp-component-update-table" "css_element"

    When I click on "Launch course" "link"
    Then I should see "You have been enrolled in course CourseVisibility Test Course 1 ."
    And I should see "Topic 1"
    And I log out