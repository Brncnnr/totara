@totara @perform @mod_perform @javascript @vuejs
Feature: Viewing other responses

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                   |
      | john     | John      | One      | john.one@example.com    |
      | david    | David     | Two      | david.two@example.com   |
      | harry    | Harry     | Three    | harry.three@example.com |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name                 | subject_username | subject_is_participating | other_participant_username | include_required_questions | include_static_content | activity_status | relationships_can_answer    | update_participant_sections_status |
      | John is participating subject | john             | true                     | david                      | true                       | true                   | Draft           | subject, manager, appraiser | complete                           |
      | John is view-only subject     | john             | true                     | david                      | true                       | true                   | Draft           | manager, appraiser          | complete                           |
      | David is subject              | david            | false                    | admin                      | true                       | true                   | Draft           | subject, manager, appraiser | complete                           |
      | John is not participating     | harry            | true                     | david                      | false                      | true                   | Draft           | subject, manager, appraiser | complete                           |
      | John draft                    | john             | true                     | david                      | true                       | true                   | Draft           | subject, manager, appraiser | draft                              |

  Scenario: I can respond to my activities and view other non-respond activities
    When I log in as "john"
    And I navigate to the outstanding perform activities list page
    And I click on "John is participating subject" "link"
    Then I should see "John is participating subject" in the ".tui-performUserActivity h2" "css_element"
    And I should see "Static content title"
    And I should see "This content is static"
    And I should see that show others responses is toggled "off"
    And I should see perform activity relationship to user "yourself"
    And I should see perform "short text" question "Question one" is unanswered
    And I should see perform "short text" question "Question two" is unanswered
    And I should not see "Manager response"
    And I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Question one" with "John Answer one"
    And I answer "short text" question "Question two" with "John Answer two"

    When I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Performance activities"
    And I should see "Section submitted" in the tui success notification toast
    And the "Activities about you" tui tab should be active

    When I click on "John is participating subject" "link"
    And I wait until ".tui-otherParticipantResponses" "css_element" exists
    Then I should see that show others responses is toggled "on"
    And I should see "Manager response"
    And I should see "No response submitted"
    And I should see "Static content title"
    And I should see "This content is static"

    # Check the view-only report view
    When I log out
    And I log in as "admin"
    And I navigate to the view only report view of performance activity "John is participating subject" where "john" is the subject
    Then I should see perform "short text" question "Question one" is answered by "Subject" with "John Answer one"
    And I should see perform "short text" question "Question one" is unanswered by "Manager"
    And I should see perform "short text" question "Question two" is answered by "Subject" with "John Answer two"
    And I should see perform "short text" question "Question two" is unanswered by "Manager"
    And I should see "Static content title"
    And I should see "This content is static"
    And I should see the "Responses by relationship" tui select filter has the following options "All, Subject, Manager"

    When I choose "Subject" in the "Responses by relationship" tui select filter
    Then I should not see "Manager response"
    And I should see perform "short text" question "Question one" is answered by "Subject" with "John Answer one"
    And I should see perform "short text" question "Question two" is answered by "Subject" with "John Answer two"
    And I should see "Static content title"
    And I should see "This content is static"

    When I choose "Manager" in the "Responses by relationship" tui select filter
    Then I should not see "Subject response"
    And I should see perform "short text" question "Question one" is unanswered by "Manager"
    And I should see perform "short text" question "Question two" is unanswered by "Manager"
    And I should see "Static content title"
    And I should see "This content is static"

  Scenario: Manager can respond to other activities and I can view manager responses
    When I log in as "david"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    Then I should see "John is participating subject" in the ".tui-performUserActivities" "css_element"

    When I click on "John is participating subject" "link"
    Then I should see perform activity relationship to user "Manager"
    And I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Question one" with "Manager Answer one"
    And I answer "short text" question "Question two" with "Manager Answer two"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal

    Then I should see "Performance activities"
    And I should see "Section submitted" in the tui success notification toast
    Then I click on "As Manager" "link"
    And the "As Manager" tui tab should be active

    When I click on "Close" "button"
    And I log out
    And I log in as "john"
    And I navigate to the outstanding perform activities list page
    And I click on "John is participating subject" "link"
    And I answer "short text" question "Question one" with "My Answer one"
    And I answer "short text" question "Question two" with "My  Answer two"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I click on "John is participating subject" "link"
    Then I should see that show others responses is toggled "off"
    And I wait until ".tui-otherParticipantResponses" "css_element" exists
    Then I should see perform "short text" question "Question one" is answered by "Manager" with "Manager Answer one"
    And I should see perform "short text" question "Question two" is answered by "Manager" with "Manager Answer two"

  Scenario: Manager can respond to other activities and view-only participant can view managers responses
    When I log in as "david"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    Then I should see "John is view-only subject" in the ".tui-performUserActivities" "css_element"

    When I click on "John is view-only subject" "link"
    Then I should see perform activity relationship to user "Manager"
    And I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Question one" with "Manager Answer one"
    And I answer "short text" question "Question two" with "Manager Answer two"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal

    Then I should see "Performance activities"
    And I should see "Section submitted" in the tui success notification toast and close it
    And I click on "As Manager" "link"
    And the "As Manager" tui tab should be active

    When I log out
    And I log in as "john"
    And I navigate to the outstanding perform activities list page
    And I click on "John is view-only subject" "link"
    Then I should not see the show others responses toggle

    When I wait until ".tui-otherParticipantResponses" "css_element" exists
    Then I should see perform "short text" question "Question one" is answered by "Manager" with "Manager Answer one"
    And I should see perform "short text" question "Question two" is answered by "Manager" with "Manager Answer two"
    And I should not see "Subject response"

  Scenario: I can see required questions
    When I log in as "john"
    And I navigate to the outstanding perform activities list page
    And I click on "John is participating subject" "link"
    And I should see "John is participating subject" in the ".tui-performUserActivity h2" "css_element"
    Then I should see perform "Question one" question is "required"
    And I should see perform "Question two" question is "required"
    When I click on "Submit" "button"
    Then I should see "Question one" has the validation error "Required"
    And I should see "Question two" has the validation error "Required"

  Scenario: I can see and submit empty optional questions
    When I log in as "harry"
    And I navigate to the outstanding perform activities list page
    And I click on "John is not participating" "link"
    Then I should see perform "Question one" question is "optional"
    And I should see perform "Question two" question is "optional"

    When I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Performance activities"
    And I should see "Section submitted" in the tui success notification toast
    And the "Activities about you" tui tab should be active

  Scenario: I can save as a draft
    When I log in as "john"
    And I navigate to the outstanding perform activities list page
    And I click on "John draft" "link"
    Then I should see perform "Question one" question is "required"
    And I should see perform "Question two" question is "required"
    And I answer "short text" question "Question one" with "John Answer one"
    When I click on "Save as draft" "button"
    Then I should see "Draft saved" in the tui success notification toast
    And I log out
    When I log in as "david"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    And I click on "John draft" "link"
    And I should see that show others responses is toggled "off"
    And I click show others responses
    And I wait until ".tui-otherParticipantResponses" "css_element" exists
    Then I should see "No response submitted"

  Scenario: I can navigate back to activity list by nav link
    When I log in as "john"
    And I navigate to the outstanding perform activities list page
    And I click on "John draft" "link"
    Then I should see "Performance activities"

    When I click on "Performance activities" "link"
    Then I should see "Activities about you"
    And I should see "John draft"
    And I should see "John is view-only subject"
    And I should see "John is participating subject"

