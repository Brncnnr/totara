@totara @perform @mod_perform @javascript @vuejs
Feature: Viewing user activities list with view-only access

  Background:
    Given the following "users" exist:
      | username          | firstname | lastname | email                               |
      | john              | John      | One      | john.one@example.com                |
      | david             | David     | Two      | david.two@example.com               |
      | manager-appraiser | combined  | Three    | manager-appraiser.three@example.com |
      | appraiser         | Appraiser | Four     | appraiser.four@example.com          |
    And the following job assignments exist:
      | user  | manager           | appraiser         |
      | john  | manager-appraiser | manager-appraiser |
      | david | john              | appraiser         |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name           | activity_type | create_section | create_track | activity_status | anonymous_responses |
      | Single section activity | appraisal     | false          | false        | Active          | false               |
    And the following "activity settings" exist in "mod_perform" plugin:
      | activity_name           | close_on_completion | multisection |
      | Single section activity | yes                 | no           |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name           | section_name   |
      | Single section activity | Single section |
    And the following "cohorts" exist:
      | name | idnumber | description | contextlevel | reference | cohorttype |
      | aud1 | aud1     | Audience 1  | System       | 0         | 1          |
    And the following "cohort members" exist:
      | user  | cohort |
      | john  | aud1   |
      | david | aud1   |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name           | track_description |
      | Single section activity | track 1           |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 1           | cohort          | aud1            |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name   | relationship | can_view | can_answer |
      | Single section | subject      | yes      | yes        |
      | Single section | manager      | yes      | yes        |
      | Single section | appraiser    | yes      | no         |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name   | element_name | title      |
      | Single section | short_text   | Question 1 |
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"
    # Now add a second activity, this makes sure that the activities are in the right order
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name          | activity_type | create_section | create_track | activity_status |
      | Multi section activity | feedback      | false          | false        | Active          |
    And the following "activity settings" exist in "mod_perform" plugin:
      | activity_name          | close_on_completion | multisection |
      | Multi section activity | no                  | yes          |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name          | section_name |
      | Multi section activity | Section 1    |
      | Multi section activity | Section 2    |
      | Multi section activity | Section 3    |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name          | track_description |
      | Multi section activity | track 2           |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 2           | cohort          | aud1            |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship | can_view | can_answer |
      | Section 1    | subject      | yes      | no         |
      | Section 2    | subject      | yes      | yes        |
      | Section 1    | manager      | yes      | yes        |
      | Section 2    | manager      | yes      | yes        |
      | Section 3    | manager      | yes      | yes        |
      | Section 1    | appraiser    | yes      | no         |
      | Section 2    | appraiser    | yes      | no         |
      | Section 3    | appraiser    | yes      | no         |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name | element_name | title      |
      | Section 1    | short_text   | Question 2 |
      | Section 2    | short_text   | Question 3 |
      | Section 3    | short_text   | Question 4 |
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"

  Scenario: View-only relationship should not be displayed in the expanded participant summary
    Given I log in as "john"
    When I navigate to the outstanding perform activities list page

    # First row
    Then I should see "Multi section activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Single section activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    Then I should not see "Appraiser" in the tui modal
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name           | Section progress |
      | Subject              | You            | Not started      |
      | Manager              | combined Three | Not started      |
    And I close the tui modal

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    Then I should see "(view only)" in the ".tui-performUserActivityListSection:nth-child(1)" "css_element"
    And I should not see "(view only)" in the ".tui-performUserActivityListSection:nth-child(2)" "css_element"
    And I should not see "(view only)" in the ".tui-performUserActivityListSection:nth-child(3)" "css_element"
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name           | Section progress |
      | Manager              | combined Three | Not started      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(2)" "css_element" contains:
      | Relationship to user | Name           | Section progress |
      | Subject              | You            | Not started      |
      | Manager              | combined Three | Not started      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(3)" "css_element" contains:
      | Relationship to user | Name           | Section progress |
      | Manager              | combined Three | Not started      |
    # Make sure we can follow the section link even if it's a view-only section.
    When I click on "Section 1" "link_or_button" in the ".tui-performUserActivityListSection:nth-child(1) .tui-performUserActivityListSection__header" "css_element"
    Then I should see "Multi section activity" in the ".tui-pageHeading__title" "css_element"

  Scenario: View-only access is indicated differently for single and multi section activities
    Given I log in as "appraiser"
    When I navigate to the outstanding perform activities list page
    And I click on "As Appraiser" "link"

    # First row
    Then I should see "Multi section activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "View-only" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Single section activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "View-only" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    Then I should not see "Appraiser" in the tui modal

    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name      | Section progress |
      | Subject              | David Two | Not started      |
      | Manager              | John One  | Not started      |
    And I close the tui modal

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    # For multi section activity view-only is indicated per section.
    Then I should see "(view only)" in the ".tui-performUserActivityListSection:nth-child(1)" "css_element"
    And I should see "(view only)" in the ".tui-performUserActivityListSection:nth-child(2)" "css_element"
    And I should see "(view only)" in the ".tui-performUserActivityListSection:nth-child(3)" "css_element"

  Scenario: Participant instance progress for mixed access is aggregated correctly
    Given I log in as "manager-appraiser"
    When I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"

    Then I should see "Multi section activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    And I should see "Single section activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    Then I should not see "Appraiser" in the tui modal
    And I should not see "You have view-only access to this activity." in the tui modal
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name     | Section progress |
      | Subject              | John One | Not started      |
      | Manager              | You      | Not started      |

    When I close the tui modal
    Then I click on "As Appraiser" "link"
    And I should see "Multi section activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "View-only" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    And I should see "Single section activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "View-only" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
