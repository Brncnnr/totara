@totara @totara_evidence @perform @mod_perform @javascript
Feature: Perform evidence activity
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
    Given the following "types" exist in "totara_evidence" plugin:
      | name        | idnumber | fields | location |
      | Bank Type 1 | bank1    | 0      | 0        |
      | Bank Type 2 | bank2    | 0      | 0        |
    And the following "type fields" exist in "totara_evidence" plugin:
      | evidence_type | datatype | fullname          | shortname       |
      | Bank Type 1   | text     | Custom text1      | Customtext1     |
      | Bank Type 1   | checkbox | Custom cb11       | Customcb11      |
      | Bank Type 2   | text     | Custom bank text1 | Custombanktext1 |
      | Bank Type 2   | url      | Earl              | CustomURL       |

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
    Given I log in as "admin"
    When I navigate to the edit perform activities page for activity "First Activity"
    And I click on "Edit content elements" "link_or_button"
    And I add a "Review items" activity content element
    And I set the following fields to these values:
      | content_type  | Evidence                             |
      | Question text | What evidence do you want to present |
    And I click on the "Subject" tui radio
    And I click on "Save" "button"
    And I should see "Text input custom field name"
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

  Scenario: Create a perform evidence activity
    Given I log in as "user1"
    And I click on "Evidence bank" in the totara menu
    And I click on "Add evidence item" "link"
    And I click on ".form-autocomplete-downarrow" "css_element" in the ".tw-evidence__select_type_selector_element" "css_element"
    And I click on "Bank Type 1" "text" in the ".form-autocomplete-suggestions" "css_element"
    And I click on "Use this type" "link"
    And I set the following fields to these values:
      | Evidence name | Test evidence one |
      | Custom text1  | one               |
    And I click on "Save evidence item" "button"
    And I click on "Add evidence item" "link"
    And I click on ".form-autocomplete-downarrow" "css_element" in the ".tw-evidence__select_type_selector_element" "css_element"
    And I click on "Bank Type 2" "text" in the ".form-autocomplete-suggestions" "css_element"
    And I click on "Use this type" "link"
    And I set the following fields to these values:
      | Evidence name     | Test evidence two      |
      | Custom bank text1 | Who knows              |
      | URL               | https://www.google.com |
      | Text              | Searching              |
    And I click on "Save evidence item" "button"

    When I click on "Activities" in the totara menu
    And I click on "First Activity" "link"
    And I click on "Add evidence" "button"
    And I click on ".tui-checkbox__label" "css_element" in the tui datatable row with "Test evidence one" "Evidence" in the ".tui-modalContent" "css_element"
    And I click on "Add" "button" in the ".tui-modalContent" "css_element"
    And I click on "Confirm selection" "button"
    And I click on "Close" "button" in the ".tui-participantContent__navigation" "css_element"
    And I log out

    When I log in as "user2"
    And I click on "Activities" in the totara menu
    And I click on "As Manager" "link_or_button"
    And I click on "First Activity" "link"
    Then I should see "Test evidence one"
    And I should see "Custom cb11"
