@totara @perform @mod_perform @javascript @vuejs
Feature: Viewing the section list in the user activities view and navigating to a particular section

  Background:
    Given the following "users" exist:
      | username          | firstname | lastname | email                              |
      | john              | John      | One      | john.one@example.com               |
      | david             | David     | Two      | david.two@example.com              |
      | harry             | Harry     | Three    | harry.three@example.com            |
      | manager-appraiser | combined  | Three    | manager-appraiser.four@example.com |
    And the following job assignments exist:
      | user | manager           | appraiser         |
      | john | manager-appraiser | manager-appraiser |
      | john | david             | harry             |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name                                   | activity_type | create_section | create_track | activity_status | anonymous_responses |
      | Anonymous responses - Multiple section Activity | appraisal     | false          | false        | Active          | true                |
      | Multiple section Activity                       | appraisal     | false          | false        | Active          | false               |
    And the following "activity settings" exist in "mod_perform" plugin:
      | activity_name                                   | close_on_completion | multisection |
      | Anonymous responses - Multiple section Activity | yes                 | yes          |
      | Multiple section Activity                       | yes                 | yes          |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name                                   | section_name   |
      | Anonymous responses - Multiple section Activity | Section anon 1 |
      | Anonymous responses - Multiple section Activity | Section anon 2 |
      | Anonymous responses - Multiple section Activity | Section anon 3 |
      | Multiple section Activity                       | Section 1      |
      | Multiple section Activity                       | Section 2      |
      | Multiple section Activity                       | Section 3      |
    And the following "cohorts" exist:
      | name | idnumber | description | contextlevel | reference | cohorttype |
      | aud1 | aud1     | Audience 1  | System       | 0         | 1          |
    And the following "cohort members" exist:
      | user  | cohort |
      | john  | aud1   |
      | david | aud1   |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name                                   | track_description | due_date_offset |
      | Anonymous responses - Multiple section Activity | track anon 1      | 1, DAY          |
      | Multiple section Activity                       | track 1           | 1, DAY          |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 1           | cohort          | aud1            |
      | track anon 1      | cohort          | aud1            |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name   | relationship |
      | Section anon 1 | manager      |
      | Section anon 1 | appraiser    |
      | Section anon 2 | manager      |
      | Section anon 3 | subject      |
      | Section 1      | subject      |
      | Section 1      | manager      |
      | Section 1      | appraiser    |
      | Section 2      | manager      |
      | Section 3      | subject      |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name   | element_name | title      |
      | Section anon 1 | short_text   | Question 1 |
      | Section anon 2 | short_text   | Question 2 |
      | Section anon 3 | short_text   | Question 3 |
      | Section 1      | short_text   | Question 1 |
      | Section 2      | short_text   | Question 2 |
      | Section 3      | short_text   | Question 3 |
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"
    # Now add a second activity, this makes sure that the activities are in the right order
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name           | activity_type | create_section | create_track | activity_status |
      | Single section Activity | feedback      | false          | false        | Active          |
    And the following "activity settings" exist in "mod_perform" plugin:
      | activity_name           | close_on_completion | multisection |
      | Single section Activity | no                  | no           |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name           | section_name |
      | Single section Activity | Section 4    |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name           | track_description |
      | Single section Activity | track 2           |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 2           | cohort          | aud1            |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship |
      | Section 4    | subject      |
      | Section 4    | manager      |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name | element_name | title      |
      | Section 4    | short_text   | Question 4 |
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"

  Scenario: List and complete sections as the subject with a single section
    Given I log in as "john"
    When I navigate to the outstanding perform activities list page
    # First row
    Then I should see "Single section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Third row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    Then I should not see "Section 4" in the tui modal
    And I should not see "Untitled section" in the tui modal
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name           | Section progress |
      | Subject              | You            | Not started      |
      | Manager              | David Two      | Not started      |
      | Manager              | combined Three | Not started      |
    And I close the tui modal

    When I click on "Single section Activity" "link"
    Then I should see "Single section Activity" in the ".tui-pageHeading__title" "css_element"
    And I should see "Question 4"
    And I should see perform activity relationship to user "yourself"
    When I click on "Cancel" "button"
    # First row
    Then I should see "Single section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "In progress" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is in progress" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Third row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    Then I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name           | Section progress |
      | Subject              | You            | In progress      |
      | Manager              | David Two      | Not started      |
      | Manager              | combined Three | Not started      |
    And I close the tui modal

    When I click on "Single section Activity" "link"
    Then I should see "Single section Activity" in the ".tui-pageHeading__title" "css_element"
    And I should see "Question 4"
    And I should see perform activity relationship to user "yourself"
    When I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    # First row
    Then I should see "Single section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Complete" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is in progress" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Third row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    Then I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name           | Section progress |
      | Subject              | You            | Complete         |
      | Manager              | David Two      | Not started      |
      | Manager              | combined Three | Not started      |

  Scenario: List and complete sections as the subject with multiple sections
    Given I log in as "john"
    When I navigate to the outstanding perform activities list page
    # First row
    Then I should see "Single section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Third row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    Then I should see "Section 1" in the tui modal
    And I should see "Section 2" in the tui modal
    And I should see "Section 3" in the tui modal
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name           | Section progress |
      | Subject              | You            | Not started      |
      | Manager              | David Two      | Not started      |
      | Manager              | combined Three | Not started      |
      | Appraiser            | Harry Three    | Not started      |
      | Appraiser            | combined Three | Not started      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(2)" "css_element" contains:
      | Relationship to user | Name           | Section progress |
      | Manager              | David Two      | Not started      |
      | Manager              | combined Three | Not started      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(3)" "css_element" contains:
      | Relationship to user | Name | Section progress |
      | Subject              | You  | Not started      |
    And "Section 1" "link_or_button" should exist in the ".tui-performUserActivityListSection:nth-child(1) .tui-performUserActivityListSection__header" "css_element"
    And "Section 2" "link_or_button" should not exist in the ".tui-performUserActivityListSection:nth-child(2) .tui-performUserActivityListSection__header" "css_element"
    And I should see "Section 2" in the ".tui-performUserActivityListSection:nth-child(2) .tui-performUserActivityListSection__header" "css_element"
    And "Section 3" "link_or_button" should exist in the ".tui-performUserActivityListSection:nth-child(3) .tui-performUserActivityListSection__header" "css_element"
    When I click on "Section 1" "link_or_button" in the ".tui-performUserActivityListSection:nth-child(1) .tui-performUserActivityListSection__header" "css_element"
    Then I should see "Multiple section Activity" in the ".tui-pageHeading__title" "css_element"
    Then I should see "Section 1" in the ".tui-participantContent__sectionHeading-title" "css_element"
    And I should see "Question 1"
    And I should see perform activity relationship to user "yourself"
    When I click on "Cancel" "button"

    # First row
    Then I should see "Single section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "In progress" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is in progress" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Third row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    Then I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name           | Section progress |
      | Subject              | You            | In progress      |
      | Manager              | David Two      | Not started      |
      | Manager              | combined Three | Not started      |
      | Appraiser            | Harry Three    | Not started      |
      | Appraiser            | combined Three | Not started      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(2)" "css_element" contains:
      | Relationship to user | Name           | Section progress |
      | Manager              | David Two      | Not started      |
      | Manager              | combined Three | Not started      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(3)" "css_element" contains:
      | Relationship to user | Name | Section progress |
      | Subject              | You  | Not started      |
    When I click on "Section 3" "link_or_button" in the ".tui-performUserActivityListSection:nth-child(3) .tui-performUserActivityListSection__header" "css_element"
    Then I should see "Multiple section Activity" in the ".tui-pageHeading__title" "css_element"
    Then I should see "Section 3" in the ".tui-participantContent__sectionHeading-title" "css_element"
    And I should see "Question 3"
    And I should see perform activity relationship to user "yourself"
    When I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast

    # First row
    Then I should see "Single section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "In progress" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is in progress" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Third row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    Then I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name           | Section progress |
      | Subject              | You            | In progress      |
      | Manager              | David Two      | Not started      |
      | Manager              | combined Three | Not started      |
      | Appraiser            | Harry Three    | Not started      |
      | Appraiser            | combined Three | Not started      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(2)" "css_element" contains:
      | Relationship to user | Name           | Section progress |
      | Manager              | David Two      | Not started      |
      | Manager              | combined Three | Not started      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(3)" "css_element" contains:
      | Relationship to user | Name | Section progress |
      | Subject              | You  | Complete Closed  |

  Scenario: List and complete sections as the subject with multiple sections with anonymous answers
    Given I log in as "john"

    When I navigate to the outstanding perform activities list page
    # First row
    Then I should see "Single section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Third row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(3)" "css_element"
    Then I should see "Section anon 1" in the tui modal
    And I should see "Section anon 2" in the tui modal
    And I should see "Section anon 3" in the tui modal
    # In anon mode we should not get "No items to display" empty table messages in sections were the logged in user is not participating.
    And I should not see "No items to display"
    And I should see "1" rows in the tui datatable in the ".tui-performUserActivityListSection" "css_element"
    And I should see the tui datatable in the ".tui-performUserActivityListSection" "css_element" contains:
      | Relationship to user | Name | Section progress |
      | Subject              | You  | Not started      |
    And "Section anon 1" "link_or_button" should not exist in the ".tui-performUserActivityListSections" "css_element"
    And "Section anon 2" "link_or_button" should not exist in the ".tui-performUserActivityListSections" "css_element"
    And I should see "Section anon 1" in the ".tui-performUserActivityListSections" "css_element"
    And I should see "Section anon 2" in the ".tui-performUserActivityListSections" "css_element"
    And "Section anon 3" "link_or_button" should exist in the ".tui-performUserActivityListSections" "css_element"

    When I click on "Section anon 3" "link_or_button" in the ".tui-performUserActivityListSections" "css_element"
    Then I should see "Anonymous responses - Multiple section Activity" in the ".tui-pageHeading__title" "css_element"
    Then I should see "Section anon 3" in the ".tui-participantContent__sectionHeading-title" "css_element"
    And I should see "Question 3"
    And I should see perform activity relationship to user "yourself"

  Scenario: List and complete sections as the manager-appraiser with multiple sections with anonymous answers
    Given I log in as "manager-appraiser"

    When I navigate to the outstanding perform activities list page
    And I click on "As Appraiser" "link"
    # First row
    Then I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    Then I should see "Section anon 1" in the tui modal
    And I should see "Section anon 2" in the tui modal
    And I should see "Section anon 3" in the tui modal

    # In anon mode we should not get "No items to display" empty table messages in sections were the logged in user is not participating.
    And I should not see "No items to display"
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name | Section progress |
      | Manager              | You  | Not started      |
      | Appraiser            | You  | Not started      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(2)" "css_element" contains:
      | Relationship to user | Name | Section progress |
      | Manager              | You  | Not started      |
    And ".tui-performUserActivityListSection:nth-child(3) .tui-performUserActivityListSection__data" "css_element" should not exist
    And "Section anon 1" "link_or_button" should exist in the ".tui-performUserActivityListSections" "css_element"
    And "Section anon 2" "link_or_button" should not exist in the ".tui-performUserActivityListSections" "css_element"
    And "Section anon 3" "link_or_button" should not exist in the ".tui-performUserActivityListSections" "css_element"
    And I should see "Section anon 3" in the ".tui-performUserActivityListSections" "css_element"

    When I click on "Section anon 1" "link" in the ".tui-performUserActivityListSections" "css_element"
    Then I should see "Anonymous responses - Multiple section Activity" in the ".tui-pageHeading__title" "css_element"
    And I should see "Section anon 1" in the ".tui-participantContent__sectionHeading-title" "css_element"
    And I should see "Question 1"
    And I should see perform activity relationship to user "Appraiser"
    When I click on "Cancel" "button"
    And I click on "As Appraiser" "link"
    # First row
    Then I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "In progress" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is in progress" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

  Scenario: List and complete sections as the manager
    Given I log in as "manager-appraiser"
    When I navigate to the outstanding perform activities list page
    And I click on "As Appraiser" "link"

    # First row
    Then I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    Then I should see "Section 1" in the tui modal
    And I should see "Section 2" in the tui modal
    And I should see "Section 3" in the tui modal
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name        | Section progress |
      | Subject              | John One    | Not started      |
      | Manager              | David Two   | Not started      |
      | Manager              | You         | Not started      |
      | Appraiser            | Harry Three | Not started      |
      | Appraiser            | You         | Not started      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(2)" "css_element" contains:
      | Relationship to user | Name      | Section progress |
      | Manager              | David Two | Not started      |
      | Manager              | You       | Not started      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(3)" "css_element" contains:
      | Relationship to user | Name     | Section progress |
      | Subject              | John One | Not started      |
    And "Section 1" "link_or_button" should exist in the ".tui-performUserActivityListSection:nth-child(1) .tui-performUserActivityListSection__header" "css_element"
    And "Section 2" "link_or_button" should not exist in the ".tui-performUserActivityListSection:nth-child(2) .tui-performUserActivityListSection__header" "css_element"
    And "Section 3" "link_or_button" should not exist in the ".tui-performUserActivityListSection:nth-child(3) .tui-performUserActivityListSection__header" "css_element"
    And I should see "Section 3" in the ".tui-performUserActivityListSection:nth-child(3) .tui-performUserActivityListSection__header" "css_element"
    When I click on "Section 1" "link_or_button" in the ".tui-performUserActivityListSection:nth-child(1) .tui-performUserActivityListSection__header" "css_element"
    Then I should see "Multiple section Activity" in the ".tui-pageHeading__title" "css_element"
    And I should see "Section 1" in the ".tui-participantContent__sectionHeading-title" "css_element"
    And I should see "Question 1"
    And I should see perform activity relationship to user "Appraiser"
    When I click on "Cancel" "button"
    And I click on "As Manager" "link"

    # First row
    Then I should see "Single section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is in progress" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Third row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    Then I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name        | Section progress |
      | Subject              | John One    | Not started      |
      | Manager              | David Two   | Not started      |
      | Manager              | You         | Not started      |
      | Appraiser            | Harry Three | Not started      |
      | Appraiser            | You         | In progress      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(2)" "css_element" contains:
      | Relationship to user | Name      | Section progress |
      | Manager              | David Two | Not started      |
      | Manager              | You       | Not started      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(3)" "css_element" contains:
      | Relationship to user | Name     | Section progress |
      | Subject              | John One | Not started      |
    When I click on "Section 2" "link_or_button" in the ".tui-performUserActivityListSection:nth-child(2) .tui-performUserActivityListSection__header" "css_element"
    Then I should see "Multiple section Activity" in the ".tui-pageHeading__title" "css_element"
    And I should see "Section 2" in the ".tui-participantContent__sectionHeading-title" "css_element"
    And I should see "Question 2"
    And I should see perform activity relationship to user "Manager"
    When I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    Then I click on "As Manager" "link"
    # First row
    Then I should see "Single section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "In progress" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is in progress" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Third row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    Then I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name        | Section progress |
      | Subject              | John One    | Not started      |
      | Manager              | David Two   | Not started      |
      | Manager              | You         | Not started      |
      | Appraiser            | Harry Three | Not started      |
      | Appraiser            | You         | In progress      |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(2)" "css_element" contains:
      | Relationship to user | Name      | Section progress |
      | Manager              | David Two | Not started      |
      | Manager              | You       | Complete Closed  |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(3)" "css_element" contains:
      | Relationship to user | Name     | Section progress |
      | Subject              | John One | Not started      |

  Scenario: Show subject instance created date, overdue and closed icon on user activity list
    Given I log in as "john"
    When I navigate to the outstanding perform activities list page

    # First row
    Then I should see "Single section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Third row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    # Display subject instance created date
    Then I should see "##today##j F Y##" in the ".tui-performUserActivityListSectionsModal__overview-created" "css_element"
    And I close the tui modal

    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    # Subject instance created date and due date display.
    Then I should see "##today##j F Y##" in the ".tui-performUserActivityListSectionsModal__overview-created" "css_element"
    And I close the tui modal

    # Complete Single section activity
    And I click on "Single section Activity" "link"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast

    # Section 2 in progress
    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    And I click on "Section 1" "link_or_button" in the ".tui-performUserActivityListSection:nth-child(1) .tui-performUserActivityListSection__header" "css_element"
    And I click on "Cancel" "button"

    # Complete Section 3 of multiple section activity
    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    And I click on "Section 3" "link_or_button" in the ".tui-performUserActivityListSection:nth-child(3) .tui-performUserActivityListSection__header" "css_element"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast

    # Overdue lozenge shows on due subject/participant instances
    And Subject instances for "track 1" track are due "##yesterday##"
    And Subject instances for "track 2" track are due "##yesterday##"
    And I reload the page

    # Closed participant instance shows the closed icon
    # First row
    Then I should see "Single section Activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Feedback" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Complete" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is overdue" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "Multiple section Activity" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Overdue" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is overdue" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Third row
    And I should see "Anonymous responses - Multiple section Activity" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(3) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    # Overdue lozenge shows on non-completed participant sections
    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    Then I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name           | Section progress    |
      | Subject              | You            | Complete            |
      | Manager              | David Two      | Not started Overdue |
      | Manager              | combined Three | Not started Overdue |
    And I close the tui modal

    # Uncompleted sections show overdue lozenge
    When I click on "View details" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    Then I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(1)" "css_element" contains:
      | Relationship to user | Name           | Section progress    |
      | Subject              | You            | In progress Overdue |
      | Manager              | David Two      | Not started Overdue |
      | Manager              | combined Three | Not started Overdue |
      | Appraiser            | Harry Three    | Not started Overdue |
      | Appraiser            | combined Three | Not started Overdue |
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(2)" "css_element" contains:
      | Relationship to user | Name           | Section progress    |
      | Manager              | David Two      | Not started Overdue |
      | Manager              | combined Three | Not started Overdue |

    # Closed participant section shows the closed icon
    And I should see the tui datatable in the ".tui-performUserActivityListSection:nth-child(3)" "css_element" contains:
      | Relationship to user | Name | Section progress |
      | Subject              | You  | Complete Closed  |
