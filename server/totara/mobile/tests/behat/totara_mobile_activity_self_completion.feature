@totara @totara_mobile @core_completion @javascript
Feature: Test the totara_mobile_completion_activity_self_complete mutation

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
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                | Test seminar                                         |
      | Completion tracking | Learners can manually mark the activity as completed |
    And I turn editing mode off
    And I log out

  Scenario: Test the mutation by self-completing an activity
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar"
    Then I should see "Manually mark this activity when complete"
    And the field "I have completed this activity" matches value "0"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_completion_activity_self_complete\",\"variables\": {\"cmid\": 2, \"complete\": true}}"
    And I click on "Submit Request 2" "button"
    Then I should see "\"core_completion_activity_self_complete\": true" in the "#response2" "css_element"
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar"
    Then I should see "Manually mark this activity when complete"
    And the field "I have completed this activity" matches value "1"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_completion_activity_self_complete\",\"variables\": {\"cmid\": 2, \"complete\": false}}"
    And I click on "Submit Request 2" "button"
    Then I should see "\"core_completion_activity_self_complete\": true" in the "#response2" "css_element"
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar"
    Then I should see "Manually mark this activity when complete"
    And the field "I have completed this activity" matches value "0"