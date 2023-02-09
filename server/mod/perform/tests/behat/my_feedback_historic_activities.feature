@totara @totara_feedback360 @totara_appraisal @perform @mod_perform @javascript @vuejs
Feature: Make sure user can see #60 feedback in Historic activities under Performance

  Background:
    Given I am on a totara site
    And I enable the "feedback360" advanced feature
    And I enable the "appraisals" advanced feature
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH1    |
    And the following config values are set as admin:
      | showhistoricactivities | 1 |
    And I log in as "admin"
    And I navigate to "Manage 360° Feedback (legacy)" node in "Site administration > Legacy features"
    And I press "Create Feedback"
    And I set the following fields to these values:
      | Name               | Normal feedback                          |
      | Description        | This is a simple normal feedback request |
    And I press "Create Feedback"
    And I switch to "Content" tab
    And I set the field "datatype" to "Long text"
    And I press "Add"
    And I set the field "Question" to "How much do you like me?"
    And I press "Save changes"
    And I switch to "Assignments" tab
    And I set the field "groupselector" to "Audience"
    And I click on "Cohort 1 (CH1)" "link" in the "Assign Group to 360° Feedback?" "totaradialogue"
    And I click on "Save" "button" in the "Assign Group to 360° Feedback?" "totaradialogue"
    And I follow "(Activate Now)"
    And I press "Continue"
    And I log out

  Scenario: User still can see feedbacks
    And I log in as "user1"
    And I navigate to the outstanding perform activities list page
    When I click on "Historic activities" "link"
    Then I should see "Your historic activities"
    And I should see the tui datatable contains:
      | Activity title  | Type                   | Status |
      | Normal feedback | 360° Feedback (legacy) | Active |