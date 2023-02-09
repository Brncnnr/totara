@totara @perform @mod_perform @javascript @vuejs
Feature: User activity anonymise responses

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname  | email             |
      | subject   | sam       | Subject   | sean@example.com  |
      | manager   | john      | Manager   | manny@example.com |
      | appraiser | kyla      | Appraiser | ava@example.com   |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name                         | subject_username | subject_is_participating | other_participant_username | third_participant_username | relationships_can_view      | relationships_can_answer    | anonymous_responses |
      | Anonymise responses activity          | subject          | true                     | manager                    | appraiser                  | manager, appraiser, subject | manager, appraiser, subject | true                |
      | Anonymise responses view-only subject | subject          | true                     | manager                    | appraiser                  | manager, appraiser, subject | manager, appraiser          | true                |
      | Activity One                          | subject          | true                     | manager                    | appraiser                  | manager, appraiser, subject | manager, appraiser, subject | false               |

  Scenario: I can view anonymise responses
    Given I log in as "subject"
    And I navigate to the outstanding perform activities list page
    And I click on "Anonymise responses activity" "link"
    When I click show others responses
    Then I should not see "Manager response"
    And I should not see "Appraiser response"
    And I should see "Others’ responses"
    And I should see "No response submitted"

  Scenario: manager can submit anonymise responses
    Given I log in as "manager"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    And I click on "Anonymise responses activity" "link"
    And I answer "short text" question "Question one" with "Manager Answer one"
    And I answer "short text" question "Question two" with "Manager Answer two"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I log out
    When I log in as "subject"
    And I navigate to the outstanding perform activities list page
    And I click on "Anonymise responses activity" "link"
    And I answer "short text" question "Question one" with "My Answer one"
    And I answer "short text" question "Question two" with "My Answer two"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I click on "Anonymise responses activity" "link"
    Then I should not see "Manager response"
    And I should not see "Appraiser response"
    And I should see "Others’ responses"
    And I should see "Manager Answer one"
    And I should see "Manager Answer two"

    # Check view-only reporting view
    When I log out
    And I log in as "admin"
    And I navigate to the view only report view of performance activity "Anonymise responses activity" where "subject" is the subject

    Then I should see "All responses anonymised" in the ".tui-participantContent__user" "css_element"
    # The missing ("No response submitted") answers should be last.
    And I should see "Manager Answer one" as answer "any" in the anonymous responses group for question "Question one"
    # No responses from the appraiser and subject, and missing ("No response submitted") answers should be last.
    And I should see "My Answer one" as answer "any" in the anonymous responses group for question "Question one"
    And I should see "No response submitted" as answer "any" in the anonymous responses group for question "Question one"

    And I should see "Manager Answer two" as answer "any" in the anonymous responses group for question "Question two"
    # No responses from the appraiser and subject, and missing ("No response submitted") answers should be last.
    And I should see "My Answer two" as answer "any" in the anonymous responses group for question "Question two"
    And I should see "No response submitted" as answer "any" in the anonymous responses group for question "Question two"

  Scenario: manager can submit anonymise responses and view-only subject can view them
    Given I log in as "manager"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    And I click on "Anonymise responses view-only subject" "link"
    And I answer "short text" question "Question one" with "Manager Answer one"
    And I answer "short text" question "Question two" with "Manager Answer two"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I log out

    When I log in as "subject"
    And I navigate to the outstanding perform activities list page
    And I click on "Anonymise responses view-only subject" "link"
    And I should not see the show others responses toggle
    And I should see "Manager Answer one" as answer "any" in the anonymous responses group for question "Question one"
    # No responses from the appraiser, and missing ("No response submitted") answers should be last.
    And I should see "No response submitted" as answer "any" in the anonymous responses group for question "Question one"

    And I should see "Manager Answer two" as answer "any" in the anonymous responses group for question "Question two"
    # No responses from the appraiser, and missing ("No response submitted") answers should be last.
    And I should see "No response submitted" as answer "any" in the anonymous responses group for question "Question two"

    # Check view-only reporting view
    When I log out
    And I log in as "admin"
    And I navigate to the view only report view of performance activity "Anonymise responses view-only subject" where "subject" is the subject

    Then I should see "All responses anonymised" in the ".tui-participantContent__user" "css_element"
    And I should see "Manager Answer one" as answer "any" in the anonymous responses group for question "Question one"
    # No responses from the appraiser, and missing ("No response submitted") answers should be last.
    And I should see "No response submitted" as answer "any" in the anonymous responses group for question "Question one"

    And I should see "Manager Answer two" as answer "any" in the anonymous responses group for question "Question two"
    # No responses from the appraiser, and missing ("No response submitted") answers should be last.
    And I should see "No response submitted" as answer "any" in the anonymous responses group for question "Question two"
