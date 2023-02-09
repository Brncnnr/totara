@totara @totara_notification @javascript @vuejs
Feature: User notifications preferences
  As a user
  I need to be able to set notifications preferences
  so I receive my desired notifications.

  Background:
    Given the following "users" exist:
      | firstname | lastname | username | email           |
      | User      | One      | user1    | one@example.com |
      | User      | Two      | user2    | two@example.com |

  Scenario: User can access notification preferences
    When I log in as "user1"
    And I follow "Preferences" in the user menu
    And I follow "Notification preferences"
    Then I should see "Notification preferences"
    And I should see "Certification"

    When I click on "Expand all" "button"
    Then I should see "Course set completed"

    When I click on "Collapse all" "button"
    Then I should not see "Course set completed"

    When I click on "Certification" "button"
    Then I should see "Course set completed"

  Scenario: Enable capability and confirm we can navigate to another user's preference page
    # Log in as admin to enable the capability.
    Given I log in as "admin"
    And I set the following system permissions of "Authenticated User" role:
      | moodle/user:editmessageprofile | Allow |
    And I log out

    # Log in as user1 and switch off user2's notification status.
    And I log in as "user1"
    And I navigate to the user preference page for "user2"
    Then I should see "Message preferences"
    And I should see "Notification preferences"
    And I should see "Legacy notification preferences"
    When I follow "Notification preferences"
    When I click on "Certification" "button"
    Then I should see "Certification completed"
    When I click on the "Learner assigned in certification notification status" tui toggle button
    And I log out

    # Log in as user2 and confirm that notification setting has been disabled.
    And I log in as "user2"
    And I follow "Preferences" in the user menu
    And I follow "Notification preferences"
    And I click on "Certification" "button"
    Then I should see "Certification completed"
    And the "Learner assigned in certification notification status" tui toggle switch should be "off"

  Scenario: Confirm that an administrator has access to another user preference page
    Given I log in as "admin"
    And I navigate to the user preference page for "user2"
    Then I should see "Message preferences"
    And I should see "Notification preferences"
    And I should see "Legacy notification preferences"