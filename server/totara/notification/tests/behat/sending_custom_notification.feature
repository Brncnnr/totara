@totara @totara_notification @javascript @vuejs @engage_article
Feature: Sending custom notifications to user

  Background:
    Given I log in as "admin"
    And the following "users" exist:
      | firstname | lastname | username | email           |
      | One       | User     | one      | one@example.com |
      | Two       | User     | two      | two@example.com |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname        | shortname | summary      |
      | Certification 1 | cert1      | Program HTML |
    And the following job assignments exist:
      | user | manager |
      | one  | two     |
    And I log out

  Scenario: Sending notifications to subject user on assigning to certification should included the custom notification
    When I log in as "admin"
    And I navigate to system notifications page
    And I click on "Certification" "button"
    And I click on "Learner assigned in certification details" "button"
    When I click on "Actions for Learner assigned in certification event" "button"
    And I click on "Create notification" "link"
    And I click on the "totara_notification\recipient\subject" tui checkbox in the "recipients" tui checkbox group

    And I set the field "Name" to "Custom notification one"
    And I set the weka editor with css ".tui-notificationPreferenceForm__subjectEditor" to "Test custom notification subject"
    And I set the weka editor with css ".tui-notificationPreferenceForm__bodyEditor" to "Test custom notification body"
    # The status field that handled by TUI form. At this point it does not understand the label associated with it.
    # Hence we are going to have to use the checkbox's field name.
    And I click on the "enabled[value]" tui checkbox
    And I click on "Save" "button"
    Then I should see "Custom notification one"
    And I reset the email sink
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user |
      | cert1   | one  |
    When I trigger cron
    Then the message "Test custom notification subject" contains "Test custom notification body" for "one" user
    And the following emails should have been sent:
      | To                 | Subject                          | Body                          |
      | one@example.com    | Test custom notification subject | Test custom notification body |

  Scenario: Sending notification to user on certification should use the overridden value at lower context
    Given I log in as "admin"
    And I navigate to system notifications page
    And I click on "Certification" "button"
    And I click on "Learner assigned in certification details" "button"
    When I click on "Actions for Learner assigned in certification event" "button"
    And I click on "Create notification" "link"
    And I click on the "totara_notification\recipient\manager" tui checkbox in the "recipients" tui checkbox group
    And I set the field "Name" to "Custom notification one"
    And I set the weka editor with css ".tui-notificationPreferenceForm__subjectEditor" to "Custom notification subject"
    And I set the weka editor with css ".tui-notificationPreferenceForm__bodyEditor" to "Custom notification body"
    # The status field that handled by TUI form. At this point it does not understand the label associated with it.
    # Hence we are going to have to use the checkbox's field name.
    And I click on the "enabled[value]" tui checkbox
    And I click on "Save" "button"
    And I am on "Certification 1" certification homepage
    And I click on "Edit certification details" "button"
    And I follow "Notifications"
    When I click on "Learner assigned in certification details" "button"
    Then I should see "Custom notification one"
    When I click on "Actions for Custom notification one" "button"
    Then I should see "Edit"
    And I click on "Edit notification Custom notification one" "link"
    And the "Enable customising field recipient" tui toggle switch should be "off"

    When I click on the "Enable customising field recipient" tui toggle button
    Then the "Manager" "field" should be enabled

    And I click on the "Enable customising field subject" tui toggle button
    And I set the weka editor with css ".tui-notificationPreferenceForm__subjectEditor" to "Custom notification at certification context"
    And I click on "Save" "button"
    And I reset the email sink
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user |
      | cert1   | one  |
    When I trigger cron
    Then the following emails should not have been sent:
      | To              | Subject                     | Body                     |
      | two@example.com | Custom notification subject | Custom notification body |
    And the following emails should have been sent:
      | To              | Subject                                      | Body                     |
      | two@example.com | Custom notification at certification context | Custom notification body |
    And the message "Custom notification at certification context" contains "Custom notification body" for "two" user

  Scenario: Message is sent to multiple recipients
    When I log in as "admin"
    And I navigate to system notifications page
    And I click on "Certification" "button"
    And I click on "Learner assigned in certification details" "button"
    When I click on "Actions for Learner assigned in certification event" "button"
    And I click on "Create notification" "link"
    And I click on the "totara_notification\recipient\subject" tui checkbox in the "recipients" tui checkbox group
    And I click on the "totara_notification\recipient\manager" tui checkbox in the "recipients" tui checkbox group

    And I set the field "Name" to "Custom notification one"
    And I set the weka editor with css ".tui-notificationPreferenceForm__subjectEditor" to "Test custom notification subject"
    #And I set the weka editor with css ".tui-notificationPreferenceForm__bodyEditor" to "Test custom notification body"

    And I activate the weka editor with css ".tui-notificationPreferenceForm__bodyEditor"
    And I type "Hi " in the weka editor
    When I type "[recipient" in the weka editor
    When I click on "Recipient Full name" "link"
    And I type ", here is a new notification" in the weka editor
    # The status field that handled by TUI form. At this point it does not understand the label associated with it.
    # Hence we are going to have to use the checkbox's field name.
    And I click on the "enabled[value]" tui checkbox
    And I click on "Save" "button"
    Then I should see "Custom notification one"
    And I reset the email sink
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user |
      | cert1   | one  |
    When I trigger cron
    #Then the message "Test custom notification subject" contains "Test custom notification body" for "one" user
    And the following emails should have been sent:
      | To                 | Subject                          | Body                                    |
      | one@example.com    | Test custom notification subject | Hi One User, here is a new notification |
      | two@example.com    | Test custom notification subject | Hi Two User, here is a new notification |