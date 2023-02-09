@mod @mod_forum @totara @editor @editor_weka @weka @vuejs @javascript
Feature: Forum activity works as expected when Weka is the default editor
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "activities" exist:
      | activity | name       | course | idnumber | displaywordcount |
      | forum    | Test forum | C1     | FRM1     | 1                |

  Scenario: Post a new foruc topic with weka editor
    And I log in as "student1"
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"
    And I set the field "Text editor" to "Weka editor"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I press "Add a new discussion topic"
    And I set the field "Subject" to "Pukapuka"
    When I activate the weka editor with css ".tui-weka"
    And I type "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean tempor sed metus quis porta. Sed volutpat arcu eget nibh ultricies ultricies. Sed ac ligula enim." in the weka editor
    And I select the text "Lorem ipsum" in the weka editor
    And I click on the "Link" toolbar button in the weka editor
    And I set the field "URL" to "https://help.totaralearning.com/"
    And I click on "Done" "button" in the ".tui-modal" "css_element"
    And I press "Post to forum"
    Then I should see "Your post was successfully added"
    And I click on "Pukapuka" "link"
    Then I should see "25 words"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test forum"
    And I click on "Pukapuka" "link"
    And I click on "Edit" "link" in the ".forumpost" "css_element"
    Then I should see "Edited by Teacher 1" in the weka editor
    And I should not see "content" in the weka editor
    And I press "Save changes"
    Then I should see "Edited by Teacher 1" in the ".forumpost" "css_element"
