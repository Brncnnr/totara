@totara @perform @mod_perform @javascript @vuejs
Feature: Checking access to performance activities in different situations

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname | lastname    | email                   |
      | john        | John      | One         | john.one@example.com    |
      | david       | David     | Two         | david.two@example.com   |
      | harry       | Harry     | Three       | harry.three@example.com |
      | sitemanager | Terry     | Sitemanager | sitemanager@example.com |
    And the following "role assigns" exist:
      | user        | role                       | contextlevel | reference |
      | sitemanager | manager                    | System       |           |
      | harry       | performanceactivitycreator | System       |           |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name                 | subject_username | subject_is_participating | other_participant_username |
      | John is participating subject | john             | true                     | david                      |
      | David is subject              | david            | false                    | john                      |
      | John is not participating     | harry            | true                     | david                      |

  Scenario: As a site manager I can access the manage activity page
    Given I log in as "sitemanager"
    When I navigate to the manage perform activities page
    Then I should see "Manage performance activities"
    And I should see the tui datatable contains:
      | Name                          |
      | John is not participating     |
      | David is subject              |
      | John is participating subject |
    And "Participation reporting" "link" should exist
    # Site managers can create new activities by default
    And I should see "Add activity"
    When I click on "John is participating subject" "link"
    Then I should see "John is participating subject"

  Scenario: As a performance activity creator I can access the manage activities page and see the button
    Given I log in as "harry"
    When I navigate to the manage perform activities page
    Then I should see "Manage performance activities"
    And I should see "No activities have been created yet."
    And I should see "Add activity"
    When I click on "Add activity" "button"
    And I set the following fields to these values:
      | Title          | My Test Activity             |
      | Description    | My Test Activity description |
      | Type           | Feedback                     |
    When I click on "Create" "button"
    Then the "Content" tui tab should be active
    And I should see "My Test Activity"
    When I click on "Back to all performance activities" "link"
    Then I should see "Manage performance activities"
    And I should see the tui datatable contains:
      | Name             |
      | My Test Activity |
