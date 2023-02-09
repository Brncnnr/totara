@totara @totara_core @mod_perform @perform @javascript
Feature: Learning evidence activity
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "cohorts" exist:
      | idnumber | fullname |
      | ch1      | cohort 1 |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | ch1    |
      | user2 | ch1    |
      | user3 | ch1    |
    And the following job assignments exist:
      | user  | manager |
      | user1 | user2   |
      | user2 | user3   |
    And the following "programs" exist in "totara_program" plugin:
      | fullname  | shortname |
      | Program 1 | program1  |
    And the following "program assignments" exist in "totara_program" plugin:
      | user  | program  |
      | user1 | program1 |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname      | shortname |
      | Certificate 1 | cert1     |
    And the following "program assignments" exist in "totara_program" plugin:
      | user  | program |
      | user1 | cert1   |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
      | Course 2 | C2        | 1                |
      | Course 3 | C3        | 1                |
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name  | description          | activity_type | create_track | create_section | activity_status | anonymous_responses |
      | First Activity | My First description | check-in      | true         | false          | Draft           | true                |
    And the following "activity settings" exist in "mod_perform" plugin:
      | activity_name  | multisection |
      | First Activity | yes          |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name  | section_name |
      | First Activity | section 1-1  |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship | can_view | can_answer |
      | section 1-1  | subject      | yes      | no         |
      | section 1-1  | manager      | yes      | yes        |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | C3     | student |

    And I log in as "admin"

    And I am on "Program 1" program homepage
    And I press "Edit program details"
    And I switch to "Content" tab
    And I set the following fields to these values:
      | contenttype_ce | Set of courses |
    And I press "Add"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I click on "Save all changes" "button"

    And I am on "Certificate 1" program homepage
    And I press "Edit certification details"
    And I switch to "Content" tab
    And I set the following fields to these values:
      | contenttype_ce | Set of courses |
    And I press "Add"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Use the existing certification content" to "1"
    And I click on "Save changes" "button"
    And I click on "Save all changes" "button"

    When I navigate to the edit perform activities page for activity "First Activity"
    And I click on "Edit content elements" "link_or_button"
    And I add a "Review items" activity content element

    And I set the following fields to these values:
      | content_type  | Learning                             |
      | Question text | What learning do you want to present |
    And I click on the "Subject" tui radio
    And I click on "Save" "button"
    And I click on "Content (First Activity)" "link"
    And I switch to "Assignments" tui tab
    And I click on "Assign users" "button"
    And I click on "Audience" "link"
    And I click on ".tui-checkbox__label" "css_element" in the tui datatable row with "Cohort 1" "Audience name" in the ".tui-modalContent" "css_element"
    And I click on "Add" "button" in the ".tui-modal" "css_element"
    And I click on "Activate" "button"
    And I click on "Activate" "button" in the ".tui-modal" "css_element"
    And I log out
    And I trigger cron

  Scenario: Create a perform learning activity
    Given I log in as "user1"
    When I click on "Activities" in the totara menu
    And I click on "First Activity" "link"
    And I click on "Add learning" "button"
    And I click on ".tui-checkbox__label" "css_element" in the tui datatable row with "Course 3" "Name" in the ".tui-modalContent" "css_element"
    When I set the field "Learning type" to "Certification"
    And I click on ".tui-checkbox__label" "css_element" in the tui datatable row with "Certificate 1" "Name" in the ".tui-modalContent" "css_element"
    When I set the field "Learning type" to "Program"
    And I click on ".tui-checkbox__label" "css_element" in the tui datatable row with "Program 1" "Name" in the ".tui-modalContent" "css_element"
    When I switch to "Selected" tui tab
    Then I should see "Program 1"
    And I should see "Certificate 1"
    And I should see "Course 3"

    When I click on "Add" "button" in the ".tui-modalContent" "css_element"
    Then I should see "Program 1"
    And I should see "Certificate 1"
    And I should see "Course 3"
    And I click on "Confirm selection" "button"
    And I click on "Close" "button" in the ".tui-participantContent__navigation" "css_element"
    And I log out

    When I log in as "user2"
    And I click on "Activities" in the totara menu
    And I switch to "As Manager" tui tab
    And I click on "First Activity" "link"
    Then I should see "Program 1"
    And I should see "Certificate 1"
    And I should see "Course 3"
    And I navigate to the "print" user activity page for performance activity "First Activity" where "user1" is the subject and "user2" is the participant
    Then "Print" "button" should be visible
    And I should see "Program 1"
    And I should see "Certificate 1"
    And I should see "Course 3"
