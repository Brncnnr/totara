@totara @perform @mod_perform @javascript @vuejs
Feature: Activity general information supports multi-lang filters

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | john     | John      | One      | john.one@example.com |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name                 | subject_username | subject_is_participating |
      | John is participating subject | john             | true                     |
    And the multi-language content filter is enabled

  Scenario: Creating a new activity with multi lang text
    Given I navigate to the manage perform activities page
    And I click on "Add activity" "button"
    And I set the following fields to these values:
      | Title       | <span lang="en" class="multilang">it's an English title</span><span lang="de" class="multilang">deutscher Titel</span>              |
      | Description | <span lang="en" class="multilang">it's an English description </span><span lang="de" class="multilang">deutsche Beschreibung</span> |
    # Set the 'Type' field to 'Feedback'
    And I set the field with css ".tui-modalContent .tui-formRow:last-child select" to "Feedback"

    When I press "Create"
    Then I should see "it's an English title"
    And I should not see "deutscher Titel"

  Scenario: Update title with multi-lang of an existing activity
    Given I navigate to the manage perform activities page
    And I click on "John is participating subject" "link"
    And I click on "General" "link" in the ".tui-tabs__tabs" "css_element"
    When I set the following fields to these values:
      | Activity title | <span lang="en" class="multilang">title changed & updated</span><span lang="de" class="multilang">Titel geaendert und gespeichert</span>              |
      | Description    | <span lang="en" class="multilang">description changed & updated</span><span lang="de" class="multilang">Beschreibung geaendert und gespeichert</span> |
    And I press "Save changes"
    # Let's change to the content tab first to not see the form anymore
    And I click on "Content" "link" in the ".tui-tabs__tabs" "css_element"
    And I close the tui notification toast
    Then I should see "title changed & updated"
    And I should not see "Titel geaendert und gespeichert"

    # Test the user side of things
    When I log out
    And I log in as "john"
    And I navigate to the outstanding perform activities list page

    Then I should see "title changed & updated" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__title" "css_element"
    And I should see "##today##j F Y##" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-created" "css_element"
    And I should see "Appraisal" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__overview-type" "css_element"
    And I should see "Activity is not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__details-overallProgress" "css_element"
    And I should see "Not started" in the ".tui-dataTableRow:nth-child(1) .tui-performUserActivityListTableItem__progress-status" "css_element"