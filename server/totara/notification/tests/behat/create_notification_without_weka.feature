@totara @totara_notification @javascript @vuejs @editor
Feature: Produce notification's content without weka editor
  Background:
    Given I log in as "admin"
    And I navigate to "Plugins > Text editors > Manage editors" in site administration
    And I click on "Disable" "link" in the "Weka editor" "table_row"

  Scenario: Create a notification preference record with disabled weka editor
    Given I navigate to system notifications page
    And I click on "Certification" "button"
    And I click on "Actions for Learner assigned in certification event" "button"
    When I click on "Create notification" "link"
    Then "div.tui-notificationPreferenceForm__subjectEditor textarea" "css_element" should exist
    And "div.tui-notificationPreferenceForm__bodyEditor textarea" "css_element" should exist
    And I click on the "totara_notification\recipient\manager" tui checkbox in the "recipients" tui checkbox group
    And I set the field "Name" to "Custom notification"
    And I set the field with css "div.tui-notificationPreferenceForm__subjectEditor textarea" to "Custom notification subject"
    And I set the field with css "div.tui-notificationPreferenceForm__bodyEditor textarea" to "Custom notification body"
    And I click on "Save" "button"
    And I click on "Learner assigned in certification details" "button"
    Then I should see "Custom notification"
    And I click on "Actions for Custom notification" "button"
    When I click on "Edit notification Custom notification" "link"
    And I should see "Manager"
    And the field with xpath "//div[contains(@class, 'tui-notificationPreferenceForm__subjectEditor')]/textarea" matches value "Custom notification subject"
    And the field with xpath "//div[contains(@class, 'tui-notificationPreferenceForm__bodyEditor')]/textarea" matches value "Custom notification body"

  Scenario: Update a built in notification preference with disabled weka editor
    Given I navigate to system notifications page
    And I click on "Certification" "button"
    And I click on "Actions for Learner assigned in certification event" "button"
    And I click on "Learner assigned in certification details" "button"
    And I click on "Actions for Team member assigned in certification" "button"
    When I click on "Edit notification Team member assigned in certification" "link"
    Then "div.tui-notificationPreferenceForm__subjectEditor textarea" "css_element" should exist
    And "div.tui-notificationPreferenceForm__bodyEditor textarea" "css_element" should exist
    And I click on the "Enable customising field subject" tui toggle button
    And I click on the "Enable customising field body" tui toggle button
    And I set the field with css "div.tui-notificationPreferenceForm__subjectEditor textarea" to "Override notification subject"
    And I set the field with css "div.tui-notificationPreferenceForm__bodyEditor textarea" to "Override notification body"
    And I click on "Save" "button"
    And I click on "Actions for Team member assigned in certification" "button"
    When I click on "Edit notification Team member assigned in certification" "link"
    And the field with xpath "//div[contains(@class, 'tui-notificationPreferenceForm__subjectEditor')]/textarea" matches value "Override notification subject"
    And the field with xpath "//div[contains(@class, 'tui-notificationPreferenceForm__bodyEditor')]/textarea" matches value "Override notification body"