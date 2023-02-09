@totara @perform @mod_perform @javascript @vuejs
Feature: Visibility conditions effect on the Participants.

  Background:
    Given the following "users" exist:
      | username          | firstname | lastname | email                              |
      | john              | John      | One      | john.one@example.com               |
      | david             | David     | Two      | david.two@example.com              |
      | harry             | Harry     | Three    | harry.three@example.com            |
    And the following job assignments exist:
      | user | manager           | appraiser         |
      | john | david             | harry             |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name | activity_type | create_section | create_track | activity_status |
      | All responses | appraisal     | false          | false        | Active          |
      | Own responses | appraisal     | false          | false        | Active          |
      | None          | appraisal     | false          | false        | Active          |
    And the following "activity settings" exist in "mod_perform" plugin:
      | activity_name | visibility_condition |
      | All responses | 2                    |
      | Own responses | 1                    |
      | None          | 0                    |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name | section_name |
      | All responses | All section  |
      | Own responses | Own section  |
      | None          | None section |
    And the following "cohorts" exist:
      | name | idnumber | description | contextlevel | reference | cohorttype |
      | aud1 | aud1     | Audience 1  | System       | 0         | 1          |
    And the following "cohort members" exist:
      | user  | cohort |
      | john  | aud1   |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name | track_description |
      | All responses | All track         |
      | Own responses | Own track         |
      | None          | None track        |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | All track         | cohort          | aud1            |
      | Own track         | cohort          | aud1            |
      | None track        | cohort          | aud1            |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name   | relationship |
      | All section    | manager      |
      | All section    | appraiser    |
      | All section    | subject      |
      | Own section    | manager      |
      | Own section    | appraiser    |
      | Own section    | subject      |
      | None section   | manager      |
      | None section   | appraiser    |
      | None section   | subject      |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name   | element_name | title      |
      | All section    | short_text   | Question 1 |
      | Own section    | short_text   | Question 2 |
      | None section   | short_text   | Question 3 |
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"

  Scenario: Description shows for activity with All responses visibility option
    Given I log in as "john"
    And I navigate to the outstanding perform activities list page
    When I click on "All responses" "link"
    Then I should see "Responses are only visible to viewers when response submission is closed for all participants"

  Scenario: Description shows for activity with Own responses visibility option
    Given I log in as "john"
    And I navigate to the outstanding perform activities list page
    When I click on "Own responses" "link"
    Then I should see "Responses are only visible to viewers when their own response submission is closed"

  Scenario: Description does not shows for activity with None visibility option
    Given I log in as "john"
    And I navigate to the outstanding perform activities list page
    When I click on "None" "link"
    Then I should not see "Responses are only visible to viewers when response submission is closed for all participants"
    Then I should not see "Responses are only visible to viewers when their own response submission is closed"
