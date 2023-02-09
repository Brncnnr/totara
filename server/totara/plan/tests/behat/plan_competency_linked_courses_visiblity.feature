@totara @totara_plan @javascript
Feature: See that audience based visibility doesn't affect a competencies linked courses showing in a Learning Plans.

  Background:
    Given I am on a totara site
    And I disable the "competency_assignment" advanced feature
    And the following "users" exist:
      | username | firstname | lastname | email                     |
      | learner1 | Learner   | One      | learner.one@example.com   |
      | learner2 | Learner   | Two      | learner.two@example.com   |
      | manager1 | Manager   | One      | manager.one@example.com   |
    # Audiencevisible: Enrolled users only = 0, Enrolled users and audience = 1, All users = 2, No users = 3
    And the following "courses" exist:
      | fullname               | shortname   |enablecompletion | audiencevisible |
      | CourseVisibilityEnrol  | testcourse1 | 1               | 0               |
      | CourseVisibilityEnrol2 | testcourse2 | 1               | 0               |
      | CourseVisibilityAll    | testcourse3 | 1               | 2               |
      | CourseVisibilityNone   | testcourse4 | 1               | 3               |
    And the following "position" frameworks exist:
      | fullname             | idnumber | description           |
      | Position Framework 1 | PF1      | Framework description |
    And the following "position" hierarchy exists:
      | framework | fullname   | idnumber |
      | PF1       | Position 1 | P1       |
    And the following job assignments exist:
      | user     | position | manager  |
      | learner1 | P1       | manager1 |
    And the following "competency" frameworks exist:
      | fullname               | idnumber | description           |
      | Competency Framework 1 | CF1      | Framework description |
      | Competency Framework 2 | CF2      | Framework description |
    And the following "competency" hierarchy exists:
      | framework | fullname     | idnumber | description            |
      | CF1       | Competency 1 | C1       | Competency description |
      | CF1       | Competency 2 | C2       | Competency description |
      | CF2       | Competency 3 | C3       | Competency description |
    And the following "linked courses" exist in "totara_competency" plugin:
      | competency | course       | mandatory |
      | C1         | testcourse1  | 0         |
      | C1         | testcourse3  | 0         |
      | C1         | testcourse4  | 0         |
      | C3         | testcourse2  | 0         |

    When I log in as "admin"
    And I navigate to "Shared services settings" node in "Site administration > System information > Configure features"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    # Update workflow to pull competencies through positions, including their linked courses.
    And I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I click on "Edit" "link" in the "Learning Plan (Default)" "table_row"
    And I follow "Workflow"
    And I click on "Custom workflow" "radio"
    And I press "Advanced workflow settings"
    And I switch to "Competencies" tab
    And I click on "Automatically assign by position" "checkbox"
    And I click on "Automatically assign by organisation" "checkbox"
    And I click on "Include linked courses" "checkbox"
    And I click on "Include completed competencies" "checkbox"
    And I press "Save changes"
    Then I should see "Competency settings successfully updated"

    # Assign competency 1 to the position 1
    And I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"
    And I follow "Position 1"
    And I should see "Position Framework 1 - Position 1"
    And I press "Add Competency"
    Then I should see "Locate competency" in the "Link competencies" "totaradialogue"
    And I follow "Competency 1"
    And I follow "Competency 2"
    And I click on "Save" "button" in the "Link competencies" "totaradialogue"
    Then I should see "Remove" in the "Competency 1" "table_row"
    And I log out

  Scenario: Check that manual assignment of competencies is adding the linked courses.
    Given I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Learner Two"
    When I click on "Learning Plans" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "You are viewing Learner Two's plans."
    And I press "Create new learning plan"
    And I set the field "Plan name" to "Learning Plan from admin"
    And I set the field "Plan description" to "A short but meaningful description of this Learning Plan: competencies."
    When I press "Create plan"
    Then I should see "Plan creation successful"

    # Check linked courses are being added when adding a competency.
    When I switch to "Competencies" tab
    And I click on "Add competencies" "button"
    Then I should see "Competency 1"
    When I click on "Competency 1" "link"
    And I click on "Continue" "button" in the "Add competencies" "totaradialogue"
    And I should see "Some of those competencies have linked courses"
    And I click on "Save" "button" in the "Add competencies" "totaradialogue"
    Then I should see "Competency 1"

    When I switch to "Courses" tab
    Then I should see "CourseVisibilityEnrol"
    And I should see "CourseVisibilityAll"
    And I should not see "CourseVisibilityNone"
    And I log out

    When I log in as "learner2"
    And I am on a totara site
    And I follow "Learning Plans"
    And I follow "Learning Plan from admin"
    And I switch to "Courses" tab
    Then I should see "CourseVisibilityEnrol"
    And I should see "CourseVisibilityAll"
    And I should not see "CourseVisibilityNone"

  Scenario: Competencies based on job assignments are being pulled with the visible linked courses from the person who add them.
    # Competency 1 set in plan by admin.
    Given I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Learner One"
    When I click on "Learning Plans" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "You are viewing Learner One's plans."
    And I press "Create new learning plan"
    And I set the field "Plan name" to "Learning Plan from admin"
    And I set the field "Plan description" to "A short but meaningful description of this Learning Plan: competencies."
    When I press "Create plan"
    Then I should see "Plan creation successful"

    # Check that the competencies have been added from each of the job assignments.
    When I switch to "Competencies" tab
    Then I should see "Competency 1"
    And I should see "Competency 2"

    # Check that the courses linked to the competencies have been added to the plan.
    When I switch to "Courses" tab
    Then I should see "CourseVisibilityEnrol"
    And I should see "CourseVisibilityAll"
    And I should not see "CourseVisibilityNone"
    And I log out

    # Log in as learner1 and check the courses seen by the admin are in listed under courses tab
    When I log in as "learner1"
    And I am on a totara site
    And I follow "Learning Plans"
    And I follow "All Learning"
    And I follow "Learning Plan from admin"
    And I switch to "Courses" tab
    Then I should see "CourseVisibilityEnrol"
    And I should see "CourseVisibilityAll"
    And I should not see "CourseVisibilityNone"

    # Competency 1 set in plan by learner with position linked to Competency 1
    And I follow "Learning Plans"
    And I press "Create new learning plan"
    And I set the field "Plan name" to "My Learning Plan"
    And I set the field "Plan description" to "A short but meaningful description of My Learning Plan: competencies."
    When I press "Create plan"
    Then I should see "Plan creation successful"

    # Check that the competencies have been added from each of the job assignments.
    When I switch to "Competencies" tab
    Then I should see "Competency 1"
    And I should see "Competency 2"

    # Check that the courses linked to the competencies and visible for the learner have been added to the plan.
    When I switch to "Courses" tab
    Then I should see "CourseVisibilityAll"
    And I should not see "CourseVisibilityEnrol"
    And I should not see "CourseVisibilityNone"
    And I log out
