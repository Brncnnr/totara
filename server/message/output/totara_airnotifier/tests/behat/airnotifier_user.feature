@message_totara_airnotifier @totara_mobile @javascript
Feature: Message Totara AirNotifier integration tests
  In order to receive push notifications on my device
  As an app user
  I can register my device, push notifications to it, and delete my device

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
    When I log in as "admin"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"
    And I navigate to "Plugins > Message outputs > Totara AirNotifier" in site administration
    And I set the following fields to these values:
      | AirNotifier App Code | 0123456789abcdef |
    And I click on "Save changes" "button"
    # Make sure the popup notifications are enabled for assignments.
    And the following config values are set as admin:
      | totara_alert_provider_mod_assign_assign_notification_permitted | permitted | message |
      | message_provider_mod_assign_assign_notification_loggedin | totara_alert | message |
      | message_provider_mod_assign_assign_notification_loggedoff | totara_alert | message |
      | totara_airnotifier_provider_mod_assign_assign_notification_permitted | permitted | message |
      | message_provider_mod_assign_assign_notification_loggedin | totara_airnotifier | message |
      | message_provider_mod_assign_assign_notification_loggedoff | totara_airnotifier | message |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Submit your online text |
      | assignsubmission_onlinetext_enabled | 1 |
      | assignsubmission_file_enabled | 0 |
    And I log out

  Scenario: User sets FCM token and triggers a push notification message
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
      "operationName": "totara_mobile_set_fcmtoken",
      "variables": { "token": "abcdef0123456789" }
    }
    """
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"set_fcmtoken\": true" in the "#response2" "css_element"

    When I log in as "admin"
    And I follow "Continue in browser"
    And I navigate to "Server > Logs" in site administration
    And I set the field "user" to "Student 1"
    And I press "Get these logs"
    Then I should see "FCM device token registered"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Add submission"
    # This should generate a notification.
    And I set the following fields to these values:
      | Online text | I'm the student first submission |
    And I press "Save changes"
    And I log out

    When I log in as "admin"
    And I follow "Continue in browser"
    And I navigate to "Server > Logs" in site administration
    And I press "Get these logs"
    Then I should see "Push notification sent"
