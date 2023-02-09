@mod @mod_assign @totara @editor @editor_weka @weka @vuejs @javascript
Feature: Assignment activity works as expected when Weka is the default editor
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @totara_reportbuilder
  Scenario: JSON content is rendered correctly for assignments
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | {"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","marks":[{"type":"link","attrs":{"href":"https://www.totaralearning.com/products"}}],"text":"Test JSON"}]}]} |
      | assignsubmission_onlinetext_enabled | 1 |
      | assignsubmission_file_enabled | 0 |
    And I log out
    And I log in as "student1"
    Given I open my profile in edit mode
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"
    And I set the field "Text editor" to "Weka"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    Then "Test JSON" "link" should exist
    But I should not see "https://www.totaralearning.com/products"
    And I should not see "paragraph"
    When I press "Add submission"
    And I activate the weka editor with css "#uid-1"
    And I type "I'm the student's first submission" in the weka editor
    And I select the text "first submission" in the weka editor
    And I click on the "Link" toolbar button in the weka editor
    And I set the field "URL" to "https://help.totaralearning.com/"
    And I click on "Done" "button" in the ".tui-modal" "css_element"
    And I press "Save changes"
    Then I should see "Submitted"
    And "first submission" "link" should exist
    But I should not see "https://help.totaralearning.com/"
    And I should not see "paragraph"
    And I log out
    And I log in as "teacher1"
    Given I open my profile in edit mode
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"
    And I set the field "Text editor" to "Weka"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    Then I navigate to "View all submissions" in current page administration
    And I click on "Grade" "link" in the "Student 1" "table_row"
    And I set the field "Grade out of 100" to "64"
    When I activate the weka editor with css "#uid-1"
    And I type "See these comments" in the weka editor
    And I select the text "these comments" in the weka editor
    And I click on the "Link" toolbar button in the weka editor
    And I set the field "URL" to "https://test.totaralms.com/exttests/test.jpg"
    And I click on "Done" "button" in the ".tui-modal" "css_element"
    And I press "Save changes"
    And I press "Ok"
    And I am on "Course 1" course homepage
    And I log out
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "search_input" to "Assignment submissions"
    And I click on "button.tw-selectSearchText__btn" "css_element"
    And I click on "div[data-tw-grid-item-id=\"assign-source\"]" "css_element"
    And I wait for pending js
    And I press "Create and edit"
    And I switch to "Columns" tab
    And I add the "Assignment intro" column to the report
    And I add the "Feedback comment" column to the report
    And I press "Save changes"
    When I click on "View This Report" "link"
    Then "Test JSON" "link" should exist
    But I should not see "https://www.totaralearning.com/products"
    And I should not see "paragraph"
    And "these comments" "link" should exist
    But I should not see "https://test.totaralms.com/exttests/test.jpg"

  Scenario: Check word limit and word count on weka editor
    And the following "activities" exist:
      | activity | name        | course | idnumber |
      | assign   | Test assign | C1     | AS516N   |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test assign"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | File submissions | 0 |
      | Online text      | 1 |
      | id_assignsubmission_onlinetext_wordlimit_enabled |  1 |
      | id_assignsubmission_onlinetext_wordlimit         | 25 |
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"
    And I set the field "Text editor" to "Weka editor"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I follow "Test assign"
    And I press "Add submission"
    When I activate the weka editor with css ".tui-weka"
    And I type "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean tempor sed metus quis porta. Sed volutpat arcu eget nibh ultricies ultricies. Sed ac ligula enim." in the weka editor
    And I select the text "Lorem ipsum" in the weka editor
    And I click on the "Link" toolbar button in the weka editor
    And I set the field "URL" to "https://help.totaralearning.com/"
    And I click on "Done" "button" in the ".tui-modal" "css_element"
    And I press "Save changes"
    Then I should not see "The word limit for this assignment is 25 words"
    And I should see "(25 words)"
    And I should see "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean tempor sed metus quis porta. Sed volutpat arcu eget nibh ultricies ..."
    When I click on "[title='View full']" "css_element"
    Then I should not see "(25 words)"
    And I should see "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean tempor sed metus quis porta. Sed volutpat arcu eget nibh ultricies ultricies. Sed ac ligula enim."
