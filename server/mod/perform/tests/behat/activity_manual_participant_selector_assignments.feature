@totara @perform @mod_perform @javascript @vuejs
Feature: Assign manual participant selector roles
  As an activity administrator
  I need to be able to assign manual participant selector roles in individual perform activities

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user      | 1        | user1@example.com |
      | user2    | user      | 2        | user2@example.com |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | aud1     |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | aud1   |
      | user2 | aud1   |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name | activity_type | activity_status | create_track | create_section |
      | AAA           | check-in      | Draft           | false        | false          |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name | section_name |
      | AAA           | section 1    |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship        |
      | section 1    | subject             |
      | section 1    | appraiser           |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name | element_name |
      | section 1    | short_text   |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name | track_description |
      | AAA           | track 1           |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 1           | cohort          | aud1            |

  Scenario: Select manual participant roles.
    When I log in as "admin"
    And I navigate to the manage perform activities page
    Then I should see the tui datatable contains:
      | Name | Type      | Status |
      | AAA  | Check-in  | Draft  |

    When I follow "AAA"
    And I click on "Assignments" "link"
    Then I should see "Selection of participants"
    And I should see "Participants for each relationship below must be manually chosen by the selected role."
    And the field with xpath "//select[@aria-label='Peer']" matches value "Subject"
    And the field with xpath "//select[@aria-label='Mentor']" matches value "Subject"
    And the field with xpath "//select[@aria-label='Reviewer']" matches value "Subject"
    And the field with xpath "//select[@aria-label='External respondent']" matches value "Subject"

    When I set the field with xpath "//select[@aria-label='Peer']" to "Manager"
    And I set the field with xpath "//select[@aria-label='Mentor']" to "Manager's manager"
    And I set the field with xpath "//select[@aria-label='Reviewer']" to "Appraiser"
    And I set the field with xpath "//select[@aria-label='External respondent']" to "Direct report"
    And I navigate to the manage perform activities page
    And I follow "AAA"
    And I click on "Assignments" "link"
    Then the field with xpath "//select[@aria-label='Peer']" matches value "Manager"
    And the field with xpath "//select[@aria-label='Mentor']" matches value "Manager's manager"
    And the field with xpath "//select[@aria-label='Reviewer']" matches value "Appraiser"
    And the field with xpath "//select[@aria-label='External respondent']" matches value "Direct report"

    When I click on "Activate" "button"
    And I confirm the tui confirmation modal
    And I navigate to the manage perform activities page
    Then I should see the tui datatable contains:
      | Name | Type      | Status |
      | AAA  | Check-in  | Active |

    When I follow "AAA"
    And I click on "Assignments" "link"
    Then "//select[@aria-label='Peer']" "xpath_element" should not exist
    And "//select[@aria-label='Mentor']" "xpath_element" should not exist
    And "//select[@aria-label='Reviewer']" "xpath_element" should not exist
    And "//select[@aria-label='External respondent']" "xpath_element" should not exist
