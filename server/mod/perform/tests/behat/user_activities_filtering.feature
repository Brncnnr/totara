@totara @perform @mod_perform @javascript @vuejs
Feature: Filtering user activities list
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username          | firstname | lastname | email                              |
      | john              | John      | One      | john.one@example.com               |
      | david             | David     | Two      | david.two@example.com              |
      | harry             | Harry     | Three    | harry.three@example.com            |
      | manager-appraiser | combined  | Three    | manager-appraiser.four@example.com |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name                   | activity_type | subject_username | subject_is_participating | other_participant_username | number_repeated_instances | track    |
      | johns example activity 1        | check-in      | john             | true                     | harry                      | 1                         | track 1  |
      | johns example activity 2        | appraisal     | john             | true                     | harry                      | 1                         | track 2  |
      | johns example activity 3        | appraisal     | john             | true                     | harry                      | 1                         | track 3  |
      | johns example activity 4        | check-in      | john             | true                     | harry                      | 1                         | track 4  |
      | johns example activity 5        | feedback      | john             | true                     | harry                      | 1                         | track 5  |
      | johns example activity 6        | check-in      | john             | true                     | harry                      | 1                         | track 6  |
      | johns example activity 7        | feedback      | john             | true                     | harry                      | 1                         | track 7  |
      | johns annual review (repeating) | check-in      | john             | true                     | harry                      | 3                         | track 8  |
      | davids example activity 1       | check-in      | david            | false                    | john                       | 1                         | track 9  |
      | davids example activity 2       | appraisal     | david            | false                    | john                       | 1                         | track 10 |
      | davids example activity 3       | check-in      | david            | false                    | john                       | 1                         | track 11 |
      | davids example activity 4       | appraisal     | harry            | false                    | john                       | 1                         | track 12 |
      | davids example activity 5       | check-in      | harry            | false                    | john                       | 1                         | track 13 |
      | davids example activity 6       | appraisal     | harry            | false                    | john                       | 1                         | track 14 |
    And the following "subject instances with single user manager-appraiser" exist in "mod_perform" plugin:
      | activity_name                       | subject_username | manager_appraiser_username | track    |
      | Appraiser Manager combined activity | john             | manager-appraiser          | combined |

  Scenario: Can view and filter activities I am a participant in that are about me
    Given I log in as "john"
    When I navigate to the outstanding perform activities list page
    Then I should not see "Exclude completed activities"
    And I should not see "Overdue activities only"

    When I set the field "Type" to "Appraisal"
    Then I should see "3" rows in the tui datatable

    When I set the field "Type" to "Check-in"
    Then I should see "6" rows in the tui datatable

    When I set the field "Type" to "Feedback"
    Then I should see "2" rows in the tui datatable

    When I set the field "Type" to "All"
    And I set the field "Your progress" to "Complete"
    Then I should see "No matching items found."

    When I set the field "Your progress" to "All"
    And I click on "johns example activity 1" "link"
    Then I should see "johns example activity 1" in the ".tui-pageHeading__title" "css_element"
    And I should see perform "short text" question "Question one" is unanswered
    And I should see perform "short text" question "Question two" is unanswered

    When I answer "short text" question "Question one" with "My first answer"
    And I answer "short text" question "Question two" with "My second answer"
    Then I should see "Question one" has no validation errors
    And I should see "Question two" has no validation errors

    When I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Performance activities"
    And I should see "Section submitted" in the tui success notification toast
    And the "Activities about you" tui tab should be active
    And I should see "Exclude completed activities"

    When I set the field "Your progress" to "Complete"
    Then I should see "1" rows in the tui datatable

    When I set the field "Your progress" to "All"
    And I click on "Exclude completed activities" tui "toggle_switch"
    Then I should see "10" rows in the tui datatable

    When I set the field "Search by activity" to "johns annual review"
    Then I should see "3" rows in the tui datatable

    When I set the field "Sort by" to "Activity"
    Then I should see "3" rows in the tui datatable

    When Subject instances for "track 1" track are due "##yesterday##"
    And I reload the page
    And I set the field "Search by activity" to ""
    And I click on "Exclude completed activities" tui "toggle_switch"
    Then I should see "Overdue activities only"
    When I click on "Overdue activities only" tui "toggle_switch"
    Then I should see "1" rows in the tui datatable

  Scenario: Filters persist after page reload
    Given I log in as "john"
    When I navigate to the outstanding perform activities list page
    And the "Activities about you" tui tab should be active
    When Subject instances for "track 1" track are due "##yesterday##"
    And I reload the page
    And I set the field "Sort by" to "Activity"
    And I set the field "Type" to "Check-in"
    And I set the field "Your progress" to "Not started"
    And I set the field "Search by activity" to "johns example"
    And I click on "Overdue activities only" tui "toggle_switch"
    And I reload the page
    Then the field "Sort by" matches value "Activity"
    And the field "Type" matches value "Check-in"
    And the field "Your progress" matches value "Not started"
    And the field "Search by activity" matches value "johns example"
    And the "Overdue activities only" tui toggle switch should be "on"
    # If there are no longer any overdue activities but the overdue only toggle is still enabled, then it should be disabled
    When Subject instances for "track 1" track are due "##tomorrow##"
    And I reload the page
    Then I should not see "Overdue activities only"
    And I should see "3" rows in the tui datatable

  Scenario: Can see completed filter in activities I have multiple roles in
    Given I log in as "manager-appraiser"
    When I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    Then I should see "Appraiser Manager combined activity" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    And I should not see "Exclude completed activities"
    And I should not see "Overdue activities only"
    When I set the field "Your progress" to "Complete"
    Then I should see "No matching items found."

    When I set the field "Your progress" to "All"
    And I click on "Appraiser Manager combined activity" "link" in the ".tui-dataTableCell__content" "css_element"
    And I answer "short text" question "Question one" with "My first answer as manager"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Performance activities"
    When I click on "As Manager" "link"
    Then I should see "Exclude completed activities"
    And I should not see "Overdue activities only"
    When I click on "Exclude completed activities" tui "toggle_switch"
    Then I should see "No matching items found."
    When I click on "Exclude completed activities" tui "toggle_switch"
    And I set the field "Your progress" to "Complete"
    Then I should see "1" rows in the tui datatable

    When I click on "As Appraiser" "link"
    Then I should not see "Exclude completed activities"
    And I should not see "Overdue activities only"
    When I set the field "Your progress" to "Complete"
    Then I should see "No matching items found."
    When I set the field "Your progress" to "All"
    And I click on "Appraiser Manager combined activity" "link" in the ".tui-dataTableCell__content" "css_element"
    And I answer "short text" question "Question one" with "My first answer as appraiser"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Performance activities"
    When I click on "As Appraiser" "link"
    Then I should see "Exclude completed activities"
    And I should not see "Overdue activities only"
    When I click on "Exclude completed activities" tui "toggle_switch"
    Then I should see "No matching items found."
    When I click on "Exclude completed activities" tui "toggle_switch"
    And I set the field "Your progress" to "Complete"
    Then I should see "1" rows in the tui datatable

    When Subject instances for "combined" track are due "##yesterday##"
    And I navigate to the outstanding perform activities list page
    Then I should not see "Overdue activities only"
    When I click on "As Manager" "link"
    Then I should see "Overdue activities only"
    When I click on "As Appraiser" "link"
    Then I should see "Overdue activities only"