@totara @totara_notification @javascript @vuejs
Feature: Notification auditing
  As a notification administrator
  I need to be able to audit logs

  Background:
    Given I am on a totara site
    And the following "programs" exist in "totara_program" plugin:
      | fullname | shortname |
      | Prog 1   | p1        |
      | Prog 2   | p2        |
    And the following "users" exist:
      | username | firstname | lastname |
      | user1    | User   | One        |
      | user2    | User   | Two        |
    And the following "program assignments" exist in "totara_program" plugin:
      | user  | program |
      | user1 | p1      |
      | user2 | p2      |
    Given I log in as "admin"
    # Workaround until TL-30431 get fixed
    And I follow "Purge all caches"
    And I trigger cron
    And I am on site homepage

  Scenario: Auditing link drill down works within a context
    And I click on "Find Learning" in the totara menu
    And I click on "Prog 1" "text"
    And I click on "Edit program details" "button"
    # The icon next to the notification has the alt text "Notifications"
    And I navigate to "NotificationsNotifications" node in "Program Administration"
    And I click on "Actions for Learner assigned in program event" "button"
    And I click on "View notification logs" "link"

    Then I should see "Notification events: Prog 1"
    And the "notification_event_log" table should contain the following:
      | Event Name                                                        | Subject user | Schedule | Status |
      | Learner assigned in program: Learner "User One", program "Prog 1" | User One     | On event | 1/1 OK |
    And I should not see "User Two"
    And I should not see "Prog 2"

    When I click on "View" "link" in the "User One" "table_row"
    Then I should see "Sent notifications: Prog 1"
    And the "notification_log" table should contain the following:
      | Notification                | Recipient | Status |
      | Learner assigned in program | User One  | 1/1 OK |
    And I should not see "User Two"
    And I should not see "Prog 2"

    When I click on "View" "link" in the "User One" "table_row"
    Then I should see "Delivery log: Prog 1"
    And the "notification_delivery_log" table should contain the following:
      | Notification                | Recipient | Delivery Channel  |
      | Learner assigned in program | User One  | Site notification |
    And I should not see "User Two"
    And I should not see "Prog 2"

    And I follow "Back to Sent notifications: Prog 1"
    Then I should see "Sent notifications: Prog 1"
    And I follow "Back to Notification events: Prog 1"
    Then I should see "Notification events: Prog 1"

  Scenario: Auditing link drill down works outside of contexts
    When I click on "[aria-label='Show admin menu window']" "css_element"
    And I click on "Notifications" "link" in the "#quickaccess-popover-content" "css_element"
    And I click on "Program" "button"
    And I click on "Actions for Learner assigned in program event" "button"
    And I click on "View" "link"
    Then I should see "Notification events"
    And the "notification_event_log" table should contain the following:
      | Event Name                                                        | Subject user | Schedule | Status |
      | Learner assigned in program: Learner "User One", program "Prog 1" | User One     | On event | 1/1 OK |
      | Learner assigned in program: Learner "User Two", program "Prog 2" | User Two     | On event | 1/1 OK |

    When I click on "View" "link" in the "User One" "table_row"
    Then I should see "Sent notifications"
    And the "notification_log" table should contain the following:
      | Notification                | Recipient | Status |
      | Learner assigned in program | User One  | 1/1 OK |
    And I should not see "User Two"
    And I should not see "Prog 2"

    When I click on "View" "link" in the "User One" "table_row"
    Then I should see "Delivery log"
    And the "notification_delivery_log" table should contain the following:
      | Notification                | Recipient | Delivery Channel  |
      | Learner assigned in program | User One  | Site notification |
    And I should not see "User Two"
    And I should not see "Prog 2"

    And I follow "Back to Sent notifications"
    Then I should see "Sent notifications"
    And I follow "Back to Notification events"
    Then I should see "Notification events"

  Scenario: Auditing works for a user
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "User One" "link"
    And I click on "View notification logs" "link"

    Then I should see "Notification events for User One"
    And the "notification_event_log" table should contain the following:
      | Event Name                                                        | Subject user | Schedule | Status |
      | Learner assigned in program: Learner "User One", program "Prog 1" | User One     | On event | 1/1 OK |
    And I should not see "User Two"
    And I should not see "Prog 2"

    When I click on "View" "link" in the "User One" "table_row"
    Then I should see "Sent notifications for User One"
    And the "notification_log" table should contain the following:
      | Notification                | Recipient | Status |
      | Learner assigned in program | User One  | 1/1 OK |
    And I should not see "User Two"
    And I should not see "Prog 2"

    When I click on "View" "link" in the "User One" "table_row"
    Then I should see "Delivery log for User One"
    And the "notification_delivery_log" table should contain the following:
      | Notification                | Recipient | Delivery Channel  |
      | Learner assigned in program | User One  | Site notification |
    And I should not see "User Two"
    And I should not see "Prog 2"

    And I follow "Back to Sent notifications for User One"
    Then I should see "Sent notifications for User One"
    And I follow "Back to Notification events for User One"
    Then I should see "Notification events for User One"

    When I log out
    And I log in as "user1"
    And I follow "Profile" in the user menu
    And I should not see "View notification logs"

    When I log out
    And I log in as "user2"
    And I follow "Profile" in the user menu
    And I should not see "View notification logs"
    And I log out

  Scenario: Auditing notification log with own capability for an authenticated user
    And I set the following system permissions of "Authenticated User" role:
      | capability                                | permission |
      | totara/notification:auditownnotifications | Allow      |
    And I log out
    And I log in as "user1"
    And I follow "Profile" in the user menu
    And I should see "View notification logs"

    When I log out
    And I log in as "user2"
    And I follow "Profile" in the user menu
    And I should see "View notification logs"
    And I log out


