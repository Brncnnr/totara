@core @totara @javascript
Feature: Totara behat step definitions work correctly

  Background:
    Given the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | A1       |
    And the following "cohort members" exist:
      | user     | cohort |
      | admin    | A1     |

  Scenario: I am on page definitions
    Given I am on a totara site
    And I enable the "appraisals" advanced feature
    And I enable the "feedback360" advanced feature
    And I log in as "admin"

    When the following "appraisals" exist in "totara_appraisal" plugin:
      | name             |
      | Test Appraisal 1 |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal        | name      | timedue                |
      | Test Appraisal 1 | Stage 1-1 | 2082729599             |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal        | stage     | name       |
      | Test Appraisal 1 | Stage 1-1 | Page 1-1-1 |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal        | stage     | page       | name         |
      | Test Appraisal 1 | Stage 1-1 | Page 1-1-1 | Question 1-1-1-1 |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal        | type         | id |
      | Test Appraisal 1 | audience     | A1 |
    And I navigate to "Manage Appraisals (legacy)" node in "Site administration > Legacy features"
    And I click on "Activate" "link" in the "Test Appraisal 1" "table_row"
    And I press "Activate"
    And I am on "All Appraisals" page
    Then I should see "All Appraisals"
    And I should see "Test Appraisal 1"

    When I am on "Latest Appraisal" page
    Then I should see "Test Appraisal 1"

    When I am on "360° Feedback" page
    Then I should see "Feedback about you"

    When I am on "Goals" page
    Then I should see "Goals"

    When I am on "Team" page
    Then I should see "Team Members"
