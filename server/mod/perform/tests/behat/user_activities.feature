@totara @perform @mod_perform @javascript @vuejs
Feature: Viewing and responding to perform activities

  Background:
    Given the following "users" exist:
      | username          | firstname | lastname | email                              |
      | john              | John      | One      | john.one@example.com               |
      | david             | David     | Two      | david.two@example.com              |
      | harry             | Harry     | Three    | harry.three@example.com            |
      | tom               | Tom       | four     | tom.four@example.com               |
      | jerry             | Jerry     | five     | jerry.five@example.com             |
      | manager-appraiser | combined  | Three    | manager-appraiser.four@example.com |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name                 | subject_username | subject_is_participating | other_participant_username |
      | John is participating subject | john             | true                     | david                      |
      | David is subject              | david            | false                    | john                       |
      | John is not participating     | harry            | true                     | david                      |
      | Tom is subject first          | tom              | true                     | jerry                      |
      | Tom is subject second         | tom              | true                     | jerry                      |
      | Tom is subject third          | tom              | true                     | jerry                      |
      | Tom is subject fourth         | tom              | true                     | jerry                      |
      | Tom is subject fifth          | tom              | true                     | jerry                      |
      | Tom is subject sixth          | tom              | true                     | jerry                      |
      | Tom is subject seventh        | tom              | true                     | jerry                      |
      | Tom is subject eigth          | tom              | true                     | jerry                      |
      | Tom is subject ninth          | tom              | true                     | jerry                      |
      | Tom is subject tenth          | tom              | true                     | jerry                      |
    And the following "subject instances with single user manager-appraiser" exist in "mod_perform" plugin:
      | activity_name                 | subject_username | manager_appraiser_username |
      | single user manager-appraiser | john             | manager-appraiser          |

  Scenario: Can view and respond to activities I am a participant in that are about me
    Given I log in as "john"
    When I navigate to the outstanding perform activities list page

    # First row
    Then I should see "single user manager-appraiser" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "John is participating subject" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "John is participating subject" "link"
    Then I should see "John is participating subject" in the ".tui-performUserActivity h2" "css_element"
    And I should see that show others responses is toggled "off"
    And I should see perform "short text" question "Question one" is unanswered
    And I should see perform "short text" question "Question two" is unanswered

    When I answer "short text" question "Question one" with "My first answer"
    And I answer "short text" question "Question two" with "1025" characters
    And I click on "Submit" "button"
    Then I should see "Question two" has the validation error "Please enter no more than 1024 characters"

    When I answer "short text" question "Question two" with "1024" characters
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal

    Then I should see "Performance activities"
    And the "Activities about you" tui tab should be active
    And I should see "Section submitted" in the tui success notification toast
    # First row
    Then I should see "single user manager-appraiser" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "John is participating subject" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Complete" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is in progress" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

  Scenario: Can view and and respond to activities I am a participant in but are not about me
    Given I log in as "john"
    When I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    Then I should see "David is subject" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "David Two" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "David is subject" "link"
    Then I should see "David is subject" in the ".tui-pageHeading__title" "css_element"
    And I should see that show others responses is toggled "off"
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
    And I click on "As Manager" "link"
    And I should see "David is subject" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "David Two" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Complete" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is complete" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

  Scenario: Can view and and respond to activities I have multiple roles in
    Given I log in as "manager-appraiser"
    When I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    Then I should see "single user manager-appraiser" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "John One" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-subject" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "single user manager-appraiser" "link" in the ".tui-dataTableCell__content" "css_element"
    Then I should see "single user manager-appraiser" in the ".tui-performUserActivity h2" "css_element"
    And I should see perform activity relationship to user "Manager"
    And I should see that show others responses is toggled "off"
    And I should see perform "short text" question "Question one" is unanswered

    When I answer "short text" question "Question one" with "My first answer as manager"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Performance activities"
    And I should see "Section submitted" in the tui success notification toast

    When I click on "As Appraiser" "link"
    And I click on "single user manager-appraiser" "link" in the ".tui-dataTableCell__content" "css_element"
    And I should see perform activity relationship to user "Appraiser"
    And I should see that show others responses is toggled "off"
    And I should see perform "short text" question "Question one" is unanswered

    When I answer "short text" question "Question one" with "My first answer as appraiser"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Performance activities"
    And I should see "Section submitted" in the tui success notification toast

    When I click on "As Manager" "link"
    And I click on "single user manager-appraiser" "link" in the ".tui-dataTableCell__content" "css_element"
    Then I should see "single user manager-appraiser" in the ".tui-performUserActivity h2" "css_element"
    And I should see perform activity relationship to user "Manager"
    And I should see that show others responses is toggled "on"
    And I should see "Appraiser response"
    And I should see "My first answer as appraiser"

  Scenario: First access of a section changes both my progress and overall progress to "In Progress"
    Given I log in as "john"
    When I navigate to the outstanding perform activities list page
    # First row
    Then I should see "single user manager-appraiser" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "John is participating subject" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

    When I click on "John is participating subject" "link"
    Then I should see "John is participating subject" in the ".tui-pageHeading__title" "css_element"

    When I navigate to the outstanding perform activities list page
    # First row
    Then I should see "single user manager-appraiser" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    # Second row
    And I should see "John is participating subject" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "In progress" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is in progress" in the ".tui-dataTableRow:nth-child(2) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

  Scenario: Managing participation
    Given I log in as "john"
    When I navigate to the outstanding perform activities list page
    Then I should not see "Manage participation"

    When I log out
    And I log in as "admin"
    And I navigate to the outstanding perform activities list page
    And I click on "Manage participation" "link_or_button"
    Then I should see "Select activity"
    And the following fields match these values:
      | manage-participation-activity-select | John is participating subject |

    When I click on "Continue" "link"
    Then I should see "Manage participation: “John is participating subject”"

  Scenario: View response data
    Given I log in as "john"
    When I navigate to the outstanding perform activities list page
    Then I should not see "View response data"

    Then I log out
    And I log in as "admin"
    When I navigate to the outstanding perform activities list page
    And I click on "View response data" "link_or_button"
    Then I should see "Performance activity response data"

  Scenario: I cannot view activity detail where I am not a participant
    Given I log in as "john"
    And I navigate to the "view" user activity page for performance activity "John is not participating" where "harry" is the subject and "david" is the participant
    Then I should see "The requested performance activity could not be found"
    # Need this to get log out button again
    And I am on homepage
    And I log out

    When I log in as "admin"
    And I navigate to the "view" user activity page for performance activity "John is not participating" where "harry" is the subject and "david" is the participant
    Then I should see "The requested performance activity could not be found"


  Scenario: Can view prioritised activities I am a participant in that are about me
    Given I log in as "john"
    When I navigate to the outstanding perform activities list page
    Then I should see "There are 2 activities for you to complete"

    When I click on ".tui-performUserActivityList__priority:nth-child(1) .tui-actionLink" "css_element"
    Then I should see "John is participating subject" in the ".tui-pageHeading__title" "css_element"
    And I should see perform activity relationship to user "yourself"

    When I answer "short text" question "Question one" with "My first answer"
    And I answer "short text" question "Question two" with "My second answer"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Performance activities"
    And I should see "There are 1 activities for you to complete"

    When I click on ".tui-performUserActivityList__priority:nth-child(1) .tui-actionLink" "css_element"
    Then I should see "single user manager-appraiser" in the ".tui-performUserActivity h2" "css_element"
    And I should see perform activity relationship to user "yourself"

    When I answer "short text" question "Question one" with "My first answer as manager"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Performance activities"
    And I should not see "There are 1 activities for you to complete"


  Scenario: Can view prioritised activities I am a participant in but are not about me
    Given I log in as "john"
    When I navigate to the outstanding perform activities list page
    When I click on "As Manager" "link"
    Then I should see "There are 1 activities for you to complete"

    When I click on ".tui-performUserActivityList__priority:nth-child(1) .tui-actionLink" "css_element"
    Then I should see "David is subject" in the ".tui-pageHeading__title" "css_element"
    And I should see perform activity relationship to user "Manager"

    When I answer "short text" question "Question one" with "My first answer"
    And I answer "short text" question "Question two" with "My second answer"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Performance activities"
    And I should not see "There are 1 activities for you to complete"


  Scenario: Can view all prioritised activities I am a participant in that are about me
    Given I log in as "tom"
    When I navigate to the outstanding perform activities list page
    Then I should see "There are 10 activities for you to complete"
    And I should see "View all 10" in the ".tui-performUserActivityList__priority" "css_element"
    And I click on ".tui-overflowContainer__containerItem-viewAll" "css_element"
    Then I should see "10 activities to complete as Subject"

    When I click on ".tui-performUserActivitiesPriority__grid .tui-grid-item:nth-child(5) .tui-actionLink" "css_element"
    Then I should see "Tom is subject fifth" in the ".tui-pageHeading__title" "css_element"
    And I should see perform activity relationship to user "yourself"

    When I answer "short text" question "Question one" with "My first answer"
    And I click on "Submit" "button"
    And I confirm the tui confirmation modal
    Then I should see "Performance activities"
    And I should see "There are 9 activities for you to complete"

