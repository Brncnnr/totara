@totara @totara_mobile @javascript
Feature: Test various aspects of the totara mobile popup message queries

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    # Make sure the popup notifications are enabled for assignments.
    And the following config values are set as admin:
      | popup_provider_mod_assign_assign_notification_permitted | permitted | message |
      | message_provider_mod_assign_assign_notification_loggedin | popup | message |
      | message_provider_mod_assign_assign_notification_loggedoff | popup | message |
    When I log in as "admin"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Submit your online text |
      | assignsubmission_onlinetext_enabled | 1 |
      | assignsubmission_file_enabled | 0 |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Add submission"
    # This should generate a notification.
    And I set the following fields to these values:
      | Online text | I'm the student first submission |
    And I press "Save changes"
    And I log out

  Scenario: Test totara mobile unread message count query
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
      "operationName": "totara_mobile_unread_message_count",
      "variables": {}
    }
    """
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"message_popup_unread_count\": 1" in the "#response2" "css_element"
    When I log in as "student1"
    And I open the notification popover
    And I follow "You have submitted your assignment submission for Test assignment name"
    And I log out
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
      "operationName": "totara_mobile_unread_message_count",
      "variables": {}
    }
    """
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"message_popup_unread_count\": 0" in the "#response2" "css_element"

  Scenario: Test totara mobile messages query
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "2" and I fill the form with:
      | Assignment name | Test assignment2 name |
      | Description | Submit your online text |
      | assignsubmission_onlinetext_enabled | 1 |
      | assignsubmission_file_enabled | 0 |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment2 name"
    And I press "Add submission"
    # This should generate a notification.
    And I set the following fields to these values:
      | Online text | I'm the student second submission |
    And I press "Save changes"
    And I open the notification popover
    And I follow "You have submitted your assignment submission for Test assignment name"
    And I log out
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
      "operationName": "totara_mobile_messages",
      "variables": {}
    }
    """
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "You have submitted your assignment submission for Test assignment2 name" in the "#response2" "css_element"
    And I should see "\"isread\": false" in the "#response2" "css_element"
    And I should see "You have submitted your assignment submission for Test assignment name" in the "#response2" "css_element"
    And I should see "\"isread\": true" in the "#response2" "css_element"
    And I should see "\"fullmessageformat\": \"PLAIN\"" in the "#response2" "css_element"
    And I should see "\"contextUrl\": \"http" in the "#response2" "css_element"
    And I should see "\"fullmessageHTML\": \"<p>" in the "#response2" "css_element"
    And I should see "\"__typename\": \"message_popup_message\"" in the "#response2" "css_element"

  Scenario: Test totara mobile mark messages read mutation
    Given I log in as "student1"
    Then I should see "1" in the "#nav-notification-popover-container [data-region='count-container']" "css_element"
    And I follow "Continue in browser"
    And I log out
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
      "operationName": "totara_mobile_mark_messages_read",
      "variables": {
        "input": {
          "message_ids": [ 1 ]
        }
      }
    }
    """
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"read_message_ids\"" in the "#response2" "css_element"
    And I should see "\"1\"" in the "#response2" "css_element"
    And I should see "\"__typename\": \"message_popup_mark_messages_read_result\"" in the "#response2" "css_element"
    When I log in as "student1"
    Then "[data-region='count-container']" "css_element" in the "#nav-notification-popover-container" "css_element" should not be visible
