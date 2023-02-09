@totara @totara_mobile @mod_resource @javascript
Feature: Test various aspects of the totara_mobile_resource query

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

  Scenario: Test basic query and download of file
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to multiline:
    """
    {
      "operationName": "totara_mobile_resource",
      "variables": { "resourceid": 1 }
    }
    """
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"mimetype\": \"text/plain\"" in the "#response2" "css_element"
    And I click on "link0" "link" in the "#response2" "css_element"
    Then I should see "26) File request HTTP ok."
    And I should see "27) File received text/plain"
    And I should see the mobile file response on line "28"
