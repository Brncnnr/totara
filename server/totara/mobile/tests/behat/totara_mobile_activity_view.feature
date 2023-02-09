@totara @totara_mobile @core_completion @javascript
Feature: Test the totara_mobile_completion_activity_view mutation

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    When I log in as "admin"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "File" to section "1"
    And I set the following fields to these values:
      | Name | Myfile |
    And I upload "totara/mobile/tests/fixtures/smallfile.txt" file to "Select files" filemanager
    And I click on "Save and display" "button"
    And I log out

  Scenario: Test the mutation by self-completing an activity
    Given I am using the mobile emulator
    And I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_completion_activity_view\",\"variables\": {\"cmid\": 2, \"activity\": \"resource\"}}"
    And I click on "Submit Request 2" "button"
    Then I should see "\"core_completion_activity_view\": true" in the "#response2" "css_element"

    Given I log in as "admin"
    And I follow "Continue in browser"
    And I am on "Course 1" course homepage
    And I navigate to "Logs" node in "Course administration > Reports"
    And I press "Get these logs"
    And I should see "The user with id '3' viewed the 'resource' activity with course module id '2'" in the "Student 1" "table_row"
