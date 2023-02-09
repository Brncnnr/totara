@totara @perform @mod_perform @javascript @vuejs
Feature: Print view of a single-section user activity

  Background:
    Given the following "users" exist:
      | username          | firstname        | lastname | email                               |
      | john              | John             | One      | john.one@example.com                |
      | david             | David            | Two      | david.two@example.com               |
      | manager-appraiser | Managerappraiser | Three    | manager-appraiser.three@example.com |
      | appraiser         | Appraiser        | Four     | appraiser.four@example.com          |
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
      | activity_name           | track_description | due_date_offset |
      | Single section activity | track 1           | 1, DAY          |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 1           | cohort          | aud1            |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name   | element_name         | title                                         | data                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
      | Single section | short_text           | Short text question                           | {}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
      | Single section | long_text            | Long text question                            | {}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
      | Single section | date_picker          | Date picker question                          | {"yearRangeStart": 1900, "yearRangeEnd": 2050}                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
      | Single section | multi_choice_single  | Multi choice single question                  | {"options":[{"name":"option_1","value":"A"},{"name":"option_2","value":"B"}]}                                                                                                                                                                                                                                                                                                                                                                                                                                      |
      | Single section | multi_choice_multi   | Multi choice multi question                   | {"max":"2","min":"0","options":[{"name":"option_1","value":"A"},{"name":"option_2","value":"B"},{"name":"option_3","value":"C"}]}                                                                                                                                                                                                                                                                                                                                                                                  |
      | Single section | custom_rating_scale  | Custom rating scale question                  | {"options":[{"name":"option_1","value":{"text":"A","score":"1"}, "descriptionEnabled":false,"descriptionWekaDoc":{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Unused option description"}]}]}},{"name":"option_2","value":{"text":"B","score":"5"}, "descriptionEnabled":true,"descriptionWekaDoc":{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Choose the letter you like best"}]}]}},{"name":"option_3","value":{"text":"C","score":"10"}}]} |
      | Single section | numeric_rating_scale | Numeric rating scale question no description  | {"defaultValue":"3","highValue":"5","lowValue":"1","descriptionEnabled":false,"descriptionWekaDoc":{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Unused description"}]}]}}                                                                                                                                                                                                                                                                                                        |
      | Single section | numeric_rating_scale | Numeric rating scale question has description | {"defaultValue":"3","highValue":"5","lowValue":"1","descriptionEnabled":true,"descriptionWekaDoc":{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Choose the number you like best"}]}]}}                                                                                                                                                                                                                                                                                            |

    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name   | relationship | can_view | can_answer |
      | Single section | subject      | yes      | yes        |
      | Single section | manager      | no       | yes        |
      | Single section | appraiser    | yes      | no         |
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"

  Scenario: Print view for single section with user having single relationships
    # Check without any response.
    When I log in as "david"
    And I navigate to the "print" user activity page for performance activity "Single section activity" where "david" is the subject and "david" is the participant
    Then "Print" "button" should be visible
    And I should see perform activity relationship to user "yourself"
    And I should see "Appraisal"
    And I should see "Single section activity"
    And I should see "Short text question"
    And I should see "Your response"
    And the ".tui-participantContentPrint" "css_element" should contain the following sentence:
      | Printed on | ##today##j F Y## |
    And the ".tui-participantContentPrint__instanceDetails" "css_element" should contain the following sentence:
      | Created on | ##today##j F Y## |
    And I should see "Overall progress: Not started" in the ".tui-participantContentPrint__instanceDetails" "css_element"
    And I should see "Your progress: Not started" in the ".tui-participantContentPrint__instanceDetails" "css_element"
    And the ".tui-participantContentPrint__instanceDetails" "css_element" should contain the following sentence:
      | Due date: | ##tomorrow##j F Y## |

    # Empty print components should be displayed.
    And I should see perform "short text" question "Short text question" is unanswered in print view
    And I should see perform "long text" question "Long text question" is unanswered in print view
    And I should see perform "date picker" question "Date picker question" is unanswered in print view
    And I should see perform "multi choice single" question "Multi choice single question" is unanswered in print view
    And I should see perform "multi choice multi" question "Multi choice multi question" is unanswered in print view
    And I should see perform "custom rating scale" question "Custom rating scale question" is unanswered in print view
    And I should see perform "numeric rating scale" question "Numeric rating scale question no description" is unanswered in print view
    And I should see perform "numeric rating scale" question "Numeric rating scale question has description" is unanswered in print view
    And I should see "Manager response"
    And I should see "John One"
    And I should see "No response submitted"
    And I should not see "Appraiser response"

    # Add a response as the subject.
    When I navigate to the outstanding perform activities list page
    And I click on "Single section activity" "link"
    And I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Short text question" with "David short text answer one"
    And I answer "long text" question "Long text question" with "David long text answer one"
    And I answer "date picker" question "Date picker question" with "1 January 2020"
    And I answer "multi choice single" question "Multi choice single question" with "B"
    And I answer "multi choice multi" question "Multi choice multi question" with "C"
    And I answer "custom rating scale" question "Custom rating scale question" with "A (score: 1)"
    And I answer "numeric rating scale" question "Numeric rating scale question has description" with "5"
    And I answer "numeric rating scale" question "Numeric rating scale question no description" with "5"

    When I click on "Save as draft" "button"
    Then I should see "Draft saved" in the tui success notification toast

    When I click on "Cancel" "button"
    And I navigate to the "print" user activity page for performance activity "Single section activity" where "david" is the subject and "david" is the participant

    Then the ".tui-participantContentPrint" "css_element" should contain the following sentence:
      | Printed on | ##today##j F Y## |
    And the ".tui-participantContentPrint__instanceDetails" "css_element" should contain the following sentence:
      | Created on | ##today##j F Y## |
    And I should see "Overall progress: In progress" in the ".tui-participantContentPrint__instanceDetails" "css_element"
    And I should see "Your progress: In progress" in the ".tui-participantContentPrint__instanceDetails" "css_element"
    And the ".tui-participantContentPrint__instanceDetails" "css_element" should contain the following sentence:
      | Due date: | ##tomorrow##j F Y## |

    # Filled in, but not "closed" responses should be shown.
    And I should see "David short text answer one" in the ".tui-shortTextParticipantPrint" "css_element"
    And I should see "David long text answer one" in the ".tui-longTextParticipantPrint" "css_element"
    And I should see "1 January 2020" in the ".tui-datePickerParticipantPrint" "css_element"
    And I should see "5" in the ".tui-numericRatingScaleParticipantPrint" "css_element"

    # Some print components simply point to the participant form component
    And I should see perform "multi choice single" question "Multi choice single question" is answered with "B" in print view
    And I should see perform "multi choice multi" question "Multi choice multi question" is answered with "C" in print view
    And I should see perform "custom rating scale" question "Custom rating scale question" is answered with "A (score: 1)" in print view
    And I should see perform "numeric rating scale" question "Numeric rating scale question no description" is answered with "5" in print view
    And I should see perform "numeric rating scale" question "Numeric rating scale question has description" is answered with "5" in print view

    # The enabled numeric scale descriptions should be visible on draft on the activity
    And I should not see "Unused description"
    And I should see "Choose the number you like best"

    When I navigate to the outstanding perform activities list page
    And I click on "Single section activity" "link"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I should see "Section submitted and closed." in the tui success notification toast
    And I navigate to the "print" user activity page for performance activity "Single section activity" where "david" is the subject and "david" is the participant

    Then the ".tui-participantContentPrint" "css_element" should contain the following sentence:
      | Printed on | ##today##j F Y## |
    And the ".tui-participantContentPrint__instanceDetails" "css_element" should contain the following sentence:
      | Created on | ##today##j F Y## |
    And I should see "Overall progress: In progress" in the ".tui-participantContentPrint__instanceDetails" "css_element"
    And I should see "Your progress: Complete" in the ".tui-participantContentPrint__instanceDetails" "css_element"
    And the ".tui-participantContentPrint__instanceDetails" "css_element" should contain the following sentence:
      | Due date: | ##tomorrow##j F Y## |

    # No print components should be displayed any more.
    And ".tui-shortTextParticipantPrint" "css_element" should not exist in the ".tui-participantContentPrint" "css_element"
    And ".tui-longTextParticipantPrint" "css_element" should not exist in the ".tui-participantContentPrint" "css_element"
    And ".tui-datePickerParticipantPrint" "css_element" should not exist in the ".tui-participantContentPrint" "css_element"
    And ".tui-numericRatingScaleParticipantPrint" "css_element" should not exist in the ".tui-participantContentPrint" "css_element"

    # Some components reuse their participant form components as the print component
    And ".tui-staticContentElementParticipantForm" "css_element" should not exist in the ".tui-participantContentPrint" "css_element"
    And ".tui-multiChoiceSingleParticipantForm" "css_element" should not exist in the ".tui-participantContentPrint" "css_element"
    And ".tui-multiChoiceMultiParticipantForm" "css_element" should not exist in the ".tui-participantContentPrint" "css_element"
    And ".tui-customRatingScaleParticipantForm" "css_element" should not exist in the ".tui-participantContentPrint" "css_element"

    # Instead response version of the question element should be shown
    # ...FormResponseDisplay for most components and ...HtmlFormResponseDisplay for long text
    And ".tui-participantFormResponseDisplay" "css_element" should exist in the ".tui-participantContentPrint" "css_element"
    And ".tui-participantFormHtmlResponseDisplay" "css_element" should exist in the ".tui-participantContentPrint" "css_element"

    # No numeric scale descriptions should be visible on the closed activity
    And I should not see "Unused description"
    And I should not see "Choose the number you like best"

    And I should see "David short text answer one"
    And I should see "David long text answer one"
    And I should see "1 January 2020"
    And I should see "Manager response"
    And I should see "John One"
    And I should see "No response submitted"
    And I should see "B"
    And I should see "C"
    And I should see "A (score: 1)"

    # Check manager's view (response only, can't see other's responses)
    When I am on homepage
    And I log out
    And I log in as "john"
    And I navigate to the "print" user activity page for performance activity "Single section activity" where "david" is the subject and "john" is the participant
    Then I should see perform activity relationship to user "Manager"
    And I should not see "David short text answer one"
    And I should see "Your response"

    # Empty print components should be displayed.
    And I should see perform "short text" question "Short text question" is unanswered in print view
    And I should see perform "long text" question "Long text question" is unanswered in print view
    And I should see perform "date picker" question "Date picker question" is unanswered in print view
    And I should see perform "multi choice single" question "Multi choice multi question" is unanswered in print view
    And I should see perform "multi choice multi" question "Multi choice single question" is unanswered in print view
    And I should see perform "custom rating scale" question "Custom rating scale question" is unanswered in print view
    And I should see perform "numeric rating scale" question "Numeric rating scale question no description" is unanswered in print view
    And I should see perform "numeric rating scale" question "Numeric rating scale question has description" is unanswered in print view

    # Whe should see the numeric and custom scale scale descriptions
    And I should not see "Unused option description"
    And I should see "Choose the letter you like best"
    And I should not see "Unused description"
    And I should see "Choose the number you like best"

    And I should not see "Appraiser Four"

    # Check appraiser's view (view only)
    When I am on homepage
    And I log out
    And I log in as "appraiser"
    And I navigate to the "print" user activity page for performance activity "Single section activity" where "david" is the subject and "appraiser" is the participant
    Then I should see perform activity relationship to user "Appraiser"
    And I should not see "Your response"
    And I should see "David short text answer one"
    And I should see "David long text answer one"
    # Date picker
    And I should see "1 January 2020"
    And I should see "Manager response"
    And I should see "John One"
    And I should see "No response submitted"
    # Multi - single
    And I should see "B"
    # Multi - multi
    And I should see "C"
    # Custom rating
    And I should see "A (score: 1)"

    # The descriptions should no longer visible on the closed activity.
    And I should not see "Unused option description"
    And I should not see "Choose the letter you like best"
    And I should not see "Unused description"
    And I should not see "Choose the number you like best"

  Scenario: Print view for single section with user having multiple relationships
    # Add a response as the subject.
    When I log in as "john"
    And I navigate to the outstanding perform activities list page
    And I click on "Single section activity" "link"
    And I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Short text question" with "John answer one"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I should see "Section submitted and closed." in the tui success notification toast
    And I log out

    # Check manager-appraiser's view.
    When I log in as "manager-appraiser"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    And I click on "Actions" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    And I click on "Print activity" "link" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Your response"

    # Empty print components should be displayed.
    And I should see perform "short text" question "Short text question" is unanswered in print view
    And I should see perform "long text" question "Long text question" is unanswered in print view
    And I should not see "Subject response"
    And I should not see "Appraiser response"

    # Check as appraiser (view-only).
    When I navigate to the outstanding perform activities list page
    And I click on "As Appraiser" "link"
    And I click on "Actions" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    And I click on "Print activity" "link" in the ".tui-dropdown__menu--open" "css_element"
    Then I should not see "Your response"
    And I should see "Manager response"
    And I should see "Managerappraiser Three"
    And I should see "No response submitted"