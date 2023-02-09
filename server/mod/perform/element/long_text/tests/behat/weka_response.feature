@totara @perform @mod_perform @perform_element @performelement_long_text @javascript @vuejs @editor_weka @weka @_file_upload
Feature: Long text responses support the Weka editor

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | john     | John      | One      | john.one@example.com |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | aud1     |
    And the following "cohort members" exist:
      | user | cohort |
      | john | aud1   |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name | create_section | create_track | activity_status |
      | activity1     | false          | false        | Draft           |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name | track_description | due_date_offset |
      | activity1     | track1            | 3, DAY          |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track1            | cohort          | aud1            |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name | section_name |
      | activity1     | section1     |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship |
      | section1     | subject      |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name | element_name | title     | is_required |
      | section1     | long_text    | question1 | 1           |

    And I log in as "admin"
    And I navigate to the edit perform activities page for activity "activity1"
    And I click on the "On completion" tui toggle button
    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"
    And I log out

  Scenario: Performance activity long text empty response shows lines
    When I log in as "john"
    And I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"
    And I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I activate the weka editor with css ".tui-performElementParticipantFormContent"
    And I type "123" in the weka editor
    And I click on "Save as draft" "button"
    And I press backspace in the weka editor
    And I press backspace in the weka editor
    And I press backspace in the weka editor
    And I click on "Save as draft" "button"

    And I navigate to the "print" user activity page for performance activity "activity1" where "john" is the subject and "john" is the participant
    And I wait for pending js
    Then I should see perform "long text" question "question1" is unanswered in print view

  Scenario: Performance activity long text response images and files can be uploaded and are displayed correctly
    When I log in as "john"
    And I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"
    And I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists

    And I activate the weka editor with css ".tui-performElementParticipantFormContent"
    And I upload embedded media to the weka editor using the file "mod/perform/tests/behat/fixtures/blue.png"
    And I move the cursor to the end of the weka editor
    And I upload attachment to the weka editor using the file "mod/perform/tests/behat/fixtures/green.png"
    # This is to make sure that the files are all uploaded correctly
    And I wait until ".tui-attachmentNode" "css_element" exists

    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I click on "activity1" "link"

    Then I should see a weka embedded image with the name "blue.png" in the ".tui-participantFormHtmlResponseDisplay" "css_element"
    And I should not see a weka embedded image with the name "green.png" in the ".tui-participantFormHtmlResponseDisplay" "css_element"
    And I should see a weka attachment with the name "green.png" in the ".tui-participantFormHtmlResponseDisplay" "css_element"
    And I should not see a weka attachment with the name "blue.png" in the ".tui-participantFormHtmlResponseDisplay" "css_element"

    When I navigate to the "print" user activity page for performance activity "activity1" where "john" is the subject and "john" is the participant
    And I wait until the page is ready
    Then I should see a weka embedded image with the name "blue.png" in the ".tui-participantContentPrint" "css_element"
    And I should not see a weka embedded image with the name "green.png" in the ".tui-participantContentPrint" "css_element"
    And I should see a weka attachment with the name "green.png" in the ".tui-participantContentPrint" "css_element"
    And I should not see a weka attachment with the name "blue.png" in the ".tui-participantContentPrint" "css_element"

  Scenario: Performance activity long text response files can not be uploaded by external participant users
    Given the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship        |
      | section1     | external respondent |
    And the following "external participants" exist in "mod_perform" plugin:
      | subject | fullname      | email                            |
      | john    | Mark Metcalfe | mark.metcalfe@totaralearning.com |
    When I navigate to the external participants form for user "Mark Metcalfe"
    And I activate the weka editor with css ".tui-performElementParticipantFormContent"
    Then I should not see "Embedded media" in the ".tui-performElementParticipantFormContent" "css_element"
    And I should not see "Attachments" in the ".tui-performElementParticipantFormContent" "css_element"
    When I type "My response!" in the weka editor
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal

  Scenario: Performance activity long text can't submit empty response when required
    When I log in as "john"
    And I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"
    And I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I activate the weka editor with css ".tui-performElementParticipantFormContent"
    And I type "\n\n\n        \n\n       \n\n       " in the weka editor
    And I click on "Submit" "button"
    # The weka front end isn't able to check if the content is only whitespace at the moment,
    # So the form needs to actually be submitted before the validation error is shown (validation is done in the backend)
    # If at some point in the future Weka can do this validation in vue, then the following step can be removed.
    And I confirm the tui confirmation modal
    Then I should see "question1" has the validation error "Question is required"
