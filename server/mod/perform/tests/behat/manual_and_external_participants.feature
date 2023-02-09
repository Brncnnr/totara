@totara @perform @mod_perform @javascript @vuejs
Feature: Allow users to select manual (internal and external) participants for an activity and for those users to participate.

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname |
      | subject   | Subject   | User     |
      | manager   | Manager   | User     |
      | appraiser | Appraiser | User     |
      | colleague | Colleague | User     |

    Given the following "organisation" frameworks exist:
      | fullname                    | idnumber | description           |
      | Test organisation framework | FW002    | Framework description |
    And the following "organisation" hierarchy exists:
      | framework | fullname          | idnumber | description             |
      | FW002     | Test Organisation | ORG001   | This is an organisation |

    And the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname  |
      | FW001     | POS001   | Position1 |

    And the following job assignments exist:
      | user      | manager | appraiser | fullname   | organisation | position |
      | subject   | manager | appraiser | Subject JA | ORG001       | POS001   |
      | colleague | manager |           |            | ORG001       |          |
    And the following "cohorts" exist:
      | name | idnumber | description | contextlevel | reference | cohorttype |
      | aud1 | aud1     | Audience 1  | System       | 0         | 1          |
    And the following "cohort members" exist:
      | user      | cohort |
      | subject   | aud1   |
      | colleague | aud1   |
    When I log in as "admin"
    And I navigate to the manage perform activities page

  Scenario: Create an activity and make users select internal and external manual participants making sure those participants can participate in the activity
    # Setup the activity so it can be activated
    When I click on "Add activity" "button"
    And I set the following fields to these values:
      | Title | Act1      |
      | Type  | Appraisal |
    And I click on "Create" "button"
    And I click the add responding participant button
    And I click on the "Subject" tui checkbox
    And I click on the "Manager" tui checkbox
    And I click on the "Manager's manager" tui checkbox
    And I click on the "Appraiser" tui checkbox
    And I click on the "Peer" tui checkbox
    And I click on the "Mentor" tui checkbox
    And I click on the "Reviewer" tui checkbox
    And I click on the "External respondent" tui checkbox
    And I click on "Done" "button"
    And I close the tui notification toast
    # Toggle "View other responses"
    And I click on ".tui-performActivitySectionRelationship:nth-of-type(2) .tui-checkbox__label" "css_element"
    Then I should see "Activity saved" in the tui success notification toast
    And I close the tui notification toast
    And I click on "Edit content elements" "link_or_button"
    And I add a "Text: Short response" activity content element
    When I set the following fields to these values:
      | rawTitle | Question 1 |
    And I save the activity content element
    And I follow "Content (Act1)"
    And I click on "Assignments" "link"
    And I click on "Assign users" "button"
    And I click on "Audience" "link" in the ".tui-dropdown__menu" "css_element"
    And I toggle the adder picker entry with "aud1" for "Audience name"
    And I save my selections and close the adder
    And I close the tui notification toast

    Then I should see "Selection of participants"
    And I should see "Participants for each relationship below must be manually chosen by the selected role."
    And the field with xpath "//select[@aria-label='Peer']" matches value "Subject"
    And the field with xpath "//select[@aria-label='Mentor']" matches value "Subject"
    And the field with xpath "//select[@aria-label='Reviewer']" matches value "Subject"
    And the field with xpath "//select[@aria-label='External respondent']" matches value "Subject"

    When I set the field with xpath "//select[@aria-label='Peer']" to "Subject"
    And I set the field with xpath "//select[@aria-label='Mentor']" to "Manager"
    And I set the field with xpath "//select[@aria-label='Reviewer']" to "Appraiser"
    And I set the field with xpath "//select[@aria-label='External respondent']" to "Manager"
    And I reload the page
    And I click on "Assignments" "link"
    Then the field with xpath "//select[@aria-label='Peer']" matches value "Subject"
    And the field with xpath "//select[@aria-label='Mentor']" matches value "Manager"
    And the field with xpath "//select[@aria-label='Reviewer']" matches value "Appraiser"
    And the field with xpath "//select[@aria-label='External respondent']" matches value "Manager"

    # Activate the activity
    When I click on "Activate" "button"
    And I confirm the tui confirmation modal
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"
    And I run the scheduled task "mod_perform\task\create_manual_participant_progress_task"
    And I log out

    # Subject makes selection (appraiser user for the peer relationship)
    When I log in as "subject"
    And I navigate to the outstanding perform activities list page
    Then I should see "There is 1 activity which requires you to select some of the participants" in the ".tui-actionCard" "css_element"
    And I should see "When you have selected the participants, the activities will begin and become visible to them." in the ".tui-actionCard" "css_element"
    When I click on "Select participants" "link" in the ".tui-actionCard" "css_element"
    Then I should see "Select participants"
    And I should see "Note: None of these activities can start until participants are selected."
    And I should see "Act1 for Subject User"
    And I should see "Created" in the ".tui-performActivityParticipantSelector:nth-child(2) .tui-performActivityParticipantSelector__meta" "css_element"
    And I should see the current date in format "j F Y" in the ".tui-performActivityParticipantSelector:nth-child(2) .tui-performActivityParticipantSelector__meta" "css_element"
    # Subject can select themselves
    And I should see the following options in the tui taglist in the ".tui-formRow" "css_element":
      | Admin User     |
      | Appraiser User |
      | Colleague User |
      | Manager User   |
    And I should see "Peer" in the ".tui-formRow" "css_element"
    When I click on "Save" "button"
    Then I should see "You must select at least one user." in the ".tui-formRow" "css_element"
    When I select from the tui taglist in the ".tui-formRow" "css_element":
      | Appraiser User |
    And I click on "Save" "button"
    Then I should see "The participants have been successfully saved." in the tui success notification toast
    And I should not see "Act1 for Subject User"
    And I should see "You have no remaining participants to select."
    When I click on "Back to all performance activities" "link"
    Then I should not see "Select participants"
    And I should see "There are no activities assigned to you yet"
    And I should not see "Act1"
    And I log out

    # Manager makes selection (colleague for mentor relationship, and external respondent too)
    When I log in as "manager"
    And I navigate to the outstanding perform activities list page
    And I click on "Select participants" "link" in the ".tui-actionCard" "css_element"

    # Named job assignment details at subject instance creation.
    And I should see "Subject JA" in the ".tui-performActivityParticipantSelector:nth-child(2) .tui-jobAssignment .tui-jobAssignment__jobAssignmentDetails:nth-of-type(1)" "css_element"
    And I should see "(Position1)" in the ".tui-performActivityParticipantSelector:nth-child(2) .tui-jobAssignment .tui-jobAssignment__jobAssignmentDetails:nth-of-type(1)" "css_element"
    And I should see "Test Organisation" in the ".tui-performActivityParticipantSelector:nth-child(2) .tui-jobAssignment .tui-jobAssignment__jobAssignmentDetails:nth-of-type(1)" "css_element"

    # Unnamed job assignment details at subject instance creation.
    Then I should see "Unnamed job assignment" in the ".tui-performActivityParticipantSelector:nth-child(3) .tui-jobAssignment .tui-jobAssignment__jobAssignmentDetails:nth-of-type(1)" "css_element"
    And I should see "(ID: 1)" in the ".tui-performActivityParticipantSelector:nth-child(3) .tui-jobAssignment .tui-jobAssignment__jobAssignmentDetails:nth-of-type(1)" "css_element"
    And I should see "Test Organisation" in the ".tui-performActivityParticipantSelector:nth-child(3) .tui-jobAssignment .tui-jobAssignment__jobAssignmentDetails:nth-of-type(1)" "css_element"

    Then I should see "Mentor" in the ".tui-formRow:nth-child(1)" "css_element"
    And I should see "External respondent" in the ".tui-formRow:nth-child(2)" "css_element"
    And I should see the following options in the tui taglist in the ".tui-formRow:nth-child(1)" "css_element":
      | Admin User     |
      | Appraiser User |
      | Colleague User |
      | Manager User   |
    When I select from the tui taglist in the ".tui-formRow:nth-child(1)" "css_element":
      | Colleague User |
    And I click on "Save" "button"

    # Set external participant
    Then I should see "Required" in the ".tui-formRow:nth-child(2)" "css_element"
    When I click on "Add" "button"
    And I set the following fields to these values:
      | External respondent 1's name          | Mark Metcalfe       |
      | External respondent 1's email address | example@example.com |
      | External respondent 2's name          | Steve Example       |
      | External respondent 2's email address | example@example.com |
    And I click on "Save" "button"
    Then I should see "Please enter a different email address" in the ".tui-formRow:nth-child(2)" "css_element"
    When I set the following fields to these values:
      | External respondent 1's email address | mark.metcalfe@totaralearning.com |
    And I click on "Save" "button"

    Then I should see "The participants have been successfully saved." in the tui success notification toast
    When I click on "Back to all performance activities" "link"
    Then I should see "Select participants"
    And I should not see "As Manager"
    And I should not see "Act1"
    And I log out

    # Appraiser makes selection (manager for reviewer relationship)
    When I log in as "appraiser"
    And I navigate to the outstanding perform activities list page
    And I click on "Select participants" "link" in the ".tui-actionCard" "css_element"
    Then I should see "Reviewer" in the ".tui-formRow" "css_element"
    And I should see the following options in the tui taglist in the ".tui-formRow" "css_element":
      | Admin User     |
      | Appraiser User |
      | Colleague User |
      | Manager User   |
    When I select from the tui taglist in the ".tui-formRow" "css_element":
      | Manager User |
    And I click on "Save" "button"
    And I click on "Back to all performance activities" "link"
    Then I should not see "Select participants"

    # Appraiser was the last person that needed to make a selection, so participant instances should exist now
    When I click on "As Appraiser" "link"
    Then I should see "Act1"

    When I click on "Act1" "link"
    Then I should see perform activity relationship to user "Appraiser"
    When I press the "back" button in the browser
    And I click on "As Peer" "link"
    And I click on "Act1" "link"
    Then I should see perform activity relationship to user "Peer"
    And I log out

    # Subject views activity
    When I log in as "subject"
    And I navigate to the outstanding perform activities list page
    And I should not see "There are no activities assigned to you yet"
    When I click on "Act1" "link"
    Then I should see perform activity relationship to user "yourself"
    And I log out

    # Colleague views activity
    When I log in as "colleague"
    And I navigate to the outstanding perform activities list page
    And I click on "As Mentor" "link"
    And I should not see "There are no activities assigned to you yet"
    When I click on "Act1" "link"
    Then I should see perform activity relationship to user "Mentor"
    And I log out

    # Manager views activity
    When I log in as "manager"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    And I should not see "You have not been added as a participant in someone else's activity yet"

    When I click on "Act1" "link"
    Then I should see perform activity relationship to user "Manager"
    When I press the "back" button in the browser
    And I click on "As Reviewer" "link"
    When I click on "Act1" "link"
    Then I should see perform activity relationship to user "Reviewer"
    When I log out

    # External participant views activity
    When I navigate to the external participants form for user "Mark Metcalfe"
    # The menu should not be there
    Then I should not see "Home" in the ".totaraNav" "css_element"
    And I should not see "You are logged in as"
    And "Login" "button" should not exist
    And I should see perform activity relationship to user "External respondent" as an "external" participant
    And I should see perform "short text" question "Question 1" is unanswered
    When I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Question 1" with "External participant was here"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Thank you"
    And I should see "Your responses have been submitted"
    And "Review your responses." "link" should exist

    # Try to access the page again, it should still be accessible
    When I follow "Review your responses."
    Then I should see perform activity relationship to user "External respondent" as an "external" participant

    # Try to access invalid page
    When I navigate to the external participants form with the wrong token
    Then I should see "This performance activity is no longer available."

    When I log in as "manager"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    And I click on "Act1" "link"
    Then I should see that show others responses is toggled "off"
    And I answer "short text" question "Question 1" with "My Answer one"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I click on "As Manager" "link"
    And I click on "Act1" "link"
    And I wait until ".tui-otherParticipantResponses" "css_element" exists
    Then I should see that show others responses is toggled "off"
    Then I should see "Mark Metcalfe"
    And I should see perform "short text" question "Question 1" is answered by "External respondent" with "External participant was here"
    When I log out

    # Change setting of activity to close on completion
    Given I log in as "admin"
    When I navigate to the manage perform activities page
    And I click on "Act1" "link"
    And I click on the "On completion" tui toggle button
    And I confirm the tui confirmation modal
    Then I should see "Activity saved" in the tui success notification toast
    When I close the tui notification toast
    And I log out

    # Complete as appraiser and peer
    When I log in as "appraiser"
    And I navigate to the outstanding perform activities list page
    And I click on "As Appraiser" "link"
    And I click on "Act1" "link"
    Then I should see perform activity relationship to user "Appraiser"
    When I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Question 1" with "Appraiser was here"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I click on "As Peer" "link"
    And I click on "Act1" "link"
    Then I should see perform activity relationship to user "Peer"
    When I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Question 1" with "Peer was here"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I log out

    # Complete as subject
    When I log in as "subject"
    And I navigate to the outstanding perform activities list page
    And I should not see "There are no activities assigned to you yet"
    When I click on "Act1" "link"
    Then I should see perform activity relationship to user "yourself"
    When I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Question 1" with "Subject was here"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I log out

    # Complete as colleague
    When I log in as "colleague"
    And I navigate to the outstanding perform activities list page
    And I click on "As Mentor" "link"
    And I should not see "You have not been added as a participant in someone else's activity yet"
    When I click on "Act1" "link"
    Then I should see perform activity relationship to user "Mentor"
    When I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Question 1" with "Mentor was here"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I log out

    # Complete as the maanager and the reviewer
    When I log in as "manager"
    And I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    And I should not see "You have not been added as a participant in someone else's activity yet"
    When I click on "Act1" "link"
    Then I should see perform activity relationship to user "Manager"
    When I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Question 1" with "Manager was here"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    When I click on "As Reviewer" "link"
    And I click on "Act1" "link"
    Then I should see perform activity relationship to user "Reviewer"
    When I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Question 1" with "Reviewer was here"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    And I close the tui notification toast
    And I log out

    # Complete as external respondent again
    When I navigate to the external participants form for user "Mark Metcalfe"
    Then I should see perform activity relationship to user "External respondent" as an "external" participant
    And I should see perform "short text" question "Question 1" is unanswered
    When I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Question 1" with "External participant 1 was here"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Thank you"
    And I should see "Your responses have been submitted"
    And "Review your responses." "link" should exist

    # Alright that's the last participant, subject instance should be closed after that
    When I navigate to the external participants form for user "Steve Example"
    Then I should see perform activity relationship to user "External respondent" as an "external" participant
    # Make sure the navigate back link is not displayed for external participants
    And I should not see "Performance activities"
    And I should see perform "short text" question "Question 1" is unanswered
    When I wait until ".tui-performElementParticipantFormContent .tui-formField" "css_element" exists
    And I answer "short text" question "Question 1" with "External participant 2 was here"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Thank you"
    And I should see "Your responses have been submitted"
    And "Review your responses." "link" should not exist
    And I should see "This activity is now closed."

    When I navigate to the external participants form for user "Steve Example"
    Then I should see "This performance activity is no longer available."

    When I navigate to the external participants form for user "Mark Metcalfe"
    Then I should see "This performance activity is no longer available."

    # Check view-only report view of the completed and closed activity
    When I log in as "admin"
    And I navigate to the view only report view of performance activity "Act1" where "subject" is the subject
    Then I should see the "Responses by relationship" tui select filter has the following options "All, Subject, Manager, Manager's manager, Appraiser, Peer, Mentor, Reviewer, External respondent"

    Then I should see perform "short text" question "Question 1" is answered by "Subject" with "Subject was here"
    Then I should see perform "short text" question "Question 1" is answered by "Manager" with "Manager was here"
    # More accurately "No participants identified for manager's manager"
    Then I should see perform "short text" question "Question 1" is unanswered by "Manager's manager"
    Then I should see perform "short text" question "Question 1" is answered by "Appraiser" with "Appraiser was here"
    Then I should see perform "short text" question "Question 1" is answered by "Peer" with "Peer was here"
    Then I should see perform "short text" question "Question 1" is answered by "Mentor" with "Mentor was here"
    Then I should see perform "short text" question "Question 1" is answered by "Reviewer" with "Reviewer was here"
    And I should see "External participant 1 was here"
    And I should see "External participant 2 was here"

    When I choose "Manager's manager" in the "Responses by relationship" tui select filter
    Then I should see "No participants identified"

    When I choose "External respondent" in the "Responses by relationship" tui select filter
    And I should see "External participant 1 was here"
    And I should see "External participant 2 was here"
