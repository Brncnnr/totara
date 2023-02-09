@totara @totara_notification @javascript @vuejs
Feature: Notifications page
  As a notifications administrator
  I need to be able to view notifications and manage notifications
  so they can be available to users.

  Scenario: Admin is able to view notifications page
    Given I log in as "admin"
    And I navigate to system notifications page
    Then I should see "Certification"

    When I click on "Expand all" "button"
    Then I should see "Course set completed"

    When I click on "Collapse all" "button"
    Then I should not see "Course set completed"

    When I click on "Certification" "button"
    Then I should see "Course set completed"

    When I click on "Course set completed details" "button"
    Then I should see "Certification course set completed"

  Scenario: Admin is able to create/update/delete custom notification
    Given I log in as "admin"
    And I navigate to system notifications page
    Then I should not see "Learner assigned in certification"
    And I should not see "Learner assigned in certification"

    When I click on "Certification" "button"
    Then I should see "Learner assigned in certification"

    When I click on "Actions for Learner assigned in certification event" "button"
    Then I should see "Create notification"
    And I click on "Create notification" "link"
    Then I should see "Create notification" in the ".tui-modalContent__header-title" "css_element"

    When I click on "Close" "button"
    And I click on "Actions for Learner assigned in certification event" "button"
    Then I should see "Create notification"
    When I click on "Create notification" "link"
    Then I should see "Create notification" in the ".tui-modalContent__header-title" "css_element"

    When I click on the "totara_notification\recipient\manager" tui checkbox in the "recipients" tui checkbox group
    And I set the field "Name" to "Test custom notification"
    And I set the weka editor with css ".tui-notificationPreferenceForm__subjectEditor" to "Test custom notification subject"
    And I set the weka editor with css ".tui-notificationPreferenceForm__bodyEditor" to "Test custom notification body"
    And I click on "Save" "button"
    And I navigate to system notifications page
    And I click on "Certification" "button"
    And I click on "Learner assigned in certification details" "button"
    Then I should see "Test custom notification"
    And I should see "Manager"

    #Update custom notification
    When I click on "Actions for Test custom notification" "button"
    Then I should see "Edit"
    And I click on "Edit notification Test custom notification" "link"
    Then I should see "Edit notification"

    When I set the field "Name" to "New notification"
    And I click on the "totara_notification\recipient\subject" tui checkbox in the "recipients" tui checkbox group
    And I click on "Save" "button"
    Then I should see "New notification"
    And I should see "Subject"

    #Delete custom notification
    When I click on "Actions for New notification" "button"
    Then I should see "Delete"
    And I click on "Delete notification New notification" "link"
    And I should see "Delete notification: New notification"
    And I should see "Are you sure? Deleting this notification will remove its instances in other contexts, such as categories and courses. This action cannot be undone."
    And I click on "Delete" "button"
    And I should see "Successfully deleted notification"

  Scenario: Admin is able to create custom notification in context notification page
    Given I log in as "admin"
    And the following "courses" exist:
      | fullname   | shortname | format |
      | Course 101 | c101      | topics |
    And I navigate to notifications page of "course" "c101"
    And I click on "Expand all" "button"
    When I click on "Actions for Course set completed event" "button"
    Then I should see "Create notification"
    And I click on "Create notification" option in the dropdown menu
    And I click on the "totara_notification\recipient\manager" tui checkbox in the "recipients" tui checkbox group
    And I set the field "Name" to "Test context notification name"
    And I set the weka editor with css ".tui-notificationPreferenceForm__subjectEditor" to "Test context notification subject"
    And I set the weka editor with css ".tui-notificationPreferenceForm__bodyEditor" to "Test context notification body"
    And I click on "Save" "button"
    And I click on "Course set completed details" "button"
    Then I should see "Test context notification name"
    And I should see "Manager"

    When I navigate to system notifications page
    And I click on "Certification" "button"
    And I click on "Course set completed details" "button"
    Then I should not see "Test context notification name"

  Scenario: Admin is able to view notifications page through admin menu
    Given I log in as "admin"
    And I click on "Show admin menu window" "button"
    When I click on "Notifications" "link" in the "#quickaccess-popover-content" "css_element"
    Then I should see "Notifications"

  Scenario: Admin is able to update notification status
    Given I log in as "admin"
    When I navigate to system notifications page
    And I click on "Certification" "button"
    Then ".tui-toggleSwitch__btn[aria-pressed='true'][aria-label='Course set completed notification status']" "css_element" should exist
    When I click on the "Course set completed notification status" tui toggle button
    And I navigate to system notifications page
    And I click on "Certification" "button"
    Then ".tui-toggleSwitch__btn[aria-label='Course set completed notification status']" "css_element" should exist
    And ".tui-toggleSwitch__btn[aria-pressed='true'][aria-label='Course set completed notification status']" "css_element" should not exist