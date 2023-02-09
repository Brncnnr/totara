@totara @perform @mod_perform @perform_element @javascript @vuejs @editor_weka @weka @_file_upload
Feature: View static content

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
    And I log in as "admin"
    And I navigate to the edit perform activities page for activity "activity1"
    And I click on "Edit content elements" "link_or_button"
    And I add a "Static content" activity content element
    And I set the following fields to these values:
      | rawTitle | Static Content |
    And I activate the weka editor with css ".tui-performAdminCustomElement__content"
    And I type "Static content text content" in the weka editor
    And I upload embedded media to the weka editor using the file "mod/perform/tests/behat/fixtures/blue.png"
    And I wait until ".tui-responsiveImage" "css_element" exists
    And I save the activity content element
    And I navigate to the edit perform activities page for activity "activity1"
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name | element_name   | title          | is_required |
      | section1     | short_text     | question1      | 1           |
    And I click on "Activate" tui "button" in the "draft state" tui "action_card"
    And I confirm the tui confirmation modal
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"
    And I log out

  Scenario: Normal users can view images uploaded to a static content element
    When I log in as "john"
    And I navigate to the outstanding perform activities list page
    And I click on "activity1" "link"
    And I wait until the page is ready
    Then I should see "Static content text content" in the ".tui-staticContentElementParticipantForm" "css_element"
    And I should see a weka embedded image with the name "blue.png" in the ".tui-staticContentElementParticipantForm" "css_element"

  Scenario: External participant users can view images uploaded to a static content element
    Given the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship        |
      | section1     | external respondent |
    And the following "external participants" exist in "mod_perform" plugin:
      | subject | fullname      | email                            |
      | john    | Mark Metcalfe | mark.metcalfe@totaralearning.com |
    When I navigate to the external participants form for user "Mark Metcalfe"
    And I wait until the page is ready
    Then I should see "Static content text content" in the ".tui-staticContentElementParticipantForm" "css_element"
    And I should see a weka embedded image with the name "blue.png" in the ".tui-staticContentElementParticipantForm" "css_element"
