@totara @perform @mod_perform @javascript @vuejs
Feature: Manually add participants as a manager

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                   |
      | john     | John      | One      | john.one@example.com    |
      | harry    | Harry     | Three    | harry.three@example.com |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name         | subject_username | subject_is_participating | other_participant_username |
      | Subject and manager   | john             | true                     | harry                      |
      | Subject only activity | john             | true                     |                            |
    And I log in as "admin"

  Scenario: A manager can manually add participants to a subject instance
    When I navigate to the perform manage participation subject instances report for activity "Subject and manager"
    And I click on "Actions" "button"
    And I click on "Add participants" "link"
    Then I should see "Back to manage participation"

    # Test back navigation
    When I click on "Back to manage participation" "link"
    And I click on "Actions" "button"
    And I click on "Add participants" "link"
    Then I should see "Back to manage participation"
    When I click on "Cancel" "link"
    And I click on "Actions" "button"
    And I click on "Add participants" "link"

    # Correct stuff is displayed
    Then I should see "Back to manage participation"
    And I should see "Add participants"
    And I should see "Subject and manager for John One"
    And I should not see "Created" in the ".tui-performActivityParticipantSelector" "css_element"
    And I should see "Manager" in the ".tui-formRow" "css_element"
    And I should see the following options in the tui taglist in the ".tui-formRow" "css_element":
      | Admin User  |
      | Harry Three |

    # Validation error if no users are selected
    When I click on "Save" "button"
    Then I should see "You must select at least one user in any of the relationships to create additional participant instances."

    # Actually make a selection
    When I select from the tui taglist in the ".tui-formRow" "css_element":
      | Admin User |
    And I click on "Save" "button"
    Then I should see "Confirm create participant instances" in the tui modal
    And I should see "1 additional participant instance will be generated" in the ".tui-modalContent" "css_element"
    When I close the tui modal
    And I click on "Save" "button"
    Then I should see "Confirm create participant instances" in the tui modal
    When I click on "Create" "button"
    Then I should see "Manage participation: “Subject and manager”"
    And I should see "1 participant instance created" in the tui success notification toast

    # Admin user can now participate in the activity
    When I navigate to the outstanding perform activities list page
    And I click on "As Manager" "link"
    Then I should see "Subject and manager" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"

  Scenario: No participants can be manually added when subject is the only relationship
    When I navigate to the perform manage participation subject instances report for activity "Subject only activity"
    And I click on "Actions" "button"
    And I click on "Add participants" "link"
    Then I should see "Subject only activity for John One"
    And I should see "There are no relationships available for adding additional participants to this subject instance."
    And I should not see "Save"
    And I should not see "Cancel"
