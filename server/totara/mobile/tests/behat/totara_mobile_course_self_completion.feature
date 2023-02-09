@totara @totara_mobile @core_completion @javascript
Feature: Test the totara_mobile_completion_course_self_complete mutation

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
    And I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Manual self completion" "link"
    And I click on "criteria_self_value" "checkbox"
    And I press "Save changes"
    And I add the "Course completion status" block
    And I add the "Self completion" block
    And I log out

  Scenario: Test the mutation by self-completing a course
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Status: Not yet started"
    And I log out
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "student1"
    And I set the field "password" to "student1"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to "{\"operationName\": \"totara_mobile_completion_course_self_complete\",\"variables\": {\"courseid\": 2}}"
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"core_completion_course_self_complete\": true" in the "#response2" "css_element"
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Status: Complete"
    And I should see "You have already completed this course"
