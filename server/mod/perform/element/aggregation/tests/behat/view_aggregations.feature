@totara @perform @mod_perform @perform_element @javascript @vuejs
Feature: Respond to sources and view aggregate responses

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname  | email                 |
      | jared     | Jared     | Stanton   | jared@example.com     |
      | tom       | Tom       | Johnson   | tom@example.com       |
      | appraiser | appraiser | appraiser | appraiser@example.com |
    And the following job assignments exist:
      | user  | manager | appraiser |
      | jared | tom     | appraiser |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | aud1     |
    And the following "cohort members" exist:
      | user  | cohort |
      | jared | aud1   |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name        | description                         | activity_type | create_track | create_section | activity_status |
      | Aggregation Activity | We will average and sum some values | check-in      | false        | false          | Active          |
    And the following "activity settings" exist in "mod_perform" plugin:
      | activity_name        | close_on_completion | multisection |
      | Aggregation Activity | yes                 | yes          |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name        | track_description |
      | Aggregation Activity | track 1           |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 1           | cohort          | aud1            |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name        | section_name             |
      | Aggregation Activity | Source section one       |
      | Aggregation Activity | Source section two       |
      | Aggregation Activity | Display aggregation here |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name             | relationship        |
      | Source section one       | subject             |
      | Source section one       | manager             |
      | Source section one       | manager's manager   |
      | Source section two       | external respondent |
      | Display aggregation here | external respondent |
      | Display aggregation here | subject             |
      | Display aggregation here | appraiser           |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name             | element_name         | title                                | data                                                                                                                                   |
      | Source section one       | numeric_rating_scale | On a scale of 1 - 5                  | {"defaultValue":"1", "highValue":"5", "lowValue":"3"}                                                                                  |
      | Source section two       | custom_rating_scale  | Zero or a hundy                      | {"options": [{"name":"option_1","value": {"text":"A hundy","score":"100"}}, {"name":"option_2","value": {"text":"Zero","score":"0"}}]} |
      | Display aggregation here | short_text           | Not needed to be filled short answer | {}                                                                                                                                     |
      | Display aggregation here | aggregation          | Average of previous answers          | {"excludedValues": [], "calculations": ["average"], "sourceSectionElementTitles": ["On a scale of 1 - 5", "Zero or a hundy"]}          |
      | Source section one       | aggregation          | Aggregation with excluded values     | {"excludedValues": [0, 100], "calculations": ["average"], "sourceSectionElementTitles": ["On a scale of 1 - 5", "Zero or a hundy"]}    |
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"
    And I run the scheduled task "mod_perform\task\create_manual_participant_progress_task"
    When I log in as "jared"
    And I navigate to the outstanding perform activities list page
    And I click on "Select participants" "link_or_button"
    And I set the following fields to these values:
      | External respondent 1's name          | Harry Smith       |
      | External respondent 1's email address | harry@example.com |
    And I click on "Save" "button"
    And I log out

  Scenario: I can respond to aggregation sources and view the aggregated responses
    When I navigate to the external participants form for user "Harry Smith"
    And I answer "multi choice single" question "Zero or a hundy" with "A hundy (score: 100)"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I click on "Source section two" "link_or_button"
    Then I should see perform "custom rating scale" question "Zero or a hundy" is answered by the current user with "A hundy (score: 100)"
    And I should not see "Subject response"
    And I should not see "Manager response"
    And I should not see "Appraiser response"

    When I click on "Display aggregation here" "link_or_button"
    Then I should see "Calculations are based on the latest submitted values. Unanswered questions are excluded from calculation." in the ".tui-aggregationParticipantForm" "css_element"
    And I should see "Your response" in the ".tui-aggregationParticipantForm" "css_element"

    Then I should see perform "aggregation" question "Average of previous answers" is answered by the current user with "Average: 100.00"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Subject" with "No response submitted"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Manager" with "No response submitted"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Manager's manager" with "No participants identified"

    When I log in as "appraiser"
    And I navigate to the outstanding perform activities list page
    And I click on "As Appraiser" "link"
    And I click on "Aggregation Activity" "link"
    And I click on "Display aggregation here" "link_or_button"
    # The appraiser is not in any source sections, so they should not get the "Your response" line.
    Then I should not see "Your response" in the ".tui-aggregationParticipantForm" "css_element"

    When I click show others responses
    Then I should see perform "short text" question "Not needed to be filled short answer" is answered by "Subject" with "No response submitted"
    And I should see perform "short text" question "Not needed to be filled short answer" is answered by "External respondent" with "No response submitted"
    # The appraiser is not in any source sections, so they should not get the "Your response" line.
    And I should not see "Your response" in the ".tui-aggregationParticipantForm" "css_element"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Subject" with "No response submitted"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Manager" with "No response submitted"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Manager's manager" with "No participants identified"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "External respondent" with "Average: 100.00"

    When I log out
    And I log in as "jared"
    And I navigate to the outstanding perform activities list page
    And I click on "Aggregation Activity" "link"
    And I click on "Display aggregation here" "link_or_button"
    Then I should see perform "aggregation" question "Average of previous answers" is answered by the current user with "No response submitted"

    When I click on "Source section one" "link_or_button"
    Then I should see "Calculations are based on the latest submitted values. Unanswered questions, and the following values are excluded from calculation: 0, 100" in the ".tui-aggregationParticipantForm" "css_element"

    When I answer "numeric rating scale" question "On a scale of 1 - 5" with "5"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I click on "Source section one" "link_or_button"
    Then I should see perform "numeric rating scale" question "On a scale of 1 - 5" is answered by the current user with "5"
    And I should see perform "numeric rating scale" question "On a scale of 1 - 5" is answered by "Manager" with "No response submitted"
    And I should see perform "numeric rating scale" question "On a scale of 1 - 5" is answered by "Manager's manager" with "No participants identified"
    And I should not see "external respondent"

    When I click on "Display aggregation here" "link_or_button"
    Then I should see "Calculations are based on the latest submitted values. Unanswered questions are excluded from calculation." in the ".tui-aggregationParticipantForm" "css_element"
    And I should see perform "aggregation" question "Average of previous answers" is answered by the current user with "Average: 5.00"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Manager" with "No response submitted"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Manager's manager" with "No participants identified"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "External respondent" with "Average: 100.00"

    When I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I navigate to the "print" user activity page for performance activity "Aggregation Activity" where "jared" is the subject and "jared" is the participant

    Then I should see perform "custom rating scale" question "On a scale of 1 - 5" is answered by the current user with "5"
    And I should see perform "custom rating scale" question "On a scale of 1 - 5" is answered by "Manager" with "No response submitted"
    And I should see perform "custom rating scale" question "On a scale of 1 - 5" is answered by "Manager's manager" with "No participants identified"

    And I should see "Calculations are based on the latest submitted values. Unanswered questions are excluded from calculation."
    And I should see "Calculations are based on the latest submitted values. Unanswered questions, and the following values are excluded from calculation: 0, 100"
    And I should see perform "aggregation" question "Average of previous answers" is answered by the current user with "Average: 5.00"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Manager" with "No response submitted"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Manager's manager" with "No participants identified"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "External respondent" with "Average: 100.00"

    When I log out
    And I log in as "admin"
    And I navigate to the view only report view of performance activity "Aggregation Activity" where "jared" is the subject
    And I click on "Display aggregation here" "link_or_button"
    Then I should see "Calculations are based on the latest submitted values. Unanswered questions are excluded from calculation." in the ".tui-aggregationParticipantForm" "css_element"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Subject" with "Average: 5.00"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Manager" with "No response submitted"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Manager's manager" with "No participants identified"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "External respondent" with "Average: 100.00"
    And I should see the "Responses by relationship" tui select filter has the following options "All, Subject, Manager, Manager's manager, Appraiser, External respondent"

    When I choose "Subject" in the "Responses by relationship" tui select filter
    Then I should see perform "short text" question "Not needed to be filled short answer" is answered by "Subject" with "No response submitted"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Subject" with "Average: 5.00"

    When I choose "Manager" in the "Responses by relationship" tui select filter
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Manager" with "No response submitted"

    When I choose "Appraiser" in the "Responses by relationship" tui select filter
    Then I should see perform "short text" question "Not needed to be filled short answer" is answered by "Appraiser" with "No response submitted"

    When I choose "Manager's manager" in the "Responses by relationship" tui select filter
    And I should see perform "aggregation" question "Average of previous answers" is answered by "Manager's manager" with "No participants identified"

    When I choose "External respondent" in the "Responses by relationship" tui select filter
    Then I should see perform "short text" question "Not needed to be filled short answer" is answered by "External respondent" with "No response submitted"
    And I should see perform "aggregation" question "Average of previous answers" is answered by "External respondent" with "Average: 100.00"

  Scenario: Check aggregation preview in activity response data report
    When I log in as "admin"
    And I navigate to the mod perform response data report for "Aggregation Activity" activity
    Then I click on "Actions" "button" in the "Average of previous answers" "table_row"
    And "Preview question" "button" should exist in the ".tui-dropdown__menu--open" "css_element"
    When I click on "Preview question" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Average of previous answers" in the tui modal
    And I should see "Calculations are based on the latest submitted values. Unanswered questions are excluded from calculation." in the tui modal
