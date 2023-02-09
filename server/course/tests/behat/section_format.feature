@core @totara @core_course @editor @editor_weka @javascript
Feature: Course sections summary format

  Background: Set up a course and open the editor prefernces
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I open my profile in edit mode
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"

  Scenario: Change the preferred editor to Weka and create a course
    Given I set the field "Text editor" to "Weka"
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    When I follow "Increase the number of sections"
    And I edit the section "6"
    And I activate the weka editor with css "#uid-1"
    And I type "Hello world" in the weka editor
    And I click on "Save changes" "button"
    Then I should see "Hello world"
    And I should not see "paragraph"

  Scenario: Change the preferred editor to Text and create a course
    Given I set the field "Text editor" to "Plain text area"
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    When I follow "Increase the number of sections"
    And I edit the section "6"
    And I set the following fields to these values:
    | Summary | Hello <a href='#behat'>World</a> |
    And I click on "Save changes" "button"
    Then I should see "Hello World"
    And I follow "World"